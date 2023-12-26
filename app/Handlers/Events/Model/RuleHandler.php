<?php

/*
 * RuleHandler.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Handlers\Events\Model;

use FireflyIII\Events\Model\Rule\RuleActionFailedOnArray;
use FireflyIII\Events\Model\Rule\RuleActionFailedOnObject;
use FireflyIII\Notifications\User\RuleActionFailed;
use Illuminate\Support\Facades\Notification;

/**
 * Class RuleHandler
 */
class RuleHandler
{
    public function ruleActionFailedOnArray(RuleActionFailedOnArray $event): void
    {
        $ruleAction = $event->ruleAction;
        $rule       = $ruleAction->rule;

        /** @var bool $preference */
        $preference = app('preferences')->getForUser($rule->user, 'notification_rule_action_failures', true)->data;
        if (false === $preference) {
            return;
        }
        app('log')->debug('Now in ruleActionFailedOnArray');
        $journal = $event->journal;
        $error   = $event->error;
        $user    = $ruleAction->rule->user;

        $mainMessage = trans('rules.main_message', ['rule' => $rule->title, 'action' => $ruleAction->action_type, 'group' => $journal['transaction_group_id'], 'error' => $error]);
        $groupTitle  = $journal['description'] ?? '';
        $groupLink   = route('transactions.show', [$journal['transaction_group_id']]);
        $ruleTitle   = $rule->title;
        $ruleLink    = route('rules.edit', [$rule->id]);
        $params      = [$mainMessage, $groupTitle, $groupLink, $ruleTitle, $ruleLink];

        Notification::send($user, new RuleActionFailed($params));
    }

    public function ruleActionFailedOnObject(RuleActionFailedOnObject $event): void
    {
        $ruleAction = $event->ruleAction;
        $rule       = $ruleAction->rule;

        /** @var bool $preference */
        $preference = app('preferences')->getForUser($rule->user, 'notification_rule_action_failures', true)->data;
        if (false === $preference) {
            return;
        }
        app('log')->debug('Now in ruleActionFailedOnObject');
        $journal = $event->journal;
        $error   = $event->error;
        $user    = $ruleAction->rule->user;

        $mainMessage = trans('rules.main_message', ['rule' => $rule->title, 'action' => $ruleAction->action_type, 'group' => $journal->transaction_group_id, 'error' => $error]);
        $groupTitle  = $journal->description ?? '';
        $groupLink   = route('transactions.show', [$journal->transaction_group_id]);
        $ruleTitle   = $rule->title;
        $ruleLink    = route('rules.edit', [$rule->id]);
        $params      = [$mainMessage, $groupTitle, $groupLink, $ruleTitle, $ruleLink];

        Notification::send($user, new RuleActionFailed($params));
    }
}
