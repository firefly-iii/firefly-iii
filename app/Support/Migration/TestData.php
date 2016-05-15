<?php
declare(strict_types = 1);
namespace FireflyIII\Support\Migration;

use Carbon\Carbon;
use Crypt;
use DB;
use Navigation;
use Storage;

/**
 * TestData.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

/**
 * Class TestData
 *
 * @package FireflyIII\Support\Migration
 */
class TestData
{
    /** @var array */
    private $data = [];
    /** @var Carbon */
    private $end;
    /** @var Carbon */
    private $start;

    /**
     * TestData constructor.
     *
     * @param array $data
     */
    private function __construct(array $data)
    {
        $this->data = $data;
        $start      = new Carbon;
        $start->startOfYear();
        $start->subYears(2);
        $end = new Carbon;

        $this->start = $start;
        $this->end   = $end;
    }

    /**
     * @param array $data
     */
    public static function run(array $data)
    {
        $seeder = new TestData($data);
        $seeder->go();
    }

    /**
     *
     */
    private function createAccounts()
    {
        $insert = [];
        foreach ($this->data['accounts'] as $account) {
            $insert[] = [
                'created_at'      => DB::raw('NOW()'),
                'updated_at'      => DB::raw('NOW()'),
                'user_id'         => $account['user_id'],
                'account_type_id' => $account['account_type_id'],
                'name'            => Crypt::encrypt($account['name']),
                'active'          => 1,
                'encrypted'       => 1,
                'virtual_balance' => 0,
                'iban'            => isset($account['iban']) ? Crypt::encrypt($account['iban']) : null,
            ];
        }
        DB::table('accounts')->insert($insert);
        $insert = [];
        foreach ($this->data['account-meta'] as $meta) {
            $insert[] = [
                'created_at' => DB::raw('NOW()'),
                'updated_at' => DB::raw('NOW()'),
                'account_id' => $meta['account_id'],
                'name'       => $meta['name'],
                'data'       => $meta['data'],
            ];
        }
        DB::table('account_meta')->insert($insert);
    }

    /**
     *
     */
    private function createAttachments()
    {
        $insert = [];
        $disk   = Storage::disk('upload');
        foreach ($this->data['attachments'] as $attachment) {
            $data         = Crypt::encrypt($attachment['content']);
            $attachmentId = DB::table('attachments')->insertGetId(
                [
                    'created_at'      => DB::raw('NOW()'),
                    'updated_at'      => DB::raw('NOW()'),
                    'attachable_id'   => $attachment['attachable_id'],
                    'attachable_type' => $attachment['attachable_type'],
                    'user_id'         => $attachment['user_id'],
                    'md5'             => md5($attachment['content']),
                    'filename'        => $attachment['filename'],
                    'title'           => $attachment['title'],
                    'description'     => $attachment['description'],
                    'notes'           => $attachment['notes'],
                    'mime'            => $attachment['mime'],
                    'size'            => strlen($attachment['content']),
                    'uploaded'        => 1,
                ]
            );

            $disk->put('at-' . $attachmentId . '.data', $data);
        }
    }

    /**
     *
     */
    private function createBills()
    {
        $insert = [];
        foreach ($this->data['bills'] as $bill) {
            $insert[] = [
                'created_at'      => DB::raw('NOW()'),
                'updated_at'      => DB::raw('NOW()'),
                'user_id'         => $bill['user_id'],
                'name'            => Crypt::encrypt($bill['name']),
                'match'           => Crypt::encrypt($bill['match']),
                'amount_min'      => $bill['amount_min'],
                'amount_max'      => $bill['amount_max'],
                'date'            => $bill['date'],
                'active'          => $bill['active'],
                'automatch'       => $bill['automatch'],
                'repeat_freq'     => $bill['repeat_freq'],
                'skip'            => $bill['skip'],
                'name_encrypted'  => 1,
                'match_encrypted' => 1,
            ];
        }
        DB::table('bills')->insert($insert);
    }

