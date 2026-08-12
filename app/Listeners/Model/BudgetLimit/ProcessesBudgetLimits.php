<?php




declare(strict_types=1);

namespace FireflyIII\Listeners\Model\BudgetLimit;

use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Events\Model\BudgetLimit\CreatedBudgetLimit;
use FireflyIII\Events\Model\BudgetLimit\DestroyedBudgetLimit;
use FireflyIII\Events\Model\BudgetLimit\UpdatedBudgetLimit;
use FireflyIII\Generator\Webhook\MessageGeneratorInterface;
use FireflyIII\Models\Budget;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Models\AvailableBudgetCalculator;
use FireflyIII\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProcessesBudgetLimits implements ShouldQueue
{
    public function handle(CreatedBudgetLimit|DestroyedBudgetLimit|UpdatedBudgetLimit $event): void
    {
        Log::debug(sprintf('Now in ProcessesBudgetLimits::handle for event %s', get_class($event)));
        if ($event instanceof DestroyedBudgetLimit) {
            // need to recalculate all available budgets for this user.
            $calculator = new AvailableBudgetCalculator();
            $calculator->setUser($event->user);
            $calculator->setStart($event->start->clone());
            $calculator->setEnd($event->end->clone());
            $calculator->setCreate(false);
            $calculator->setCurrency(Amount::getPrimaryCurrencyByUserGroup($event->user->userGroup));
            $calculator->recalculateByRange();

            // do webhooks
            if ($event->createWebhookMessages) {
                $this->createWebhookMessages($event->user, $event->budget, WebhookTrigger::STORE_UPDATE_BUDGET_LIMIT);
            }

            return;
        }

        $calculator = new AvailableBudgetCalculator();
        $calculator->setUser($event->budgetLimit->budget->user);
        $calculator->setStart($event->budgetLimit->start_date->clone());
        $calculator->setEnd($event->budgetLimit->end_date->clone());
        $calculator->setCreate(true);
        $calculator->setCurrency($event->budgetLimit->transactionCurrency);
        $calculator->recalculateByRange();

        // do webhooks:
        if ($event->createWebhookMessages) {
            Log::debug('Event says to create webhook messages');
            $this->createWebhookMessages($event->budgetLimit->budget->user, $event->budgetLimit->budget, WebhookTrigger::STORE_UPDATE_BUDGET_LIMIT);
        }
    }

    private function createWebhookMessages(User $user, Budget $budget, WebhookTrigger $trigger): void
    {
        /** @var MessageGeneratorInterface $engine */
        $engine = app(MessageGeneratorInterface::class);
        $engine->setUser($user);
        $engine->setObjects(new Collection()->push($budget));
        $engine->setTrigger($trigger);
        $engine->generateMessages();
    }
}
