<?php
/**
 * ReconcileControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers\Account;

use FireflyIII\Models\Transaction;
use Tests\TestCase;

/**
 * Class ConfigurationControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReconcileControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::edit
     */
    public function testEdit()
    {
        $journal = $this->user()->transactionJournals()->where('transaction_type_id', 5)->first();

        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.edit', [$journal->id]));
        $response->assertStatus(200);

        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::edit
     */
    public function testEditRedirect()
    {
        $journal = $this->user()->transactionJournals()->where('transaction_type_id', '!=', 5)->first();

        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.edit', [$journal->id]));
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.edit', [$journal->id]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::overview()
     */
    public function testOverview()
    {
        $parameters = [
            'startBalance' => '0',
            'endBalance'   => '10',
            'transactions' => [1, 2, 3],
            'cleared'      => [4, 5, 6],
        ];
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.overview', [1, '20170101', '20170131']) . '?' . http_build_query($parameters));
        $response->assertStatus(200);
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\Account\ReconcileController::overview()
     * @expectedExceptionMessage is not an asset account
     */
    public function testOverviewNotAsset()
    {
        $account    = $this->user()->accounts()->where('account_type_id', '!=', 3)->first();
        $parameters = [
            'startBalance' => '0',
            'endBalance'   => '10',
            'transactions' => [1, 2, 3],
            'cleared'      => [4, 5, 6],
        ];
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.overview', [$account->id, '20170101', '20170131']) . '?' . http_build_query($parameters));
        $response->assertStatus(500);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::__construct
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::reconcile()
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::redirectToOriginalAccount()
     */
    public function testReconcile()
    {
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile', [1, '20170101', '20170131']));
        $response->assertStatus(200);

        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::__construct
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::reconcile()
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::redirectToOriginalAccount()
     */
    public function testReconcileInitialBalance()
    {
        $transaction = Transaction::leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                                  ->where('accounts.user_id', $this->user()->id)->where('accounts.account_type_id', 6)->first(['account_id']);
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile', [$transaction->account_id, '20170101', '20170131']));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::__construct
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::reconcile()
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::redirectToOriginalAccount()
     */
    public function testReconcileNoDates()
    {
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile', [1]));
        $response->assertStatus(200);

        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::__construct
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::reconcile()
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::redirectToOriginalAccount()
     */
    public function testReconcileNoEndDate()
    {
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile', [1, '20170101']));
        $response->assertStatus(200);

        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::__construct
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::reconcile()
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::redirectToOriginalAccount()
     */
    public function testReconcileNotAsset()
    {
        $account = $this->user()->accounts()->where('account_type_id', '!=', 6)->where('account_type_id', '!=', 3)->first();
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile', [$account->id, '20170101', '20170131']));
        $response->assertStatus(302);
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::show()
     */
    public function testShow()
    {
        $journal = $this->user()->transactionJournals()->where('transaction_type_id', 5)->first();
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.show', [$journal->id]));
        $response->assertStatus(200);

        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::show()
     */
    public function testShowSomethingElse()
    {
        $journal = $this->user()->transactionJournals()->where('transaction_type_id', '!=', 5)->first();
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.show', [$journal->id]));
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.show', [$journal->id]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::submit()
     */
    public function testSubmit()
    {
        $data = [
            'transactions' => [1, 2, 3],
            'reconcile'    => 'create',
            'difference'   => '5',
            'end'          => '20170131',
        ];
        $this->be($this->user());
        $response = $this->post(route('accounts.reconcile.submit', [1, '20170101', '20170131']), $data);

        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::transactions()
     */
    public function testTransactions()
    {
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.transactions', [1, '20170101', '20170131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::transactions()
     */
    public function testTransactionsInitialBalance()
    {
        $transaction = Transaction::leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                                  ->where('accounts.user_id', $this->user()->id)->where('accounts.account_type_id', 6)->first(['account_id']);
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.transactions', [$transaction->account_id, '20170101', '20170131']));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::update
     */
    public function testUpdate()
    {
        $journal = $this->user()->transactionJournals()->where('transaction_type_id', 5)->first();
        $data    = [
            'amount' => '5',
        ];

        $this->be($this->user());
        $response = $this->post(route('accounts.reconcile.update', [$journal->id]), $data);
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::update
     */
    public function testUpdateNotReconcile()
    {
        $journal = $this->user()->transactionJournals()->where('transaction_type_id', '!=', 5)->first();
        $data    = [
            'amount' => '5',
        ];

        $this->be($this->user());
        $response = $this->post(route('accounts.reconcile.update', [$journal->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.show', [$journal->id]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController::update
     */
    public function testUpdateZero()
    {
        $journal = $this->user()->transactionJournals()->where('transaction_type_id', 5)->first();
        $data    = [
            'amount' => '0',
        ];

        $this->be($this->user());
        $response = $this->post(route('accounts.reconcile.update', [$journal->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

}