    /**
     *
     */
    private function createBudgets()
    {
        $insert = [];
        foreach ($this->data['budgets'] as $budget) {
            $insert[] = [
                'created_at' => DB::raw('NOW()'),
                'updated_at' => DB::raw('NOW()'),
                'user_id'    => $budget['user_id'],
                'name'       => Crypt::encrypt($budget['name']),
                'encrypted'  => 1,
            ];
        }
        DB::table('budgets')->insert($insert);

        foreach ($this->data['budget-limits'] as $limit) {
            $amount  = rand($limit['amount_min'], $limit['amount_max']);
            $limitId = DB::table('budget_limits')->insertGetId(
                [
                    'created_at'  => DB::raw('NOW()'),
                    'updated_at'  => DB::raw('NOW()'),
                    'budget_id'   => $limit['budget_id'],
                    'startdate'   => $limit['startdate'],
                    'amount'      => $amount,
                    'repeats'     => 0,
                    'repeat_freq' => $limit['repeat_freq'],
                ]
            );

            DB::table('limit_repetitions')->insert(
                [
                    'created_at'      => DB::raw('NOW()'),
                    'updated_at'      => DB::raw('NOW()'),
                    'budget_limit_id' => $limitId,
                    'startdate'       => $limit['startdate'],
                    'enddate'         => Navigation::endOfPeriod(new Carbon($limit['startdate']), $limit['repeat_freq'])->format('Y-m-d'),
                    'amount'          => $amount,
                ]
            );
        }
        $current = clone $this->start;
        while ($current <= $this->end) {
            foreach ($this->data['monthly-limits'] as $limit) {
                $amount  = rand($limit['amount_min'], $limit['amount_max']);
                $limitId = DB::table('budget_limits')->insertGetId(
                    [
                        'created_at'  => DB::raw('NOW()'),
                        'updated_at'  => DB::raw('NOW()'),
                        'budget_id'   => $limit['budget_id'],
                        'startdate'   => $current->format('Y-m-d'),
                        'amount'      => $amount,
                        'repeats'     => 0,
                        'repeat_freq' => 'monthly',
                    ]
                );

                DB::table('limit_repetitions')->insert(
                    [
                        'created_at'      => DB::raw('NOW()'),
                        'updated_at'      => DB::raw('NOW()'),
                        'budget_limit_id' => $limitId,
                        'startdate'       => $current->format('Y-m-d'),
                        'enddate'         => Navigation::endOfPeriod($current, 'monthly')->format('Y-m-d'),
                        'amount'          => $amount,
                    ]
                );
            }

            $current->addMonth();
        }

    }

    /**
     *
     */
    private function createCategories()
    {
        $insert = [];
        foreach ($this->data['categories'] as $category) {
            $insert[] = [
                'created_at' => DB::raw('NOW()'),
                'updated_at' => DB::raw('NOW()'),
                'user_id'    => $category['user_id'],
                'name'       => Crypt::encrypt($category['name']),
                'encrypted'  => 1,
            ];
        }
        DB::table('categories')->insert($insert);
    }

