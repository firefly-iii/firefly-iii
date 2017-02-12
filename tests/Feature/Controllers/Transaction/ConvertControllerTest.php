<?php
/**
 * ConvertControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers\Transaction;


use FireflyIII\Models\TransactionJournal;
use Tests\TestCase;

/**
 * Class ConvertControllerTest
 *
 * @package Tests\Feature\Controllers\Transaction
 */
class ConvertControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::index
     */
    public function testIndexDepositTransfer()
    {
        $deposit = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)->first();

        $this->be($this->user());
        $response = $this->get(route('transactions.convert.index', ['transfer', $deposit->id]));
        $response->assertStatus(200);
        $response->assertSee('Convert a deposit into a transfer');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::index
     */
    public function testIndexDepositWithdrawal()
    {
        $deposit = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)->first();
        $this->be($this->user());
        $response = $this->get(route('transactions.convert.index', ['withdrawal', $deposit->id]));
        $response->assertStatus(200);
        $response->assertSee('Convert a deposit into a withdrawal');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::index
     */
    public function testIndexTransferDeposit()
    {
        $transfer = TransactionJournal::where('transaction_type_id', 3)->where('user_id', $this->user()->id)->first();
        $this->be($this->user());
        $response = $this->get(route('transactions.convert.index', ['deposit', $transfer->id]));
        $response->assertStatus(200);
        $response->assertSee('Convert a transfer into a deposit');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::index
     */
    public function testIndexTransferWithdrawal()
    {
        $transfer = TransactionJournal::where('transaction_type_id', 3)->where('user_id', $this->user()->id)->first();
        $this->be($this->user());
        $response = $this->get(route('transactions.convert.index', ['withdrawal', $transfer->id]));
        $response->assertStatus(200);
        $response->assertSee('Convert a transfer into a withdrawal');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::index
     */
    public function testIndexWithdrawalDeposit()
    {
        $withdrawal= TransactionJournal::where('transaction_type_id', 1)->where('user_id', $this->user()->id)->first();
        $this->be($this->user());
        $response = $this->get(route('transactions.convert.index', ['deposit', $withdrawal->id]));
        $response->assertStatus(200);
        $response->assertSee('Convert a withdrawal into a deposit');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::index
     */
    public function testIndexWithdrawalTransfer()
    {
        $withdrawal= TransactionJournal::where('transaction_type_id', 1)->where('user_id', $this->user()->id)->first();
        $this->be($this->user());
        $response = $this->get(route('transactions.convert.index', ['transfer', $withdrawal->id]));
        $response->assertStatus(200);
        $response->assertSee('Convert a withdrawal into a transfer');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController::postIndex
     */
    public function testPostIndex()
    {
        $withdrawal= TransactionJournal::where('transaction_type_id', 1)->where('user_id', $this->user()->id)->first();
        // convert a withdrawal to a transfer. Requires the ID of another asset account.
        $data = [
            'destination_account_asset' => 2,
        ];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index', ['transfer', $withdrawal->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.show', [$withdrawal->id]));
    }


}