<?php




declare(strict_types=1);



namespace FireflyIII\Factory;

use FireflyIII\Models\Webhook;
use FireflyIII\Models\WebhookMessage;
use Illuminate\Support\Facades\Log;

class WebhookMessageFactory
{
    public function create(Webhook $webhook, array $data): WebhookMessage
    {
        $webhookMessage          = new WebhookMessage();
        $webhookMessage->webhook()->associate($webhook);
        $webhookMessage->sent    = false;
        $webhookMessage->errored = false;
        $webhookMessage->uuid    = $data['uuid'];
        $webhookMessage->message = $data;
        $webhookMessage->save();
        Log::debug(sprintf('Stored new webhook message #%d', $webhookMessage->id));

        return $webhookMessage;
    }
}
