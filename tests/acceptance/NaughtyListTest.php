<?php
/**
 * NaughtyListTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */
declare(strict_types = 1);

use FireflyIII\Models\TransactionJournal;

/**
 * Class NaughtyListTest
 */
class NaughtyListTest extends TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Transaction\MassController::update
     * @dataProvider naughtyStringProvider
     *
     * @param string $description
     */
    public function testMassUpdate(string $description)
    {
        $deposit = TransactionJournal::where('transaction_type_id', 2)->where('user_id', $this->user()->id)
                                     ->whereNull('deleted_at')
                                     ->first();
        $this->session(['transactions.mass-edit.url' => 'http://localhost']);

        $data = [
            'journals'                                  => [$deposit->id],
            'description'                               => [$deposit->id => $description],
            'amount'                                    => [$deposit->id => 1600],
            'amount_currency_id_amount_' . $deposit->id => 1,
            'date'                                      => [$deposit->id => '2014-07-24'],
            'source_account_name'                       => [$deposit->id => 'Job'],
            'destination_account_id'                    => [$deposit->id => 1],
            'category'                                  => [$deposit->id => 'Salary'],
        ];

        $this->be($this->user());
        $response = $this->call('post', route('transactions.mass.update', [$deposit->id]), $data);
        $this->assertNotEquals($response->getStatusCode(), 500);

        // visit them should show updated content
        $this->call('get', route('transactions.show', [$deposit->id]));
        $this->assertResponseOk();
        $this->see($description);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Transaction\SingleController::store
     * @dataProvider naughtyStringProvider
     *
     * @param string $description
     */
    public function testSingleStore(string $description)
    {
        $this->session(['transactions.create.url' => 'http://localhost']);
        $this->be($this->user());

        $data     = [
            'what'                      => 'withdrawal',
            'amount'                    => '10',
            'amount_currency_id_amount' => 1,
            'source_account_id'         => 1,
            'destination_account_name'  => 'Some destination',
            'date'                      => '2016-01-01',
            'description'               => $description,
        ];
        $response = $this->call('post', route('transactions.store', ['withdrawal']), $data);
        $this->assertNotEquals($response->getStatusCode(), 500);

    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Transaction\SingleController::update
     * @dataProvider naughtyStringProvider
     *
     * @param string $description
     */
    public function testSingleUpdate(string $description)
    {
        $this->session(['transactions.edit.url' => 'http://localhost']);
        $this->be($this->user());
        $data = [
            'id'                        => 123,
            'what'                      => 'withdrawal',
            'description'               => $description,
            'source_account_id'         => 1,
            'destination_account_name'  => 'PLUS',
            'amount'                    => '123',
            'amount_currency_id_amount' => 1,
            'budget_id'                 => 1,
            'category'                  => 'Daily groceries',
            'tags'                      => '',
            'date'                      => '2016-01-01',
        ];

        $response = $this->call('post', route('transactions.update', [123]), $data);
        $this->assertNotEquals($response->getStatusCode(), 500);
    }

}