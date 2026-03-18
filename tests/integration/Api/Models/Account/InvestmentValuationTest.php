<?php

declare(strict_types=1);

namespace Tests\integration\Api\Models\Account;

use Carbon\Carbon;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Models\UserGroup;
use FireflyIII\Models\UserRole;
use Laravel\Passport\Passport;
use FireflyIII\Support\Facades\FireflyConfig;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\Support\Singleton\PreferencesSingleton;
use FireflyIII\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Override;
use Tests\integration\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class InvestmentValuationTest extends TestCase
{
    use RefreshDatabase;

    private TransactionCurrency $btc;
    private TransactionCurrency $eur;

    public function testForeignCurrencyAssetAccountGetsPrimaryValuation(): void
    {
        $user       = $this->createUser('investor@example.com');
        $asset      = $this->createInvestmentAccount($user, 'BTC wallet');

        $this->enablePrimaryConversion($user);
        $this->createTransfer($user, $asset, '40000', '2');
        $this->storeRate($user, '20000');

        $response   = $this->showAccount($user, $asset);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.current_balance', '2');
        $response->assertJsonPath('data.attributes.pc_current_balance', '40000.00');
    }

    public function testUpdatingExchangeRateRefreshesInvestmentValuation(): void
    {
        $user       = $this->createUser('updater@example.com');
        $asset      = $this->createInvestmentAccount($user, 'BTC stash');

        $this->enablePrimaryConversion($user);
        $this->createTransfer($user, $asset, '40000', '2');
        $rate       = $this->storeRate($user, '20000');

        $this->showAccount($user, $asset)->assertJsonPath('data.attributes.pc_current_balance', '40000.00');

        Passport::actingAs($user);
        $response   = $this->putJson(route('api.v1.exchange-rates.update', [$rate]), [
            'date' => self::valuationDate()->format('Y-m-d'),
            'rate' => '25000',
        ]);

        $response->assertOk();
        $this->showAccount($user, $asset)->assertJsonPath('data.attributes.pc_current_balance', '50000.00');
    }

    public function testExchangeRateCachesStayIsolatedPerUserGroup(): void
    {
        $firstUser  = $this->createUser('group-a@example.com');
        $secondUser = $this->createUser('group-b@example.com');
        $firstAsset = $this->createInvestmentAccount($firstUser, 'BTC A');
        $secondAsset = $this->createInvestmentAccount($secondUser, 'BTC B');

        $this->enablePrimaryConversion($firstUser);
        $this->enablePrimaryConversion($secondUser);

        $this->createTransfer($firstUser, $firstAsset, '40000', '2');
        $this->createTransfer($secondUser, $secondAsset, '40000', '2');

        $this->storeRate($firstUser, '20000');
        $this->storeRate($secondUser, '30000');

        $this->showAccount($firstUser, $firstAsset)->assertJsonPath('data.attributes.pc_current_balance', '40000.00');
        $this->showAccount($secondUser, $secondAsset)->assertJsonPath('data.attributes.pc_current_balance', '60000.00');
    }

    public function testBulkEndpointsInvalidateInvestmentValuationCaches(): void
    {
        $user       = $this->createUser('bulk@example.com');
        $asset      = $this->createInvestmentAccount($user, 'BTC bulk');

        $this->enablePrimaryConversion($user);
        $this->createTransfer($user, $asset, '40000', '2');

        Passport::actingAs($user);
        $storeResponse = $this->postJson(
            route('api.v1.exchange-rates.store.by-currencies', [$this->btc->code, $this->eur->code]),
            [self::valuationDate()->format('Y-m-d') => '20000']
        );

        $storeResponse->assertOk();
        $this->showAccount($user, $asset)->assertJsonPath('data.attributes.pc_current_balance', '40000.00');

        $deleteResponse = $this->deleteJson(route('api.v1.exchange-rates.destroy', [$this->btc->code, $this->eur->code]));
        $deleteResponse->assertNoContent();

        $this->showAccount($user, $asset)->assertJsonPath('data.attributes.pc_current_balance', '2.00');
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->eur = TransactionCurrency::where('code', 'EUR')->firstOrFail();
        $this->btc = TransactionCurrency::create([
            'name'           => 'Bitcoin',
            'code'           => 'BTC',
            'symbol'         => 'BTC',
            'decimal_places' => 8,
            'enabled'        => true,
        ]);
    }

    private function createCashAccount(User $user, string $name = 'Cash account'): Account
    {
        return $this->createAccountWithCurrency($user, $name, $this->eur);
    }

    private function createAccountWithCurrency(User $user, string $name, TransactionCurrency $currency): Account
    {
        $account = Account::factory()
            ->for($user)
            ->withType(AccountTypeEnum::ASSET)
            ->create([
                'user_group_id'    => $user->user_group_id,
                'name'             => $name,
                'virtual_balance'  => null,
                'native_virtual_balance' => null,
            ])
        ;

        AccountMeta::create([
            'account_id' => $account->id,
            'name'       => 'currency_id',
            'data'       => $currency->id,
        ]);

        return $account;
    }

    private function createInvestmentAccount(User $user, string $name): Account
    {
        return $this->createAccountWithCurrency($user, $name, $this->btc);
    }

    private function createTransfer(User $user, Account $asset, string $eurAmount, string $btcAmount): void
    {
        $cashAccount   = $this->createCashAccount($user);
        $transferType  = TransactionType::where('type', TransactionTypeEnum::TRANSFER->value)->firstOrFail();
        $date          = self::valuationDate();
        $journal       = TransactionJournal::create([
            'user_id'                 => $user->id,
            'user_group_id'           => $user->user_group_id,
            'transaction_type_id'     => $transferType->id,
            'transaction_currency_id' => $this->eur->id,
            'description'             => 'Buy BTC',
            'date'                    => $date,
            'date_tz'                 => $date->format('e'),
            'order'                   => 0,
            'tag_count'               => 0,
            'completed'               => true,
        ]);

        Transaction::create([
            'account_id'              => $cashAccount->id,
            'transaction_journal_id'  => $journal->id,
            'description'             => null,
            'transaction_currency_id' => $this->eur->id,
            'amount'                  => '-'.$eurAmount,
            'foreign_currency_id'     => $this->btc->id,
            'foreign_amount'          => '-'.$btcAmount,
            'identifier'              => 0,
            'reconciled'              => false,
        ]);

        Transaction::create([
            'account_id'              => $asset->id,
            'transaction_journal_id'  => $journal->id,
            'description'             => null,
            'transaction_currency_id' => $this->btc->id,
            'amount'                  => $btcAmount,
            'foreign_currency_id'     => $this->eur->id,
            'foreign_amount'          => $eurAmount,
            'identifier'              => 0,
            'reconciled'              => false,
        ]);
    }

    private function createUser(string $email): User
    {
        $group = UserGroup::create(['title' => $email]);
        $role  = UserRole::where('title', 'owner')->firstOrFail();
        $user  = User::create(['email' => $email, 'password' => 'password', 'user_group_id' => $group->id]);

        GroupMembership::create(['user_id' => $user->id, 'user_group_id' => $group->id, 'user_role_id' => $role->id]);

        return $user;
    }

    private function enablePrimaryConversion(User $user): void
    {
        FireflyConfig::set('enable_exchange_rates', true);
        Preferences::setForUser($user, 'convert_to_primary', true);
        PreferencesSingleton::getInstance()->resetPreferences();
    }

    private function showAccount(User $user, Account $account)
    {
        Passport::actingAs($user);
        PreferencesSingleton::getInstance()->resetPreferences();

        return $this->getJson(route('api.v1.accounts.show', [$account]).'?date='.self::valuationDate()->format('Y-m-d'));
    }

    private function storeRate(User $user, string $rate)
    {
        $date = self::valuationDate()->clone()->startOfDay();

        return CurrencyExchangeRate::create([
            'user_id'          => $user->id,
            'user_group_id'    => $user->user_group_id,
            'from_currency_id' => $this->btc->id,
            'to_currency_id'   => $this->eur->id,
            'date'             => $date,
            'date_tz'          => $date->format('e'),
            'rate'             => $rate,
        ]);
    }

    private static function valuationDate(): Carbon
    {
        return Carbon::create(2026, 3, 18, 12, 0, 0, 'UTC');
    }
}
