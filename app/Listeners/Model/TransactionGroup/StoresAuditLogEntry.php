<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Model\TransactionGroup;

use Carbon\Carbon;
use FireflyIII\Events\Model\TransactionGroup\TransactionGroupRequestsAuditLogEntry;
use FireflyIII\Repositories\AuditLogEntry\ALERepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class StoresAuditLogEntry implements ShouldQueue
{
    public function handle(TransactionGroupRequestsAuditLogEntry $event): void
    {
        $array      = [
            'auditable' => $event->auditable,
            'changer'   => $event->changer,
            'action'    => $event->field,
            'before'    => $event->before,
            'after'     => $event->after,
        ];

        if ($event->before === $event->after) {
            Log::debug('Will not store event log because before and after are the same.');

            return;
        }
        if ($event->before instanceof Carbon && $event->after instanceof Carbon && $event->before->eq($event->after)) {
            Log::debug('Will not store event log because before and after Carbon values are the same.');

            return;
        }
        if ($event->before instanceof Carbon && $event->after instanceof Carbon) {
            $array['before'] = $event->before->toIso8601String();
            $array['after']  = $event->after->toIso8601String();
            Log::debug(sprintf('Converted "before" to "%s".', $event->before));
            Log::debug(sprintf('Converted "after" to "%s".', $event->after));
        }

        /** @var ALERepositoryInterface $repository */
        $repository = app(ALERepositoryInterface::class);
        $repository->store($array);
    }
}
