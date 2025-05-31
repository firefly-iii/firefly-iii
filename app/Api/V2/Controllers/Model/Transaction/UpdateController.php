<?php

/*
 * UpdateController.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V2\Controllers\Model\Transaction;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Model\Transaction\UpdateRequest;
use FireflyIII\Events\UpdatedTransactionGroup;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

class UpdateController extends Controller
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
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/transactions/updateTransaction
     *
     * Update a transaction.
     *
     * @throws FireflyException
     */
    public function update(UpdateRequest $request, TransactionGroup $transactionGroup): JsonResponse
    {
        app('log')->debug('Now in update routine for transaction group [v2]!');
        $data             = $request->getAll();
        $transactionGroup = $this->groupRepository->update($transactionGroup, $data);
        $applyRules       = $data['apply_rules'] ?? true;
        $fireWebhooks     = $data['fire_webhooks'] ?? true;
        $amountChanged = true;

        event(new UpdatedTransactionGroup($transactionGroup, $applyRules, $fireWebhooks, $amountChanged));
        app('preferences')->mark();

        /** @var User $admin */
        $admin            = auth()->user();

        // use new group collector:
        /** @var GroupCollectorInterface $collector */
        $collector        = app(GroupCollectorInterface::class);
        $collector->setUser($admin)->setTransactionGroup($transactionGroup);

        $selectedGroup    = $collector->getGroups()->first();
        if (null === $selectedGroup) {
            throw new FireflyException('200032: Cannot find transaction. Possibly, a rule deleted this transaction after its creation.');
        }

        $transformer      = new TransactionGroupTransformer();
        $transformer->setParameters($this->parameters);

        return response()->api($this->jsonApiObject('transactions', $selectedGroup, $transformer))->header('Content-Type', self::CONTENT_TYPE);
    }
}
