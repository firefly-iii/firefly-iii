<?php
/**
 * SimpleJournalList.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

declare(strict_types=1);

namespace FireflyIII\Support\Binder;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class SimpleJournalList
 */
class SimpleJournalList implements BinderInterface
{
    /**
     * @param string $value
     * @param Route  $route
     *
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public static function routeBinder(string $value, Route $route): Collection
    {
        if (auth()->check()) {
            $list = array_unique(array_map('\intval', explode(',', $value)));
            if (0 === \count($list)) {
                throw new NotFoundHttpException; // @codeCoverageIgnore
            }

            // prep some vars
            $messages = [];
            $final    = new Collection;
            /** @var JournalRepositoryInterface $repository */
            $repository = app(JournalRepositoryInterface::class);

            // get all journals:
            /** @var \Illuminate\Support\Collection $collection */
            $collection = auth()->user()->transactionJournals()
                                ->whereIn('transaction_journals.id', $list)
                                ->where('transaction_journals.completed', 1)
                                ->get(['transaction_journals.*']);

            // filter the list! Yay!
            /** @var TransactionJournal $journal */
            foreach ($collection as $journal) {
                $sources      = $repository->getJournalSourceAccounts($journal);
                $destinations = $repository->getJournalDestinationAccounts($journal);
                if ($sources->count() > 1) {
                    $messages[] = (string)trans('firefly.cannot_edit_multiple_source', ['description' => $journal->description, 'id' => $journal->id]);
                    continue;
                }

                if ($destinations->count() > 1) {
                    $messages[] = (string)trans('firefly.cannot_edit_multiple_dest', ['description' => $journal->description, 'id' => $journal->id]);
                    continue;
                }
                if (TransactionType::OPENING_BALANCE === $repository->getTransactionType($journal)) {
                    $messages[] = (string)trans('firefly.cannot_edit_opening_balance');
                    continue;
                }

                // cannot edit reconciled transactions / journals:
                if ($repository->isJournalReconciled($journal)) {
                    $messages[] = (string)trans('firefly.cannot_edit_reconciled', ['description' => $journal->description, 'id' => $journal->id]);
                    continue;
                }

                $final->push($journal);
            }

            if ($final->count() > 0) {
                if (\count($messages) > 0) {
                    session()->flash('info', $messages);
                }

                return $final;
            }
        }
        throw new NotFoundHttpException;
    }
}
