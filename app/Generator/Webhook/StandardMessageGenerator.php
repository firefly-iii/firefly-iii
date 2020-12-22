<?php
declare(strict_types=1);
/*
 * WebhookMessageGenerator.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Generator\Webhook;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\Webhook;
use FireflyIII\Models\WebhookMessage;
use FireflyIII\Transformers\AccountTransformer;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Log;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class StandardMessageGenerator
 */
class StandardMessageGenerator implements MessageGeneratorInterface
{
    private int        $version = 0;
    private User       $user;
    private Collection $objects;
    private int        $trigger;
    private Collection $webhooks;

    /**
     *
     */
    public function generateMessages(): void
    {
        // get the webhooks:
        $this->webhooks = $this->getWebhooks();

        // do some debugging
        Log::debug(
            sprintf('StandardMessageGenerator will generate messages for %d object(s) and %d webhook(s).', $this->objects->count(), $this->webhooks->count())
        );
        $this->run();
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param Collection $objects
     */
    public function setObjects(Collection $objects): void
    {
        $this->objects = $objects;
    }

    /**
     * @param int $trigger
     */
    public function setTrigger(int $trigger): void
    {
        $this->trigger = $trigger;
    }

    /**
     * @return Collection
     */
    private function getWebhooks(): Collection
    {
        return $this->user->webhooks()->where('active', 1)->where('trigger', $this->trigger)->get(['webhooks.*']);
    }

    /**
     *
     */
    private function run(): void
    {
        Log::debug('Now in StandardMessageGenerator::run');
        /** @var Webhook $webhook */
        foreach ($this->webhooks as $webhook) {
            $this->runWebhook($webhook);
        }
    }

    /**
     * @param Webhook $webhook
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
     * @param Webhook $webhook
     * @param Model   $model
     */
    private function generateMessage(Webhook $webhook, Model $model): void
    {
        $class = get_class($model);
        Log::debug(sprintf('Now in generateMessage(#%d, %s#%d)', $webhook->id, $class, $model->id));

        $uuid         = Uuid::uuid4();
        $basicMessage = [
            'uuid'     => $uuid->toString(),
            'user_id'  => 0,
            'trigger'  => config('firefly.webhooks.triggers')[$webhook->trigger],
            'response' => config('firefly.webhooks.responses')[$webhook->response],
            'url'      => $webhook->url,
            'version'  => sprintf('v%d', $this->getVersion()),
            'content'  => [],
        ];

        // depends on the model how user_id is set:
        switch ($class) {
            default:
                Log::error(sprintf('Webhook #%d was given %s#%d to deal with but can\'t extract user ID from it.', $webhook->id, $class, $model->id));

                return;
            case TransactionGroup::class:
                /** @var TransactionGroup $model */
                $basicMessage['user_id'] = $model->user->id;
                break;
        }

        // then depends on the response what to put in the message:
        switch ($webhook->response) {
            default:
                Log::error(
                    sprintf('The response code for webhook #%d is "%d" and the message generator cant handle it. Soft fail.', $webhook->id, $webhook->response)
                );

                return;
            case Webhook::RESPONSE_NONE:
                $basicMessage['content'] = [];
                break;
            case Webhook::RESPONSE_TRANSACTIONS:
                $transformer = new TransactionGroupTransformer;
                try {
                    $basicMessage['content'] = $transformer->transformObject($model);
                } catch (FireflyException $e) {
                    Log::error(
                        sprintf('The transformer could not include the requested transaction group for webhook #%d: %s', $webhook->id, $e->getMessage())
                    );

                    return;
                }
                break;
            case Webhook::RESPONSE_ACCOUNTS:
                $accounts = $this->collectAccounts($model);
                foreach ($accounts as $account) {
                    $transformer = new AccountTransformer;
                    $transformer->setParameters(new ParameterBag);
                    $basicMessage['content'][] = $transformer->transform($account);
                }
        }
        $this->storeMessage($webhook, $basicMessage);
    }

    /**
     * @param TransactionGroup $transactionGroup
     *
     * @return Collection
     */
    private function collectAccounts(TransactionGroup $transactionGroup): Collection
    {
        $accounts = new Collection;
        /** @var TransactionJournal $journal */
        foreach ($transactionGroup->transactionJournals as $journal) {
            /** @var Transaction $transaction */
            foreach ($journal->transactions as $transaction) {
                $accounts->push($transaction->account);
            }
        }

        return $accounts->unique();
    }

    /**
     * @param Webhook $webhook
     * @param array   $message
     *
     * @return WebhookMessage
     */
    private function storeMessage(Webhook $webhook, array $message): WebhookMessage
    {
        $webhookMessage = new WebhookMessage;
        $webhookMessage->webhook()->associate($webhook);
        $webhookMessage->sent    = false;
        $webhookMessage->errored = false;
        $webhookMessage->uuid    = $message['uuid'];
        $webhookMessage->message = $message;
        $webhookMessage->save();
        Log::debug(sprintf('Stored new webhook message #%d', $webhookMessage->id));

        return $webhookMessage;
    }


    /**
     * @inheritDoc
     */
    public function getVersion(): int
    {
        return $this->version;
    }
}
