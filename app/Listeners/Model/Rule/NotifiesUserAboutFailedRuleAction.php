<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Model\Rule;

use FireflyIII\Events\Model\Rule\RuleActionFailedOnArray;
use FireflyIII\Events\Model\Rule\RuleActionFailedOnObject;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Notifications\User\RuleActionFailed;
use FireflyIII\Support\Facades\Preferences;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

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
        $groupTitle  = is_array($journal) ? $journal['description'] ?? '' : $journal->description ?? '';

        $mainMessage = trans('rules.main_message', ['rule' => $rule->title, 'action' => $ruleAction->action_type, 'group' => $groupId, 'error' => $error]);
        $groupLink   = route('transactions.show', [$groupId]);
        $ruleTitle   = $rule->title;
        $ruleLink    = route('rules.edit', [$rule->id]);
        $params      = [$mainMessage, $groupTitle, $groupLink, $ruleTitle, $ruleLink];
        NotificationSender::send($user, new RuleActionFailed($params));
    }
}
