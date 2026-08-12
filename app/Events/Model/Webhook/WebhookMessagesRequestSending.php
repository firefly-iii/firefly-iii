<?php




declare(strict_types=1);



namespace FireflyIII\Events\Model\Webhook;

use FireflyIII\Events\Event;
use Illuminate\Queue\SerializesModels;

class WebhookMessagesRequestSending extends Event
{
    use SerializesModels;
}
