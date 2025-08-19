<?php

namespace Database\Seeders;

use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Enums\WebhookResponse;
use FireflyIII\Enums\WebhookDelivery;
use FireflyIII\Models\WebhookTrigger as WebhookTriggerModel;
use FireflyIII\Models\WebhookResponse as WebhookResponseModel;
use FireflyIII\Models\WebhookDelivery as WebhookDeliveryModel;
use Illuminate\Database\Seeder;

class WebhookDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (WebhookTrigger::cases() as $trigger) {
            if (null === WebhookTriggerModel::where('key', $trigger->value)->where('title', $trigger->name)->first()) {
                try {
                    WebhookTriggerModel::create(['key' => $trigger->value, 'title' => $trigger->name]);
                } catch (\PDOException $e) {
                    // @ignoreException
                }
            }
        }
        foreach (WebhookResponse::cases() as $response) {
            if (null === WebhookResponseModel::where('key', $response->value)->where('title', $response->name)->first()) {
                try {
                    WebhookResponseModel::create(['key' => $response->value, 'title' => $response->name]);
                } catch (\PDOException $e) {
                    // @ignoreException
                }
            }
        }
        foreach (WebhookDelivery::cases() as $delivery) {
            if (null === WebhookDeliveryModel::where('key', $delivery->value)->where('title', $delivery->name)->first()) {
                try {
                    WebhookDeliveryModel::create(['key' => $delivery->value, 'title' => $delivery->name]);
                } catch (\PDOException $e) {
                    // @ignoreException
                }
            }
        }
    }
}
