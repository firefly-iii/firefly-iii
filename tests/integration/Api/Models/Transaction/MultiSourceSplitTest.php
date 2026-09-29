<?php

/*
 * MultiSourceSplitTest.php
 * Part of firefly-iii-multisource, a fork of Firefly III.
 * Licensed under the GNU Affero General Public License v3 or later.
 */

declare(strict_types=1);

namespace Tests\integration\Api\Models\Transaction;

use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Override;
use Tests\integration\TestCase;

/**
 * Regression test for the one feature of this fork: split withdrawals with
 * different source accounts (and split deposits with different destination
 * accounts) must survive create, update and the upgrade/correction commands.
 *
 * @internal
 *
 * @coversNothing
 */
final class MultiSourceSplitTest extends TestCase
{
    use RefreshDatabase;

    private Account $bank;
    private Account $voucher;
    private User    $user;

    public function testWithdrawalKeepsDifferentSourceAccounts(): void
    {
        $groupId = $this->storeWithdrawal();
        $this->assertSources($groupId, [$this->bank->id, $this->voucher->id]);

        // update: change only the description, sources must stay as they are.
        $group    = TransactionGroup::query()->findOrFail($groupId);
        $journals = $group->transactionJournals()->orderBy('id')->get();
        $response = $this->putJson(route('api.v1.transactions.update', ['transactionGroup' => $groupId]), [
            'group_title'  => 'Einkauf (geändert)',
            'transactions' => [
                ['transaction_journal_id' => (string) $journals[0]->id, 'description' => 'Teil Girokonto'],
                ['transaction_journal_id' => (string) $journals[1]->id, 'description' => 'Teil Gutschein'],
            ],
        ]);
        $response->assertOk();
        $this->assertSources($groupId, [$this->bank->id, $this->voucher->id]);

        // the correction that runs during every upgrade must not unify them.
        Artisan::call('correction:group-accounts');
        $this->assertSources($groupId, [$this->bank->id, $this->voucher->id]);
    }

    public function testTransferStillRequiresEqualAccounts(): void
    {
        $other    = Account::factory()->for($this->user)->withType(AccountTypeEnum::ASSET)->create(['name' => 'Tagesgeld']);
        $response = $this->postJson(route('api.v1.transactions.store'), [
            'group_title'  => 'Umbuchung',
            'transactions' => [
                ['type' => 'transfer', 'date' => '2026-09-01', 'amount' => '10', 'description' => 'A', 'source_id' => (string) $this->bank->id, 'destination_id' => (string) $other->id],
                ['type' => 'transfer', 'date' => '2026-09-01', 'amount' => '5', 'description' => 'B', 'source_id' => (string) $this->voucher->id, 'destination_id' => (string) $other->id],
            ],
        ]);
        $response->assertUnprocessable();
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->user    = $this->createAuthenticatedUser();
        $this->actingAs($this->user);
        $this->bank    = Account::factory()->for($this->user)->withType(AccountTypeEnum::ASSET)->create(['name' => 'Girokonto']);
        $this->voucher = Account::factory()->for($this->user)->withType(AccountTypeEnum::ASSET)->create(['name' => 'Gutschein']);
    }

    private function storeWithdrawal(): int
    {
        $response = $this->postJson(route('api.v1.transactions.store'), [
            'group_title'  => 'Einkauf',
            'transactions' => [
                ['type' => 'withdrawal', 'date' => '2026-09-01', 'amount' => '30', 'description' => 'Teil Girokonto', 'source_id' => (string) $this->bank->id, 'destination_name' => 'Laden'],
                ['type' => 'withdrawal', 'date' => '2026-09-01', 'amount' => '20', 'description' => 'Teil Gutschein', 'source_id' => (string) $this->voucher->id, 'destination_name' => 'Laden'],
            ],
        ]);
        $response->assertOk();

        return (int) $response->json('data.id');
    }

    private function assertSources(int $groupId, array $expected): void
    {
        $journalIds = TransactionGroup::query()->findOrFail($groupId)->transactionJournals()->pluck('id')->all();
        $actual     = Transaction::query()
            ->whereIn('transaction_journal_id', $journalIds)
            ->where('amount', '<', 0)
            ->orderBy('transaction_journal_id')
            ->pluck('account_id')
            ->map(static fn ($id): int => (int) $id)
            ->all()
        ;
        sort($expected);
        sort($actual);
        self::assertSame($expected, $actual, 'Source accounts of the split withdrawal were changed.');
    }
}
