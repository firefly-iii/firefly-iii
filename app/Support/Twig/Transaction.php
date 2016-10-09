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
            $this->transactionSourceAccount(),
            $this->transactionDestinationAccount(),
            $this->optionalJournalAmount(),
            $this->transactionBudgets(),
            $this->transactionCategories(),
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
                return ' (' . Amount::formatWithCode($code, $amount, true) . ')';
            }

            return '';


        }, ['is_safe' => ['html']]
        );
    }

    public function splitJournalIndicator(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'splitJournalIndicator', function (int $journalId) {
            $count = TransactionModel::where('transaction_journal_id', $journalId)->whereNull('deleted_at')->count();
            if ($count > 2) {
                return '<i class="fa fa-fw fa-share-alt-square" aria-hidden="true"></i>';
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

            return '<a title="' . e($name) . '" href="' . route('accounts.show', [$id]) . '">' . e($name) . '</a>';

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

            $name = intval($transaction->account_encrypted) === 1 ? Crypt::decrypt($transaction->account_name) : $transaction->account_name;
            $id   = intval($transaction->account_id);
            $type = $transaction->account_type;
            // if the amount is negative, assume that the current account (the one in $transaction) is indeed the source account.

            if (bccomp($transaction->transaction_amount, '0') === 1) {
                // if the amount is positive, find the opposing account and use that one:
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

            return '<a title="' . e($name) . '" href="' . route('accounts.show', [$id]) . '">' . e($name) . '</a>';

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
                    $txt = '<i class="fa fa-long-arrow-left fa-fw" title="' . trans('firefly.withdrawal') . '"></i>';
                    break;
                case TransactionType::DEPOSIT:
                    $txt = '<i class="fa fa-long-arrow-right fa-fw" title="' . trans('firefly.deposit') . '"></i>';
                    break;
                case TransactionType::TRANSFER:
                    $txt = '<i class="fa fa-fw fa-exchange" title="' . trans('firefly.transfer') . '"></i>';
                    break;
                case TransactionType::OPENING_BALANCE:
                    $txt = '<i class="fa-fw fa fa-ban" title="' . trans('firefly.openingBalance') . '"></i>';
                    break;
                default:
                    $txt = '';
                    break;
            }

            return $txt;
        }, ['is_safe' => ['html']]
        );
    }
}