    /**
     *
     */
    private function createJournals()
    {
        $current      = clone $this->start;
        $transactions = [];
        while ($current <= $this->end) {
            $date  = $current->format('Y-m-');
            $month = $current->format('F');

            // run all monthly withdrawals:
            foreach ($this->data['monthly-withdrawals'] as $withdrawal) {
                $description    = str_replace(':month', $month, $withdrawal['description']);
                $journalId      = DB::table('transaction_journals')->insertGetId(
                    [
                        'created_at'              => DB::raw('NOW()'),
                        'updated_at'              => DB::raw('NOW()'),
                        'user_id'                 => $withdrawal['user_id'],
                        'transaction_type_id'     => 1,
                        'bill_id'                 => $withdrawal['bill_id'] ?? null,
                        'transaction_currency_id' => 1,
                        'description'             => Crypt::encrypt($description),
                        'completed'               => 1,
                        'date'                    => $date . $withdrawal['day-of-month'],
                        'interest_date'           => $withdrawal['interest_date'] ?? null,
                        'book_date'               => $withdrawal['book_date'] ?? null,
                        'process_date'            => $withdrawal['process_date'] ?? null,
                        'encrypted'               => 1,
                        'order'                   => 0,
                        'tag_count'               => 0,
                    ]
                );
                $amount         = (rand($withdrawal['min_amount'] * 100, $withdrawal['max_amount'] * 100)) / 100;
                $transactions[] = [
                    'created_at'             => DB::raw('NOW()'),
                    'updated_at'             => DB::raw('NOW()'),
                    'transaction_journal_id' => $journalId,
                    'account_id'             => $withdrawal['source_id'],
                    'amount'                 => $amount * -1,
                ];
                $transactions[] = [
                    'created_at'             => DB::raw('NOW()'),
                    'updated_at'             => DB::raw('NOW()'),
                    'transaction_journal_id' => $journalId,
                    'account_id'             => $withdrawal['destination_id'],
                    'amount'                 => $amount,
                ];

                // link to budget if set.
                if (isset($withdrawal['budget_id'])) {
                    DB::table('budget_transaction_journal')->insert(
                        [
                            'budget_id'              => $withdrawal['budget_id'],
                            'transaction_journal_id' => $journalId,

                        ]
                    );
                }
                // link to category if set.
                if (isset($withdrawal['category_id'])) {
                    DB::table('category_transaction_journal')->insert(
                        [
                            'category_id'            => $withdrawal['category_id'],
                            'transaction_journal_id' => $journalId,

                        ]
                    );
                }
            }

            // run all monthly deposits:
            foreach ($this->data['monthly-deposits'] as $deposit) {
                $description    = str_replace(':month', $month, $deposit['description']);
                $journalId      = DB::table('transaction_journals')->insertGetId(
                    [
                        'created_at'              => DB::raw('NOW()'),
                        'updated_at'              => DB::raw('NOW()'),
                        'user_id'                 => $deposit['user_id'],
                        'transaction_type_id'     => 2,
                        'bill_id'                 => $deposit['bill_id'] ?? null,
                        'transaction_currency_id' => 1,
                        'description'             => Crypt::encrypt($description),
                        'completed'               => 1,
                        'date'                    => $date . $deposit['day-of-month'],
                        'interest_date'           => $deposit['interest_date'] ?? null,
                        'book_date'               => $deposit['book_date'] ?? null,
                        'process_date'            => $deposit['process_date'] ?? null,
                        'encrypted'               => 1,
                        'order'                   => 0,
                        'tag_count'               => 0,
                    ]
                );
                $amount         = (rand($deposit['min_amount'] * 100, $deposit['max_amount'] * 100)) / 100;
                $transactions[] = [
                    'created_at'             => DB::raw('NOW()'),
                    'updated_at'             => DB::raw('NOW()'),
                    'transaction_journal_id' => $journalId,
                    'account_id'             => $deposit['source_id'],
                    'amount'                 => $amount * -1,
                ];
                $transactions[] = [
                    'created_at'             => DB::raw('NOW()'),
                    'updated_at'             => DB::raw('NOW()'),
                    'transaction_journal_id' => $journalId,
                    'account_id'             => $deposit['destination_id'],
                    'amount'                 => $amount,
                ];

                // link to category if set.
                if (isset($deposit['category_id'])) {
                    DB::table('category_transaction_journal')->insert(
                        [
                            'category_id'            => $deposit['category_id'],
                            'transaction_journal_id' => $journalId,

                        ]
                    );
                }
            }
            // run all monthly transfers:
            foreach ($this->data['monthly-transfers'] as $transfer) {
                $description    = str_replace(':month', $month, $transfer['description']);
                $journalId      = DB::table('transaction_journals')->insertGetId(
                    [
                        'created_at'              => DB::raw('NOW()'),
                        'updated_at'              => DB::raw('NOW()'),
                        'user_id'                 => $transfer['user_id'],
                        'transaction_type_id'     => 3,
                        'bill_id'                 => $transfer['bill_id'] ?? null,
                        'transaction_currency_id' => 1,
                        'description'             => Crypt::encrypt($description),
                        'completed'               => 1,
                        'date'                    => $date . $transfer['day-of-month'],
                        'interest_date'           => $transfer['interest_date'] ?? null,
                        'book_date'               => $transfer['book_date'] ?? null,
                        'process_date'            => $transfer['process_date'] ?? null,
                        'encrypted'               => 1,
                        'order'                   => 0,
                        'tag_count'               => 0,
                    ]
                );
                $amount         = (rand($transfer['min_amount'] * 100, $transfer['max_amount'] * 100)) / 100;
                $transactions[] = [
                    'created_at'             => DB::raw('NOW()'),
                    'updated_at'             => DB::raw('NOW()'),
                    'transaction_journal_id' => $journalId,
                    'account_id'             => $transfer['source_id'],
                    'amount'                 => $amount * -1,
                ];
                $transactions[] = [
                    'created_at'             => DB::raw('NOW()'),
                    'updated_at'             => DB::raw('NOW()'),
                    'transaction_journal_id' => $journalId,
                    'account_id'             => $transfer['destination_id'],
                    'amount'                 => $amount,
                ];
                // link to category if set.
                if (isset($transfer['category_id'])) {
                    DB::table('category_transaction_journal')->insert(
                        [
                            'category_id'            => $transfer['category_id'],
                            'transaction_journal_id' => $journalId,

                        ]
                    );
                }
            }

            $current->addMonth();
        }

        DB::table('transactions')->insert($transactions);
    }

