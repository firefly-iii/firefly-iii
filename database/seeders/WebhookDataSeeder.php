<?php




declare(strict_types=1);



namespace Database\Seeders;

use FireflyIII\Enums\WebhookDelivery;
use FireflyIII\Enums\WebhookResponse;
use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Models\WebhookDelivery as WebhookDeliveryModel;
use FireflyIII\Models\WebhookResponse as WebhookResponseModel;
use FireflyIII\Models\WebhookTrigger as WebhookTriggerModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class WebhookDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (WebhookTrigger::cases() as $trigger) {
            if (null === WebhookTriggerModel::query()->where('key', $trigger->value)->where('title', $trigger->name)->first()) {
                try {
                    WebhookTriggerModel::create(['key' => $trigger->value, 'title' => $trigger->name]);
                } catch (\PDOException $e) {
                    Log::debug(sprintf('Webhook trigger with name "%s" already exists and that is OK.', $trigger->name));
                }
            }
        }
        foreach (WebhookResponse::cases() as $response) {
            if (null === WebhookResponseModel::query()->where('key', $response->value)->where('title', $response->name)->first()) {
                try {
                    WebhookResponseModel::create(['key' => $response->value, 'title' => $response->name]);
                } catch (\PDOException $e) {
                    Log::debug(sprintf('Webhook response with name "%s" already exists and that is OK.', $response->name));
                }
            }
        }
        foreach (WebhookDelivery::cases() as $delivery) {
            if (null === WebhookDeliveryModel::query()->where('key', $delivery->value)->where('title', $delivery->name)->first()) {
                try {
                    WebhookDeliveryModel::create(['key' => $delivery->value, 'title' => $delivery->name]);
                } catch (\PDOException $e) {
                    Log::debug(sprintf('Webhook delivery type with name "%s" already exists and that is OK.', $delivery->name));
                }
            }
        }
    }
}
