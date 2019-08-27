<?php
declare(strict_types=1);
/**
 * GracefulNotFoundHandler.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FireflyIII\Exceptions;


use Exception;
use FireflyIII\Models\Account;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\User;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Log;

/**
 * Class GracefulNotFoundHandler
 */
class GracefulNotFoundHandler extends ExceptionHandler
{
    /**
     * Render an exception into an HTTP response.
     *
     * @param Request   $request
     * @param Exception $exception
     *
     *
     * @return mixed
     */
    public function render($request, Exception $exception)
    {
        $route = $request->route();
        if (null === $route) {
            return parent::render($request, $exception);
        }
        $name = $route->getName();
        if (!auth()->check()) {
            return parent::render($request, $exception);
        }

        switch ($name) {
            default:
                Log::error(sprintf('GracefulNotFoundHandler cannot handle route with name "%s"', $name));

                return parent::render($request, $exception);
            case 'accounts.show':
                return $this->handleAccount($request, $exception);
            case 'transactions.show':
                return $this->handleGroup($request, $exception);
                break;
            case 'attachments.show':
            case 'attachments.edit':
                // redirect to original attachment holder.
                return $this->handleAttachment($request, $exception);
                break;
            case 'bills.show':
                $request->session()->reflash();

                return redirect(route('bills.index'));
                break;
            case 'currencies.show':
                $request->session()->reflash();

                return redirect(route('currencies.index'));
                break;
            case 'budgets.show':
                $request->session()->reflash();

                return redirect(route('budgets.index'));
                break;
            case 'piggy-banks.show':
                $request->session()->reflash();

                return redirect(route('piggy-banks.index'));
                break;
            case 'recurring.show':
                $request->session()->reflash();

                return redirect(route('recurring.index'));
                break;
            case 'tags.show.all':
            case 'tags.show':
                $request->session()->reflash();

                return redirect(route('tags.index'));
                break;
            case 'categories.show':
                $request->session()->reflash();

                return redirect(route('categories.index'));
                break;
            case 'rules.edit':
                $request->session()->reflash();

                return redirect(route('rules.index'));
                break;
            case 'transactions.mass.edit':
            case 'transactions.mass.delete':
            case 'transactions.bulk.edit':
                $request->session()->reflash();

                return redirect(route('index'));
                break;
        }
    }

    /**
     * @param Request   $request
     * @param Exception $exception
     *
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
     */
    private function handleAccount($request, Exception $exception)
    {
        Log::debug('404 page is probably a deleted account. Redirect to overview of account types.');
        /** @var User $user */
        $user      = auth()->user();
        $route     = $request->route();
        $accountId = (int)$route->parameter('account');
        /** @var Account $account */
        $account = $user->accounts()->with(['accountType'])->withTrashed()->find($accountId);
        if (null === $account) {
            Log::error(sprintf('Could not find account %d, so give big fat error.', $accountId));

            return parent::render($request, $exception);
        }
        $type      = $account->accountType;
        $shortType = config(sprintf('firefly.shortNamesByFullName.%s', $type->type));
        $request->session()->reflash();

        return redirect(route('accounts.index', [$shortType]));
    }

    private function handleAttachment(Request $request, Exception $exception)
    {
        Log::debug('404 page is probably a deleted attachment. Redirect to parent object.');
        /** @var User $user */
        $user         = auth()->user();
        $route        = $request->route();
        $attachmentId = (int)$route->parameter('attachment');
        /** @var Attachment $attachment */
        $attachment = $user->attachments()->withTrashed()->find($attachmentId);
        if (null === $attachment) {
            Log::error(sprintf('Could not find attachment %d, so give big fat error.', $attachmentId));

            return parent::render($request, $exception);
        }
        // get bindable.
        if (TransactionJournal::class === $attachment->attachable_type) {
            // is linked to journal, get group of journal (if not also deleted)
            /** @var TransactionJournal $journal */
            $journal = $user->transactionJournals()->withTrashed()->find($attachment->attachable_id);
            if (null !== $journal) {
                return redirect(route('transactions.show', [$journal->transaction_group_id]));
            }

        }
        if (Bill::class === $attachment->attachable_type) {
            // is linked to bill.
            /** @var Bill $bill */
            $bill = $user->bills()->withTrashed()->find($attachment->attachable_id);
            if (null !== $bill) {
                return redirect(route('bills.show', [$bill->id]));
            }
        }

        Log::error(sprintf('Could not redirect attachment %d, its linked to a %s.', $attachmentId, $attachment->attachable_type));

        return parent::render($request, $exception);
    }

    /**
     * @param           $request
     * @param Exception $exception
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector|\Symfony\Component\HttpFoundation\Response
     */
    private function handleGroup($request, Exception $exception)
    {
        Log::debug('404 page is probably a deleted group. Redirect to overview of group types.');
        /** @var User $user */
        $user    = auth()->user();
        $route   = $request->route();
        $groupId = (int)$route->parameter('transactionGroup');

        /** @var TransactionGroup $group */
        $group = $user->transactionGroups()->withTrashed()->find($groupId);
        if (null === $group) {
            Log::error(sprintf('Could not find group %d, so give big fat error.', $groupId));

            return parent::render($request, $exception);
        }
        /** @var TransactionJournal $journal */
        $journal = $group->transactionJournals()->withTrashed()->first();
        if (null === $journal) {
            Log::error(sprintf('Could not find journal for group %d, so give big fat error.', $groupId));

            return parent::render($request, $exception);
        }
        $type = $journal->transactionType->type;
        $request->session()->reflash();

        return redirect(route('transactions.index', [strtolower($type)]));

    }

}
