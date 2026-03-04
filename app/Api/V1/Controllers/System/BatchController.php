<?php

declare(strict_types=1);

/*
 * BatchController.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\System;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Events\Model\TransactionGroup\TransactionGroupEventFlags;
use FireflyIII\Events\Model\TransactionGroup\UserRequestedBatchProcessing;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class BatchController extends Controller
{
    private JournalRepositoryInterface $repository;

    /**
     * UserController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(function ($request, $next) {
            $this->repository = app(JournalRepositoryInterface::class);
            $this->repository->setUser(auth()->user()); // should not have to do this.

            return $next($request);
        });
    }

    public function finishBatch(Request $request): JsonResponse
    {
        Log::debug('Now in finishBatch.');
        $journals          = $this->repository->getUncompletedJournals();
        if (0 === count($journals)) {
            Log::debug('Counted zero journals, return.');

            return response()->json([], 204);
        }
        Log::debug(sprintf('Counted %d journals.', count($journals)));

        /** @var TransactionJournal $first */
        $first             = $journals->first();
        $group             = $first?->transactionGroup;
        if (null === $group) {
            Log::debug('First group is NULL.');

            return response()->json([], 204);
        }
        $flags             = new TransactionGroupEventFlags();
        $flags->applyRules = 'true' === $request->input('apply_rules');
        event(new UserRequestedBatchProcessing($flags));
        // event(new CreatedSingleTransactionGroup($group, $flags));

        return response()->json([], 204);
    }
}
