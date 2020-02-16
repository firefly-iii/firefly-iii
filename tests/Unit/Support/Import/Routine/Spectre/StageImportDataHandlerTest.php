<?php
/**
 * StageImportDataHandlerTest.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace Tests\Unit\Support\Import\Routine\Spectre;


use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Services\Spectre\Object\Account as SpectreAccount;
use FireflyIII\Services\Spectre\Object\Transaction as SpectreTransaction;
use FireflyIII\Services\Spectre\Request\ListTransactionsRequest;
use FireflyIII\Support\Import\Routine\File\OpposingAccountMapper;
use FireflyIII\Support\Import\Routine\Spectre\StageImportDataHandler;
use Log;
use Mockery;
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
     * @covers \FireflyIII\Support\Import\Routine\Spectre\StageImportDataHandler
     */
    public function testRunBasic(): void
    {
        // needs to be a full spectre account this time.
        $spectreAccount = new SpectreAccount(
            [
                'id'            => 1234,
                'login_id'      => 5678,
                'currency_code' => 'EUR',
                'balance'       => 1000,
                'name'          => 'Fake Spectre Account',
                'nature'        => 'account',
                'created_at'    => '2018-01-01 12:12:12',
                'updated_at'    => '2018-01-01 12:12:12',
                'extra'         => [],
            ]
        );

        $today = new Carbon;
        // create fake transactions:
        $op1          = 'Some opposing account #' . $this->randomInt();
        $op2          = 'Some opposing revenue account #' . $this->randomInt();
        $transactions = [
            new SpectreTransaction(
                [
                    'id'            => 1,
                    'mode'          => 'mode',
                    'status'        => 'active',
                    'made_on'       => $today->toW3cString(),
                    'amount'        => -123.45,
                    'currency_code' => 'EUR',
                    'description'   => 'Fake description #' . $this->randomInt(),
                    'category'      => 'some-category',
                    'duplicated'    => false,
                    'extra'         => [
                        'payee' => $op1,
                    ],
                    'account_id'    => 1234,
                    'created_at'    => $today->toW3cString(),
                    'updated_at'    => $today->toW3cString(),
                ]
            ),
            new SpectreTransaction(
                [
                    'id'            => 2,
                    'mode'          => 'mode',
                    'status'        => 'active',
                    'made_on'       => $today->toW3cString(),
                    'amount'        => 563.21,
                    'currency_code' => 'EUR',
                    'description'   => 'Fake second description #' . $this->randomInt(),
                    'category'      => 'some-other-category',
                    'duplicated'    => false,
                    'extra'         => [
                        'payee' => $op2,
                    ],
                    'account_id'    => 1234,
                    'created_at'    => $today->toW3cString(),
                    'updated_at'    => $today->toW3cString(),
                ]
            ),
        ];


        $account            = $this->user()->accounts()->where('account_type_id', 3)->first();
        $expense            = $this->user()->accounts()->where('account_type_id', 4)->first();
        $revenue            = $this->user()->accounts()->where('account_type_id', 5)->first();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'sid_a__' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [
            'accounts'        => [$spectreAccount->toArray()],
            'account_mapping' => [
                1234 => 322,
            ],
        ];
        $job->save();

        // mock repositories
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $importRepos  = $this->mock(ImportJobRepositoryInterface::class);
        $lrRequest    = $this->mock(ListTransactionsRequest::class);
        $mapper       = $this->mock(OpposingAccountMapper::class);

        $expected = [
            0 => [
                'transactions' => [
                    0 => [
                        // transaction here
                        'date'                  => $today->format('Y-m-d'),
                        'tags'                  => ['mode', 'active'],
                        'type'                  => 'withdrawal',
                        'user'                  => $job->user_id,
                        'notes'                 => "Imported from \"Fake Spectre Account\"  \npayee: " . $op1 . "  \n",
                        'external_id'           => '1',
                        // journal data:
                        'description'           => $transactions[0]->getDescription(),
                        'piggy_bank_id'         => null,
                        'piggy_bank_name'       => null,
                        'bill_id'               => null,
                        'bill_name'             => null,
                        'original-source'       => sprintf('spectre-v%s', config('firefly.version')),
                        'currency_id'           => null,
                        'currency_code'         => 'EUR',
                        'amount'                => '-123.45',
                        'budget_id'             => null,
                        'budget_name'           => null,
                        'category_id'           => null,
                        'category_name'         => 'some-category',
                        'source_id'             => $account->id,
                        'source_name'           => null,
                        'destination_id'        => $expense->id,
                        'destination_name'      => null,
                        'foreign_currency_id'   => null,
                        'foreign_currency_code' => null,
                        'foreign_amount'        => null,
                        'reconciled'            => false,
                        'identifier'            => 0,
                    ],
                ],
            ],
            1 => [
                'transactions' => [
                    0 => [
                        // transaction here
                        'date'                  => $today->format('Y-m-d'),
                        'tags'                  => ['mode', 'active'],
                        'type'                  => 'deposit',
                        'user'                  => $job->user_id,
                        'notes'                 => "Imported from \"Fake Spectre Account\"  \npayee: " . $op2 . "  \n",
                        'external_id'           => '2',
                        // journal data:
                        'description'           => $transactions[1]->getDescription(),
                        'piggy_bank_id'         => null,
                        'piggy_bank_name'       => null,
                        'bill_id'               => null,
                        'bill_name'             => null,
                        'original-source'       => sprintf('spectre-v%s', config('firefly.version')),
                        'currency_id'           => null,
                        'currency_code'         => 'EUR',
                        'amount'                => '563.21',
                        'budget_id'             => null,
                        'budget_name'           => null,
                        'category_id'           => null,
                        'category_name'         => 'some-other-category',
                        'source_id'             => $revenue->id,
                        'source_name'           => null,
                        'destination_id'        => $account->id,
                        'destination_name'      => null,
                        'foreign_currency_id'   => null,
                        'foreign_currency_code' => null,
                        'foreign_amount'        => null,
                        'reconciled'            => false,
                        'identifier'            => 0,
                    ],
                ],
            ],
        ];

        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('findNull')->once()->withArgs([322])->andReturn($account);
        $importRepos->shouldReceive('setUser')->once();
        $importRepos->shouldReceive('setTransactions')->once()->withArgs([Mockery::any(), $expected]);
        $lrRequest->shouldReceive('setUser')->once();
        $lrRequest->shouldReceive('setAccount')->once()->withArgs([Mockery::any()]);
        $lrRequest->shouldReceive('call')->once();
        $lrRequest->shouldReceive('getTransactions')->once()->andReturn($transactions);
        $mapper->shouldReceive('setUser')->once();
        // mapper should be called twice:
        $mapper->shouldReceive('map')->withArgs(
            [null, -123.45, ['name' => $op1, 'iban' => null, 'number' => null, 'bic' => null]]
        )->once()->andReturn($expense);
        $mapper->shouldReceive('map')->withArgs(
            [null, 563.21, ['name' => $op2, 'iban' => null, 'number' => null, 'bic' => null]]
        )->once()->andReturn($revenue);


        $handler = new StageImportDataHandler;
        $handler->setImportJob($job);
        try {
            $handler->run();
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }
    }

    /**
     * Also has empty local account.
     *
     * @covers \FireflyIII\Support\Import\Routine\Spectre\StageImportDataHandler
     */
    public function testRunForeignCurrency(): void
    {
        // needs to be a full spectre account this time.
        $spectreAccount = new SpectreAccount(
            [
                'id'            => 1234,
                'login_id'      => 5678,
                'currency_code' => 'EUR',
                'balance'       => 1000,
                'name'          => 'Fake Spectre Account',
                'nature'        => 'account',
                'created_at'    => '2018-01-01 12:12:12',
                'updated_at'    => '2018-01-01 12:12:12',
                'extra'         => [],
            ]
        );

        $today = new Carbon;
        // create fake transactions:
        $op1                = 'Some opposing account #' . $this->randomInt();
        $op2                = 'Some opposing revenue account #' . $this->randomInt();
        $transactions       = [
            new SpectreTransaction(
                [
                    'id'            => 1,
                    'mode'          => 'mode',
                    'status'        => 'active',
                    'made_on'       => $today->toW3cString(),
                    'amount'        => -123.45,
                    'currency_code' => 'EUR',
                    'description'   => 'Fake description #' . $this->randomInt(),
                    'category'      => 'some-category',
                    'duplicated'    => true,
                    'extra'         => [
                        'payee'           => $op1,
                        'original_amount' => -200.01,
                    ],
                    'account_id'    => 1234,
                    'created_at'    => $today->toW3cString(),
                    'updated_at'    => $today->toW3cString(),
                ]
            ),
            new SpectreTransaction(
                [
                    'id'            => 2,
                    'mode'          => 'mode',
                    'status'        => 'active',
                    'made_on'       => $today->toW3cString(),
                    'amount'        => 563.21,
                    'currency_code' => 'EUR',
                    'description'   => 'Fake second description #' . $this->randomInt(),
                    'category'      => 'some-other-category',
                    'duplicated'    => false,
                    'extra'         => [
                        'payee'                  => $op2,
                        'customer_category_name' => 'cat-name',
                        'original_currency_code' => 'USD',
                    ],
                    'account_id'    => 1234,
                    'created_at'    => $today->toW3cString(),
                    'updated_at'    => $today->toW3cString(),
                ]
            ),
        ];


        $account            = $this->user()->accounts()->where('account_type_id', 3)->first();
        $expense            = $this->user()->accounts()->where('account_type_id', 4)->first();
        $revenue            = $this->user()->accounts()->where('account_type_id', 5)->first();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'sid_a_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [
            'accounts'        => [$spectreAccount->toArray()],
            'account_mapping' => [
                1234 => 322,
                5678 => 0,
            ],
        ];
        $job->save();

        // mock repositories
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $importRepos  = $this->mock(ImportJobRepositoryInterface::class);
        $lrRequest    = $this->mock(ListTransactionsRequest::class);
        $mapper       = $this->mock(OpposingAccountMapper::class);

        $expected = [
            0 => [
                'transactions' => [
                    0 => [
                        // data here.
                        'date'                  => $today->format('Y-m-d'),
                        'type'                  => 'withdrawal',
                        'tags'                  => ['mode', 'active', 'possibly-duplicated'],
                        'user'                  => $job->user_id,
                        'notes'                 => "Imported from \"Fake Spectre Account\"  \npayee: " . $op1 . "  \n",
                        'external_id'           => '1',
                        // journal data:
                        'description'           => $transactions[0]->getDescription(),
                        'piggy_bank_id'         => null,
                        'piggy_bank_name'       => null,
                        'bill_id'               => null,
                        'bill_name'             => null,
                        'original-source'       => sprintf('spectre-v%s', config('firefly.version')),
                        'currency_id'           => null,
                        'currency_code'         => 'EUR',
                        'amount'                => '-123.45',
                        'budget_id'             => null,
                        'budget_name'           => null,
                        'category_id'           => null,
                        'category_name'         => 'some-category',
                        'source_id'             => $account->id,
                        'source_name'           => null,
                        'destination_id'        => $expense->id,
                        'destination_name'      => null,
                        'foreign_currency_id'   => null,
                        'foreign_currency_code' => null,
                        'foreign_amount'        => '-200.01',
                        'reconciled'            => false,
                        'identifier'            => 0,
                    ],
                ],
            ],
            1 => [
                'transactions' => [
                    0 => [
                        // data here.
                        'date'                  => $today->format('Y-m-d'),
                        'type'                  => 'deposit',
                        'tags'                  => ['mode', 'active', 'cat-name'],
                        'user'                  => $job->user_id,
                        'notes'                 => "Imported from \"Fake Spectre Account\"  \npayee: " . $op2 . "  \n",
                        'external_id'           => '2',
                        // journal data:
                        'description'           => $transactions[1]->getDescription(),
                        'piggy_bank_id'         => null,
                        'piggy_bank_name'       => null,
                        'bill_id'               => null,
                        'bill_name'             => null,
                        'original-source'       => sprintf('spectre-v%s', config('firefly.version')),
                        'currency_id'           => null,
                        'currency_code'         => 'EUR',
                        'amount'                => '563.21',
                        'budget_id'             => null,
                        'budget_name'           => null,
                        'category_id'           => null,
                        'category_name'         => 'some-other-category',
                        'source_id'             => $revenue->id,
                        'source_name'           => null,
                        'destination_id'        => $account->id,
                        'destination_name'      => null,
                        'foreign_currency_id'   => null,
                        'foreign_currency_code' => 'USD',
                        'foreign_amount'        => null,
                        'reconciled'            => false,
                        'identifier'            => 0,
                    ],
                ],
            ],
        ];

        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('findNull')->once()->withArgs([322])->andReturn($account);
        $importRepos->shouldReceive('setUser')->once();
        $importRepos->shouldReceive('setTransactions')->once()
                    ->withArgs([Mockery::any(), $expected]);
        $lrRequest->shouldReceive('setUser')->once();
        $lrRequest->shouldReceive('setAccount')->once()->withArgs([Mockery::any()]);
        $lrRequest->shouldReceive('call')->once();
        $lrRequest->shouldReceive('getTransactions')->once()->andReturn($transactions);
        $mapper->shouldReceive('setUser')->once();
        // mapper should be called twice:
        $mapper->shouldReceive('map')->withArgs(
            [null, -123.45, ['name' => $op1, 'iban' => null, 'number' => null, 'bic' => null]]
        )->once()->andReturn($expense);
        $mapper->shouldReceive('map')->withArgs(
            [null, 563.21, ['name' => $op2, 'iban' => null, 'number' => null, 'bic' => null]]
        )->once()->andReturn($revenue);


        $handler = new StageImportDataHandler;
        $handler->setImportJob($job);
        try {
            $handler->run();
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Support\Import\Routine\Spectre\StageImportDataHandler
     */
    public function testRunWithDuplication(): void
    {
        // needs to be a full spectre account this time.
        $spectreAccount = new SpectreAccount(
            [
                'id'            => 1234,
                'login_id'      => 5678,
                'currency_code' => 'EUR',
                'balance'       => 1000,
                'name'          => 'Fake Spectre Account',
                'nature'        => 'account',
                'created_at'    => '2018-01-01 12:12:12',
                'updated_at'    => '2018-01-01 12:12:12',
                'extra'         => [],
            ]
        );

        $today = new Carbon;
        // create fake transactions:
        $op1          = 'Some opposing account #' . $this->randomInt();
        $op2          = 'Some opposing revenue account #' . $this->randomInt();
        $transactions = [
            new SpectreTransaction(
                [
                    'id'            => 1,
                    'mode'          => 'mode',
                    'status'        => 'active',
                    'made_on'       => $today->toW3cString(),
                    'amount'        => -123.45,
                    'currency_code' => 'EUR',
                    'description'   => 'Fake description #' . $this->randomInt(),
                    'category'      => 'some-category',
                    'duplicated'    => true,
                    'extra'         => [
                        'payee' => $op1,
                    ],
                    'account_id'    => 1234,
                    'created_at'    => $today->toW3cString(),
                    'updated_at'    => $today->toW3cString(),
                ]
            ),
            new SpectreTransaction(
                [
                    'id'            => 2,
                    'mode'          => 'mode',
                    'status'        => 'active',
                    'made_on'       => $today->toW3cString(),
                    'amount'        => 563.21,
                    'currency_code' => 'EUR',
                    'description'   => 'Fake second description #' . $this->randomInt(),
                    'category'      => 'some-other-category',
                    'duplicated'    => false,
                    'extra'         => [
                        'payee'                  => $op2,
                        'customer_category_name' => 'cat-name',
                    ],
                    'account_id'    => 1234,
                    'created_at'    => $today->toW3cString(),
                    'updated_at'    => $today->toW3cString(),
                ]
            ),
        ];


        $account            = $this->user()->accounts()->where('account_type_id', 3)->first();
        $expense            = $this->user()->accounts()->where('account_type_id', 4)->first();
        $revenue            = $this->user()->accounts()->where('account_type_id', 5)->first();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'sid_a_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [
            'accounts'        => [$spectreAccount->toArray()],
            'account_mapping' => [
                1234 => 322,
            ],
        ];
        $job->save();

        // mock repositories
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $importRepos  = $this->mock(ImportJobRepositoryInterface::class);
        $lrRequest    = $this->mock(ListTransactionsRequest::class);
        $mapper       = $this->mock(OpposingAccountMapper::class);

        $expected = [
            0 => [
                'transactions' => [
                    0 => [
                        // data here
                        'date'            => $today->format('Y-m-d'),
                        'type'            => 'withdrawal',
                        'tags'            => ['mode', 'active', 'possibly-duplicated'],
                        'user'            => $job->user_id,
                        'notes'           => "Imported from \"Fake Spectre Account\"  \npayee: " . $op1 . "  \n",
                        'external_id'     => '1',
                        // journal data:
                        'description'     => $transactions[0]->getDescription(),
                        'piggy_bank_id'   => null,
                        'piggy_bank_name' => null,
                        'bill_id'         => null,
                        'bill_name'       => null,
                        'original-source' => sprintf('spectre-v%s', config('firefly.version')),
                        'currency_id'           => null,
                        'currency_code'         => 'EUR',
                        'amount'                => '-123.45',
                        'budget_id'             => null,
                        'budget_name'           => null,
                        'category_id'           => null,
                        'category_name'         => 'some-category',
                        'source_id'             => $account->id,
                        'source_name'           => null,
                        'destination_id'        => $expense->id,
                        'destination_name'      => null,
                        'foreign_currency_id'   => null,
                        'foreign_currency_code' => null,
                        'foreign_amount'        => null,
                        'reconciled'            => false,
                        'identifier'            => 0,
                    ],
                ],
            ],
            1 => [
                'transactions' => [
                    0 => [
                        // data here
                        'date'            => $today->format('Y-m-d'),
                        'type'            => 'deposit',
                        'tags'            => ['mode', 'active', 'cat-name'],
                        'user'            => $job->user_id,
                        'notes'           => "Imported from \"Fake Spectre Account\"  \npayee: " . $op2 . "  \n",
                        'external_id'     => '2',
                        // journal data:
                        'description'     => $transactions[1]->getDescription(),
                        'piggy_bank_id'   => null,
                        'piggy_bank_name' => null,
                        'bill_id'         => null,
                        'bill_name'       => null,
                        'original-source' => sprintf('spectre-v%s', config('firefly.version')),
                        'currency_id'           => null,
                        'currency_code'         => 'EUR',
                        'amount'                => '563.21',
                        'budget_id'             => null,
                        'budget_name'           => null,
                        'category_id'           => null,
                        'category_name'         => 'some-other-category',
                        'source_id'             => $revenue->id,
                        'source_name'           => null,
                        'destination_id'        => $account->id,
                        'destination_name'      => null,
                        'foreign_currency_id'   => null,
                        'foreign_currency_code' => null,
                        'foreign_amount'        => null,
                        'reconciled'            => false,
                        'identifier'            => 0,
                    ],
                ],
            ],
        ];

        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('findNull')->once()->withArgs([322])->andReturn($account);
        $importRepos->shouldReceive('setUser')->once();
        $importRepos->shouldReceive('setTransactions')->once()
                    ->withArgs([Mockery::any(), $expected]);
        $lrRequest->shouldReceive('setUser')->once();
        $lrRequest->shouldReceive('setAccount')->once()->withArgs([Mockery::any()]);
        $lrRequest->shouldReceive('call')->once();
        $lrRequest->shouldReceive('getTransactions')->once()->andReturn($transactions);
        $mapper->shouldReceive('setUser')->once();
        // mapper should be called twice:
        $mapper->shouldReceive('map')->withArgs(
            [null, -123.45, ['name' => $op1, 'iban' => null, 'number' => null, 'bic' => null]]
        )->once()->andReturn($expense);
        $mapper->shouldReceive('map')->withArgs(
            [null, 563.21, ['name' => $op2, 'iban' => null, 'number' => null, 'bic' => null]]
        )->once()->andReturn($revenue);


        $handler = new StageImportDataHandler;
        $handler->setImportJob($job);
        try {
            $handler->run();
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }
    }

}
