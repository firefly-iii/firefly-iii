<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Model\TransactionGroup;

use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Events\Model\TransactionGroup\CreatedSingleTransactionGroup;
use FireflyIII\Events\Model\TransactionGroup\UserRequestedBatchProcessing;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Facades\AppConfiguration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ProcessesNewTransactionGroup implements ShouldQueue
{
    use SupportsGroupProcessingTrait;

    public function handle(CreatedSingleTransactionGroup|UserRequestedBatchProcessing $event): void
    {
        Log::debug(sprintf('Running event handler for %s', get_class($event)));

        $setting    = AppConfiguration::get('enable_batch_processing', false)->data;
        if (true === $event->flags->batchSubmission && true === $setting) {
            Log::debug('Will do nothing for event because it is part of a batch.');

            return;
        }
        $repository = app(JournalRepositoryInterface::class);
        $journals   = $event->objects->transactionJournals->merge($repository->getAllUncompletedJournals());

        Log::debug(sprintf('Transaction journal count is %d', $journals->count()));
        if (!$event->flags->applyRules) {
            Log::debug(sprintf('Will NOT process rules for %d journal(s)', $journals->count()));
        }
        if (!$event->flags->recalculateCredit) {
            Log::debug(sprintf('Will NOT recalculate credit for %d journal(s)', $journals->count()));
        }
        if (!$event->flags->fireWebhooks) {
            Log::debug(sprintf('Will NOT fire webhooks for %d journal(s)', $journals->count()));
        }

        if ($event->flags->applyRules) {
            $this->processRules($journals, 'store-journal');
        }
        if ($event->flags->recalculateCredit) {
            $this->recalculateCredit($event->objects->accounts);
        }
        if ($event->flags->fireWebhooks) {
            $this->createWebhookMessages($event->objects->transactionGroups, WebhookTrigger::STORE_TRANSACTION);
        }
        $this->removePeriodStatistics($event->objects);
        $this->recalculateRunningBalance($event->objects);
        $repository->markAsCompleted($journals);
    }
}
