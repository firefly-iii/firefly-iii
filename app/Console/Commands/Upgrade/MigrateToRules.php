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

    /** @var UserRepositoryInterface */
    private $userRepository;
    /** @var RuleGroupRepositoryInterface */
    private $ruleGroupRepository;
    /** @var BillRepositoryInterface */
    private $billRepository;
    /** @var RuleRepositoryInterface */
    private $ruleRepository;
    private $count;

    /**
     * Execute the console command.
     *
     * @return int
     * @throws FireflyException
     */
    public function handle(): int
    {
        $this->stupidLaravel();
        $start = microtime(true);

        // @codeCoverageIgnoreStart
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->warn('This command has already been executed.');

            return 0;
        }
        // @codeCoverageIgnoreEnd

        $users = $this->userRepository->all();
        /** @var User $user */
        foreach ($users as $user) {
            $this->migrateUser($user);
        }

        if (0 === $this->count) {
            $this->line('All bills are OK.');
        }
        if (0 !== $this->count) {
            $this->line(sprintf('Verified and fixed %d bill(s).', $this->count));
        }

        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Verified and fixed bills in %s seconds.', $end));
        $this->markAsExecuted();

        return 0;
    }

    /**
     * Laravel will execute ALL __construct() methods for ALL commands whenever a SINGLE command is
     * executed. This leads to noticeable slow-downs and class calls. To prevent this, this method should
     * be called from the handle method instead of using the constructor to initialize the command.
     *
     * @codeCoverageIgnore
     */
    private function stupidLaravel(): void
    {
        $this->count               = 0;
        $this->userRepository      = app(UserRepositoryInterface::class);
        $this->ruleGroupRepository = app(RuleGroupRepositoryInterface::class);
        $this->billRepository      = app(BillRepositoryInterface::class);
        $this->ruleRepository      = app(RuleRepositoryInterface::class);
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
     * Migrate bills to new rule structure for a specific user.
     *
     * @param User $user
     * @throws FireflyException
     */
    private function migrateUser(User $user): void
    {
        $this->ruleGroupRepository->setUser($user);
        $this->billRepository->setUser($user);
        $this->ruleRepository->setUser($user);

        /** @var Preference $lang */
        $lang       = app('preferences')->getForUser($user, 'language', 'en_US');
        $groupTitle = (string)trans('firefly.rulegroup_for_bills_title', [], $lang->data);
        $ruleGroup  = $this->ruleGroupRepository->findByTitle($groupTitle);
        //$currency   = $this->getCurrency($user);

        if (null === $ruleGroup) {
            $ruleGroup = $this->ruleGroupRepository->store(
                [
                    'title'       => (string)trans('firefly.rulegroup_for_bills_title', [], $lang->data),
                    'description' => (string)trans('firefly.rulegroup_for_bills_description', [], $lang->data),
                    'active'      => true,
                ]
            );
        }
        $bills = $this->billRepository->getBills();

        /** @var Bill $bill */
        foreach ($bills as $bill) {
            $this->migrateBill($ruleGroup, $bill, $lang);
        }

    }

    /**
     * @param RuleGroup $ruleGroup
     * @param Bill $bill
     * @throws FireflyException
     */
    private function migrateBill(RuleGroup $ruleGroup, Bill $bill, Preference $language): void
    {
        if ('MIGRATED_TO_RULES' === $bill->match) {
            return;
        }

        // get match thing:
        $match   = implode(' ', explode(',', $bill->match));
        $newRule = [
            'rule_group_id'   => $ruleGroup->id,
            'active'          => true,
            'strict'          => false,
            'stop_processing' => false, // field is no longer used.
            'title'           => (string)trans('firefly.rule_for_bill_title', ['name' => $bill->name], $language->data),
            'description'     => (string)trans('firefly.rule_for_bill_description', ['name' => $bill->name], $language->data),
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
        $newBillData = [
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
        $this->count++;
    }
}
