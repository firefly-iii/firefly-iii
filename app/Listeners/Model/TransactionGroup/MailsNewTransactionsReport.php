<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Model\TransactionGroup;

use FireflyIII\Events\Model\TransactionGroup\TransactionGroupsRequestedReporting;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Notifications\User\TransactionCreation;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\Transformers\TransactionGroupTransformer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class MailsNewTransactionsReport implements ShouldQueue
{
    public function handle(TransactionGroupsRequestedReporting $event): void
    {
        Log::debug('In MailsNewTransactionsReport.');

        /** @var UserRepositoryInterface $repository */
        $repository  = app(UserRepositoryInterface::class);
        $user        = $repository->find($event->userId);

        /** @var bool $sendReport */
        $sendReport  = Preferences::getForUser($user, 'notification_transaction_creation', false)->data;

        if (false === $sendReport) {
            Log::debug('Not sending report, because config says so.');

            return;
        }

        if (null === $user || 0 === $event->groups->count()) {
            Log::debug('No transaction groups in event, nothing to email about.');

            return;
        }
        Log::debug('Continue with message!');

        // transform groups into array:
        /** @var TransactionGroupTransformer $transformer */
        $transformer = app(TransactionGroupTransformer::class);
        $groups      = [];

        /** @var TransactionGroup $group */
        foreach ($event->groups as $group) {
            $groups[] = $transformer->transformObject($group);
        }

        NotificationSender::send($user, new TransactionCreation($groups));
        Log::debug('If there is no error above this line, message was sent.');
    }
}
