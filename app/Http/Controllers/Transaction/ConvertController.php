<?php
/**
 * ConvertController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers\Transaction;

use FireflyIII\Events\UpdatedTransactionJournal;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Http\Controllers\ModelInformation;
use Illuminate\Http\Request;
use Log;
use View;

/**
 * Class ConvertController.
 */
class ConvertController extends Controller
{
    use ModelInformation;

    /** @var JournalRepositoryInterface Journals and transactions overview */
    private $repository;

    /**
     * ConvertController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // some useful repositories:
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(JournalRepositoryInterface::class);

                app('view')->share('title', (string)trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-exchange');

                return $next($request);
            }
        );
    }


    /**
     * Show overview of a to be converted transaction.
     *
     * @param TransactionType    $destinationType
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function index(TransactionType $destinationType, TransactionJournal $journal)
    {
        // @codeCoverageIgnoreStart
        if ($this->isOpeningBalance($journal)) {
            Log::debug('This is an opening balance.');

            return $this->redirectToAccount($journal);
        }
        // @codeCoverageIgnoreEnd
        $positiveAmount = $this->repository->getJournalTotal($journal);
        $sourceType     = $journal->transactionType;
        $subTitle       = (string)trans('firefly.convert_to_' . $destinationType->type, ['description' => $journal->description]);
        $subTitleIcon   = 'fa-exchange';

        if ($sourceType->type === $destinationType->type) { // cannot convert to its own type.
            Log::debug('This is already a transaction of the expected type..');
            session()->flash('info', (string)trans('firefly.convert_is_already_type_' . $destinationType->type));

            return redirect(route('transactions.show', [$journal->id]));
        }

        if ($journal->transactions()->count() > 2) { // cannot convert split.
            Log::info('This journal has more than two transactions.');
            session()->flash('error', (string)trans('firefly.cannot_convert_split_journal'));

            return redirect(route('transactions.show', [$journal->id]));
        }

        // get source and destination account:
        $sourceAccount      = $this->repository->getJournalSourceAccounts($journal)->first();
        $destinationAccount = $this->repository->getJournalDestinationAccounts($journal)->first();

        return view(
            'transactions.convert', compact(
                                      'sourceType', 'destinationType', 'journal', 'positiveAmount', 'sourceAccount', 'destinationAccount', 'sourceType',
                                      'subTitle', 'subTitleIcon'
                                  )
        );
    }


    /**
     * Do the conversion.
     *
     * @param Request            $request
     * @param TransactionType    $destinationType
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @throws FireflyException
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function postIndex(Request $request, TransactionType $destinationType, TransactionJournal $journal)
    {
        // @codeCoverageIgnoreStart
        if ($this->isOpeningBalance($journal)) {
            Log::debug('Journal is opening balance, return to account.');

            return $this->redirectToAccount($journal);
        }
        // @codeCoverageIgnoreEnd

        $data = $request->all();

        if ($journal->transactionType->type === $destinationType->type) {
            Log::info('Journal is already of the desired type.');
            session()->flash('error', (string)trans('firefly.convert_is_already_type_' . $destinationType->type));

            return redirect(route('transactions.show', [$journal->id]));
        }

        if ($journal->transactions()->count() > 2) {
            Log::info('Journal has more than two transactions.');
            session()->flash('error', (string)trans('firefly.cannot_convert_split_journal'));

            return redirect(route('transactions.show', [$journal->id]));
        }

        // get the new source and destination account:
        $source      = $this->getSourceAccount($journal, $destinationType, $data);
        $destination = $this->getDestinationAccount($journal, $destinationType, $data);

        // update the journal:
        $errors = $this->repository->convert($journal, $destinationType, $source, $destination);

        if ($errors->count() > 0) {
            Log::error('Errors while converting: ', $errors->toArray());

            return redirect(route('transactions.convert.index', [strtolower($destinationType->type), $journal->id]))->withErrors($errors)->withInput();
        }

        // Success? Fire rules!
        event(new UpdatedTransactionJournal($journal));


        session()->flash('success', (string)trans('firefly.converted_to_' . $destinationType->type));

        return redirect(route('transactions.show', [$journal->id]));
    }
}
