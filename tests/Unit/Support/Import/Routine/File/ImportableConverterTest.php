<?php
/**
 * ImportableConverterTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Support\Import\Routine\File;


use Amount;
use Carbon\Carbon;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Placeholder\ImportTransaction;
use FireflyIII\Support\Import\Routine\File\AssetAccountMapper;
use FireflyIII\Support\Import\Routine\File\CurrencyMapper;
use FireflyIII\Support\Import\Routine\File\ImportableConverter;
use FireflyIII\Support\Import\Routine\File\OpposingAccountMapper;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * todo test foreign currency
 * todo test budget (known and unknown)
 * todo test category (known and unknown)
 * todo test foreign currency
 *
 * Class ImportableConverterTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ImportableConverterTest extends TestCase
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
     * Basic test. Should match a withdrawal. Amount is negative.
     *
     * @covers \FireflyIII\Support\Import\Routine\File\ImportableConverter
     */
    public function testBasic(): void
    {
        $nullAccount        = ['name' => null, 'iban' => null, 'number' => null, 'bic' => null];
        $importable         = new ImportTransaction;
        $importable->amount = '-45.67';
        $importable->date   = '20180917';
        $importable->tags   = ['a', 'b', 'c'];
        $importables        = [$importable];

        $job                = $this->user()->importJobs()->first();
        $job->configuration = [
            'date-format' => 'Ymd',
        ];
        $job->save();

        // mock used classes:
        $repository     = $this->mock(ImportJobRepositoryInterface::class);
        $assetMapper    = $this->mock(AssetAccountMapper::class);
        $opposingMapper = $this->mock(OpposingAccountMapper::class);
        $currencyMapper = $this->mock(CurrencyMapper::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);

        $accountRepos->shouldReceive('setUser')->once();

        // get default currency
        $euro = $this->getEuro();
        $usd  = $this->getDollar();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro)->once();

        // set user and config:
        $repository->shouldReceive('setUser')->once();
        $assetMapper->shouldReceive('setUser')->once();
        $opposingMapper->shouldReceive('setUser')->once();
        $currencyMapper->shouldReceive('setUser')->once();
        $assetMapper->shouldReceive('setDefaultAccount')->withArgs([0])->once();

        // respond to mapping call:
        $asset   = $this->user()->accounts()->where('account_type_id', 3)->first();
        $expense = $this->user()->accounts()->where('account_type_id', 4)->first();

        $assetMapper->shouldReceive('map')->once()->withArgs([null, $nullAccount])->andReturn($asset);
        $opposingMapper->shouldReceive('map')->once()->withArgs([null, '-45.67', $nullAccount])->andReturn($expense);
        $currencyMapper->shouldReceive('map')->once()->withArgs([null, ['name' => null, 'code' => null, 'symbol' => null]])->andReturn($usd);
        $currencyMapper->shouldReceive('map')->once()->withArgs([null, ['code' => null]])->andReturn(null);


        $converter = new ImportableConverter;
        $converter->setImportJob($job);
        $result = $converter->convert($importables);

        // verify content of $result
        $this->assertEquals('withdrawal', $result[0]['transactions'][0]['type']);
        $this->assertEquals('2018-09-17 00:00:00', $result[0]['transactions'][0]['date']);
        $this->assertEquals($importable->tags, $result[0]['transactions'][0]['tags']);
        $this->assertEquals($usd->id, $result[0]['transactions'][0]['currency_id']);
    }

    /**
     * Two asset accounts mean its a transfer.
     *
     * @covers \FireflyIII\Support\Import\Routine\File\ImportableConverter
     */
    public function testBasicDefaultCurrency(): void
    {
        $nullAccount        = ['name' => null, 'iban' => null, 'number' => null, 'bic' => null];
        $importable         = new ImportTransaction;
        $importable->amount = '45.67';
        $importables        = [$importable];

        $job                = $this->user()->importJobs()->first();
        $job->configuration = [
            'date-format' => 'Ymd',
        ];
        $job->save();

        // mock used classes:
        $repository     = $this->mock(ImportJobRepositoryInterface::class);
        $assetMapper    = $this->mock(AssetAccountMapper::class);
        $opposingMapper = $this->mock(OpposingAccountMapper::class);
        $currencyMapper = $this->mock(CurrencyMapper::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->atLeast()->once()->andReturn('1');

        // get default currency
        $euro = TransactionCurrency::whereCode('EUR')->first();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro)->once();

        // set user and config:
        $repository->shouldReceive('setUser')->once();
        $assetMapper->shouldReceive('setUser')->once();
        $opposingMapper->shouldReceive('setUser')->once();
        $currencyMapper->shouldReceive('setUser')->once();
        $assetMapper->shouldReceive('setDefaultAccount')->withArgs([0])->once();

        // respond to mapping call:
        $asset = $this->user()->accounts()->where('account_type_id', 3)->first();
        $other = $this->user()->accounts()->where('account_type_id', 3)->where('id', '!=', $asset->id)->first();

        $assetMapper->shouldReceive('map')->atLeast()->once()->withArgs([null, $nullAccount])->andReturn($asset);
        $opposingMapper->shouldReceive('map')->atLeast()->once()->withArgs([null, '45.67', $nullAccount])->andReturn($other);

        $currencyMapper->shouldReceive('map')->atLeast()->once()->withArgs([null, ['name' => null, 'code' => null, 'symbol' => null]])->andReturn(null);
        $currencyMapper->shouldReceive('map')->atLeast()->once()->withArgs([null, ['code' => null]])->andReturn(null);
        $currencyMapper->shouldReceive('map')->atLeast()->once()->withArgs([$euro->id, []])->andReturn($euro);


        $converter = new ImportableConverter;
        $converter->setImportJob($job);
        $result = $converter->convert($importables);

        // verify content of $result
        $today = new Carbon();
        $this->assertEquals('transfer', $result[0]['transactions'][0]['type']);
        $this->assertEquals($today->format('Y-m-d H:i:s'), $result[0]['transactions'][0]['date']);
        $this->assertEquals([], $result[0]['transactions'][0]['tags']);
        $this->assertEquals($euro->id, $result[0]['transactions'][0]['currency_id']);
    }

    /**
     * Positive amount, so transaction is a deposit.
     *
     * @covers \FireflyIII\Support\Import\Routine\File\ImportableConverter
     */
    public function testBasicDeposit(): void
    {
        $nullAccount                   = ['name' => null, 'iban' => null, 'number' => null, 'bic' => null];
        $importable                    = new ImportTransaction;
        $importable->amount            = '45.67';
        $importable->date              = '20180917';
        $importable->meta['date-book'] = '20180102';
        $importables                   = [$importable];

        $job                = $this->user()->importJobs()->first();
        $job->configuration = [
            'date-format' => 'Ymd',
        ];
        $job->save();

        // mock used classes:
        $repository     = $this->mock(ImportJobRepositoryInterface::class);
        $assetMapper    = $this->mock(AssetAccountMapper::class);
        $opposingMapper = $this->mock(OpposingAccountMapper::class);
        $currencyMapper = $this->mock(CurrencyMapper::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser')->once();

        // get default currency
        $euro = TransactionCurrency::whereCode('EUR')->first();
        $usd  = TransactionCurrency::whereCode('USD')->first();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro)->once();

        // set user and config:
        $repository->shouldReceive('setUser')->once();
        $assetMapper->shouldReceive('setUser')->once();
        $opposingMapper->shouldReceive('setUser')->once();
        $currencyMapper->shouldReceive('setUser')->once();
        $assetMapper->shouldReceive('setDefaultAccount')->withArgs([0])->once();

        // respond to mapping call:
        $asset   = $this->user()->accounts()->where('account_type_id', 3)->first();
        $revenue = $this->user()->accounts()->where('account_type_id', 5)->first();

        $assetMapper->shouldReceive('map')->atLeast()->once()->withArgs([null, $nullAccount])->andReturn($asset);
        $opposingMapper->shouldReceive('map')->atLeast()->once()->withArgs([null, '45.67', $nullAccount])->andReturn($revenue);
        $currencyMapper->shouldReceive('map')->once()->withArgs([null, ['name' => null, 'code' => null, 'symbol' => null]])->andReturn($usd);
        $currencyMapper->shouldReceive('map')->once()->withArgs([null, ['code' => null]])->andReturn(null);


        $converter = new ImportableConverter;
        $converter->setImportJob($job);
        $result = $converter->convert($importables);

        // verify content of $result
        $this->assertEquals('deposit', $result[0]['transactions'][0]['type']);
        $this->assertEquals('2018-09-17 00:00:00', $result[0]['transactions'][0]['date']);
        $this->assertEquals([], $result[0]['transactions'][0]['tags']);
        $this->assertEquals($usd->id, $result[0]['transactions'][0]['currency_id']);
        $this->assertEquals($revenue->id, $result[0]['transactions'][0]['source_id']);
        $this->assertEquals($asset->id, $result[0]['transactions'][0]['destination_id']);
        $this->assertEquals('2018-01-02 00:00:00', $result[0]['transactions'][0]['book_date']);

    }

    /**
     * Source and destination are the same. Should result in error message.
     *
     * @covers \FireflyIII\Support\Import\Routine\File\ImportableConverter
     */
    public function testBasicSameAssets(): void
    {
        $nullAccount        = ['name' => null, 'iban' => null, 'number' => null, 'bic' => null];
        $importable         = new ImportTransaction;
        $importable->amount = '-45.67';
        $importable->date   = '20180917';
        $importables        = [$importable];

        $job                = $this->user()->importJobs()->first();
        $job->configuration = [
            'date-format' => 'Ymd',
        ];
        $job->save();

        // mock used classes:
        $repository     = $this->mock(ImportJobRepositoryInterface::class);
        $assetMapper    = $this->mock(AssetAccountMapper::class);
        $opposingMapper = $this->mock(OpposingAccountMapper::class);
        $currencyMapper = $this->mock(CurrencyMapper::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser')->once();

        // get default currency
        $euro = TransactionCurrency::whereCode('EUR')->first();
        $usd  = TransactionCurrency::whereCode('USD')->first();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro)->once();

        // set user and config:
        $repository->shouldReceive('setUser')->once();
        $assetMapper->shouldReceive('setUser')->once();
        $opposingMapper->shouldReceive('setUser')->once();
        $currencyMapper->shouldReceive('setUser')->once();
        $assetMapper->shouldReceive('setDefaultAccount')->withArgs([0])->once();

        // respond to mapping call:
        $asset = $this->user()->accounts()->where('account_type_id', 3)->first();

        $assetMapper->shouldReceive('map')->atLeast()->once()->withArgs([null, $nullAccount])->andReturn($asset);
        $opposingMapper->shouldReceive('map')->atLeast()->once()->withArgs([null, '-45.67', $nullAccount])->andReturn($asset);
        $currencyMapper->shouldReceive('map')->once()->withArgs([null, ['name' => null, 'code' => null, 'symbol' => null]])->andReturn($usd);
        $currencyMapper->shouldReceive('map')->once()->withArgs([null, ['code' => null]])->andReturn(null);
        $repository->shouldReceive('addErrorMessage')->withArgs(
            [Mockery::any(),
             'Row #1: Source ("' . $asset->name . '", #' . $asset->id . ') and destination ("' . $asset->name . '", #' . $asset->id . ') are the same account.']
        )->once();

        $converter = new ImportableConverter;
        $converter->setImportJob($job);
        $result = $converter->convert($importables);
        $this->assertEquals([], $result);
    }

    /**
     * Two asset accounts mean its a transfer. This has a positive amount.
     *
     * @covers \FireflyIII\Support\Import\Routine\File\ImportableConverter
     */
    public function testBasicTransfer(): void
    {
        $nullAccount          = ['name' => null, 'iban' => null, 'number' => null, 'bic' => null];
        $importable           = new ImportTransaction;
        $importable->amount   = '45.67';
        $importable->date     = '20180917';
        $importable->billId   = 2; // will NOT be ignored despite it's not valid.
        $importable->billName = 'Some Bill'; // will always be included even when bill ID is not valid.
        $importables          = [$importable];

        $job                = $this->user()->importJobs()->first();
        $job->configuration = [
            'date-format' => 'Ymd',
        ];
        $job->save();

        // mock used classes:
        $repository     = $this->mock(ImportJobRepositoryInterface::class);
        $assetMapper    = $this->mock(AssetAccountMapper::class);
        $opposingMapper = $this->mock(OpposingAccountMapper::class);
        $currencyMapper = $this->mock(CurrencyMapper::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser')->once();

        // get default currency
        $euro = TransactionCurrency::whereCode('EUR')->first();
        $usd  = TransactionCurrency::whereCode('USD')->first();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro)->once();

        // set user and config:
        $repository->shouldReceive('setUser')->once();
        $assetMapper->shouldReceive('setUser')->once();
        $opposingMapper->shouldReceive('setUser')->once();
        $currencyMapper->shouldReceive('setUser')->once();
        $assetMapper->shouldReceive('setDefaultAccount')->withArgs([0])->once();

        // respond to mapping call:
        $asset = $this->user()->accounts()->where('account_type_id', 3)->first();
        $other = $this->user()->accounts()->where('account_type_id', 3)->where('id', '!=', $asset->id)->first();

        $assetMapper->shouldReceive('map')->atLeast()->once()->withArgs([null, $nullAccount])->andReturn($asset);
        $opposingMapper->shouldReceive('map')->atLeast()->once()->withArgs([null, '45.67', $nullAccount])->andReturn($other);

        $currencyMapper->shouldReceive('map')->once()->withArgs([null, ['name' => null, 'code' => null, 'symbol' => null]])->andReturn($usd);
        $currencyMapper->shouldReceive('map')->once()->withArgs([null, ['code' => null]])->andReturn(null);


        $converter = new ImportableConverter;
        $converter->setImportJob($job);
        $result = $converter->convert($importables);

        // verify content of $result
        $this->assertEquals('transfer', $result[0]['transactions'][0]['type']);
        $this->assertEquals('2018-09-17 00:00:00', $result[0]['transactions'][0]['date']);
        $this->assertEquals([], $result[0]['transactions'][0]['tags']);
        $this->assertEquals(2, $result[0]['transactions'][0]['bill_id']); // will NOT be ignored.
        $this->assertEquals($importable->billName, $result[0]['transactions'][0]['bill_name']);
        $this->assertEquals($usd->id, $result[0]['transactions'][0]['currency_id']);

        // since amount is positive, $asset recieves the money
        $this->assertEquals($other->id, $result[0]['transactions'][0]['source_id']);
        $this->assertEquals($asset->id, $result[0]['transactions'][0]['destination_id']);
    }

    /**
     * Transfer with negative amount flows the other direction. See source_id and destination_id
     *
     * @covers \FireflyIII\Support\Import\Routine\File\ImportableConverter
     */
    public function testBasicTransferNegative(): void
    {
        $nullAccount          = ['name' => null, 'iban' => null, 'number' => null, 'bic' => null];
        $importable           = new ImportTransaction;
        $importable->amount   = '-45.67';
        $importable->date     = '20180917';
        $importable->billId   = 3; // is added to array of valid values, see below.
        $importable->billName = 'Some bill'; // will be added even when ID is valid.
        $importables          = [$importable];

        $validMappings = [
            'bill-id' => [3],
        ];

        $job                = $this->user()->importJobs()->first();
        $job->configuration = [
            'date-format' => 'Ymd',
        ];
        $job->save();

        // mock used classes:
        $repository     = $this->mock(ImportJobRepositoryInterface::class);
        $assetMapper    = $this->mock(AssetAccountMapper::class);
        $opposingMapper = $this->mock(OpposingAccountMapper::class);
        $currencyMapper = $this->mock(CurrencyMapper::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser')->once();

        // get default currency
        $euro = TransactionCurrency::whereCode('EUR')->first();
        $usd  = TransactionCurrency::whereCode('USD')->first();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro)->once();

        // set user and config:
        $repository->shouldReceive('setUser')->once();
        $assetMapper->shouldReceive('setUser')->once();
        $opposingMapper->shouldReceive('setUser')->once();
        $currencyMapper->shouldReceive('setUser')->once();
        $assetMapper->shouldReceive('setDefaultAccount')->withArgs([0])->once();

        // respond to mapping call:
        $asset = $this->user()->accounts()->where('account_type_id', 3)->first();
        $other = $this->user()->accounts()->where('account_type_id', 3)->where('id', '!=', $asset->id)->first();

        $assetMapper->shouldReceive('map')->once()->withArgs([null, $nullAccount])->andReturn($asset);
        $opposingMapper->shouldReceive('map')->once()->withArgs([null, '-45.67', $nullAccount])->andReturn($other);
        $currencyMapper->shouldReceive('map')->once()->withArgs([null, ['name' => null, 'code' => null, 'symbol' => null]])->andReturn($usd);
        $currencyMapper->shouldReceive('map')->once()->withArgs([null, ['code' => null]])->andReturn(null);


        $converter = new ImportableConverter;
        $converter->setImportJob($job);
        $converter->setMappedValues($validMappings);
        $result = $converter->convert($importables);

        // verify content of $result
        $this->assertEquals('transfer', $result[0]['transactions'][0]['type']);
        $this->assertEquals('2018-09-17 00:00:00', $result[0]['transactions'][0]['date']);
        $this->assertEquals([], $result[0]['transactions'][0]['tags']);
        $this->assertEquals(3, $result[0]['transactions'][0]['bill_id']);
        $this->assertEquals($importable->billName, $result[0]['transactions'][0]['bill_name']);
        $this->assertEquals($usd->id, $result[0]['transactions'][0]['currency_id']);

        // since amount is negative, $asset sends the money
        $this->assertEquals($asset->id, $result[0]['transactions'][0]['source_id']);
        $this->assertEquals($other->id, $result[0]['transactions'][0]['destination_id']);
    }

    /**
     * When source and dest are weird account types, will give error.
     *
     * @covers \FireflyIII\Support\Import\Routine\File\ImportableConverter
     */
    public function testBasicWeirdAccounts(): void
    {
        $nullAccount        = ['name' => null, 'iban' => null, 'number' => null, 'bic' => null];
        $importable         = new ImportTransaction;
        $importable->amount = '-45.67';
        $importable->date   = '20180917';
        $importables        = [$importable];

        $job                = $this->user()->importJobs()->first();
        $job->configuration = [
            'date-format' => 'Ymd',
        ];
        $job->save();

        // mock used classes:
        $repository     = $this->mock(ImportJobRepositoryInterface::class);
        $assetMapper    = $this->mock(AssetAccountMapper::class);
        $opposingMapper = $this->mock(OpposingAccountMapper::class);
        $currencyMapper = $this->mock(CurrencyMapper::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser')->once();

        // get default currency
        $euro = TransactionCurrency::whereCode('EUR')->first();
        $usd  = TransactionCurrency::whereCode('USD')->first();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro)->once();

        // set user and config:
        $repository->shouldReceive('setUser')->once();
        $assetMapper->shouldReceive('setUser')->once();
        $opposingMapper->shouldReceive('setUser')->once();
        $currencyMapper->shouldReceive('setUser')->once();
        $assetMapper->shouldReceive('setDefaultAccount')->withArgs([0])->once();

        // respond to mapping call:
        $asset = $this->user()->accounts()->where('account_type_id', 6)->first();
        $other = $this->user()->accounts()->where('account_type_id', 2)->where('id', '!=', $asset)->first();

        $assetMapper->shouldReceive('map')->once()->withArgs([null, $nullAccount])->andReturn($asset);
        $opposingMapper->shouldReceive('map')->once()->withArgs([null, '-45.67', $nullAccount])->andReturn($other);
        $currencyMapper->shouldReceive('map')->once()->withArgs([null, ['name' => null, 'code' => null, 'symbol' => null]])->andReturn($usd);
        $currencyMapper->shouldReceive('map')->once()->withArgs([null, ['code' => null]])->andReturn(null);
        $repository->shouldReceive('addErrorMessage')->withArgs(
            [Mockery::any(), 'Row #1: Cannot determine transaction type. Source account is a Initial balance account, destination is a Cash account']
        )->once();

        $converter = new ImportableConverter;
        $converter->setImportJob($job);
        $result = $converter->convert($importables);
        $this->assertEquals([], $result);
    }

    /**
     * Submit no amount information.
     *
     * @covers \FireflyIII\Support\Import\Routine\File\ImportableConverter
     */
    public function testEmpty(): void
    {

        $job                = $this->user()->importJobs()->first();
        $job->configuration = [];
        $job->save();

        // mock used classes:
        $repository     = $this->mock(ImportJobRepositoryInterface::class);
        $assetMapper    = $this->mock(AssetAccountMapper::class);
        $opposingMapper = $this->mock(OpposingAccountMapper::class);
        $currencyMapper = $this->mock(CurrencyMapper::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser')->once();

        $euro = TransactionCurrency::whereCode('EUR')->first();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro)->once();
        $repository->shouldReceive('setUser')->once();
        $assetMapper->shouldReceive('setUser')->once();
        $opposingMapper->shouldReceive('setUser')->once();
        $currencyMapper->shouldReceive('setUser')->once();
        $assetMapper->shouldReceive('setDefaultAccount')->withArgs([0])->once();

        $converter = new ImportableConverter;
        $converter->setImportJob($job);
    }

    /**
     * Basic input until it stops crashing.
     *
     * @covers \FireflyIII\Support\Import\Routine\File\ImportableConverter
     */
    public function testNoAmount(): void
    {
        $importable         = new ImportTransaction;
        $importables        = [$importable];
        $job                = $this->user()->importJobs()->first();
        $job->configuration = [];
        $job->save();

        // mock used classes:
        $repository     = $this->mock(ImportJobRepositoryInterface::class);
        $assetMapper    = $this->mock(AssetAccountMapper::class);
        $opposingMapper = $this->mock(OpposingAccountMapper::class);
        $currencyMapper = $this->mock(CurrencyMapper::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser')->once();

        $euro = TransactionCurrency::whereCode('EUR')->first();
        Amount::shouldReceive('getDefaultCurrencyByUser')->andReturn($euro)->once();
        $repository->shouldReceive('setUser')->once();
        $assetMapper->shouldReceive('setUser')->once();
        $opposingMapper->shouldReceive('setUser')->once();
        $currencyMapper->shouldReceive('setUser')->once();
        $assetMapper->shouldReceive('setDefaultAccount')->withArgs([0])->once();
        $repository->shouldReceive('addErrorMessage')->withArgs([Mockery::any(), 'Row #1: No transaction amount information.'])->once();

        $converter = new ImportableConverter;
        $converter->setImportJob($job);
        $result = $converter->convert($importables);
        $this->assertEquals([], $result);
    }

}
