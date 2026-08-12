<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Model\TransactionGroup;

use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Events\Model\TransactionGroup\DestroyedSingleTransactionGroup;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ProcessesDestroyedTransactionGroup implements ShouldQueue
{
    use SupportsGroupProcessingTrait;

    public function handle(DestroyedSingleTransactionGroup $event): void
    {
        Log::debug(sprintf('User called %s', get_class($event)));

        if (!$event->flags->recalculateCredit) {
            Log::debug(sprintf('Will NOT recalculate credit for %d journal(s)', $event->objects->transactionJournals->count()));
        }
        if (!$event->flags->fireWebhooks) {
            Log::debug(sprintf('Will NOT fire webhooks for %d journal(s)', $event->objects->transactionJournals->count()));
        }

        if ($event->flags->recalculateCredit) {
            $this->recalculateCredit($event->objects->accounts);
        }
        if ($event->flags->fireWebhooks) {
            $this->createWebhookMessages($event->objects->transactionGroups, WebhookTrigger::DESTROY_TRANSACTION);
        }
        $this->removePeriodStatistics($event->objects);
        $this->recalculateRunningBalance($event->objects);
    }
}
