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

namespace FireflyIII\Api\V1\Controllers\Models\Transaction;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Events\DestroyedTransactionGroup;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

/**
 * Class DestroyController
 */
class DestroyController extends Controller
{
    private JournalRepositoryInterface $repository;


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

                $this->repository = app(JournalRepositoryInterface::class);
                $this->repository->setUser($admin);

                return $next($request);
            }
        );
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param TransactionGroup $transactionGroup
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function destroy(TransactionGroup $transactionGroup): JsonResponse
    {
        $this->repository->destroyGroup($transactionGroup);
        // trigger just after destruction
        event(new DestroyedTransactionGroup($transactionGroup));

        return response()->json([], 204);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param TransactionJournal $transactionJournal
     *
     * @codeCoverageIgnore
     * @return JsonResponse
     */
    public function destroyJournal(TransactionJournal $transactionJournal): JsonResponse
    {
        $this->repository->destroyJournal($transactionJournal);

        return response()->json([], 204);
    }
}