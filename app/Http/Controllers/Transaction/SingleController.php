<?php
/**
 * SingleController.php
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

use Carbon\Carbon;
use FireflyIII\Events\StoredTransactionJournal;
use FireflyIII\Events\UpdatedTransactionJournal;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\JournalFormRequest;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Http\Controllers\ModelInformation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Log;
use View;

/**
 * Class SingleController.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SingleController extends Controller
{
    use ModelInformation;

    /** @var AttachmentHelperInterface The attachment helper. */
    private $attachments;
    /** @var BudgetRepositoryInterface The budget repository */
    private $budgets;
    /** @var JournalRepositoryInterface Journals and transactions overview */
    private $repository;

    /**
     * SingleController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $maxFileSize = app('steam')->phpBytes(ini_get('upload_max_filesize'));
        $maxPostSize = app('steam')->phpBytes(ini_get('post_max_size'));
        $uploadSize  = min($maxFileSize, $maxPostSize);
        app('view')->share('uploadSize', $uploadSize);

        // some useful repositories:
        $this->middleware(
            function ($request, $next) {
                $this->budgets     = app(BudgetRepositoryInterface::class);
                $this->attachments = app(AttachmentHelperInterface::class);
                $this->repository  = app(JournalRepositoryInterface::class);

                app('view')->share('title', (string)trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-repeat');

                return $next($request);
            }
        );
    }

    /**
     * CLone a transaction.
     *
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function cloneTransaction(TransactionJournal $journal)
    {
        $source       = $this->repository->getJournalSourceAccounts($journal)->first();
        $destination  = $this->repository->getJournalDestinationAccounts($journal)->first();
        $budgetId     = $this->repository->getJournalBudgetId($journal);
        $categoryName = $this->repository->getJournalCategoryName($journal);
        $tags         = implode(',', $this->repository->getTags($journal));
        /** @var Transaction $transaction */
        $transaction   = $journal->transactions()->first();
        $amount        = app('steam')->positive($transaction->amount);
        $foreignAmount = null === $transaction->foreign_amount ? null : app('steam')->positive($transaction->foreign_amount);

        // make sure previous URI is correct:
        session()->put('transactions.create.fromStore', true);
        session()->put('transactions.create.uri', app('url')->previous());

        $preFilled = [
            'description'               => $journal->description,
            'source_id'                 => $source->id,
            'source_name'               => $source->name,
            'destination_id'            => $destination->id,
            'destination_name'          => $destination->name,
            'amount'                    => $amount,
            'source_amount'             => $amount,
            'destination_amount'        => $foreignAmount,
            'foreign_amount'            => $foreignAmount,
            'native_amount'             => $foreignAmount,
            'amount_currency_id_amount' => $transaction->foreign_currency_id ?? 0,
            'date'                      => (new Carbon())->format('Y-m-d'),
            'budget_id'                 => $budgetId,
            'category'                  => $categoryName,
            'tags'                      => $tags,
            'interest_date'             => $this->repository->getMetaField($journal, 'interest_date'),
            'book_date'                 => $this->repository->getMetaField($journal, 'book_date'),
            'process_date'              => $this->repository->getMetaField($journal, 'process_date'),
            'due_date'                  => $this->repository->getMetaField($journal, 'due_date'),
            'payment_date'              => $this->repository->getMetaField($journal, 'payment_date'),
            'invoice_date'              => $this->repository->getMetaField($journal, 'invoice_date'),
            'internal_reference'        => $this->repository->getMetaField($journal, 'internal_reference'),
            'notes'                     => $this->repository->getNoteText($journal),
        ];

        session()->flash('preFilled', $preFilled);

        return redirect(route('transactions.create', [strtolower($journal->transactionType->type)]));
    }

    /**
     * Create a new journal.
     *
     * @param Request     $request
     * @param string|null $what
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function create(Request $request, string $what = null)
    {
        $what           = strtolower($what ?? TransactionType::DEPOSIT);
        $what           = (string)($request->old('what') ?? $what);
        $budgets        = app('expandedform')->makeSelectListWithEmpty($this->budgets->getActiveBudgets());
        $preFilled      = session()->has('preFilled') ? session('preFilled') : [];
        $subTitle       = (string)trans('form.add_new_' . $what);
        $subTitleIcon   = 'fa-plus';
        $optionalFields = app('preferences')->get('transaction_journal_optional_fields', [])->data;
        $source         = (int)$request->get('source');

        // grab old currency ID from old data:
        $currencyID                             = (int)$request->old('amount_currency_id_amount');
        $preFilled['amount_currency_id_amount'] = $currencyID;

        if (('withdrawal' === $what || 'transfer' === $what) && $source > 0) {
            $preFilled['source_id'] = $source;
        }
        if ('deposit' === $what && $source > 0) {
            $preFilled['destination_id'] = $source;
        }

        session()->put('preFilled', $preFilled);

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('transactions.create.fromStore')) {
            $this->rememberPreviousUri('transactions.create.uri');
        }
        session()->forget('transactions.create.fromStore');

        return view(
            'transactions.single.create',
            compact('subTitleIcon', 'budgets', 'what', 'subTitle', 'optionalFields', 'preFilled')
        );
    }

    /**
     * Show a special JSONified view of a transaction, for easier debug purposes.
     *
     * @param TransactionJournal $journal
     *
     * @codeCoverageIgnore
     * @return JsonResponse
     */
    public function debugShow(TransactionJournal $journal): JsonResponse
    {
        $array                 = $journal->toArray();
        $array['transactions'] = [];
        $array['meta']         = [];

        /** @var Transaction $transaction */
        foreach ($journal->transactions as $transaction) {
            $array['transactions'][] = $transaction->toArray();
        }
        /** @var TransactionJournalMeta $meta */
        foreach ($journal->transactionJournalMeta as $meta) {
            $array['meta'][] = $meta->toArray();
        }

        return response()->json($array);
    }

    /**
     * Shows the form that allows a user to delete a transaction journal.
     *
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function delete(TransactionJournal $journal)
    {
        // Covered by another controller's tests
        // @codeCoverageIgnoreStart
        if ($this->isOpeningBalance($journal)) {
            return $this->redirectToAccount($journal);
        }
        // @codeCoverageIgnoreEnd

        $what     = strtolower($journal->transaction_type_type ?? $journal->transactionType->type);
        $subTitle = (string)trans('firefly.delete_' . $what, ['description' => $journal->description]);

        // put previous url in session
        $this->rememberPreviousUri('transactions.delete.uri');

        return view('transactions.single.delete', compact('journal', 'subTitle', 'what'));
    }

    /**
     * Actually destroys the journal.
     *
     * @param TransactionJournal $transactionJournal
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(TransactionJournal $transactionJournal): RedirectResponse
    {
        // @codeCoverageIgnoreStart
        if ($this->isOpeningBalance($transactionJournal)) {
            return $this->redirectToAccount($transactionJournal);
        }
        // @codeCoverageIgnoreEnd
        $type = $this->repository->getTransactionType($transactionJournal);
        session()->flash('success', (string)trans('firefly.deleted_' . strtolower($type), ['description' => $transactionJournal->description]));

        $this->repository->destroy($transactionJournal);

        app('preferences')->mark();

        return redirect($this->getPreviousUri('transactions.delete.uri'));
    }

    /**
     * Edit a journal.
     *
     * @param TransactionJournal         $journal
     *
     * @param JournalRepositoryInterface $repository
     *
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function edit(TransactionJournal $journal, JournalRepositoryInterface $repository)
    {
        $transactionType = $repository->getTransactionType($journal);

        // redirect to account:
        if ($transactionType === TransactionType::OPENING_BALANCE) {
            return $this->redirectToAccount($journal);
        }
        // redirect to reconcile edit:
        if ($transactionType === TransactionType::RECONCILIATION) {
            return redirect(route('accounts.reconcile.edit', [$journal->id]));
        }

        // redirect to split edit:
        if ($this->isSplitJournal($journal)) {
            return redirect(route('transactions.split.edit', [$journal->id]));
        }

        $what       = strtolower($transactionType);
        $budgetList = app('expandedform')->makeSelectListWithEmpty($this->budgets->getBudgets());

        // view related code
        $subTitle = (string)trans('breadcrumbs.edit_journal', ['description' => $journal->description]);

        // journal related code
        $sourceAccounts      = $repository->getJournalSourceAccounts($journal);
        $destinationAccounts = $repository->getJournalDestinationAccounts($journal);
        $optionalFields      = app('preferences')->get('transaction_journal_optional_fields', [])->data;
        $pTransaction        = $repository->getFirstPosTransaction($journal);
        $foreignCurrency     = $pTransaction->foreignCurrency ?? $pTransaction->transactionCurrency;
        $preFilled           = [
            'date'                 => $repository->getJournalDate($journal, null), //  $journal->dateAsString()
            'interest_date'        => $repository->getJournalDate($journal, 'interest_date'),
            'book_date'            => $repository->getJournalDate($journal, 'book_date'),
            'process_date'         => $repository->getJournalDate($journal, 'process_date'),
            'category'             => $repository->getJournalCategoryName($journal),
            'budget_id'            => $repository->getJournalBudgetId($journal),
            'tags'                 => implode(',', $repository->getTags($journal)),
            'source_id'            => $sourceAccounts->first()->id,
            'source_name'          => $sourceAccounts->first()->edit_name,
            'destination_id'       => $destinationAccounts->first()->id,
            'destination_name'     => $destinationAccounts->first()->edit_name,
            'bill_id'              => $journal->bill_id,
            'bill_name'            => null === $journal->bill_id ? null : $journal->bill->name,

            // new custom fields:
            'due_date'             => $repository->getJournalDate($journal, 'due_date'),
            'payment_date'         => $repository->getJournalDate($journal, 'payment_date'),
            'invoice_date'         => $repository->getJournalDate($journal, 'invoice_date'),
            'interal_reference'    => $repository->getMetaField($journal, 'internal_reference'),
            'notes'                => $repository->getNoteText($journal),

            // amount fields
            'amount'               => $pTransaction->amount,
            'source_amount'        => $pTransaction->amount,
            'native_amount'        => $pTransaction->amount,
            'destination_amount'   => $pTransaction->foreign_amount,
            'currency'             => $pTransaction->transactionCurrency,
            'source_currency'      => $pTransaction->transactionCurrency,
            'native_currency'      => $pTransaction->transactionCurrency,
            'foreign_currency'     => $foreignCurrency,
            'destination_currency' => $foreignCurrency,
        ];

        // amounts for withdrawals and deposits:
        // amount, native_amount, source_amount, destination_amount
        if (null !== $pTransaction->foreign_amount && ($journal->isWithdrawal() || $journal->isDeposit())) {
            $preFilled['amount']   = $pTransaction->foreign_amount;
            $preFilled['currency'] = $pTransaction->foreignCurrency;
        }

        session()->flash('preFilled', $preFilled);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('transactions.edit.fromUpdate')) {
            $this->rememberPreviousUri('transactions.edit.uri');
        }
        session()->forget('transactions.edit.fromUpdate');

        return view(
            'transactions.single.edit',
            compact('journal', 'optionalFields', 'what', 'budgetList', 'subTitle')
        )->with('data', $preFilled);
    }

    /**
     * Stores a new journal.
     *
     * @param JournalFormRequest         $request
     * @param JournalRepositoryInterface $repository
     *
     * @return RedirectResponse
     * @throws \FireflyIII\Exceptions\FireflyException
     *
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function store(JournalFormRequest $request, JournalRepositoryInterface $repository): RedirectResponse
    {
        $doSplit       = 1 === (int)$request->get('split_journal');
        $createAnother = 1 === (int)$request->get('create_another');
        $data          = $request->getJournalData();
        $journal       = $repository->store($data);


        if (null === $journal->id) {
            // error!
            Log::error('Could not store transaction journal.');
            session()->flash('error', (string)trans('firefly.unknown_journal_error'));

            return redirect(route('transactions.create', [$request->input('what')]))->withInput();
        }

        /** @var array $files */
        $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
        $this->attachments->saveAttachmentsForModel($journal, $files);

        // store the journal only, flash the rest.
        Log::debug(sprintf('Count of error messages is %d', $this->attachments->getErrors()->count()));
        if (\count($this->attachments->getErrors()->get('attachments')) > 0) {
            session()->flash('error', $this->attachments->getErrors()->get('attachments'));
        }
        // flash messages
        if (\count($this->attachments->getMessages()->get('attachments')) > 0) {
            session()->flash('info', $this->attachments->getMessages()->get('attachments'));
        }

        event(new StoredTransactionJournal($journal));

        session()->flash('success_uri', route('transactions.show', [$journal->id]));
        session()->flash('success', (string)trans('firefly.stored_journal', ['description' => $journal->description]));
        app('preferences')->mark();

        // @codeCoverageIgnoreStart
        if (true === $createAnother) {
            session()->put('transactions.create.fromStore', true);

            return redirect(route('transactions.create', [$request->input('what')]))->withInput();
        }

        if (true === $doSplit) {
            return redirect(route('transactions.split.edit', [$journal->id]));
        }

        // @codeCoverageIgnoreEnd

        return redirect($this->getPreviousUri('transactions.create.uri'));
    }

    /**
     * Update a journal.
     *
     * @param JournalFormRequest         $request
     * @param JournalRepositoryInterface $repository
     * @param TransactionJournal         $journal
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function update(JournalFormRequest $request, JournalRepositoryInterface $repository, TransactionJournal $journal)
    {
        // @codeCoverageIgnoreStart
        if ($this->isOpeningBalance($journal)) {
            return $this->redirectToAccount($journal);
        }
        // @codeCoverageIgnoreEnd

        $data = $request->getJournalData();

        // keep current bill:
        $data['bill_id'] = $journal->bill_id;

        // remove it if no checkbox:
        if (!$request->boolean('keep_bill_id')) {
            $data['bill_id'] = null;
        }


        $journal = $repository->update($journal, $data);
        /** @var array $files */
        $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
        $this->attachments->saveAttachmentsForModel($journal, $files);

        // @codeCoverageIgnoreStart
        if (\count($this->attachments->getErrors()->get('attachments')) > 0) {
            session()->flash('error', $this->attachments->getErrors()->get('attachments'));
        }
        if (\count($this->attachments->getMessages()->get('attachments')) > 0) {
            session()->flash('info', $this->attachments->getMessages()->get('attachments'));
        }
        // @codeCoverageIgnoreEnd

        event(new UpdatedTransactionJournal($journal));
        // update, get events by date and sort DESC

        $type = strtolower($this->repository->getTransactionType($journal));
        session()->flash('success', (string)trans('firefly.updated_' . $type, ['description' => $data['description']]));
        app('preferences')->mark();

        // @codeCoverageIgnoreStart
        if (1 === (int)$request->get('return_to_edit')) {
            session()->put('transactions.edit.fromUpdate', true);

            return redirect(route('transactions.edit', [$journal->id]))->withInput(['return_to_edit' => 1]);
        }
        // @codeCoverageIgnoreEnd

        // redirect to previous URL.
        return redirect($this->getPreviousUri('transactions.edit.uri'));
    }
}
