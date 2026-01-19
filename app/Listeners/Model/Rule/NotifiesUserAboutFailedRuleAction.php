<?php

declare(strict_types=1);
/*
 * NotifiesUserAboutFailedRuleAction.php
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

namespace FireflyIII\Listeners\Model\Rule;

use FireflyIII\Events\Model\Rule\RuleActionFailedOnArray;
use FireflyIII\Events\Model\Rule\RuleActionFailedOnObject;
use FireflyIII\Notifications\User\RuleActionFailed;
use FireflyIII\Support\Facades\Preferences;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotifiesUserAboutFailedRuleAction implements ShouldQueue
{
    public function handle(RuleActionFailedOnArray|RuleActionFailedOnObject $event): void
    {
        $ruleAction  = $event->ruleAction;
        $rule        = $ruleAction->rule;

        /** @var bool $preference */
        $preference  = Preferences::getForUser($rule->user, 'notification_rule_action_failures', true)->data;
        if (false === $preference) {
            return;
        }
        Log::debug('Now in ruleActionFailedOnArray');
        $journal     = $event->journal;
        $error       = $event->error;
        $user        = $ruleAction->rule->user;

        $groupId     = is_array($journal) ? $journal['transaction_group_id'] : $journal->transaction_group_id;
        $groupTitle  = is_array($journal) ? ($journal['description'] ?? '') : ($journal->description ?? '');


        $mainMessage = trans('rules.main_message', ['rule' => $rule->title, 'action' => $ruleAction->action_type, 'group' => $groupId, 'error' => $error]);
        $groupLink   = route('transactions.show', [$groupId]);
        $ruleTitle   = $rule->title;
        $ruleLink    = route('rules.edit', [$rule->id]);
        $params      = [$mainMessage, $groupTitle, $groupLink, $ruleTitle, $ruleLink];

        try {
            Notification::send($user, new RuleActionFailed($params));
        } catch (ClientException $e) {
            Log::error(sprintf('[a] Error sending notification that the rule action failed: %s', $e->getMessage()));
        }
    }
}
