<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Model\Bill;

use FireflyIII\Events\Model\Bill\UpdatedExistingBill;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class UpdatesRulesForChangedBill implements ShouldQueue
{
    public function handle(UpdatedExistingBill $event): void
    {
        // update rule actions.
        if ($event->bill->name !== $event->oldData['name']) {
            $this->updateBillTriggersAndActions($event->bill, $event->oldData);
        }
    }

    private function updateBillTriggersAndActions(Bill $bill, array $oldData): void
    {
        Log::debug(sprintf('Now in updateBillTriggersAndActions(#%d)', $bill->id));
        $repository = app(RuleRepositoryInterface::class);
        $repository->setUser($bill->user);
        $rules      = $repository->getAll();

        /** @var Rule $rule */
        foreach ($rules as $rule) {
            $this->updateRule($bill, $rule, $oldData);
        }
    }

    private function updateRule(Bill $bill, Rule $rule, array $oldData): void
    {
        $triggers = ['bill_is', 'bill_ends', 'bill_starts', 'bill_contains'];

        /** @var RuleTrigger $trigger */
        foreach ($rule->ruleTriggers as $trigger) {
            if (in_array($trigger->trigger_type, $triggers, true) && $trigger->trigger_value === $oldData['name']) {
                Log::debug(sprintf('Updated trigger #%d in rule #%d to new subscription name', $trigger->id, $rule->id));
                $trigger->trigger_value = $bill->name;
                $trigger->save();
            }
        }

        /** @var RuleAction $action */
        foreach ($rule->ruleActions as $action) {
            if ('link_to_bill' === $action->action_type && $action->action_value === $oldData['name']) {
                Log::debug(sprintf('Updated action #%d in rule #%d to new subscription name', $action->id, $rule->id));
                $action->action_value = $bill->name;
                $action->save();
            }
        }
    }
}
