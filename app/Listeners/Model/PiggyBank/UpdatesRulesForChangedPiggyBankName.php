<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Model\PiggyBank;

use FireflyIII\Events\Model\PiggyBank\PiggyBankNameIsChanged;
use FireflyIII\Models\Account;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdatesRulesForChangedPiggyBankName implements ShouldQueue
{
    public function handle(PiggyBankNameIsChanged $event): void
    {
        // loop all accounts, collect all user's rules.
        /** @var Account $account */
        foreach ($event->piggyBank->accounts as $account) {
            /** @var Rule $rule */
            foreach ($account->user->rules as $rule) {
                /** @var RuleAction $ruleAction */
                foreach ($rule->ruleActions()->where('action_type', 'update_piggy')->get() as $ruleAction) {
                    if ($event->oldName === $ruleAction->action_value) {
                        $ruleAction->action_value = $event->newName;
                        $ruleAction->save();
                    }
                }
            }
        }
    }
}
