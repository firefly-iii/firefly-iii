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
        if (isset($this->data['accounts']) && is_array($this->data['accounts'])) {
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
        }
        if (isset($this->data['account-meta']) && is_array($this->data['account-meta'])) {
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
    }

    /**
     *
     */
    private function createAttachments()
    {
        if (isset($this->data['attachments']) && is_array($this->data['attachments'])) {
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
    }

    /**
     *
     */
    private function createBills()
    {
        if (isset($this->data['bills']) && is_array($this->data['bills'])) {
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
    }

    /**
     *
     */
    private function createBudgets()
    {
        if (isset($this->data['budgets']) && is_array($this->data['budgets'])) {
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
        }

        if (isset($this->data['budget-limits']) && is_array($this->data['budget-limits'])) {
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
        }
        if (isset($this->data['monthly-limits']) && is_array($this->data['monthly-limits'])) {
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
    }

    /**
     *
     */
    private function createCategories()
    {
        if (isset($this->data['categories']) && is_array($this->data['categories'])) {
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
            if (isset($this->data['monthly-withdrawals']) && is_array($this->data['monthly-withdrawals'])) {
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
            }

            // run all monthly deposits:
            if (isset($this->data['monthly-deposits']) && is_array($this->data['monthly-deposits'])) {
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
            }

            // run all monthly transfers:
            if (isset($this->data['monthly-transfers']) && is_array($this->data['monthly-transfers'])) {
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
            }

            $current->addMonth();
        }

        DB::table('transactions')->insert($transactions);
    }

    /**
     *
     */
    private function createPiggyBanks()
    {
        if (isset($this->data['piggy-banks']) && is_array($this->data['piggy-banks'])) {
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
    }

    /**
     *
     */
    private function createRules()
    {
        if (isset($this->data['rule-groups']) && is_array($this->data['rule-groups'])) {
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
        }
        if (isset($this->data['rules']) && is_array($this->data['rules'])) {
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
        }

        if (isset($this->data['rule-triggers']) && is_array($this->data['rule-triggers'])) {
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
        }
        if (isset($this->data['rule-actions']) && is_array($this->data['rule-actions'])) {
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
    }

    /**
     *
     */
    private function createTags()
    {
        if (isset($this->data['tags']) && is_array($this->data['tags'])) {
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
    }

    /**
     *
     */
    private function createUsers()
    {
        if (isset($this->data['users']) && is_array($this->data['users'])) {
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
        }
        if (isset($this->data['roles']) && is_array($this->data['roles'])) {
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
    }

}
