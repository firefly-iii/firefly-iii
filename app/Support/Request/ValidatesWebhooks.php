<?php

namespace FireflyIII\Support\Request;

use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Models\Webhook;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Log;

trait ValidatesWebhooks
{
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator): void {
                Log::debug('Validating webhook');
                if ($validator->failed()) {
                    return;
                }
                $data      = $validator->getData();
                $triggers  = $data['triggers'] ?? [];
                $responses = $data['responses'] ?? [];

                if (0 === count($triggers) || 0 === count($responses)) {
                    Log::debug('No trigger or response, return.');

                    return;
                }
                $validTriggers  = array_values(Webhook::getTriggers());
                $validResponses = array_values(Webhook::getResponses());
                $containsAny = false;
                $count = 0;
                foreach ($triggers as $trigger) {
                    if (!in_array($trigger, $validTriggers, true)) {
                        return;
                    }
                    $count++;
                    if($trigger === WebhookTrigger::ANY->name) {
                        $containsAny = true;
                    }
                }
                if($containsAny && $count > 1) {
                    $validator->errors()->add('triggers.0', trans('validation.only_any_trigger'));
                    return;
                }
                foreach ($responses as $response) {
                    if (!in_array($response, $validResponses, true)) {
                        return;
                    }
                }
                // some combinations are illegal.
                foreach ($triggers as $i => $trigger) {
                    $forbidden = config(sprintf('webhooks.forbidden_responses.%s', $trigger));
                    if (null === $forbidden) {
                        $validator->errors()->add(sprintf('triggers.%d', $i), trans('validation.unknown_webhook_trigger', ['trigger' => $trigger,]));
                        continue;
                    }
                    foreach ($responses as $ii => $response) {
                        if (in_array($response, $forbidden, true)) {
                            Log::debug(sprintf('Trigger %s and response %s are forbidden.', $trigger, $response));
                            $validator->errors()->add(sprintf('responses.%d', $ii), trans('validation.bad_webhook_combination', ['trigger' => $trigger, 'response' => $response,]));
                            return;
                        }
                    }
                }
            }
        );
    }
}
