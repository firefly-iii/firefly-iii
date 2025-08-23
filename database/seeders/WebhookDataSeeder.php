<?php


/*
 * WebhookDataSeeder.php
 * Copyright (c) 2025 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

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
