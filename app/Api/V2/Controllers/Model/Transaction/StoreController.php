<?php

/*
 * StoreController.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Api\V2\Controllers\Model\Transaction;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Model\Transaction\StoreRequest;
use FireflyIII\Events\StoredTransactionGroup;
use FireflyIII\Exceptions\DuplicateTransactionException;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use FireflyIII\Rules\IsDuplicateTransaction;
use FireflyIII\Transformers\V2\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Class StoreController
 */
class StoreController extends Controller
{
    private TransactionGroupRepositoryInterface $groupRepository;

    /**
     * TransactionController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->groupRepository = app(TransactionGroupRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * TODO this method is practically the same as the V1 method and borrows as much code as possible.
     *
     * @throws FireflyException
     * @throws ValidationException
     */
    public function post(StoreRequest $request): JsonResponse
    {
        app('log')->debug('Now in API v2 StoreController::store()');
        $data               = $request->getAll();
        $userGroup          = $request->getUserGroup();
        $data['user_group'] = $userGroup;


        // overrule user group and see where we end up.
        // what happens when we refer to a budget that is not in this user group?

        app('log')->channel('audit')->info('Store new transaction over API.', $data);

        try {
            $transactionGroup = $this->groupRepository->store($data);
        } catch (DuplicateTransactionException $e) {
            app('log')->warning('Caught a duplicate transaction. Return error message.');
            $validator = Validator::make(
                ['transactions' => [['description' => $e->getMessage()]]],
                ['transactions.0.description' => new IsDuplicateTransaction()]
            );

            throw new ValidationException($validator);
        } catch (FireflyException $e) {
            app('log')->warning('Caught an exception. Return error message.');
            app('log')->error($e->getMessage());
            $message   = sprintf('Internal exception: %s', $e->getMessage());
            $validator = Validator::make(['transactions' => [['description' => $message]]], ['transactions.0.description' => new IsDuplicateTransaction()]);

            throw new ValidationException($validator);
        }
        app('preferences')->mark();
        $applyRules         = $data['apply_rules'] ?? true;
        $fireWebhooks       = $data['fire_webhooks'] ?? true;
        event(new StoredTransactionGroup($transactionGroup, $applyRules, $fireWebhooks));

        /** @var User $admin */
        $admin              = auth()->user();

        // use new group collector:
        /** @var GroupCollectorInterface $collector */
        $collector          = app(GroupCollectorInterface::class);
        $collector
            ->setUser($admin)
            // filter on transaction group.
            ->setTransactionGroup($transactionGroup)
        ;

        $selectedGroup      = $collector->getGroups()->first();
        if (null === $selectedGroup) {
            throw new FireflyException('200032: Cannot find transaction. Possibly, a rule deleted this transaction after its creation.');
        }

        $transformer        = new TransactionGroupTransformer();
        $transformer->setParameters($this->parameters);

        return response()
            ->api($this->jsonApiObject('transactions', $selectedGroup, $transformer))
            ->header('Content-Type', self::CONTENT_TYPE)
        ;
    }
}
