<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Model\Budget;

use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Events\Model\Budget\CreatedBudget;
use FireflyIII\Events\Model\Budget\DestroyingBudget;
use FireflyIII\Events\Model\Budget\UpdatedBudget;
use FireflyIII\Generator\Webhook\MessageGeneratorInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProcessesBudgets implements ShouldQueue
{
    public function handle(CreatedBudget|DestroyingBudget|UpdatedBudget $event): void
    {
        Log::debug(sprintf('Will now handle %s', get_class($event)));
        $trigger = WebhookTrigger::STORE_BUDGET;
        if ($event instanceof DestroyingBudget) {
            $trigger = WebhookTrigger::DESTROY_BUDGET;
        }
        if ($event instanceof UpdatedBudget) {
            $trigger = WebhookTrigger::UPDATE_BUDGET;
        }

        /** @var MessageGeneratorInterface $engine */
        $engine  = app(MessageGeneratorInterface::class);
        $engine->setUser($event->budget->user);
        $engine->setObjects(new Collection()->push($event->budget));
        $engine->setTrigger($trigger);
        $engine->generateMessages();
    }
}
