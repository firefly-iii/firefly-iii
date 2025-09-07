<?php

/*
 * StandardMessageGenerator.php
 * Copyright (c) 2021 james@firefly-iii.org
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

declare(strict_types=1);

namespace FireflyIII\Generator\Webhook;

use FireflyIII\Enums\WebhookResponse;
use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\Webhook;
use FireflyIII\Models\WebhookMessage;
use FireflyIII\Models\WebhookResponse as WebhookResponseModel;
use FireflyIII\Models\WebhookTrigger as WebhookTriggerModel;
use FireflyIII\Support\JsonApi\Enrichments\AccountEnrichment;
use FireflyIII\Support\JsonApi\Enrichments\BudgetEnrichment;
use FireflyIII\Support\JsonApi\Enrichments\BudgetLimitEnrichment;
use FireflyIII\Transformers\AccountTransformer;
use FireflyIII\Transformers\BudgetLimitTransformer;
use FireflyIII\Transformers\BudgetTransformer;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class StandardMessageGenerator
 */
class StandardMessageGenerator implements MessageGeneratorInterface
{
    private Collection     $objects;
    private WebhookTrigger $trigger;
    private User           $user;
    private int            $version = 0;
    private Collection     $webhooks;

    public function __construct()
    {
        $this->objects  = new Collection();
        $this->webhooks = new Collection();
    }

    public function generateMessages(): void
    {
        Log::debug(__METHOD__);
        // get the webhooks:
        if (0 === $this->webhooks->count()) {
            $this->webhooks = $this->getWebhooks();
        }

        // do some debugging
        Log::debug(sprintf('StandardMessageGenerator will generate messages for %d object(s) and %d webhook(s).', $this->objects->count(), $this->webhooks->count()));
        $this->run();
    }

    private function getWebhooks(): Collection
    {
        return $this->user->webhooks()
            ->leftJoin('webhook_webhook_trigger', 'webhook_webhook_trigger.webhook_id', 'webhooks.id')
            ->leftJoin('webhook_triggers', 'webhook_webhook_trigger.webhook_trigger_id', 'webhook_triggers.id')
            ->where('active', true)
            ->whereIn('webhook_triggers.title', [$this->trigger->name, WebhookTrigger::ANY->name])
            ->get(['webhooks.*'])
        ;
    }

    /**
     * @throws FireflyException
     */
    private function run(): void
    {
        Log::debug('Now in StandardMessageGenerator::run');

        /** @var Webhook $webhook */
        foreach ($this->webhooks as $webhook) {
            $this->runWebhook($webhook);
        }
        Log::debug('Done with StandardMessageGenerator::run');
    }

    /**
     * @throws FireflyException
     */
    private function runWebhook(Webhook $webhook): void
    {
        Log::debug(sprintf('Now in runWebhook(#%d)', $webhook->id));

        /** @var Model $object */
        foreach ($this->objects as $object) {
            $this->generateMessage($webhook, $object);
        }
    }