    /**
     *
     */
    private function createMultiDeposits()
    {
        foreach ($this->data['multi-deposits'] as $deposit) {
            $journalId = DB::table('transaction_journals')->insertGetId(
                [
                    'created_at'              => DB::raw('NOW()'),
                    'updated_at'              => DB::raw('NOW()'),
                    'user_id'                 => $deposit['user_id'],
                    'transaction_type_id'     => 2,
                    'transaction_currency_id' => 1,
                    'description'             => Crypt::encrypt($deposit['description']),
                    'completed'               => 1,
                    'date'                    => $deposit['date'],
                    'interest_date'           => $deposit['interest_date'] ?? null,
                    'book_date'               => $deposit['book_date'] ?? null,
                    'process_date'            => $deposit['process_date'] ?? null,
                    'encrypted'               => 1,
                    'order'                   => 0,
                    'tag_count'               => 0,
                ]
            );
            foreach ($deposit['source_ids'] as $index => $source) {
                $description = $deposit['description'] . ' (#' . ($index + 1) . ')';
                $amount      = $deposit['amounts'][$index];
                $first       = DB::table('transactions')->insertGetId(
                    [
                        'created_at'             => DB::raw('NOW()'),
                        'updated_at'             => DB::raw('NOW()'),
                        'account_id'             => $deposit['destination_id'],
                        'transaction_journal_id' => $journalId,
                        'description'            => $description,
                        'amount'                 => $amount,
                    ]
                );
                $second      = DB::table('transactions')->insertGetId(
                    [
                        'created_at'             => DB::raw('NOW()'),
                        'updated_at'             => DB::raw('NOW()'),
                        'account_id'             => $source,
                        'transaction_journal_id' => $journalId,
                        'description'            => $description,
                        'amount'                 => $amount * -1,
                    ]
                );
                // link first and second to budget and category, if present.

                if (isset($deposit['category_ids'][$index])) {
                    DB::table('category_transaction')->insert(
                        [
                            'category_id'    => $deposit['category_ids'][$index],
                            'transaction_id' => $first,
                        ]
                    );
                    DB::table('category_transaction')->insert(
                        [
                            'category_id'    => $deposit['category_ids'][$index],
                            'transaction_id' => $second,
                        ]
                    );
                }
            }
        }
    }

