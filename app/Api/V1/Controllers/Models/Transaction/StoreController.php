<?php

/*
 * StoreController.php
 * Copyright (c) 2021 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Models\Transaction;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\Transaction\StoreRequest;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Events\StoredTransactionGroup;
use FireflyIII\Exceptions\DuplicateTransactionException;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use FireflyIII\Rules\IsDuplicateTransaction;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Support\JsonApi\Enrichments\TransactionGroupEnrichment;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use League\Fractal\Resource\Item;

/**
 * Class StoreController
 */
class StoreController extends Controller
{
    use TransactionFilter;

    protected array $acceptedRoles = [UserRoleEnum::MANAGE_TRANSACTIONS];
    private TransactionGroupRepositoryInterface $groupRepository;

    /**
     * TransactionController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $admin */
                $admin                 = auth()->user();
                $userGroup             = $this->validateUserGroup($request);

                $this->groupRepository = app(TransactionGroupRepositoryInterface::class);
                $this->groupRepository->setUser($admin);
                $this->groupRepository->setUserGroup($userGroup);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/transactions/storeTransaction
     *
     * Store a new transaction.
     *
     * @throws FireflyException|ValidationException
     */
    public function store(StoreRequest $request): JsonResponse
    {
        Log::debug('Now in API StoreController::store()');
        $data               = $request->getAll();
        $data['user']       = auth()->user();
        $data['user_group'] = $this->userGroup;


        Log::channel('audit')->info('Store new transaction over API.', $data);

        try {
            $transactionGroup = $this->groupRepository->store($data);
        } catch (DuplicateTransactionException $e) {
            Log::warning('Caught a duplicate transaction. Return error message.');
            $validator = Validator::make(['transactions' => [['description' => $e->getMessage()]]], ['transactions.0.description' => new IsDuplicateTransaction()]);

            throw new ValidationException($validator);
        } catch (FireflyException $e) {
            Log::warning('Caught an exception. Return error message.');
            Log::error($e->getMessage());
            $message   = sprintf('Internal exception: %s', $e->getMessage());
            $validator = Validator::make(['transactions' => [['description' => $message]]], ['transactions.0.description' => new IsDuplicateTransaction()]);

            throw new ValidationException($validator);
        }
        app('preferences')->mark();
        $applyRules         = $data['apply_rules'] ?? true;
        $fireWebhooks       = $data['fire_webhooks'] ?? true;
        event(new StoredTransactionGroup($transactionGroup, $applyRules, $fireWebhooks));

        $manager            = $this->getManager();

        /** @var User $admin */
        $admin              = auth()->user();

        // use new group collector:
        /** @var GroupCollectorInterface $collector */
        $collector          = app(GroupCollectorInterface::class);
        $collector
            ->setUser($admin)
            ->setUserGroup($this->userGroup)
            // filter on transaction group.
            ->setTransactionGroup($transactionGroup)
            // all info needed for the API:
            ->withAPIInformation()
        ;

        $selectedGroup      = $collector->getGroups()->first();
        if (null === $selectedGroup) {
            throw new FireflyException('200032: Cannot find transaction. Possibly, a rule deleted this transaction after its creation.');
        }

        // enrich
        $enrichment         = new TransactionGroupEnrichment();
        $enrichment->setUser($admin);
        $selectedGroup      = $enrichment->enrichSingle($selectedGroup);

        /** @var TransactionGroupTransformer $transformer */
        $transformer        = app(TransactionGroupTransformer::class);
        $transformer->setParameters($this->parameters);
        $resource           = new Item($selectedGroup, $transformer, 'transactions');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