    /**
     * @throws FireflyException
     */
    private function generateMessage(Webhook $webhook, Model $model): void
    {
        $class         = $model::class;
        // Line is ignored because all of Firefly III's Models have an id property.
        Log::debug(sprintf('Now in generateMessage(#%d, %s#%d)', $webhook->id, $class, $model->id));
        $uuid          = Uuid::uuid4();

        /** @var WebhookResponseModel $response */
        $response      = $webhook->webhookResponses()->first();
        $triggers      = $this->getTriggerTitles($webhook->webhookTriggers()->get());
        $basicMessage  = [
            'uuid'          => $uuid->toString(),
            'user_id'       => 0,
            'user_group_id' => 0,
            'trigger'       => $this->trigger->name,
            'response'      => $response->title, // guess that the database is correct.
            'url'           => $webhook->url,
            'version'       => sprintf('v%d', $this->getVersion()),
            'content'       => [],
        ];

        switch ($class) {
            default:
                // Line is ignored because all of Firefly III's Models have an id property.
                Log::error(sprintf('Webhook #%d was given %s#%d to deal with but can\'t extract user ID from it.', $webhook->id, $class, $model->id));

                return;

            case Budget::class:
                /** @var Budget $model */
                $basicMessage['user_id']       = $model->user_id;
                $basicMessage['user_group_id'] = $model->user_group_id;
                $relevantResponse              = WebhookResponse::BUDGET->name;

                break;

            case BudgetLimit::class:
                $basicMessage['user_id']       = $model->budget->user_id;
                $basicMessage['user_group_id'] = $model->budget->user_group_id;
                $relevantResponse              = WebhookResponse::BUDGET->name;

                break;

            case TransactionGroup::class:
                /** @var TransactionGroup $model */
                $basicMessage['user_id']       = $model->user_id;
                $basicMessage['user_group_id'] = $model->user_group_id;

                break;
        }
        $responseTitle = $this->getRelevantResponse($triggers, $response, $class);

        switch ($responseTitle) {
            default:
                Log::error(sprintf('The response code for webhook #%d is "%s" and the message generator cant handle it. Soft fail.', $webhook->id, $webhook->response));

                return;

            case WebhookResponse::BUDGET->name:
                $basicMessage['content'] = [];
                if ($model instanceof Budget) {
                    $enrichment              = new BudgetEnrichment();
                    $enrichment->setUser($model->user);

                    /** @var Budget $model */
                    $model                   = $enrichment->enrichSingle($model);
                    $transformer             = new BudgetTransformer();
                    $basicMessage['content'] = $transformer->transform($model);
                }
                if ($model instanceof BudgetLimit) {
                    $user                    = $model->budget->user;
                    $enrichment              = new BudgetLimitEnrichment();
                    $enrichment->setUser($user);

                    $parameters              = new ParameterBag();
                    $parameters->set('start', $model->start_date);
                    $parameters->set('end', $model->end_date);

                    /** @var BudgetLimit $model */
                    $model                   = $enrichment->enrichSingle($model);
                    $transformer             = new BudgetLimitTransformer();
                    $transformer->setParameters($parameters);
                    $basicMessage['content'] = $transformer->transform($model);
                }

                break;

            case WebhookResponse::NONE->name:
                $basicMessage['content'] = [];

                break;

            case WebhookResponse::TRANSACTIONS->name:
                /** @var TransactionGroup $model */
                $transformer             = new TransactionGroupTransformer();

                try {
                    $basicMessage['content'] = $transformer->transformObject($model);
                } catch (FireflyException $e) {
                    Log::error(
                        sprintf('The transformer could not include the requested transaction group for webhook #%d: %s', $webhook->id, $e->getMessage())
                    );
                    Log::error($e->getTraceAsString());

                    return;
                }

                break;

            case WebhookResponse::ACCOUNTS->name:
                /** @var TransactionGroup $model */
                $accounts                = $this->collectAccounts($model);
                $enrichment              = new AccountEnrichment();
                $enrichment->setDate(null);
                $enrichment->setUser($model->user);
                $accounts                = $enrichment->enrich($accounts);
                foreach ($accounts as $account) {
                    $transformer               = new AccountTransformer();
                    $transformer->setParameters(new ParameterBag());
                    $basicMessage['content'][] = $transformer->transform($account);
                }
        }
        $this->storeMessage($webhook, $basicMessage);
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    private function collectAccounts(TransactionGroup $transactionGroup): Collection
    {
        $accounts = new Collection();

        /** @var TransactionJournal $journal */
        foreach ($transactionGroup->transactionJournals as $journal) {
            /** @var Transaction $transaction */
            foreach ($journal->transactions as $transaction) {
                $accounts->push($transaction->account);
            }
        }

        return $accounts->unique();
    }

    private function storeMessage(Webhook $webhook, array $message): void
    {
        $webhookMessage          = new WebhookMessage();
        $webhookMessage->webhook()->associate($webhook);
        $webhookMessage->sent    = false;
        $webhookMessage->errored = false;
        $webhookMessage->uuid    = $message['uuid'];
        $webhookMessage->message = $message;
        $webhookMessage->save();
        Log::debug(sprintf('Stored new webhook message #%d', $webhookMessage->id));
    }

    public function setObjects(Collection $objects): void
    {
        $this->objects = $objects;
    }

    public function setTrigger(WebhookTrigger $trigger): void
    {
        $this->trigger = $trigger;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function setWebhooks(Collection $webhooks): void
    {
        $this->webhooks = $webhooks;
    }

    private function getRelevantResponse(array $triggers, WebhookResponseModel $response, string $class): string
    {
        // return none if none.
        if (WebhookResponse::NONE->name === $response->title) {
            Log::debug(sprintf('Return "%s" because requested nothing.', WebhookResponse::NONE->name));

            return WebhookResponse::NONE->name;
        }

        if (WebhookResponse::RELEVANT->name === $response->title) {
            Log::debug('Expected response is any relevant data.');

            // depends on the $class
            switch ($class) {
                case TransactionGroup::class:
                    Log::debug(sprintf('Return "%s" because class is %s', WebhookResponse::TRANSACTIONS->name, $class));

                    return WebhookResponse::TRANSACTIONS->name;

                case Budget::class:
                case BudgetLimit::class:
                    Log::debug(sprintf('Return "%s" because class is %s', WebhookResponse::BUDGET->name, $class));

                    return WebhookResponse::BUDGET->name;

                default:
                    throw new FireflyException(sprintf('Cannot deal with "relevant" if the given object is a "%s"', $class));
            }
        }
        Log::debug(sprintf('Return response again: %s', $response->title));

        return $response->title;
    }

    private function getTriggerTitles(Collection $collection): array
    {
        $return = [];

        /** @var WebhookTriggerModel $item */
        foreach ($collection as $item) {
            $return[] = $item->title;
        }

        return array_unique($return);
    }
}
