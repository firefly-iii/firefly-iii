<?php

/**
 * GracefulNotFoundHandler.php
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

namespace FireflyIII\Exceptions;

use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Models\Account;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\User;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class GracefulNotFoundHandler
 */
class GracefulNotFoundHandler extends ExceptionHandler
{
    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     *
     * @throws \Throwable
     *
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    public function render($request, \Throwable $e): Response
    {
        $route = $request->route();
        if (null === $route) {
            return parent::render($request, $e);
        }
        $name  = $route->getName();
        if (!auth()->check()) {
            return parent::render($request, $e);
        }

        switch ($name) {
            default:
                app('log')->warning(sprintf('GracefulNotFoundHandler cannot handle route with name "%s"', $name));

                return parent::render($request, $e);

            case 'accounts.show':
            case 'accounts.edit':
            case 'accounts.show.all':
                return $this->handleAccount($request, $e);

            case 'transactions.show':
            case 'transactions.edit':
                return $this->handleGroup($request, $e);

            case 'attachments.show':
            case 'attachments.edit':
            case 'attachments.download':
            case 'attachments.view':
                // redirect to original attachment holder.
                return $this->handleAttachment($request, $e);

            case 'bills.show':
                $request->session()->reflash();

                return redirect(route('bills.index'));

            case 'currencies.show':
                $request->session()->reflash();

                return redirect(route('currencies.index'));

            case 'budgets.show':
            case 'budgets.edit':
            case 'budgets.show.limit':
                $request->session()->reflash();

                return redirect(route('budgets.index'));

            case 'piggy-banks.show':
                $request->session()->reflash();

                return redirect(route('piggy-banks.index'));

            case 'recurring.show':
            case 'recurring.edit':
                $request->session()->reflash();

                return redirect(route('recurring.index'));

            case 'tags.show.all':
            case 'tags.show':
            case 'tags.edit':
                $request->session()->reflash();

                return redirect(route('tags.index'));

            case 'categories.show':
            case 'categories.edit':
            case 'categories.show.all':
                $request->session()->reflash();

                return redirect(route('categories.index'));

            case 'rules.edit':
                $request->session()->reflash();

                return redirect(route('rules.index'));

            case 'transactions.mass.edit':
            case 'transactions.mass.delete':
            case 'transactions.bulk.edit':
                if ('POST' === $request->method()) {
                    $request->session()->reflash();

                    return redirect(route('index'));
                }

                return parent::render($request, $e);
        }
    }

    /**
     * @throws \Throwable
     */
    private function handleAccount(Request $request, \Throwable $exception): Response
    {
        app('log')->debug('404 page is probably a deleted account. Redirect to overview of account types.');

        /** @var User $user */
        $user      = auth()->user();
        $route     = $request->route();
        $param     = $route->parameter('account');
        $accountId = 0;
        if ($param instanceof Account) {
            $accountId = $param->id;
        }
        if (!($param instanceof Account) && !is_object($param)) {
            $accountId = (int) $param;
        }

        /** @var null|Account $account */
        $account   = $user->accounts()->with(['accountType'])->withTrashed()->find($accountId);
        if (null === $account) {
            app('log')->error(sprintf('Could not find account %d, so give big fat error.', $accountId));

            return parent::render($request, $exception);
        }
        $type      = $account->accountType;
        $shortType = config(sprintf('firefly.shortNamesByFullName.%s', $type->type));
        $request->session()->reflash();

        return redirect(route('accounts.index', [$shortType]));
    }

    /**
     * @return Response
     *
     * @throws \Throwable
     */
    private function handleGroup(Request $request, \Throwable $exception)
    {
        app('log')->debug('404 page is probably a deleted group. Redirect to overview of group types.');

        /** @var User $user */
        $user    = auth()->user();
        $route   = $request->route();
        $param   = $route->parameter('transactionGroup');
        $groupId = !is_object($param) ? (int) $param : 0;

        /** @var null|TransactionGroup $group */
        $group   = $user->transactionGroups()->withTrashed()->find($groupId);
        if (null === $group) {
            app('log')->error(sprintf('Could not find group %d, so give big fat error.', $groupId));

            return parent::render($request, $exception);
        }

        /** @var null|TransactionJournal $journal */
        $journal = $group->transactionJournals()->withTrashed()->first();
        if (null === $journal) {
            app('log')->error(sprintf('Could not find journal for group %d, so give big fat error.', $groupId));

            return parent::render($request, $exception);
        }
        $type    = $journal->transactionType->type;
        $request->session()->reflash();

        if (TransactionTypeEnum::RECONCILIATION->value === $type) {
            return redirect(route('accounts.index', ['asset']));
        }

        return redirect(route('transactions.index', [strtolower($type)]));
    }

    /**
     * @return Response
     *
     * @throws \Throwable
     */
    private function handleAttachment(Request $request, \Throwable $exception)
    {
        app('log')->debug('404 page is probably a deleted attachment. Redirect to parent object.');

        /** @var User $user */
        $user         = auth()->user();
        $route        = $request->route();
        $param        = $route->parameter('attachment');
        $attachmentId = is_object($param) ? 0 : (int) $param;

        /** @var null|Attachment $attachment */
        $attachment   = $user->attachments()->withTrashed()->find($attachmentId);
        if (null === $attachment) {
            app('log')->error(sprintf('Could not find attachment %d, so give big fat error.', $attachmentId));

            return parent::render($request, $exception);
        }
        // get bindable.
        if (TransactionJournal::class === $attachment->attachable_type) {
            // is linked to journal, get group of journal (if not also deleted)
            /** @var null|TransactionJournal $journal */
            $journal = $user->transactionJournals()->withTrashed()->find($attachment->attachable_id);
            if (null !== $journal) {
                return redirect(route('transactions.show', [$journal->transaction_group_id]));
            }
        }
        if (Bill::class === $attachment->attachable_type) {
            // is linked to bill.
            /** @var null|Bill $bill */
            $bill = $user->bills()->withTrashed()->find($attachment->attachable_id);
            if (null !== $bill) {
                return redirect(route('bills.show', [$bill->id]));
            }
        }

        app('log')->error(sprintf('Could not redirect attachment %d, its linked to a %s.', $attachmentId, $attachment->attachable_type));

        return parent::render($request, $exception);
    }
}
