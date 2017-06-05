<?php
/**
 * SplitController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Transaction;


use ExpandedForm;
use FireflyIII\Events\UpdatedTransactionJournal;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalTaskerInterface;
use FireflyIII\Repositories\Journal\JournalUpdateInterface;
use Illuminate\Http\Request;
use Log;
use Preferences;
use Session;
use Steam;
use View;

/**
 * Class SplitController
 *
 * @package FireflyIII\Http\Controllers\Transaction
 *
 */
class SplitController extends Controller
{

    /** @var  AccountRepositoryInterface */
    private $accounts;

    /** @var AttachmentHelperInterface */
    private $attachments;

    /** @var  BudgetRepositoryInterface */
    private $budgets;

    /** @var CurrencyRepositoryInterface */
    private $currencies;

    /** @var JournalTaskerInterface */
    private $tasker;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();


        // some useful repositories:
        $this->middleware(
            function ($request, $next) {
                $this->accounts    = app(AccountRepositoryInterface::class);
                $this->budgets     = app(BudgetRepositoryInterface::class);
                $this->tasker      = app(JournalTaskerInterface::class);
                $this->attachments = app(AttachmentHelperInterface::class);
                $this->currencies  = app(CurrencyRepositoryInterface::class);
                View::share('mainTitleIcon', 'fa-share-alt');
                View::share('title', trans('firefly.split-transactions'));

                return $next($request);
            }
        );
    }

    /**
     * @param Request            $request
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function edit(Request $request, TransactionJournal $journal)
    {
        if ($this->isOpeningBalance($journal)) {
            return $this->redirectToAccount($journal);
        }

        $uploadSize     = min(Steam::phpBytes(ini_get('upload_max_filesize')), Steam::phpBytes(ini_get('post_max_size')));
        $currencies     = $this->currencies->get();
        $assetAccounts  = ExpandedForm::makeSelectList($this->accounts->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]));
        $optionalFields = Preferences::get('transaction_journal_optional_fields', [])->data;
        $budgets        = ExpandedForm::makeSelectListWithEmpty($this->budgets->getActiveBudgets());
        $preFilled      = $this->arrayFromJournal($request, $journal);
        $subTitle       = trans('breadcrumbs.edit_journal', ['description' => $journal->description]);
        $subTitleIcon   = 'fa-pencil';

        Session::flash('gaEventCategory', 'transactions');
        Session::flash('gaEventAction', 'edit-split-' . $preFilled['what']);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('transactions.edit-split.fromUpdate') !== true) {
            $this->rememberPreviousUri('transactions.edit-split.uri');
        }
        Session::forget('transactions.edit-split.fromUpdate');

        return view(
            'transactions.split.edit',
            compact(
                'subTitleIcon', 'currencies', 'optionalFields',
                'preFilled', 'subTitle', 'amount', 'sourceAccounts', 'uploadSize', 'destinationAccounts', 'assetAccounts',
                'budgets', 'journal'
            )
        );
    }


    /**
     * @param Request                $request
     * @param JournalUpdateInterface $updater
     * @param TransactionJournal     $journal
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(Request $request, JournalUpdateInterface $updater, TransactionJournal $journal)
    {
        if ($this->isOpeningBalance($journal)) {
            return $this->redirectToAccount($journal);
        }

        $data    = $this->arrayFromInput($request);
        $journal = $updater->updateSplitJournal($journal, $data);
        /** @var array $files */
        $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
        // save attachments:
        $this->attachments->saveAttachmentsForModel($journal, $files);
        event(new UpdatedTransactionJournal($journal));

        // flash messages
        // @codeCoverageIgnoreStart
        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            Session::flash('info', $this->attachments->getMessages()->get('attachments'));
        }
        // @codeCoverageIgnoreEnd

        $type = strtolower($journal->transactionTypeStr());
        Session::flash('success', strval(trans('firefly.updated_' . $type, ['description' => e($data['journal_description'])])));
        Preferences::mark();

        // @codeCoverageIgnoreStart
        if (intval($request->get('return_to_edit')) === 1) {
            // set value so edit routine will not overwrite URL:
            Session::put('transactions.edit-split.fromUpdate', true);

            return redirect(route('transactions.split.edit', [$journal->id]))->withInput(['return_to_edit' => 1]);
        }
        // @codeCoverageIgnoreEnd

        // redirect to previous URL.
        return redirect($this->getPreviousUri('transactions.edit-split.uri'));
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function arrayFromInput(Request $request): array
    {
        $array = [
            'journal_description'            => $request->get('journal_description'),
            'journal_source_account_id'      => $request->get('journal_source_account_id'),
            'journal_source_account_name'    => $request->get('journal_source_account_name'),
            'journal_destination_account_id' => $request->get('journal_destination_account_id'),
            'what'                           => $request->get('what'),
            'date'                           => $request->get('date'),
            // all custom fields:
            'interest_date'                  => $request->get('interest_date'),
            'book_date'                      => $request->get('book_date'),
            'process_date'                   => $request->get('process_date'),
            'due_date'                       => $request->get('due_date'),
            'payment_date'                   => $request->get('payment_date'),
            'invoice_date'                   => $request->get('invoice_date'),
            'internal_reference'             => $request->get('internal_reference'),
            'notes'                          => $request->get('notes'),
            'tags'                           => explode(',', $request->get('tags')),

            // transactions.
            'transactions'                   => $this->getTransactionDataFromRequest($request),
        ];


        return $array;
    }

    /**
     * @param Request            $request
     * @param TransactionJournal $journal
     *
     * @return array
     */
    private function arrayFromJournal(Request $request, TransactionJournal $journal): array
    {
        $sourceAccounts      = $journal->sourceAccountList();
        $destinationAccounts = $journal->destinationAccountList();
        $array               = [
            'journal_description'            => $request->old('journal_description', $journal->description),
            'journal_amount'                 => $journal->amountPositive(),
            'sourceAccounts'                 => $sourceAccounts,
            'journal_source_account_id'      => $request->old('journal_source_account_id', $sourceAccounts->first()->id),
            'journal_source_account_name'    => $request->old('journal_source_account_name', $sourceAccounts->first()->name),
            'journal_destination_account_id' => $request->old('journal_destination_account_id', $destinationAccounts->first()->id),
            'destinationAccounts'            => $destinationAccounts,
            'what'                           => strtolower($journal->transactionTypeStr()),
            'date'                           => $request->old('date', $journal->date),
            'tags'                           => join(',', $journal->tags->pluck('tag')->toArray()),

            // all custom fields:
            'interest_date'                  => $request->old('interest_date', $journal->getMeta('interest_date')),
            'book_date'                      => $request->old('book_date', $journal->getMeta('book_date')),
            'process_date'                   => $request->old('process_date', $journal->getMeta('process_date')),
            'due_date'                       => $request->old('due_date', $journal->getMeta('due_date')),
            'payment_date'                   => $request->old('payment_date', $journal->getMeta('payment_date')),
            'invoice_date'                   => $request->old('invoice_date', $journal->getMeta('invoice_date')),
            'internal_reference'             => $request->old('internal_reference', $journal->getMeta('internal_reference')),
            'notes'                          => $request->old('notes', $journal->getMeta('notes')),

            // transactions.
            'transactions'                   => $this->getTransactionDataFromJournal($journal),
        ];

        return $array;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return array
     */
    private function getTransactionDataFromJournal(TransactionJournal $journal): array
    {
        $transactions = $this->tasker->getTransactionsOverview($journal);
        $return       = [];
        /** @var array $transaction */
        foreach ($transactions as $index => $transaction) {
            $set = [
                'description'                 => $transaction['description'],
                'source_account_id'           => $transaction['source_account_id'],
                'source_account_name'         => $transaction['source_account_name'],
                'destination_account_id'      => $transaction['destination_account_id'],
                'destination_account_name'    => $transaction['destination_account_name'],
                'amount'                      => round($transaction['destination_amount'], 12),
                'budget_id'                   => isset($transaction['budget_id']) ? intval($transaction['budget_id']) : 0,
                'category'                    => $transaction['category'],
                'transaction_currency_id'     => $transaction['transaction_currency_id'],
                'transaction_currency_code'   => $transaction['transaction_currency_code'],
                'transaction_currency_symbol' => $transaction['transaction_currency_symbol'],
                'foreign_amount'              => round($transaction['foreign_destination_amount'], 12),
                'foreign_currency_id'         => $transaction['foreign_currency_id'],
                'foreign_currency_code'       => $transaction['foreign_currency_code'],
                'foreign_currency_symbol'     => $transaction['foreign_currency_symbol'],

            ];

            // set initial category and/or budget:
            if (count($transactions) === 1 && $index === 0) {
                $set['budget_id'] = $journal->budgetId();
                $set['category']  = $journal->categoryAsString();
            }

            $return[] = $set;

        }

        return $return;
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function getTransactionDataFromRequest(Request $request): array
    {
        $return       = [];
        $transactions = $request->get('transactions');
        foreach ($transactions as $transaction) {

            $return[] = [
                'description'              => $transaction['description'],
                'source_account_id'        => $transaction['source_account_id'] ?? 0,
                'source_account_name'      => $transaction['source_account_name'] ?? '',
                'destination_account_id'   => $transaction['destination_account_id'] ?? 0,
                'destination_account_name' => $transaction['destination_account_name'] ?? '',
                'amount'                   => round($transaction['amount'] ?? 0, 12),
                'foreign_amount'           => !isset($transaction['foreign_amount']) ? null : round($transaction['foreign_amount'] ?? 0, 12),
                'budget_id'                => isset($transaction['budget_id']) ? intval($transaction['budget_id']) : 0,
                'category'                 => $transaction['category'] ?? '',
                'transaction_currency_id'  => intval($transaction['transaction_currency_id']),
                'foreign_currency_id'      => $transaction['foreign_currency_id'] ?? null,

            ];
        }
        Log::debug(sprintf('Found %d splits in request data.', count($return)));

        return $return;
    }


}
