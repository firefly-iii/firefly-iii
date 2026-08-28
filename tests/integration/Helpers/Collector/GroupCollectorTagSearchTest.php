<?php

/*
 * GroupCollectorTagSearchTest.php
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

declare(strict_types=1);

namespace Tests\integration\Helpers\Collector;

use FireflyIII\Models\AccountType;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\Search\SearchInterface;
use FireflyIII\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\integration\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class GroupCollectorTagSearchTest extends TestCase
{
    use RefreshDatabase;

    public function testTagSearchReturnsCorrectTotal(): void
    {
        // https://github.com/firefly-iii/firefly-iii/issues/12662
        $user = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $assetType   = AccountType::query()->where('type', 'Asset account')->first();
        $expenseType = AccountType::query()->where('type', 'Expense account')->first();
        $source      = $user->accounts()->create(['account_type_id' => $assetType->id, 'name' => 'Asset source', 'user_group_id' => $user->user_group_id]);
        $expense     = $user->accounts()->create([
            'account_type_id' => $expenseType->id,
            'name'            => 'Expense destination',
            'user_group_id'   => $user->user_group_id
        ]);

        $warranty = $this->createTag($user, 'warranty');
        foreach (['alpha', 'beta', 'gamma', 'delta', 'epsilon'] as $name) {
            $this->createTag($user, $name);
        }
        $extraTags = Tag::query()->where('user_id', $user->id)->where('tag', '!=', 'warranty')->get();

        $currencyId = DB::table('transaction_currencies')->first()->id;
        $withdrawal = DB::table('transaction_types')->where('type', 'Withdrawal')->first()->id;

        $groupOne   = TransactionGroup::create(['user_id' => $user->id, 'user_group_id' => $user->user_group_id, 'title' => 'group one']);
        $journalOne = TransactionJournal::create([
            'user_id'                 => $user->id,
            'user_group_id'           => $user->user_group_id,
            'transaction_group_id'    => $groupOne->id,
            'transaction_type_id'     => $withdrawal,
            'transaction_currency_id' => $currencyId,
            'description'             => 'group one journal',
            'date'                    => '2026-01-01 00:00:00',
            'tag_count'               => 5
        ]);
        $journalOne->transaction_group_id = $groupOne->id;
        $journalOne->save();
        // 1 warranty tag + 5 extra tags: fan-out of 6 raw rows for this journal.
        $journalOne->tags()->attach($warranty->id);
        $journalOne->tags()->attach($extraTags->pluck('id')->toArray());
        $this->createTransactionRows($journalOne->id, $source->id, $expense->id, $currencyId, '100');

        $groupTwo   = TransactionGroup::create(['user_id' => $user->id, 'user_group_id' => $user->user_group_id, 'title' => 'group two']);
        $journalTwo = TransactionJournal::create([
            'user_id'                 => $user->id,
            'user_group_id'           => $user->user_group_id,
            'transaction_group_id'    => $groupTwo->id,
            'transaction_type_id'     => $withdrawal,
            'transaction_currency_id' => $currencyId,
            'description'             => 'group two journal',
            'date'                    => '2026-01-02 00:00:00',
            'tag_count'               => 1
        ]);
        $journalTwo->transaction_group_id = $groupTwo->id;
        $journalTwo->save();
        $journalTwo->tags()->attach($warranty->id);
        $this->createTransactionRows($journalTwo->id, $source->id, $expense->id, $currencyId, '50');

        $groupThree   = TransactionGroup::create(['user_id' => $user->id, 'user_group_id' => $user->user_group_id, 'title' => 'group three']);
        $journalThree = TransactionJournal::create([
            'user_id'                 => $user->id,
            'user_group_id'           => $user->user_group_id,
            'transaction_group_id'    => $groupThree->id,
            'transaction_type_id'     => $withdrawal,
            'transaction_currency_id' => $currencyId,
            'description'             => 'group three journal',
            'date'                    => '2026-01-03 00:00:00',
            'tag_count'               => 0
        ]);
        $journalThree->transaction_group_id = $groupThree->id;
        $journalThree->save();
        $this->createTransactionRows($journalThree->id, $source->id, $expense->id, $currencyId, '25');

        $search = app(SearchInterface::class);
        $search->setUser($user);
        $search->parseQuery('tag_contains:warranty');
        $search->setLimit(50);
        $search->setPage(1);

        $paginator = $search->searchTransactions();

        $this->assertSame(2, $paginator->total());
        $this->assertCount(2, $paginator->items());
    }

    private function createTag(User $user, string $name): Tag
    {
        return Tag::create([
            'user_id'       => $user->id,
            'user_group_id' => $user->user_group_id,
            'tag'           => $name,
            'tag_mode'      => 'nothing'
        ]);
    }

    private function createTransactionRows(int $journalId, int $sourceId, int $destinationId, int $currencyId, string $amount): void
    {
        Transaction::create([
            'account_id'              => $sourceId,
            'transaction_journal_id'  => $journalId,
            'transaction_currency_id' => $currencyId,
            'amount'                  => '-' . $amount
        ]);
        Transaction::create([
            'account_id'              => $destinationId,
            'transaction_journal_id'  => $journalId,
            'transaction_currency_id' => $currencyId,
            'amount'                  => $amount
        ]);
    }
}
