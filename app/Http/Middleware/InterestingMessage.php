<?php
/**
 * InterestingMessage.php
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

namespace FireflyIII\Http\Middleware;


use Closure;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Http\Request;
use Preferences;

/**
 * Class InterestingMessage
 */
class InterestingMessage
{
    /**
     * Flashes the user an interesting message if the URL parameters warrant it.
     *
     * @param Request  $request
     * @param \Closure $next
     *
     * @return mixed
     *
     */
    public function handle(Request $request, Closure $next)
    {
        //Log::debug(sprintf('Interesting Message middleware for URI %s', $request->url()));
        if ($this->testing()) {
            return $next($request);
        }

        if ($this->groupMessage($request)) {
            Preferences::mark();
            $this->handleGroupMessage($request);
        }

        return $next($request);
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    private function groupMessage(Request $request): bool
    {
        // get parameters from request.
        $transactionGroupId = $request->get('transaction_group_id');
        $message            = $request->get('message');

        return null !== $transactionGroupId && null !== $message;
    }

    /**
     * @param Request $request
     */
    private function handleGroupMessage(Request $request): void
    {

        // get parameters from request.
        $transactionGroupId = $request->get('transaction_group_id');
        $message            = $request->get('message');

        // send message about newly created transaction group.
        /** @var TransactionGroup $group */
        $group = auth()->user()->transactionGroups()->with(['transactionJournals', 'transactionJournals.transactionType'])->find((int)$transactionGroupId);

        if (null === $group) {
            return;
        }

        $count = $group->transactionJournals->count();

        /** @var TransactionJournal $journal */
        $journal = $group->transactionJournals->first();
        if (null === $journal) {
            return;
        }
        $title = $count > 1 ? $group->title : $journal->description;
        if ('created' === $message) {
            session()->flash('success_uri', route('transactions.show', [$transactionGroupId]));
            session()->flash('success', (string)trans('firefly.stored_journal', ['description' => $title]));
        }
        if ('updated' === $message) {
            $type = strtolower($journal->transactionType->type);
            session()->flash('success_uri', route('transactions.show', [$transactionGroupId]));
            session()->flash('success', (string)trans(sprintf('firefly.updated_%s', $type), ['description' => $title]));
        }
    }

    /**
     * @return bool
     */
    private function testing(): bool
    {
        // ignore middleware in test environment.
        return 'testing' === config('app.env') || !auth()->check();
    }
}