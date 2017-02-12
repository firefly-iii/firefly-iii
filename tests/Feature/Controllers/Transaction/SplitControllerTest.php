<?php
/**
 * SplitControllerTest.php
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
 * Class SplitControllerTest
 *
 * @package Tests\Feature\Controllers\Transaction
 */
class SplitControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SplitController::edit
     * Implement testEdit().
     */
    public function testEdit()
    {
        $deposit = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)->first();
        $this->be($this->user());
        $response = $this->get(route('transactions.split.edit', [$deposit->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SplitController::update
     * Implement testUpdate().
     */
    public function testUpdate()
    {
        $this->session(['transactions.edit-split.url' => 'http://localhost']);
        $deposit = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)->first();
        $data    = [
            'id'                             => $deposit->id,
            'what'                           => 'deposit',
            'journal_description'            => 'Updated salary',
            'currency_id'                    => 1,
            'journal_destination_account_id' => 1,
            'journal_amount'                 => 1591,
            'date'                           => '2014-01-24',
            'tags'                           => '',
            'transactions'                   => [
                [
                    'description'         => 'Split #1',
                    'source_account_name' => 'Job',
                    'amount'              => 1591,
                    'category'            => '',
                ],
            ],
        ];
        $this->be($this->user());
        $response = $this->post(route('transactions.split.update', [$deposit->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // journal is updated?
        $response = $this->get(route('transactions.show', [$deposit->id]));
        $response->assertStatus(200);
        $response->assertSee('Updated salary');
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

}