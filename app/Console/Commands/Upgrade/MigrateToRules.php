<?php
/**
 * MigrateToRules.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Console\Commands\Upgrade;


use Crypt;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Collection;
use Log;

/**
 * Class MigrateToRules
 */
class MigrateToRules extends Command
{
    public const CONFIG_NAME = '4780_bills_to_rules';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate bills to rules.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:bills-to-rules {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $start            = microtime(true);

        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->warn('This command has already been executed.');

            return 0;
        }

        foreach (User::get() as $user) {
            /** @var Preference $lang */
            $lang               = app('preferences')->getForUser($user, 'language', 'en_US');
            $groupName          = (string)trans('firefly.rulegroup_for_bills_title', [], $lang->data);
            $ruleGroup          = $user->ruleGroups()->where('title', $groupName)->first();
            $currencyPreference = app('preferences')->getForUser($user, 'currencyPreference', config('firefly.default_currency', 'EUR'));

            if (null === $currencyPreference) {
                $this->error('User has no currency preference. Impossible.');

                return 1;
            }
            $currencyCode = $this->tryDecrypt($currencyPreference->data);

            // try json decrypt just in case.
            if (\strlen($currencyCode) > 3) {
                $currencyCode = json_decode($currencyCode) ?? 'EUR';
            }

            $currency = TransactionCurrency::where('code', $currencyCode)->first();
            if (null === $currency) {
                $this->line('Fall back to default currency in migrateBillsToRules().');
                $currency = app('amount')->getDefaultCurrencyByUser($user);
            }

            if (null === $ruleGroup) {
                $array     = RuleGroup::get(['order'])->pluck('order')->toArray();
                $order     = \count($array) > 0 ? max($array) + 1 : 1;
                $ruleGroup = RuleGroup::create(
                    [
                        'user_id'     => $user->id,
                        'title'       => (string)trans('firefly.rulegroup_for_bills_title', [], $lang->data),
                        'description' => (string)trans('firefly.rulegroup_for_bills_description', [], $lang->data),
                        'order'       => $order,
                        'active'      => 1,
                    ]
                );
            }

            // loop bills.
            $order = 1;
            $count = 0;
            /** @var Collection $collection */
            $collection = $user->bills()->get();
            /** @var Bill $bill */
            foreach ($collection as $bill) {
                if ('MIGRATED_TO_RULES' !== $bill->match) {
                    $count++;
                    $rule = Rule::create(
                        [
                            'user_id'         => $user->id,
                            'rule_group_id'   => $ruleGroup->id,
                            'title'           => (string)trans('firefly.rule_for_bill_title', ['name' => $bill->name], $lang->data),
                            'description'     => (string)trans('firefly.rule_for_bill_description', ['name' => $bill->name], $lang->data),
                            'order'           => $order,
                            'active'          => $bill->active,
                            'stop_processing' => 1,
                        ]
                    );
                    // add default trigger
                    RuleTrigger::create(
                        [
                            'rule_id'         => $rule->id,
                            'trigger_type'    => 'user_action',
                            'trigger_value'   => 'store-journal',
                            'active'          => 1,
                            'stop_processing' => 0,
                            'order'           => 1,
                        ]
                    );
                    // add trigger for description
                    $match = implode(' ', explode(',', $bill->match));
                    RuleTrigger::create(
                        [
                            'rule_id'         => $rule->id,
                            'trigger_type'    => 'description_contains',
                            'trigger_value'   => $match,
                            'active'          => 1,
                            'stop_processing' => 0,
                            'order'           => 2,
                        ]
                    );
                    if ($bill->amount_max !== $bill->amount_min) {
                        // add triggers for amounts:
                        RuleTrigger::create(
                            [
                                'rule_id'         => $rule->id,
                                'trigger_type'    => 'amount_less',
                                'trigger_value'   => round($bill->amount_max, $currency->decimal_places),
                                'active'          => 1,
                                'stop_processing' => 0,
                                'order'           => 3,
                            ]
                        );
                        RuleTrigger::create(
                            [
                                'rule_id'         => $rule->id,
                                'trigger_type'    => 'amount_more',
                                'trigger_value'   => round((float)$bill->amount_min, $currency->decimal_places),
                                'active'          => 1,
                                'stop_processing' => 0,
                                'order'           => 4,
                            ]
                        );
                    }
                    if ($bill->amount_max === $bill->amount_min) {
                        RuleTrigger::create(
                            [
                                'rule_id'         => $rule->id,
                                'trigger_type'    => 'amount_exactly',
                                'trigger_value'   => round((float)$bill->amount_min, $currency->decimal_places),
                                'active'          => 1,
                                'stop_processing' => 0,
                                'order'           => 3,
                            ]
                        );
                    }

                    // create action
                    RuleAction::create(
                        [
                            'rule_id'         => $rule->id,
                            'action_type'     => 'link_to_bill',
                            'action_value'    => $bill->name,
                            'order'           => 1,
                            'active'          => 1,
                            'stop_processing' => 0,
                        ]
                    );

                    $order++;
                    $bill->match = 'MIGRATED_TO_RULES';
                    $bill->save();
                    $this->line(sprintf('Updated bill #%d ("%s") so it will use rules.', $bill->id, $bill->name));
                }

                // give bills a currency when they dont have one.
                if (null === $bill->transaction_currency_id) {
                    $this->line(sprintf('Gave bill #%d ("%s") a currency (%s).', $bill->id, $bill->name, $currency->name));
                    $bill->transactionCurrency()->associate($currency);
                    $bill->save();
                }
            }
            if ($count > 0) {
                $this->info(sprintf('Migrated %d bills for user %s', $count, $user->email));
            }
            if (0 === $count) {
                $this->info(sprintf('Bills are correct for user %s.', $user->email));
            }
        }
        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Verified and fixed bills in %s seconds.', $end));
        $this->markAsExecuted();

        return 0;
    }

    /**
     * @return bool
     */
    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool)$configVar->data;
        }

        return false; // @codeCoverageIgnore
    }

    /**
     *
     */
    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    private function tryDecrypt(string $value): string
    {
        try {
            $value = Crypt::decrypt($value);
        } catch (DecryptException $e) {
            Log::debug(sprintf('Could not decrypt. %s', $e->getMessage()));
        }

        return $value;
    }
}