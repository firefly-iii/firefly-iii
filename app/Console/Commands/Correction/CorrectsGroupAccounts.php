<?php

/**
 * FixGroupAccounts.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Events\Model\TransactionGroup\TransactionGroupEventFlags;
use FireflyIII\Events\Model\TransactionGroup\TransactionGroupEventObjects;
use FireflyIII\Events\Model\TransactionGroup\UpdatedSingleTransactionGroup;
use FireflyIII\Events\Model\Webhook\WebhookMessagesRequestSending;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CorrectsGroupAccounts extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Unify the source / destination accounts of split groups.';
    protected $signature   = 'correction:group-accounts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Log::debug('Start of correction:group-accounts');
        $groups                   = [];
        //        $res                      = TransactionJournal::query()
        //            ->groupBy('transaction_group_id')
        //            ->havingRaw('the_count > 1')
        //            ->get([
        //            'transaction_group_id',
        //            DB::raw('COUNT(transaction_group_id) as the_count'),
        //        ]);

        $groups                   = TransactionJournal::query()
            ->groupBy('transaction_group_id')
            ->havingRaw('the_count > 1')
            ->get([
                'transaction_group_id',
                DB::raw('COUNT(transaction_group_id) as the_count'),
            ])
            ->pluck('transaction_group_id')
            ->toArray()
        ;

        //        /** @var TransactionJournal $journal */
        //        foreach ($res as $journal) {
        //            if ((int) $journal->the_count > 1) {
        //                $groups[] = (int) $journal->transaction_group_id;
        //            }
        //        }
        //        var_dump($groups);
        //        var_dump($groups2);exit;
        $flags                    = new TransactionGroupEventFlags();
        $flags->applyRules        = false;
        $flags->fireWebhooks      = false;
        $flags->recalculateCredit = false;
        $flags->unifyOnly         = true;
        $objects                  = new TransactionGroupEventObjects();
        $collection = TransactionGroup::whereIn('id', $groups)->get();
        foreach($collection as $item) {
            $objects->appendFromTransactionGroup($item);
        }
        //foreach ($groups as $groupId) {
            //$group = TransactionGroup::find($groupId);
        //}
        Log::debug(sprintf('Fire event for %d transaction group(s)', count($collection)));
        event(new UpdatedSingleTransactionGroup($flags, $objects));
        event(new WebhookMessagesRequestSending());
        Log::debug('End of correction:group-accounts');

        return 0;
    }
}
