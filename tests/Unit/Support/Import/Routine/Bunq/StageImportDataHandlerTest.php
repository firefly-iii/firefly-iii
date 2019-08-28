<?php
/**
 * StageImportDataHandlerTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Support\Import\Routine\Bunq;


use bunq\Model\Generated\Endpoint\BunqResponsePaymentList;
use bunq\Model\Generated\Endpoint\Payment as BunqPayment;
use bunq\Model\Generated\Object\Amount;
use bunq\Model\Generated\Object\LabelMonetaryAccount;
use bunq\Model\Generated\Object\LabelUser;
use bunq\Model\Generated\Object\Pointer;
use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Services\Bunq\ApiContext;
use FireflyIII\Services\Bunq\Payment;
use FireflyIII\Support\Import\Routine\Bunq\PaymentConverter;
use FireflyIII\Support\Import\Routine\Bunq\StageImportDataHandler;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class StageImportDataHandlerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class StageImportDataHandlerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * @covers \FireflyIII\Support\Import\Routine\Bunq\StageImportDataHandler
     */
    public function testRunBasic(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'sidh_bbunq_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'bunq';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // fake objects:
        $deposit                 = $this->user()->accounts()->where('account_type_id', 5)->first();
        $account                 = $this->user()->accounts()->where('account_type_id', 3)->first();
        $contextPreference       = new Preference;
        $contextPreference->name = 'Some name';
        $contextPreference->data = '{"a":"b"}';
        $config                  = [
            'accounts' => [
                ['id' => 1234], // bunq account
            ],
            'mapping'  => [
                1234 => 5678, // Firefly III mapping.
            ],
        ];
        $amount                  = new Amount('150', 'EUR');
        $pointer                 = new Pointer('iban', 'ES2364265841767173822054', 'Test Site');
        $expectedAccount         = [
            'user_id'         => 1,
            'iban'            => null,
            'name'            => 'James',
            'account_type_id' => null,
            'accountType'     => 'Revenue account',
            'virtualBalance'  => null,
            'active'          => true,
        ];
        $today                   = new Carbon;

        // ignore the deprecated fields:
        $amount->setValue('150');
        $amount->setCurrency('EUR');
        $pointer->setType('iban');
        $pointer->setValue('ES2364265841767173822054');
        $pointer->setName('Test Site');
        $labelMonetaryAccount = new LabelMonetaryAccount();
        $labelMonetaryAccount->setDisplayName('James');
        $labelUser = new LabelUser('x', 'James', 'NL');
        $labelUser->setDisplayName('James');
        $labelMonetaryAccount->setLabelUser($labelUser);

        $payment = new BunqPayment($amount, $pointer, 'Some descr', null, null);
        $payment->setAmount($amount);
        $payment->setCounterpartyAlias($labelMonetaryAccount);
        $payment->setDescription('Random description #' . $this->randomInt());
        $value = [$payment];
        $list  = new BunqResponsePaymentList($value, [], null);

        $expectedTransaction = [
            'user'               => 1,
            'type'               => 'Deposit',
            'date'               => $today->format('Y-m-d'),
            'timestamp'          => $today->toAtomString(),
            'description'        => $payment->getDescription(),
            'piggy_bank_id'      => null,
            'piggy_bank_name'    => null,
            'bill_id'            => null,
            'bill_name'          => null,
            'tags'               => [null, null],
            'internal_reference' => null,
            'external_id'        => null,
            'notes'              => null,
            'bunq_payment_id'    => null,
            'transactions'       => [
                [
                    'description'           => null,
                    'amount'                => '150',
                    'currency_id'           => null,
                    'currency_code'         => 'EUR',
                    'foreign_amount'        => null,
                    'foreign_currency_id'   => null,
                    'foreign_currency_code' => null,
                    'budget_id'             => null,
                    'budget_name'           => null,
                    'category_id'           => null,
                    'category_name'         => null,
                    'source_id'             => $deposit->id,
                    'source_name'           => null,
                    'destination_id'        => $account->id,
                    'destination_name'      => null,
                    'reconciled'            => false,
                    'identifier'            => 0,
                ],
            ],
            'original-source'    => 'bunq-v' . config('firefly.version'),
        ];

        $expectedTransactions = [$expectedTransaction];


        // mock used objects:
        $repository        = $this->mock(ImportJobRepositoryInterface::class);
        $accountRepository = $this->mock(AccountRepositoryInterface::class);
        $accountFactory    = $this->mock(AccountFactory::class);
        $context           = $this->mock(ApiContext::class);
        $payment           = $this->mock(Payment::class);
        $converter         = $this->mock(PaymentConverter::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $accountRepository->shouldReceive('setUser')->once();
        $accountFactory->shouldReceive('setUser')->once();
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_api_context', null])->andReturn($contextPreference);
        $context->shouldReceive('fromJson')->withArgs(['{"a":"b"}'])->once();
        $converter->shouldReceive('setImportJob')->atLeast()->once();
        $repository->shouldReceive('getConfiguration')->withArgs([Mockery::any()])->andReturn($config)->once();
        $accountRepository->shouldReceive('findNull')->withArgs([5678])->andReturn($account)->once();
        $payment->shouldReceive('listing')->once()->andReturn($list);

        // set new last transaction ID:
        $lastPref       = new Preference;
        $lastPref->data = 0;
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq-oldest-transaction-1234', 0])->andReturn($lastPref)->times(2);
        //
        $lastPref       = new Preference;
        $lastPref->data = 0;
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq-newest-transaction-1234', 0])->andReturn($lastPref)->once();

        // converter should convert:
        $converter->shouldReceive('convert')->atLeast()->once()->andReturn($expectedTransaction);

        // todo: improve test thing:
        Preferences::shouldReceive('setForUser');

        $handler = new StageImportDataHandler;
        $handler->setImportJob($job);
        try {
            $handler->run();

        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }
        $transactions = $handler->getTransactions();
        $this->assertEquals($expectedTransactions, $transactions);
    }

    //    /**
    //     * @covers \FireflyIII\Support\Import\Routine\Bunq\StageImportDataHandler
    //     */
    //    public function testRunEmpty(): void
    //    {
    //        $job                = new ImportJob;
    //        $job->user_id       = $this->user()->id;
    //        $job->key           = 'sidA_bbunq_' . $this->randomInt();
    //        $job->status        = 'new';
    //        $job->stage         = 'new';
    //        $job->provider      = 'bunq';
    //        $job->file_type     = '';
    //        $job->configuration = [];
    //        $job->save();
    //
    //        // fake objects:
    //        $account                 = $this->user()->accounts()->where('account_type_id', 3)->first();
    //        $contextPreference       = new Preference;
    //        $contextPreference->name = 'Some name';
    //        $contextPreference->data = '{"a":"b"}';
    //        $config                  = [
    //            'accounts' => [
    //                ['id' => 1234], // bunq account
    //            ],
    //            'mapping'  => [
    //                1234 => 5678, // Firefly III mapping.
    //            ],
    //        ];
    //        $expectedTransactions    = [];
    //        $value                   = [];
    //        $list                    = new BunqResponsePaymentList($value, [], null);
    //
    //        // mock used objects:
    //        $repository        = $this->mock(ImportJobRepositoryInterface::class);
    //        $accountRepository = $this->mock(AccountRepositoryInterface::class);
    //        $accountFactory    = $this->mock(AccountFactory::class);
    //        $context           = $this->mock(ApiContext::class);
    //        $payment           = $this->mock(Payment::class);
    //
    //        // mock calls:
    //        $repository->shouldReceive('setUser')->once();
    //        $accountRepository->shouldReceive('setUser')->once();
    //        $accountFactory->shouldReceive('setUser')->once();
    //        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_api_context', null])->andReturn($contextPreference);
    //        $context->shouldReceive('fromJson')->withArgs(['{"a":"b"}'])->once();
    //        $repository->shouldReceive('getConfiguration')->withArgs([Mockery::any()])->andReturn($config)->once();
    //        $accountRepository->shouldReceive('findNull')->withArgs([5678])->andReturn($account)->once();
    //        $payment->shouldReceive('listing')->once()->andReturn($list);
    //
    //        // set new last transaction ID:
    //        $lastPref       = new Preference;
    //        $lastPref->data = 0;
    //        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq-oldest-transaction-1234', 0])->andReturn($lastPref)->times(2);
    //
    //        $lastPref       = new Preference;
    //        $lastPref->data = 0;
    //        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq-newest-transaction-1234', 0])->andReturn($lastPref)->once();
    //
    //        // todo: improve test thing:
    //        Preferences::shouldReceive('setForUser');
    //
    //        $handler = new StageImportDataHandler;
    //        $handler->setImportJob($job);
    //        try {
    //            $handler->run();
    //
    //        } catch (FireflyException $e) {
    //            $this->assertFalse(true, $e->getMessage());
    //        }
    //        $transactions = $handler->getTransactions();
    //        $this->assertEquals($expectedTransactions, $transactions);
    //
    //    }
    //
    //    /**
    //     * @covers \FireflyIII\Support\Import\Routine\Bunq\StageImportDataHandler
    //     */
    //    public function testRunIban(): void
    //    {
    //        $job                = new ImportJob;
    //        $job->user_id       = $this->user()->id;
    //        $job->key           = 'sidh_bbunq_' . $this->randomInt();
    //        $job->status        = 'new';
    //        $job->stage         = 'new';
    //        $job->provider      = 'bunq';
    //        $job->file_type     = '';
    //        $job->configuration = [];
    //        $job->save();
    //
    //        // fake objects:
    //        $deposit                 = $this->user()->accounts()->where('account_type_id', 5)->first();
    //        $account                 = $this->user()->accounts()->where('account_type_id', 3)->first();
    //        $contextPreference       = new Preference;
    //        $contextPreference->name = 'Some name';
    //        $contextPreference->data = '{"a":"b"}';
    //        $config                  = [
    //            'accounts' => [
    //                ['id' => 1234], // bunq account
    //            ],
    //            'mapping'  => [
    //                1234 => 5678, // Firefly III mapping.
    //            ],
    //        ];
    //        $today                   = new Carbon;
    //        $amount                  = new Amount('150', 'EUR');
    //        $pointer                 = new Pointer('iban', 'ES2364265841767173822054', 'Test Site');
    //
    //        // set new last transaction ID:
    //        $lastPref       = new Preference;
    //        $lastPref->data = 0;
    //        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq-oldest-transaction-1234', 0])->andReturn($lastPref)->times(2);
    //
    //        $lastPref       = new Preference;
    //        $lastPref->data = 0;
    //        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq-newest-transaction-1234', 0])->andReturn($lastPref)->once();
    //
    //        // todo: improve test thing:
    //        Preferences::shouldReceive('setForUser');
    //
    //
    //        // ignore the deprecated fields:
    //        $amount->setValue('150');
    //        $amount->setCurrency('EUR');
    //        $pointer->setType('iban');
    //        $pointer->setValue('ES2364265841767173822054');
    //        $pointer->setName('Test Site');
    //        $labelMonetaryAccount = new LabelMonetaryAccount();
    //        $labelMonetaryAccount->setDisplayName('James');
    //        $labelUser = new LabelUser('x', 'James', 'NL');
    //        $labelUser->setDisplayName('James');
    //        $labelMonetaryAccount->setLabelUser($labelUser);
    //        $labelMonetaryAccount->setIban('RS88844660406878687897');
    //
    //        $payment = new BunqPayment($amount, $pointer, 'Some descr', null, null);
    //        $payment->setAmount($amount);
    //        $payment->setDescription('Some random thing #' . $this->randomInt());
    //        $payment->setCounterpartyAlias($labelMonetaryAccount);
    //        $value = [$payment];
    //        $list  = new BunqResponsePaymentList($value, [], null);
    //
    //        $expectedTransactions = [
    //            [
    //                'user'               => 1,
    //                'type'               => 'Deposit',
    //                'date'               => $today->format('Y-m-d'),
    //                'description'        => $payment->getDescription(),
    //                'piggy_bank_id'      => null,
    //                'piggy_bank_name'    => null,
    //                'bill_id'            => null,
    //                'bill_name'          => null,
    //                'tags'               => [null, null],
    //                'internal_reference' => null,
    //                'external_id'        => null,
    //                'notes'              => null,
    //                'bunq_payment_id'    => null,
    //                'transactions'       => [
    //                    [
    //                        'description'           => null,
    //                        'amount'                => '150',
    //                        'currency_id'           => null,
    //                        'currency_code'         => 'EUR',
    //                        'foreign_amount'        => null,
    //                        'foreign_currency_id'   => null,
    //                        'foreign_currency_code' => null,
    //                        'budget_id'             => null,
    //                        'budget_name'           => null,
    //                        'category_id'           => null,
    //                        'category_name'         => null,
    //                        'source_id'             => $deposit->id,
    //                        'source_name'           => null,
    //                        'destination_id'        => $account->id,
    //                        'destination_name'      => null,
    //                        'reconciled'            => false,
    //                        'identifier'            => 0,
    //                    ],
    //                ],
    //                'original-source'    => 'bunq-v' . config('firefly.version'),
    //            ],
    //        ];
    //
    //        // mock used objects:
    //        $repository        = $this->mock(ImportJobRepositoryInterface::class);
    //        $accountRepository = $this->mock(AccountRepositoryInterface::class);
    //        $accountFactory    = $this->mock(AccountFactory::class);
    //        $context           = $this->mock(ApiContext::class);
    //        $payment           = $this->mock(Payment::class);
    //
    //        // mock calls:
    //        $repository->shouldReceive('setUser')->once();
    //        $accountRepository->shouldReceive('setUser')->once();
    //        $accountFactory->shouldReceive('setUser')->once();
    //        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_api_context', null])->andReturn($contextPreference);
    //        $context->shouldReceive('fromJson')->withArgs(['{"a":"b"}'])->once();
    //        $repository->shouldReceive('getConfiguration')->withArgs([Mockery::any()])->andReturn($config)->once();
    //        $accountRepository->shouldReceive('findNull')->withArgs([5678])->andReturn($account)->once();
    //        $payment->shouldReceive('listing')->once()->andReturn($list);
    //        $accountRepository->shouldReceive('findByIbanNull')->withArgs(['RS88844660406878687897', [AccountType::REVENUE]])->once()->andReturn($deposit);
    //
    //
    //        $handler = new StageImportDataHandler;
    //        $handler->setImportJob($job);
    //        try {
    //            $handler->run();
    //
    //        } catch (FireflyException $e) {
    //            $this->assertFalse(true, $e->getMessage());
    //        }
    //        $transactions = $handler->getTransactions();
    //        $this->assertEquals($expectedTransactions, $transactions);
    //    }
    //
    //    /**
    //     * @covers \FireflyIII\Support\Import\Routine\Bunq\StageImportDataHandler
    //     */
    //    public function testRunIbanAsset(): void
    //    {
    //        $job                = new ImportJob;
    //        $job->user_id       = $this->user()->id;
    //        $job->key           = 'sidh_bbunq_' . $this->randomInt();
    //        $job->status        = 'new';
    //        $job->stage         = 'new';
    //        $job->provider      = 'bunq';
    //        $job->file_type     = '';
    //        $job->configuration = [];
    //        $job->save();
    //
    //        // fake objects:
    //        $account                 = $this->user()->accounts()->where('account_type_id', 3)->first();
    //        $asset                   = $this->user()->accounts()->where('account_type_id', 3)->where('id', '!=', $account->id)->first();
    //        $contextPreference       = new Preference;
    //        $contextPreference->name = 'Some name';
    //        $contextPreference->data = '{"a":"b"}';
    //        $config                  = [
    //            'accounts' => [
    //                ['id' => 1234], // bunq account
    //            ],
    //            'mapping'  => [
    //                1234 => 5678, // Firefly III mapping.
    //            ],
    //        ];
    //        $amount                  = new Amount('150', 'EUR');
    //        $pointer                 = new Pointer('iban', 'ES2364265841767173822054', 'Test Site');
    //        $expectedAccount         = [
    //            'user_id'         => 1,
    //            'iban'            => null,
    //            'name'            => 'James',
    //            'account_type_id' => null,
    //            'accountType'     => 'Revenue account',
    //            'virtualBalance'  => null,
    //            'active'          => true,
    //        ];
    //
    //        // ignore the deprecated fields:
    //        $amount->setValue('150');
    //        $amount->setCurrency('EUR');
    //        $pointer->setType('iban');
    //        $pointer->setValue('ES2364265841767173822054');
    //        $pointer->setName('Test Site');
    //        $labelMonetaryAccount = new LabelMonetaryAccount();
    //        $labelMonetaryAccount->setDisplayName('James');
    //        $labelUser = new LabelUser('x', 'James', 'NL');
    //        $labelUser->setDisplayName('James');
    //        $labelMonetaryAccount->setLabelUser($labelUser);
    //        $labelMonetaryAccount->setIban('RS88844660406878687897');
    //        $today   = new Carbon;
    //        $payment = new BunqPayment($amount, $pointer, 'Some descr', null, null);
    //        $payment->setAmount($amount);
    //        $payment->setCounterpartyAlias($labelMonetaryAccount);
    //        $payment->setDescription('Random transfer #' . $this->randomInt());
    //        $value = [$payment];
    //        $list  = new BunqResponsePaymentList($value, [], null);
    //
    //        // set new last transaction ID:
    //        $lastPref       = new Preference;
    //        $lastPref->data = 0;
    //        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq-oldest-transaction-1234', 0])->andReturn($lastPref)->once();
    //
    //        $expectedTransactions = [
    //            [
    //                'user'               => 1,
    //                'type'               => 'Transfer',
    //                'date'               => $today->format('Y-m-d'),
    //                'description'        => $payment->getDescription(),
    //                'piggy_bank_id'      => null,
    //                'piggy_bank_name'    => null,
    //                'bill_id'            => null,
    //                'bill_name'          => null,
    //                'tags'               => [null, null],
    //                'internal_reference' => null,
    //                'external_id'        => null,
    //                'notes'              => null,
    //                'bunq_payment_id'    => null,
    //                'transactions'       => [
    //                    [
    //                        'description'           => null,
    //                        'amount'                => '150',
    //                        'currency_id'           => null,
    //                        'currency_code'         => 'EUR',
    //                        'foreign_amount'        => null,
    //                        'foreign_currency_id'   => null,
    //                        'foreign_currency_code' => null,
    //                        'budget_id'             => null,
    //                        'budget_name'           => null,
    //                        'category_id'           => null,
    //                        'category_name'         => null,
    //                        'source_id'             => $asset->id,
    //                        'source_name'           => null,
    //                        'destination_id'        => $account->id,
    //                        'destination_name'      => null,
    //                        'reconciled'            => false,
    //                        'identifier'            => 0,
    //                    ],
    //                ],
    //            ],
    //        ];
    //
    //        // mock used objects:
    //        $repository        = $this->mock(ImportJobRepositoryInterface::class);
    //        $accountRepository = $this->mock(AccountRepositoryInterface::class);
    //        $accountFactory    = $this->mock(AccountFactory::class);
    //        $context           = $this->mock(ApiContext::class);
    //        $payment           = $this->mock(Payment::class);
    //
    //        // mock calls:
    //        $repository->shouldReceive('setUser')->once();
    //        $accountRepository->shouldReceive('setUser')->once();
    //        $accountFactory->shouldReceive('setUser')->once();
    //        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_api_context', null])->andReturn($contextPreference);
    //        $context->shouldReceive('fromJson')->withArgs(['{"a":"b"}'])->once();
    //        $repository->shouldReceive('getConfiguration')->withArgs([Mockery::any()])->andReturn($config)->once();
    //        $accountRepository->shouldReceive('findNull')->withArgs([5678])->andReturn($account)->once();
    //        $payment->shouldReceive('listing')->once()->andReturn($list);
    //        $accountRepository->shouldReceive('findByIbanNull')->withArgs(['RS88844660406878687897', [AccountType::REVENUE]])->once()->andReturnNull();
    //        $accountRepository->shouldReceive('findByIbanNull')->withArgs(['RS88844660406878687897', [AccountType::ASSET]])->once()->andReturn($asset);
    //
    //
    //        // set new last transaction ID:
    //        $lastPref       = new Preference;
    //        $lastPref->data = 0;
    //        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq-oldest-transaction-1234', 0])->andReturn($lastPref)->times(1);
    //
    //        $lastPref       = new Preference;
    //        $lastPref->data = 0;
    //        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq-newest-transaction-1234', 0])->andReturn($lastPref)->once();
    //
    //        // todo: improve test thing:
    //        Preferences::shouldReceive('setForUser');
    //
    //        $handler = new StageImportDataHandler;
    //        $handler->setImportJob($job);
    //        try {
    //            $handler->run();
    //
    //        } catch (FireflyException $e) {
    //            $this->assertFalse(true, $e->getMessage());
    //        }
    //        $transactions = $handler->getTransactions();
    //        //$this->assertEquals($expectedTransactions, $transactions);
    //    }

}
