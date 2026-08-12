<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Model\Webhook;

use FireflyIII\Events\Model\Webhook\WebhookMessagesRequestSending;
use FireflyIII\Jobs\SendWebhookMessage;
use FireflyIII\Models\WebhookMessage;
use FireflyIII\Support\Facades\AppConfiguration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendsWebhookMessages implements ShouldQueue
{
    public function handle(WebhookMessagesRequestSending $event): void
    {
        Log::debug(sprintf('Now in %s for %s', __METHOD__, get_class($event)));
        if (false === config('firefly.feature_flags.webhooks') || false === AppConfiguration::get('allow_webhooks', config('firefly.allow_webhooks'))->data) {
            Log::debug('Webhook event handler is disabled, do not run sendWebhookMessages().');

            return;
        }

        // kick off the job!
        $messages = WebhookMessage::query()
            ->where('webhook_messages.sent', false)
            ->get(['webhook_messages.*'])
            ->filter(static fn (WebhookMessage $message): bool => $message->webhookAttempts()->count() <= 2)
            ->splice(0, 5)
        ;
        Log::debug(sprintf('Found %d webhook message(s) ready to be send.', $messages->count()));

        /** @var WebhookMessage $message */
        foreach ($messages as $message) {
            if (false === $message->sent) {
                // set it to "sent" right away!
                $message->sent = true;
                $message->save();
                Log::debug(sprintf('Send message #%d', $message->id));
                SendWebhookMessage::dispatch($message)->afterResponse();

                continue;
            }
            Log::debug(sprintf('Skip message #%d', $message->id));
        }

        // clean up sent messages table:
        WebhookMessage::query()->where('webhook_messages.sent', true)->where('webhook_messages.created_at', '<', now()->subDays(14))->delete();
    }
}
