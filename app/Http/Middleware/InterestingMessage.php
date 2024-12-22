<?php

/**
 * InterestingMessage.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Http\Middleware;

use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\Webhook;
use Illuminate\Http\Request;

/**
 * Class InterestingMessage
 */
class InterestingMessage
{
    /**
     * Flashes the user an interesting message if the URL parameters warrant it.
     *
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        if ($this->testing()) {
            return $next($request);
        }

        if ($this->groupMessage($request)) {
            app('preferences')->mark();
            $this->handleGroupMessage($request);
        }
        if ($this->accountMessage($request)) {
            app('preferences')->mark();
            $this->handleAccountMessage($request);
        }
        if ($this->billMessage($request)) {
            app('preferences')->mark();
            $this->handleBillMessage($request);
        }
        if ($this->webhookMessage($request)) {
            app('preferences')->mark();
            $this->handleWebhookMessage($request);
        }
        if ($this->currencyMessage($request)) {
            app('preferences')->mark();
            $this->handleCurrencyMessage($request);
        }

        return $next($request);
    }

    private function testing(): bool
    {
        // ignore middleware in test environment.
        return 'testing' === config('app.env') || !auth()->check();
    }

    private function groupMessage(Request $request): bool
    {
        // get parameters from request.
        $transactionGroupId = $request->get('transaction_group_id');
        $message            = $request->get('message');

        return null !== $transactionGroupId && null !== $message;
    }

    private function handleGroupMessage(Request $request): void
    {
        // get parameters from request.
        $transactionGroupId = $request->get('transaction_group_id');
        $message            = $request->get('message');

        // send message about newly created transaction group.
        /** @var null|TransactionGroup $group */
        $group              = auth()->user()->transactionGroups()->with(['transactionJournals', 'transactionJournals.transactionType'])->find((int) $transactionGroupId);

        if (null === $group) {
            return;
        }

        $count              = $group->transactionJournals->count();

        /** @var null|TransactionJournal $journal */
        $journal            = $group->transactionJournals->first();
        if (null === $journal) {
            return;
        }
        $title              = $count > 1 ? $group->title : $journal->description;
        if ('created' === $message) {
            session()->flash('success_url', route('transactions.show', [$transactionGroupId]));
            session()->flash('success', (string) trans('firefly.stored_journal', ['description' => $title]));
        }
        if ('updated' === $message) {
            $type = strtolower($journal->transactionType->type);
            session()->flash('success_url', route('transactions.show', [$transactionGroupId]));
            session()->flash('success', (string) trans(sprintf('firefly.updated_%s', $type), ['description' => $title]));
        }
        if ('no_change' === $message) {
            $type = strtolower($journal->transactionType->type);
            session()->flash('warning_url', route('transactions.show', [$transactionGroupId]));
            session()->flash('warning', (string) trans(sprintf('firefly.no_changes_%s', $type), ['description' => $title]));
        }
    }

    private function accountMessage(Request $request): bool
    {
        // get parameters from request.
        $accountId = $request->get('account_id');
        $message   = $request->get('message');

        return null !== $accountId && null !== $message;
    }

    private function handleAccountMessage(Request $request): void
    {
        // get parameters from request.
        $accountId = $request->get('account_id');
        $message   = $request->get('message');

        /** @var null|Account $account */
        $account   = auth()->user()->accounts()->withTrashed()->find($accountId);

        if (null === $account) {
            return;
        }
        if ('deleted' === $message) {
            session()->flash('success', (string) trans('firefly.account_deleted', ['name' => $account->name]));
        }
        if ('created' === $message) {
            session()->flash('success', (string) trans('firefly.stored_new_account', ['name' => $account->name]));
        }
        if ('updated' === $message) {
            session()->flash('success', (string) trans('firefly.updated_account', ['name' => $account->name]));
        }
    }

    private function billMessage(Request $request): bool
    {
        // get parameters from request.
        $billId  = $request->get('bill_id');
        $message = $request->get('message');

        return null !== $billId && null !== $message;
    }

    private function handleBillMessage(Request $request): void
    {
        // get parameters from request.
        $billId  = $request->get('bill_id');
        $message = $request->get('message');

        /** @var null|Bill $bill */
        $bill    = auth()->user()->bills()->withTrashed()->find($billId);

        if (null === $bill) {
            return;
        }
        if ('deleted' === $message) {
            session()->flash('success', (string) trans('firefly.deleted_bill', ['name' => $bill->name]));
        }
        if ('created' === $message) {
            session()->flash('success', (string) trans('firefly.stored_new_bill', ['name' => $bill->name]));
        }
    }

    private function webhookMessage(Request $request): bool
    {
        // get parameters from request.
        $webhookId = $request->get('webhook_id');
        $message   = $request->get('message');

        return null !== $webhookId && null !== $message;
    }

    private function handleWebhookMessage(Request $request): void
    {
        // get parameters from request.
        $webhookId = $request->get('webhook_id');
        $message   = $request->get('message');

        /** @var null|Webhook $webhook */
        $webhook   = auth()->user()->webhooks()->withTrashed()->find($webhookId);

        if (null === $webhook) {
            return;
        }
        if ('deleted' === $message) {
            session()->flash('success', (string) trans('firefly.deleted_webhook', ['title' => $webhook->title]));
        }
        if ('updated' === $message) {
            session()->flash('success', (string) trans('firefly.updated_webhook', ['title' => $webhook->title]));
        }
        if ('created' === $message) {
            session()->flash('success', (string) trans('firefly.stored_new_webhook', ['title' => $webhook->title]));
        }
    }

    private function currencyMessage(Request $request): bool
    {
        // get parameters from request.
        $code    = $request->get('code');
        $message = $request->get('message');

        return null !== $code && null !== $message;
    }

    private function handleCurrencyMessage(Request $request): void
    {
        // params:
        // get parameters from request.
        $code     = $request->get('code');
        $message  = $request->get('message');

        /** @var null|TransactionCurrency $currency */
        $currency = TransactionCurrency::whereCode($code)->first();

        if (null === $currency) {
            return;
        }
        if ('enabled' === $message) {
            session()->flash('success', (string) trans('firefly.currency_is_now_enabled', ['name' => $currency->name]));
        }
        if ('enable_failed' === $message) {
            session()->flash('error', (string) trans('firefly.could_not_enable_currency', ['name' => $currency->name]));
        }
        if ('disabled' === $message) {
            session()->flash('success', (string) trans('firefly.currency_is_now_disabled', ['name' => $currency->name]));
        }
        if ('disable_failed' === $message) {
            session()->flash('error', (string) trans('firefly.could_not_disable_currency', ['name' => $currency->name]));
        }
        if ('default' === $message) {
            session()->flash('success', (string) trans('firefly.new_default_currency', ['name' => $currency->name]));
        }
        if ('default_failed' === $message) {
            session()->flash('error', (string) trans('firefly.default_currency_failed', ['name' => $currency->name]));
        }
    }
}
