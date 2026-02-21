<?php

declare(strict_types=1);

namespace Tests\integration\Api\Models\Account;

use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Factory\AccountMetaFactory;
use FireflyIII\Models\Account;
use FireflyIII\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Override;
use Tests\integration\TestCase;

/**
 * @internal
 *
 * @covers \FireflyIII\Api\V1\Controllers\Models\Account\StatementController
 */
final class StatementControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $ccAccount;
    private Account $regularAccount;

    public function testStatementReturns200ForCcAccount(): void
    {
        $this->actingAs($this->user);
        $response = $this->getJson(route('api.v1.accounts.statements', ['account' => $this->ccAccount->id, 'date' => '2026-02-14']));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'statement' => ['start', 'end', 'closing_day', 'due_date', 'total_charges', 'total_payments', 'balance'],
            'data',
            'meta',
        ]);
    }

    public function testStatementPeriodDatesAreCorrect(): void
    {
        $this->actingAs($this->user);
        $response = $this->getJson(route('api.v1.accounts.statements', ['account' => $this->ccAccount->id, 'date' => '2026-02-14']));
        $response->assertStatus(200);
        $response->assertJson([
            'statement' => [
                'start'       => '2026-02-01',
                'end'         => '2026-02-28',
                'closing_day' => 31,
                'due_date'    => '2026-03-08',
            ],
        ]);
    }

    public function testStatementReturns404ForNonCcAccount(): void
    {
        $this->actingAs($this->user);
        $response = $this->getJson(route('api.v1.accounts.statements', ['account' => $this->regularAccount->id]));
        $response->assertStatus(404);
    }

    public function testStatementReturns404WithoutClosingDay(): void
    {
        $this->actingAs($this->user);

        $noClosingAccount = Account::factory()
            ->for($this->user)
            ->withType(AccountTypeEnum::ASSET)
            ->create()
        ;
        $metaFactory = app(AccountMetaFactory::class);
        $metaFactory->crud($noClosingAccount, 'account_role', 'ccAsset');

        $response = $this->getJson(route('api.v1.accounts.statements', ['account' => $noClosingAccount->id]));
        $response->assertStatus(404);
    }

    public function testStatementDefaultsToToday(): void
    {
        $this->actingAs($this->user);
        $response = $this->getJson(route('api.v1.accounts.statements', ['account' => $this->ccAccount->id]));
        $response->assertStatus(200);
        $response->assertJsonStructure(['statement' => ['start', 'end']]);
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser();
        $this->actingAs($this->user);

        $this->ccAccount = Account::factory()
            ->for($this->user)
            ->withType(AccountTypeEnum::ASSET)
            ->create()
        ;
        $metaFactory = app(AccountMetaFactory::class);
        $metaFactory->crud($this->ccAccount, 'account_role', 'ccAsset');
        $metaFactory->crud($this->ccAccount, 'cc_type', 'monthlyFull');
        $metaFactory->crud($this->ccAccount, 'cc_closing_day', '31');
        $metaFactory->crud($this->ccAccount, 'cc_monthly_payment_date', '2026-03-08');

        $this->regularAccount = Account::factory()
            ->for($this->user)
            ->withType(AccountTypeEnum::ASSET)
            ->create()
        ;
        $metaFactory->crud($this->regularAccount, 'account_role', 'defaultAsset');
    }
}