    /**
     *
     */
    private function createMultiWithdrawals()
    {
        foreach ($this->data['multi-withdrawals'] as $withdrawal) {
            $journalId = DB::table('transaction_journals')->insertGetId(
                [
                    'created_at'              => DB::raw('NOW()'),
                    'updated_at'              => DB::raw('NOW()'),
                    'user_id'                 => $withdrawal['user_id'],
                    'transaction_type_id'     => 1,
                    'transaction_currency_id' => 1,
                    'description'             => Crypt::encrypt($withdrawal['description']),
                    'completed'               => 1,
                    'date'                    => $withdrawal['date'],
                    'interest_date'           => $withdrawal['interest_date'] ?? null,
                    'book_date'               => $withdrawal['book_date'] ?? null,
                    'process_date'            => $withdrawal['process_date'] ?? null,
                    'encrypted'               => 1,
                    'order'                   => 0,
                    'tag_count'               => 0,
                ]
            );
            foreach ($withdrawal['destination_ids'] as $index => $destination) {
                $description = $withdrawal['description'] . ' (#' . ($index + 1) . ')';
                $amount      = $withdrawal['amounts'][$index];
                $first       = DB::table('transactions')->insertGetId(
                    [
                        'created_at'             => DB::raw('NOW()'),
                        'updated_at'             => DB::raw('NOW()'),
                        'account_id'             => $withdrawal['source_id'],
                        'transaction_journal_id' => $journalId,
                        'description'            => $description,
                        'amount'                 => $amount * -1,
                    ]
                );
                $second      = DB::table('transactions')->insertGetId(
                    [
                        'created_at'             => DB::raw('NOW()'),
                        'updated_at'             => DB::raw('NOW()'),
                        'account_id'             => $destination,
                        'transaction_journal_id' => $journalId,
                        'description'            => $description,
                        'amount'                 => $amount,
                    ]
                );
                // link first and second to budget and category, if present.
                if (isset($withdrawal['budget_ids'][$index])) {
                    DB::table('budget_transaction')->insert(
                        [
                            'budget_id'      => $withdrawal['budget_ids'][$index],
                            'transaction_id' => $first,
                        ]
                    );
                    DB::table('budget_transaction')->insert(
                        [
                            'budget_id'      => $withdrawal['budget_ids'][$index],
                            'transaction_id' => $second,
                        ]
                    );
                }

                if (isset($withdrawal['category_ids'][$index])) {
                    DB::table('category_transaction')->insert(
                        [
                            'category_id'    => $withdrawal['category_ids'][$index],
                            'transaction_id' => $first,
                        ]
                    );
                    DB::table('category_transaction')->insert(
                        [
                            'category_id'    => $withdrawal['category_ids'][$index],
                            'transaction_id' => $second,
                        ]
                    );
                }
            }
        }
    }

    /**
     *
     */
    private function createPiggyBanks()
    {
        foreach ($this->data['piggy-banks'] as $piggyBank) {
            $piggyId = DB::table('piggy_banks')->insertGetId(
                [
                    'created_at'    => DB::raw('NOW()'),
                    'updated_at'    => DB::raw('NOW()'),
                    'account_id'    => $piggyBank['account_id'],
                    'name'          => Crypt::encrypt($piggyBank['name']),
                    'targetamount'  => $piggyBank['targetamount'],
                    'startdate'     => $piggyBank['startdate'],
                    'reminder_skip' => 0,
                    'remind_me'     => 0,
                    'order'         => $piggyBank['order'],
                    'encrypted'     => 1,
                ]
            );
            if (isset($piggyBank['currentamount'])) {
                DB::table('piggy_bank_repetitions')->insert(
                    [
                        'created_at'    => DB::raw('NOW()'),
                        'updated_at'    => DB::raw('NOW()'),
                        'piggy_bank_id' => $piggyId,
                        'startdate'     => $piggyBank['startdate'],
                        'currentamount' => $piggyBank['currentamount'],
                    ]
                );
            }
        }
    }

