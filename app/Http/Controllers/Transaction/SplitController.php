<?php
/**
 * SplitController.php
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
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\SplitJournalFormRequest;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Http\Controllers\ModelInformation;
use FireflyIII\Support\Http\Controllers\RequestInformation;
use Illuminate\Http\Request;
use View;

/**
 * Class SplitController.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SplitController extends Controller
{
    use ModelInformation, RequestInformation;

    /** @var AttachmentHelperInterface Attachment helper */
    private $attachments;

    /** @var BudgetRepositoryInterface The budget repository */
    private $budgets;

    /** @var CurrencyRepositoryInterface The currency repository */
    private $currencies;
    /** @var JournalRepositoryInterface Journals and transactions overview */
    private $repository;

    /**
     * SplitController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // some useful repositories:
        $this->middleware(
            function ($request, $next) {
                $this->budgets     = app(BudgetRepositoryInterface::class);
                $this->attachments = app(AttachmentHelperInterface::class);
                $this->currencies  = app(CurrencyRepositoryInterface::class);
                $this->repository  = app(JournalRepositoryInterface::class);
                app('view')->share('mainTitleIcon', 'fa-share-alt');
                app('view')->share('title', (string)trans('firefly.split-transactions'));

                return $next($request);
            }
        );
    }

    /**
     * Edit a split.
     *
     * @param Request            $request
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     * @throws FireflyException
     */
    public function edit(Request $request, TransactionJournal $journal)
    {
        if ($this->isOpeningBalance($journal)) {
            return $this->redirectToAccount($journal); // @codeCoverageIgnore
        }
        // basic fields:
        $uploadSize   = min(app('steam')->phpBytes(ini_get('upload_max_filesize')), app('steam')->phpBytes(ini_get('post_max_size')));
        $subTitle     = (string)trans('breadcrumbs.edit_journal', ['description' => $journal->description]);
        $subTitleIcon = 'fa-pencil';

        // lists and collections
        $currencies = $this->currencies->get();
        $budgets    = app('expandedform')->makeSelectListWithEmpty($this->budgets->getActiveBudgets());

        // other fields
        $optionalFields = app('preferences')->get('transaction_journal_optional_fields', [])->data;
        $preFilled      = $this->arrayFromJournal($request, $journal);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('transactions.edit-split.fromUpdate')) {
            $this->rememberPreviousUri('transactions.edit-split.uri');
        }
        session()->forget('transactions.edit-split.fromUpdate');

        return view(
            'transactions.split.edit', compact(
                                         'subTitleIcon', 'currencies', 'optionalFields', 'preFilled', 'subTitle', 'uploadSize', 'budgets',
                                         'journal'
                                     )
        );
    }

    /**
     * Store new split journal.
     *
     * @param SplitJournalFormRequest $request
     * @param TransactionJournal      $journal
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function update(SplitJournalFormRequest $request, TransactionJournal $journal)
    {
        if ($this->isOpeningBalance($journal)) {
            return $this->redirectToAccount($journal); // @codeCoverageIgnore
        }
        $data = $request->getAll();

        // keep current bill:
        $data['bill_id'] = $journal->bill_id;
        $journal         = $this->repository->update($journal, $data);

        /** @var array $files */
        $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
        // save attachments:
        $this->attachments->saveAttachmentsForModel($journal, $files);
        event(new UpdatedTransactionJournal($journal));

        // flash messages
        // @codeCoverageIgnoreStart
        if (\count($this->attachments->getMessages()->get('attachments')) > 0) {
            session()->flash('info', $this->attachments->getMessages()->get('attachments'));
        }
        // @codeCoverageIgnoreEnd

        $type = strtolower($this->repository->getTransactionType($journal));
        session()->flash('success', (string)trans('firefly.updated_' . $type, ['description' => $journal->description]));
        app('preferences')->mark();

        // @codeCoverageIgnoreStart
        if (1 === (int)$request->get('return_to_edit')) {
            // set value so edit routine will not overwrite URL:
            session()->put('transactions.edit-split.fromUpdate', true);

            return redirect(route('transactions.split.edit', [$journal->id]))->withInput(['return_to_edit' => 1]);
        }
        // @codeCoverageIgnoreEnd

        // redirect to previous URL.
        return redirect($this->getPreviousUri('transactions.edit-split.uri'));
    }


    /**
     * Get info from old input.
     *
     * @param $array
     * @param $old
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function updateWithPrevious($array, $old): array // update object with new info
    {
        if (0 === \count($old) || !isset($old['transactions'])) {
            return $array;
        }
        $old = $old['transactions'];

        foreach ($old as $index => $row) {
            if (isset($array[$index])) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $array[$index] = array_merge($array[$index], $row);
                continue;
            }
            // take some info from first transaction, that should at least exist.
            $array[$index]                            = $row;
            $array[$index]['currency_id']             = $array[0]['currency_id'];
            $array[$index]['currency_code']           = $array[0]['currency_code'] ?? '';
            $array[$index]['currency_symbol']         = $array[0]['currency_symbol'] ?? '';
            $array[$index]['foreign_amount']          = round($array[0]['foreign_destination_amount'] ?? '0', 12);
            $array[$index]['foreign_currency_id']     = $array[0]['foreign_currency_id'];
            $array[$index]['foreign_currency_code']   = $array[0]['foreign_currency_code'];
            $array[$index]['foreign_currency_symbol'] = $array[0]['foreign_currency_symbol'];
        }

        return $array;
    }
}
