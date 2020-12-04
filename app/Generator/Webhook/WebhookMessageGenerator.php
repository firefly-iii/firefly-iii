<?php
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

use FireflyIII\Events\RequestedSendWebhookMessages;
use FireflyIII\Events\StoredWebhookMessage;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\Webhook;
use FireflyIII\Models\WebhookMessage;
use FireflyIII\Transformers\AccountTransformer;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class WebhookMessageGenerator
 */
class WebhookMessageGenerator
{
    private User       $user;
    private Collection $transactionGroups;
    private int        $trigger;
    private Collection $webhooks;

    /**
     *
     */
    public function generateMessages(): void
    {
        $this->webhooks = $this->getWebhooks();
        Log::debug(sprintf('Generate messages for %d group(s) and %d webhook(s).', $this->transactionGroups->count(), $this->webhooks->count()));
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
     * @param Collection $transactionGroups
     */
    public function setTransactionGroups(Collection $transactionGroups): void
    {
        $this->transactionGroups = $transactionGroups;
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

    private function run(): void
    {
        /** @var Webhook $webhook */
        foreach ($this->webhooks as $webhook) {
            $this->runWebhook($webhook);
        }
        event(new RequestedSendWebhookMessages);
    }

    /**
     * @param Webhook $webhook
     *
     * @throws FireflyException
     */
    private function runWebhook(Webhook $webhook): void
    {
        /** @var TransactionGroup $transactionGroup */
        foreach ($this->transactionGroups as $transactionGroup) {
            $this->generateMessage($webhook, $transactionGroup);
        }
    }

    /**
     * @param Webhook          $webhook
     * @param TransactionGroup $transactionGroup
     */
    private function generateMessage(Webhook $webhook, TransactionGroup $transactionGroup): void
    {
        Log::debug(sprintf('Generating message for webhook #%d and transaction group #%d.', $webhook->id, $transactionGroup->id));

        // message depends on what the webhook sets.
        $uuid    = Uuid::uuid4();
        $message = [
            'user_id'  => $transactionGroup->user->id,
            'trigger'  => config('firefly.webhooks.triggers')[$webhook->trigger],
            'url'      => $webhook->url,
            'uuid'     => $uuid->toString(),
            'version'  => 0,
            'response' => config('firefly.webhooks.responses')[$webhook->response],
            'content'  => [],
        ];

        switch ($webhook->response) {
            default:
                throw new FireflyException(sprintf('Cannot handle this webhook response (%d)', $webhook->response));
            case Webhook::RESPONSE_NONE:
                $message['content'] = [];
            case Webhook::RESPONSE_TRANSACTIONS:
                $transformer        = new TransactionGroupTransformer;
                $message['content'] = $transformer->transformObject($transactionGroup);
                break;
            case Webhook::RESPONSE_ACCOUNTS:
                $accounts = $this->collectAccounts($transactionGroup);
                foreach ($accounts as $account) {
                    $transformer = new AccountTransformer;
                    $transformer->setParameters(new ParameterBag);
                    $message['content'][] = $transformer->transform($account);
                }
        }
        $this->storeMessage($webhook, $message);
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

        return $webhookMessage;
    }


}