    /**
     *
     */
    private function createRules()
    {
        $insert = [];
        foreach ($this->data['rule-groups'] as $group) {
            $insert[] = [
                'created_at'  => DB::raw('NOW()'),
                'updated_at'  => DB::raw('NOW()'),
                'user_id'     => $group['user_id'],
                'order'       => $group['order'],
                'title'       => $group['title'],
                'description' => $group['description'],
                'active'      => 1,
            ];
        }
        DB::table('rule_groups')->insert($insert);
        $insert = [];
        foreach ($this->data['rules'] as $rule) {
            $insert[] = [
                'created_at'      => DB::raw('NOW()'),
                'updated_at'      => DB::raw('NOW()'),
                'user_id'         => $rule['user_id'],
                'rule_group_id'   => $rule['rule_group_id'],
                'order'           => $rule['order'],
                'active'          => $rule['active'],
                'stop_processing' => $rule['stop_processing'],
                'title'           => $rule['title'],
                'description'     => $rule['description'],
            ];
        }
        DB::table('rules')->insert($insert);

        $insert = [];
        foreach ($this->data['rule-triggers'] as $trigger) {
            $insert[] = [
                'created_at'      => DB::raw('NOW()'),
                'updated_at'      => DB::raw('NOW()'),
                'rule_id'         => $trigger['rule_id'],
                'order'           => $trigger['order'],
                'active'          => $trigger['active'],
                'stop_processing' => $trigger['stop_processing'],
                'trigger_type'    => $trigger['trigger_type'],
                'trigger_value'   => $trigger['trigger_value'],
            ];
        }
        DB::table('rule_triggers')->insert($insert);

        $insert = [];
        foreach ($this->data['rule-actions'] as $action) {
            $insert[] = [
                'created_at'      => DB::raw('NOW()'),
                'updated_at'      => DB::raw('NOW()'),
                'rule_id'         => $action['rule_id'],
                'order'           => $action['order'],
                'active'          => $action['active'],
                'stop_processing' => $action['stop_processing'],
                'action_type'     => $action['action_type'],
                'action_value'    => $action['action_value'],
            ];
        }
        DB::table('rule_actions')->insert($insert);
    }

    /**
     *
     */
    private function createTags()
    {
        $insert = [];
        foreach ($this->data['tags'] as $tag) {
            $insert[]
                = [
                'created_at' => DB::raw('NOW()'),
                'updated_at' => DB::raw('NOW()'),
                'user_id'    => $tag['user_id'],
                'tag'        => Crypt::encrypt($tag['tag']),
                'tagMode'    => $tag['tagMode'],
                'date'       => $tag['date'] ?? null,
            ];

        }
        DB::table('tags')->insert($insert);
    }

    /**
     *
     */
    private function createUsers()
    {
        $insert = [];
        foreach ($this->data['users'] as $user) {
            $insert[]
                = [
                'created_at' => DB::raw('NOW()'),
                'updated_at' => DB::raw('NOW()'),
                'email'      => $user['email'],
                'password'   => bcrypt($user['password']),
            ];

        }
        DB::table('users')->insert($insert);
        $insert = [];
        foreach ($this->data['roles'] as $role) {
            $insert[]
                = [
                'user_id' => $role['user_id'],
                'role_id' => $role['role'],
            ];
        }
        DB::table('role_user')->insert($insert);
    }

    /**
     *
     */
    private function go()
    {
        $this->createUsers();
        $this->createAccounts();
        $this->createBills();
        $this->createBudgets();
        $this->createCategories();
        $this->createPiggyBanks();
        $this->createRules();
        $this->createTags();
        $this->createJournals();
        $this->createAttachments();
        $this->createMultiWithdrawals();
        $this->createMultiDeposits();
    }

}
