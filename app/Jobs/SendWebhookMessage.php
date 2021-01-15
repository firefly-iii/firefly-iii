<?php
declare(strict_types=1);

namespace FireflyIII\Jobs;

use FireflyIII\Models\WebhookMessage;
use FireflyIII\Services\Webhook\WebhookSenderInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Class SendWebhookMessage
 */
class SendWebhookMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private WebhookMessage  $message;

    /**
     * Create a new job instance.
     *
     * @param WebhookMessage $message
     */
    public function __construct(WebhookMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        // send job!
        $sender = app(WebhookSenderInterface::class);
        $sender->setMessage($this->message);
        $sender->send();
    }
}
