<?php

/*
 * DestroyController.php
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
use FireflyIII\Events\UpdatedAccount;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepository;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

/**
 * Class DestroyController
 */
class DestroyController extends Controller
{
    private TransactionGroupRepository $groupRepository;
    private JournalRepositoryInterface $repository;

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

                $this->repository      = app(JournalRepositoryInterface::class);
                $this->repository->setUser($admin);

                $this->groupRepository = app(TransactionGroupRepository::class);
                $this->groupRepository->setUser($admin);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/transactions/deleteTransaction
     *
     * Remove the specified resource from storage.
     */
    public function destroy(TransactionGroup $transactionGroup): JsonResponse
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        // grab asset account(s) from group:
        $accounts = [];

        /** @var TransactionJournal $journal */
        foreach ($transactionGroup->transactionJournals as $journal) {
            /** @var Transaction $transaction */
            foreach ($journal->transactions as $transaction) {
                $type = $transaction->account->accountType->type;
                // if is valid liability, trigger event!
                if (in_array($type, config('firefly.valid_liabilities'), true)) {
                    $accounts[] = $transaction->account;
                }
            }
        }

        $this->groupRepository->destroy($transactionGroup);

        app('preferences')->mark();

        /** @var Account $account */
        foreach ($accounts as $account) {
            app('log')->debug(sprintf('Now going to trigger updated account event for account #%d', $account->id));
            event(new UpdatedAccount($account));
        }

        return response()->json([], 204);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/transactions/deleteTransactionJournal
     *
     * Remove the specified resource from storage.
     */
    public function destroyJournal(TransactionJournal $transactionJournal): JsonResponse
    {
        $this->repository->destroyJournal($transactionJournal);
        app('preferences')->mark();

        return response()->json([], 204);
    }
}
