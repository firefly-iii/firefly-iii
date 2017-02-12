<?php
/**
 * MassControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers\Transaction;


use FireflyIII\Models\TransactionJournal;
use Tests\TestCase;

class MassControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\MassController::delete
     */
    public function testDelete()
    {
        $withdrawals = TransactionJournal::where('transaction_type_id', 1)->where('user_id', $this->user()->id)->take(2)->get()->pluck('id')->toArray();
        $this->be($this->user());
        $response = $this->get(route('transactions.mass.delete', $withdrawals));
        $response->assertStatus(200);
        $response->assertSee('Delete a number of transactions');
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\MassController::destroy
     */
    public function testDestroy()
    {
        $this->session(['transactions.mass-delete.url' => 'http://localhost']);
        $deposits = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)->take(2)->get()->pluck('id')->toArray();
        $data     = [
            'confirm_mass_delete' => $deposits,
        ];
        $this->be($this->user());
        $response = $this->post(route('transactions.mass.destroy'), $data);
        $response->assertSessionHas('success');
        $response->assertStatus(302);

        // visit them should give 404.
        $response = $this->get(route('transactions.show', [$deposits[0]]));
        $response->assertStatus(404);


    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\MassController::edit
     */
    public function testEdit()
    {
        $transfers = TransactionJournal::where('transaction_type_id', 3)->where('user_id', $this->user()->id)->take(2)->get()->pluck('id')->toArray();
        $this->be($this->user());
        $response = $this->get(route('transactions.mass.delete', $transfers));
        $response->assertStatus(200);
        $response->assertSee('Edit a number of transactions');
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\MassController::update
     */
    public function testUpdate()
    {
        $deposit = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)
                                     ->whereNull('deleted_at')
                                     ->first();
        $this->session(['transactions.mass-edit.url' => 'http://localhost']);

        $data = [
            'journals'                                  => [$deposit->id],
            'description'                               => [$deposit->id => 'Updated salary thing'],
            'amount'                                    => [$deposit->id => 1600],
            'amount_currency_id_amount_' . $deposit->id => 1,
            'date'                                      => [$deposit->id => '2014-07-24'],
            'source_account_name'                       => [$deposit->id => 'Job'],
            'destination_account_id'                    => [$deposit->id => 1],
            'category'                                  => [$deposit->id => 'Salary'],
        ];

        $this->be($this->user());
        $response = $this->post(route('transactions.mass.update', [$deposit->id]), $data);
        $response->assertSessionHas('success');
        $response->assertStatus(302);

        // visit them should show updated content
        $response = $this->get(route('transactions.show', [$deposit->id]));
        $response->assertStatus(200);
        $response->assertSee('Updated salary thing');
    }


}