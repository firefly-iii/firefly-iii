<?php

declare(strict_types=1);

/*
 * TriggersCreditRecalculation.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Listeners\Model\Account;

use FireflyIII\Events\Model\Account\CreatedNewAccount;
use FireflyIII\Events\Model\Account\UpdatedExistingAccount;
use FireflyIII\Handlers\ExchangeRate\ConversionParameters;
use FireflyIII\Handlers\ExchangeRate\ConvertsAmountToPrimaryAmount;
use FireflyIII\Models\Account;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Services\Internal\Support\CreditRecalculateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class UpdatesAccountInformation implements ShouldQueue
{
    public function handle(CreatedNewAccount|UpdatedExistingAccount $event): void
    {
        $this->recalculateCredit($event->account);
        $this->updateVirtualBalance($event->account);
        if ($event instanceof UpdatedExistingAccount) {
            $this->renameRules($event->account, $event->oldData);
        }
    }

    private function correctRuleActions(Account $account, array $oldData, Rule $rule): void
    {
        $fields = ['set_source_account', 'set_destination_account'];

        Log::debug(sprintf('Check if rule #%d actions reference account #%d "%s"', $rule->id, $account->id, $account->name));
        $fixed  = 0;

        /** @var RuleAction $action */
        foreach ($rule->ruleActions as $action) {
            // fix name:
            if ($oldData['name'] === $action->action_value && in_array($action->action_type, $fields, true)) {
                Log::debug(sprintf('Rule action #%d "%s" has old account name, replace with new.', $action->id, $action->action_type));
                $action->action_value = $account->name;
                $action->save();
                ++$fixed;
            }
        }
        Log::debug(sprintf('Corrected %d action(s) for rule #%d', $fixed, $rule->id));
    }

    private function correctRuleTriggers(Account $account, array $oldData, Rule $rule): void
    {
        $nameFields   = [
            'source_account_is',
            'source_account_contains',
            'source_account_ends',
            'source_account_starts',
            'destination_account_is',
            'destination_account_contains',
            'destination_account_ends',
            'destination_account_starts',
            'account_is',
            'account_contains',
            'account_ends',
            'account_starts',
        ];
        $numberFields = [
            'source_account_nr_is',
            'source_account_nr_contains',
            'source_account_nr_ends',
            'source_account_nr_starts',
            'destination_account_nr_is',
            'destination_account_nr_contains',
            'destination_account_nr_starts',
            'account_nr_is',
            'account_nr_contains',
            'account_nr_ends',
            'account_nr_starts',
        ];

        Log::debug(sprintf('Check if rule #%d triggers reference account #%d "%s"', $rule->id, $account->id, $account->name));
        $fixed        = 0;

        /** @var RuleTrigger $trigger */
        foreach ($rule->ruleTriggers as $trigger) {
            // fix name:
            if ($oldData['name'] === $trigger->trigger_value && in_array($trigger->trigger_type, $nameFields, true)) {
                Log::debug(sprintf('Rule trigger #%d "%s" has old account name, replace with new.', $trigger->id, $trigger->trigger_type));
                $trigger->trigger_value = $account->name;
                $trigger->save();
                ++$fixed;
            }
            // fix IBAN:
            if ($oldData['iban'] === $trigger->trigger_value && in_array($trigger->trigger_type, $numberFields, true)) {
                Log::debug(sprintf('Rule trigger #%d "%s" has old account IBAN, replace with new.', $trigger->id, $trigger->trigger_type));
                $trigger->trigger_value = $account->iban;
                $trigger->save();
                ++$fixed;
            }
            // fix account number: // account_number
            if ($oldData['account_number'] === $trigger->trigger_value && in_array($trigger->trigger_type, $numberFields, true)) {
                Log::debug(sprintf('Rule trigger #%d "%s" has old account account_number, replace with new.', $trigger->id, $trigger->trigger_type));
                $trigger->trigger_value = $account->iban;
                $trigger->save();
                ++$fixed;
            }
        }
        Log::debug(sprintf('Corrected %d trigger(s) for rule #%d', $fixed, $rule->id));
    }

    private function recalculateCredit(Account $account): void
    {
        Log::debug('Will call CreditRecalculateService because a new account was created or updated.');

        /** @var CreditRecalculateService $object */
        $object = app(CreditRecalculateService::class);
        $object->setAccount($account);
        $object->recalculate();
    }

    private function renameRules(Account $account, array $oldData): void
    {
        Log::debug('Updated account, will now correct rules.');
        $repository = app(RuleRepositoryInterface::class);
        $repository->setUser($account->user);
        $rules      = $repository->getAll();

        /** @var Rule $rule */
        foreach ($rules as $rule) {
            $this->correctRuleTriggers($account, $oldData, $rule);
            $this->correctRuleActions($account, $oldData, $rule);
        }
    }

    private function updateVirtualBalance(Account $account): void
    {
        Log::debug('Will updateVirtualBalance');
        $repository = app(AccountRepositoryInterface::class);
        $currency   = $repository->getAccountCurrency($account);

        if (null !== $currency) {
            // only when the account has a currency, because that is the only way for the
            // account to have a virtual balance.
            $params                     = new ConversionParameters();
            $params->user               = $account->user;
            $params->model              = $account;
            $params->originalCurrency   = $currency;
            $params->amountField        = 'virtual_balance';
            $params->primaryAmountField = 'native_virtual_balance';
            ConvertsAmountToPrimaryAmount::convert($params);
            Log::debug('Account primary currency virtual balance is updated.');
        }
    }
}
