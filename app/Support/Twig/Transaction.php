<?php
/**
 * Transaction.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Support\Twig;

use Amount;
use Crypt;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction as TransactionModel;
use FireflyIII\Models\TransactionType;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

/**
 * Class Transaction
 *
 * @package FireflyIII\Support\Twig
 */
class Transaction extends Twig_Extension
{
    /**
     * @return Twig_SimpleFunction
     */
    public function formatAmountPlainWithCode(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'formatAmountPlainWithCode', function (string $amount, string $code): string {

            return Amount::formatWithCode($code, $amount, false);

        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function formatAmountWithCode(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'formatAmountWithCode', function (string $amount, string $code): string {

            return Amount::formatWithCode($code, $amount, true);

        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        $filters = [
            $this->typeIconTransaction(),
        ];

        return $filters;
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        $functions = [
            $this->formatAmountWithCode(),
            $this->formatAmountPlainWithCode(),
            $this->transactionSourceAccount(),
            $this->transactionDestinationAccount(),
            $this->optionalJournalAmount(),
            $this->transactionBudgets(),
            $this->transactionIdBudgets(),
            $this->transactionCategories(),
            $this->transactionIdCategories(),
            $this->splitJournalIndicator(),
        ];

        return $functions;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName(): string
    {
        return 'transaction';
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function optionalJournalAmount(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'optionalJournalAmount', function (int $journalId, string $transactionAmount, string $code, string $type): string {

            $amount = strval(
                TransactionModel
                    ::where('transaction_journal_id', $journalId)
                    ->whereNull('deleted_at')
                    ->where('amount', '<', 0)
                    ->sum('amount')
            );

            if ($type === TransactionType::DEPOSIT || $type === TransactionType::TRANSFER) {
                $amount = bcmul($amount, '-1');
            }

            if (
                bccomp($amount, $transactionAmount) !== 0
                && bccomp($amount, bcmul($transactionAmount, '-1')) !== 0
            ) {
                // not equal?
                return sprintf(' (%s)', Amount::formatWithCode($code, $amount, true));
            }

            return '';


        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function splitJournalIndicator(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'splitJournalIndicator', function (int $journalId) {
            $count = TransactionModel::where('transaction_journal_id', $journalId)->whereNull('deleted_at')->count();
            if ($count > 2) {
                return '<i class="fa fa-fw fa-share-alt" aria-hidden="true"></i>';
            }

            return '';


        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function transactionBudgets(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'transactionBudgets', function (TransactionModel $transaction): string {
            return $this->getTransactionBudgets($transaction);
        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function transactionCategories(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'transactionCategories', function (TransactionModel $transaction): string {
            return $this->getTransactionCategories($transaction);
        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function transactionDestinationAccount(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'transactionDestinationAccount', function (TransactionModel $transaction): string {

            $name = intval($transaction->account_encrypted) === 1 ? Crypt::decrypt($transaction->account_name) : $transaction->account_name;
            $id   = intval($transaction->account_id);
            $type = $transaction->account_type;
            // if the amount is positive, assume that the current account (the one in $transaction) is indeed the destination account.

            if (bccomp($transaction->transaction_amount, '0') === -1) {
                // if the amount is negative, find the opposing account and use that one:
                $journalId = $transaction->journal_id;
                /** @var TransactionModel $other */
                $other = TransactionModel
                    ::where('transaction_journal_id', $journalId)->where('transactions.id', '!=', $transaction->id)
                    ->where('amount', '=', bcmul($transaction->transaction_amount, '-1'))->where('identifier', $transaction->identifier)
                    ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                    ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                    ->first(['transactions.account_id', 'accounts.encrypted', 'accounts.name', 'account_types.type']);
                $name  = intval($other->encrypted) === 1 ? Crypt::decrypt($other->name) : $other->name;
                $id    = $other->account_id;
                $type  = $other->type;
            }

            if ($type === AccountType::CASH) {
                return '<span class="text-success">(cash)</span>';
            }

            return sprintf('<a title="%1$s" href="%2$s">%1$s</a>', e($name), route('accounts.show', [$id]));

        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function transactionIdBudgets(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'transactionIdBudgets', function (int $transactionId): string {
            $transaction = TransactionModel::find($transactionId);

            return $this->getTransactionBudgets($transaction);
        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function transactionIdCategories(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'transactionIdCategories', function (int $transactionId): string {
            $transaction = TransactionModel::find($transactionId);

            return $this->getTransactionCategories($transaction);
        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function transactionSourceAccount(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'transactionSourceAccount', function (TransactionModel $transaction): string {

            // if the amount is negative, assume that the current account (the one in $transaction) is indeed the source account.
            $name = intval($transaction->account_encrypted) === 1 ? Crypt::decrypt($transaction->account_name) : $transaction->account_name;
            $id   = intval($transaction->account_id);
            $type = $transaction->account_type;

            // name is present in object, use that one:
            if (bccomp($transaction->transaction_amount, '0') === 1 && !is_null($transaction->opposing_account_id)) {

                $name = intval($transaction->opposing_account_encrypted) === 1 ? Crypt::decrypt($transaction->opposing_account_name)
                    : $transaction->opposing_account_name;
                $id   = intval($transaction->opposing_account_id);
                $type = intval($transaction->opposing_account_type);
            }
            // Find the opposing account and use that one:
            if (bccomp($transaction->transaction_amount, '0') === 1 && is_null($transaction->opposing_account_id)) {
                $journalId = $transaction->journal_id;
                /** @var TransactionModel $other */
                $other = TransactionModel
                    ::where('transaction_journal_id', $journalId)->where('transactions.id', '!=', $transaction->id)
                    ->where('amount', '=', bcmul($transaction->transaction_amount, '-1'))->where('identifier', $transaction->identifier)
                    ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                    ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                    ->first(['transactions.account_id', 'accounts.encrypted', 'accounts.name', 'account_types.type']);
                $name  = intval($other->encrypted) === 1 ? Crypt::decrypt($other->name) : $other->name;
                $id    = $other->account_id;
                $type  = $other->type;
            }

            if ($type === AccountType::CASH) {
                return '<span class="text-success">(cash)</span>';
            }

            return sprintf('<a title="%1$s" href="%2$s">%1$s</a>', e($name), route('accounts.show', [$id]));

        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // it's 5.
     *
     * @return Twig_SimpleFilter
     */
    public function typeIconTransaction(): Twig_SimpleFilter
    {
        return new Twig_SimpleFilter(
            'typeIconTransaction', function (TransactionModel $transaction): string {

            switch ($transaction->transaction_type_type) {
                case TransactionType::WITHDRAWAL:
                    $txt = sprintf('<i class="fa fa-long-arrow-left fa-fw" title="%s"></i>', trans('firefly.withdrawal'));
                    break;
                case TransactionType::DEPOSIT:
                    $txt = sprintf('<i class="fa fa-long-arrow-right fa-fw" title="%s"></i>', trans('firefly.deposit'));
                    break;
                case TransactionType::TRANSFER:
                    $txt = sprintf('<i class="fa fa-fw fa-exchange" title="%s"></i>', trans('firefly.transfer'));
                    break;
                case TransactionType::OPENING_BALANCE:
                    $txt = sprintf('<i class="fa-fw fa fa-ban" title="%s"></i>', trans('firefly.openingBalance'));
                    break;
                default:
                    $txt = '';
                    break;
            }

            return $txt;
        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @param TransactionModel $transaction
     *
     * @return string
     */
    private function getTransactionBudgets(TransactionModel $transaction): string
    {
        // see if the transaction has a budget:
        $budgets = $transaction->budgets()->get();
        if ($budgets->count() === 0) {
            $budgets = $transaction->transactionJournal()->first()->budgets()->get();
        }
        if ($budgets->count() > 0) {
            $str = [];
            foreach ($budgets as $budget) {
                $str[] = sprintf('<a href="%s" title="%s">%s</a>', route('budgets.show', [$budget->id]), $budget->name, $budget->name);
            }

            return join(', ', $str);
        }


        return '';
    }

    /**
     * @param TransactionModel $transaction
     *
     * @return string
     */
    private function getTransactionCategories(TransactionModel $transaction): string
    {
        // see if the transaction has a category:
        $categories = $transaction->categories()->get();
        if ($categories->count() === 0) {
            $categories = $transaction->transactionJournal()->first()->categories()->get();
        }
        if ($categories->count() > 0) {
            $str = [];
            foreach ($categories as $category) {
                $str[] = sprintf('<a href="%s" title="%s">%s</a>', route('categories.show', [$category->id]), $category->name, $category->name);
            }

            return join(', ', $str);
        }

        return '';
    }
}
