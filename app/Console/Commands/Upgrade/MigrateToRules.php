<?php

/**
 * MigrateToRules.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\Upgrade;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Preference;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Console\Command;

/**
 * Class MigrateToRules
 */
class MigrateToRules extends Command
{
    use ShowsFriendlyMessages;

    public const string CONFIG_NAME = '480_bills_to_rules';

    protected $description          = 'Migrate bills to rules.';

    protected $signature            = 'firefly-iii:bills-to-rules {--F|force : Force the execution of this command.}';
    private BillRepositoryInterface      $billRepository;
    private int                          $count;
    private RuleGroupRepositoryInterface $ruleGroupRepository;
    private RuleRepositoryInterface      $ruleRepository;
    private UserRepositoryInterface      $userRepository;

    /**
     * Execute the console command.
     *
     * @throws FireflyException
     */
    public function handle(): int
    {
        $this->stupidLaravel();

        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->friendlyInfo('This command has already been executed.');

            return 0;
        }

        $users = $this->userRepository->all();

        /** @var User $user */
        foreach ($users as $user) {
            $this->migrateUser($user);
        }

        if (0 === $this->count) {
            $this->friendlyPositive('All bills are OK.');
        }
        if (0 !== $this->count) {
            $this->friendlyInfo(sprintf('Verified and fixed %d bill(s).', $this->count));
        }

        $this->markAsExecuted();

        return 0;
    }

    /**
     * Laravel will execute ALL __construct() methods for ALL commands whenever a SINGLE command is
     * executed. This leads to noticeable slow-downs and class calls. To prevent this, this method should
     * be called from the handle method instead of using the constructor to initialize the command.
     */
    private function stupidLaravel(): void
    {
        $this->count               = 0;
        $this->userRepository      = app(UserRepositoryInterface::class);
        $this->ruleGroupRepository = app(RuleGroupRepositoryInterface::class);
        $this->billRepository      = app(BillRepositoryInterface::class);
        $this->ruleRepository      = app(RuleRepositoryInterface::class);
    }

    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool) $configVar->data;
        }

        return false;
    }

    /**
     * Migrate bills to new rule structure for a specific user.
     *
     * @throws FireflyException
     */
    private function migrateUser(User $user): void
    {
        $this->ruleGroupRepository->setUser($user);
        $this->billRepository->setUser($user);
        $this->ruleRepository->setUser($user);

        /** @var Preference $lang */
        $lang       = app('preferences')->getForUser($user, 'language', 'en_US');
        $language   = null !== $lang->data && !is_array($lang->data) ? (string) $lang->data : 'en_US';
        $groupTitle = (string) trans('firefly.rulegroup_for_bills_title', [], $language);
        $ruleGroup  = $this->ruleGroupRepository->findByTitle($groupTitle);

        if (null === $ruleGroup) {
            $ruleGroup = $this->ruleGroupRepository->store(
                [
                    'title'       => (string) trans('firefly.rulegroup_for_bills_title', [], $language),
                    'description' => (string) trans('firefly.rulegroup_for_bills_description', [], $language),
                    'active'      => true,
                ]
            );
        }
        $bills      = $this->billRepository->getBills();

        /** @var Bill $bill */
        foreach ($bills as $bill) {
            $this->migrateBill($ruleGroup, $bill, $lang);
        }
    }

    private function migrateBill(RuleGroup $ruleGroup, Bill $bill, Preference $language): void
    {
        if ('MIGRATED_TO_RULES' === $bill->match) {
            return;
        }
        $languageString = null !== $language->data && !is_array($language->data) ? (string) $language->data : 'en_US';

        // get match thing:
        $match          = implode(' ', explode(',', $bill->match));
        $newRule        = [
            'rule_group_id'   => $ruleGroup->id,
            'active'          => true,
            'strict'          => false,
            'stop_processing' => false, // field is no longer used.
            'title'           => (string) trans('firefly.rule_for_bill_title', ['name' => $bill->name], $languageString),
            'description'     => (string) trans('firefly.rule_for_bill_description', ['name' => $bill->name], $languageString),
            'trigger'         => 'store-journal',
            'triggers'        => [
                [
                    'type'  => 'description_contains',
                    'value' => $match,
                ],
            ],
            'actions'         => [
                [
                    'type'  => 'link_to_bill',
                    'value' => $bill->name,
                ],
            ],
        ];

        // two triggers or one, depends on bill content:
        if ($bill->amount_max === $bill->amount_min) {
            $newRule['triggers'][] = [
                'type'  => 'amount_exactly',
                'value' => $bill->amount_min,
            ];
        }
        if ($bill->amount_max !== $bill->amount_min) {
            $newRule['triggers'][] = [
                'type'  => 'amount_less',
                'value' => $bill->amount_max,
            ];
            $newRule['triggers'][] = [
                'type'  => 'amount_more',
                'value' => $bill->amount_min,
            ];
        }

        $this->ruleRepository->store($newRule);

        // update bill:
        $newBillData    = [
            'currency_id' => $bill->transaction_currency_id,
            'name'        => $bill->name,
            'match'       => 'MIGRATED_TO_RULES',
            'amount_min'  => $bill->amount_min,
            'amount_max'  => $bill->amount_max,
            'date'        => $bill->date,
            'repeat_freq' => $bill->repeat_freq,
            'skip'        => $bill->skip,
            'active'      => $bill->active,
        ];
        $this->billRepository->update($bill, $newBillData);
        ++$this->count;
    }

    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}
