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

namespace FireflyIII\Api\V1\Controllers\Models\Transaction;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\TransactionStoreRequest;
use FireflyIII\Events\StoredTransactionGroup;
use FireflyIII\Exceptions\DuplicateTransactionException;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use FireflyIII\Rules\IsDuplicateTransaction;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use League\Fractal\Resource\Item;
use Log;
use Validator;

/**
 * Class StoreController
 */
class StoreController extends Controller
{
    use TransactionFilter;

    private TransactionGroupRepositoryInterface $groupRepository;


    /**
     * TransactionController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $admin */
                $admin = auth()->user();

                $this->groupRepository = app(TransactionGroupRepositoryInterface::class);
                $this->groupRepository->setUser($admin);

                return $next($request);
            }
        );
    }


    /**
     * Store a new transaction.
     *
     * @param TransactionStoreRequest $request
     *
     * @return JsonResponse
     * @throws FireflyException|ValidationException
     */
    public function store(TransactionStoreRequest $request): JsonResponse
    {
        Log::debug('Now in API StoreController::store()');
        $data         = $request->getAll();
        $data['user'] = auth()->user()->id;

        Log::channel('audit')
           ->info('Store new transaction over API.', $data);

        try {
            $transactionGroup = $this->groupRepository->store($data);
        } catch (DuplicateTransactionException $e) {
            Log::warning('Caught a duplicate transaction. Return error message.');
            $validator = Validator::make(
                ['transactions' => [['description' => $e->getMessage()]]], ['transactions.0.description' => new IsDuplicateTransaction]
            );
            throw new ValidationException($validator);
        } catch (FireflyException $e) {
            Log::warning('Caught an exception. Return error message.');
            Log::error($e->getMessage());
            $message   = sprintf('Internal exception: %s', $e->getMessage());
            $validator = Validator::make(['transactions' => [['description' => $message]]], ['transactions.0.description' => new IsDuplicateTransaction]);
            throw new ValidationException($validator);
        }
        app('preferences')->mark();
        event(new StoredTransactionGroup($transactionGroup, $data['apply_rules'] ?? true));

        $manager = $this->getManager();
        /** @var User $admin */
        $admin = auth()->user();
        // use new group collector:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector
            ->setUser($admin)
            // filter on transaction group.
            ->setTransactionGroup($transactionGroup)
            // all info needed for the API:
            ->withAPIInformation();

        $selectedGroup = $collector->getGroups()->first();
        if (null === $selectedGroup) {
            throw new FireflyException('Cannot find transaction. Possibly, a rule deleted this transaction after its creation.');
        }
        /** @var TransactionGroupTransformer $transformer */
        $transformer = app(TransactionGroupTransformer::class);
        $transformer->setParameters($this->parameters);
        $resource = new Item($selectedGroup, $transformer, 'transactions');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}