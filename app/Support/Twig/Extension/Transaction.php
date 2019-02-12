<?php
/**
 * Transaction.php
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

namespace FireflyIII\Support\Twig\Extension;

use FireflyIII\Models\AccountType;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Transaction as TransactionModel;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Lang;
use Log;
use Twig_Extension;

/**
 * Class Transaction.
 */
class Transaction extends Twig_Extension
{
    /**
     * Can show the amount of a transaction, if that transaction has been collected by the journal collector.
     *
     * @param TransactionModel $transaction
     *
     * @return string
     */
    public function amount(TransactionModel $transaction): string
    {
        // at this point amount is always negative.
        $amount   = bcmul(app('steam')->positive((string)$transaction->transaction_amount), '-1');
        $format   = '%s';
        $coloured = true;

        if (TransactionType::RECONCILIATION === $transaction->transaction_type_type && 1 === bccomp((string)$transaction->transaction_amount, '0')) {
            $amount = bcmul($amount, '-1');
        }

        if (TransactionType::DEPOSIT === $transaction->transaction_type_type) {
            $amount = bcmul($amount, '-1');
        }

        if (TransactionType::TRANSFER === $transaction->transaction_type_type) {
            $amount   = app('steam')->positive($amount);
            $coloured = false;
            $format   = '<span class="text-info">%s</span>';
        }
        if (TransactionType::OPENING_BALANCE === $transaction->transaction_type_type) {
            $amount = (string)$transaction->transaction_amount;
        }

        $currency                 = new TransactionCurrency;
        $currency->symbol         = $transaction->transaction_currency_symbol;
        $currency->decimal_places = $transaction->transaction_currency_dp;
        $str                      = sprintf($format, app('amount')->formatAnything($currency, $amount, $coloured));

        if (null !== $transaction->transaction_foreign_amount) {
            $amount = bcmul(app('steam')->positive((string)$transaction->transaction_foreign_amount), '-1');
            if (TransactionType::DEPOSIT === $transaction->transaction_type_type) {
                $amount = bcmul($amount, '-1');
            }

            if (TransactionType::TRANSFER === $transaction->transaction_type_type) {
                $amount   = app('steam')->positive($amount);
                $coloured = false;
                $format   = '<span class="text-info">%s</span>';
            }

            $currency                 = new TransactionCurrency;
            $currency->symbol         = $transaction->foreign_currency_symbol;
            $currency->decimal_places = $transaction->foreign_currency_dp;
            $str                      .= ' (' . sprintf($format, app('amount')->formatAnything($currency, $amount, $coloured)) . ')';
        }

        return $str;
    }

    /**
     * @param array $transaction
     *
     * @return string
     */
    public function amountArray(array $transaction): string
    {
        // first display amount:
        $amount                       = (string)$transaction['amount'];
        $fakeCurrency                 = new TransactionCurrency;
        $fakeCurrency->decimal_places = $transaction['currency_decimal_places'];
        $fakeCurrency->symbol         = $transaction['currency_symbol'];
        $string                       = app('amount')->formatAnything($fakeCurrency, $amount, true);

        // then display (if present) the foreign amount:
        if (null !== $transaction['foreign_amount']) {
            $amount                       = (string)$transaction['foreign_amount'];
            $fakeCurrency                 = new TransactionCurrency;
            $fakeCurrency->decimal_places = $transaction['foreign_currency_decimal_places'];
            $fakeCurrency->symbol         = $transaction['foreign_currency_symbol'];
            $string                       .= ' (' . app('amount')->formatAnything($fakeCurrency, $amount, true) . ')';
        }

        return $string;
    }

    /**
     *
     * @param TransactionModel $transaction
     *
     * @return string
     */
    public function budgets(TransactionModel $transaction): string
    {
        $txt = '';
        // journal has a budget:
        if (null !== $transaction->transaction_journal_budget_id) {
            $name = $transaction->transaction_journal_budget_name;
            $txt  = sprintf('<a href="%s" title="%s">%s</a>', route('budgets.show', [$transaction->transaction_journal_budget_id]), $name, $name);
        }

        // transaction has a budget
        if (null !== $transaction->transaction_budget_id && '' === $txt) {
            $name = $transaction->transaction_budget_name;
            $txt  = sprintf('<a href="%s" title="%s">%s</a>', route('budgets.show', [$transaction->transaction_budget_id]), $name, $name);
        }

        if ('' === $txt) {
            // see if the transaction has a budget:
            $budgets = $transaction->budgets()->get();
            if (0 === $budgets->count()) {
                $budgets = $transaction->transactionJournal()->first()->budgets()->get();
            }
            if ($budgets->count() > 0) {
                $str = [];
                foreach ($budgets as $budget) {
                    $str[] = sprintf('<a href="%s" title="%s">%s</a>', route('budgets.show', [$budget->id]), $budget->name, $budget->name);
                }
                $txt = implode(', ', $str);
            }
        }

        return $txt;
    }

    /**
     * @param TransactionModel $transaction
     *
     * @return string
     */
    public function categories(TransactionModel $transaction): string
    {
        $txt = '';
        // journal has a category:
        if (null !== $transaction->transaction_journal_category_id) {
            $name = $transaction->transaction_journal_category_name;
            $txt  = sprintf('<a href="%s" title="%s">%s</a>', route('categories.show', [$transaction->transaction_journal_category_id]), $name, $name);
        }

        // transaction has a category:
        if (null !== $transaction->transaction_category_id && '' === $txt) {
            $name = $transaction->transaction_category_name;
            $txt  = sprintf('<a href="%s" title="%s">%s</a>', route('categories.show', [$transaction->transaction_category_id]), $name, $name);
        }

        if ('' === $txt) {
            // see if the transaction has a category:
            $categories = $transaction->categories()->get();
            if (0 === $categories->count()) {
                $categories = $transaction->transactionJournal()->first()->categories()->get();
            }
            if ($categories->count() > 0) {
                $str = [];
                foreach ($categories as $category) {
                    $str[] = sprintf('<a href="%s" title="%s">%s</a>', route('categories.show', [$category->id]), $category->name, $category->name);
                }

                $txt = implode(', ', $str);
            }
        }

        return $txt;
    }

    /**
     * @param TransactionModel $transaction
     *
     * @return string
     */
    public function description(TransactionModel $transaction): string
    {
        $description = $transaction->description;
        if ('' !== (string)$transaction->transaction_description) {
            $description = $transaction->transaction_description . ' (' . $transaction->description . ')';
        }

        return $description;
    }

    /**
     * @param TransactionModel $transaction
     *
     * @return string
     */
    public function destinationAccount(TransactionModel $transaction): string
    {
        if (TransactionType::RECONCILIATION === $transaction->transaction_type_type) {
            return '&mdash;';
        }

        $name          = $transaction->account_name;
        $iban          = $transaction->account_iban;
        $transactionId = (int)$transaction->account_id;
        $type          = $transaction->account_type;

        // name is present in object, use that one:
        if (null !== $transaction->opposing_account_id && bccomp($transaction->transaction_amount, '0') === -1) {
            $name          = $transaction->opposing_account_name;
            $transactionId = (int)$transaction->opposing_account_id;
            $type          = $transaction->opposing_account_type;
            $iban          = $transaction->opposing_account_iban;
        }

        // Find the opposing account and use that one:
        if (null === $transaction->opposing_account_id && bccomp($transaction->transaction_amount, '0') === -1) {
            // if the amount is negative, find the opposing account and use that one:
            $journalId = $transaction->journal_id;
            /** @var TransactionModel $other */
            $other = TransactionModel
                ::where('transaction_journal_id', $journalId)
                ->where('transactions.id', '!=', $transaction->id)
                ->where('amount', '=', bcmul($transaction->transaction_amount, '-1'))
                ->where('identifier', $transaction->identifier)
                ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                ->first(['transactions.account_id', 'accounts.encrypted', 'accounts.name', 'account_types.type']);
            if (null === $other) {
                Log::error(sprintf('Cannot find other transaction for journal #%d', $journalId));

                return '';
            }
            $name          = $other->name;
            $transactionId = $other->account_id;
            $type          = $other->type;
        }

        if (AccountType::CASH === $type) {
            $txt = '<span class="text-success">(' . trans('firefly.cash') . ')</span>';

            return $txt;
        }

        $txt = sprintf('<a title="%3$s" href="%2$s">%1$s</a>', e($name), route('accounts.show', [$transactionId]), $iban);

        return $txt;
    }

    /**
     * @param TransactionModel $transaction
     *
     * @return string
     */
    public function hasAttachments(TransactionModel $transaction): string
    {
        $res = '';
        if (\is_int($transaction->attachmentCount) && $transaction->attachmentCount > 0) {
            $res = sprintf(
                '<i class="fa fa-paperclip" title="%s"></i>', Lang::choice(
                'firefly.nr_of_attachments',
                $transaction->attachmentCount, ['count' => $transaction->attachmentCount]
            )
            );
        }
        if (null === $transaction->attachmentCount) {
            $journalId = (int)$transaction->journal_id;
            $count     = Attachment::whereNull('deleted_at')
                                   ->where('attachable_type', TransactionJournal::class)
                                   ->where('attachable_id', $journalId)
                                   ->count();
            if ($count > 0) {
                $res = sprintf('<i class="fa fa-paperclip" title="%s"></i>', Lang::choice('firefly.nr_of_attachments', $count, ['count' => $count]));
            }
        }

        return $res;
    }

    /**
     * @param TransactionModel $transaction
     *
     * @return string
     */
    public function icon(TransactionModel $transaction): string
    {
        switch ($transaction->transaction_type_type) {
            case TransactionType::WITHDRAWAL:
                $txt = sprintf('<i class="fa fa-long-arrow-left fa-fw" title="%s"></i>', (string)trans('firefly.withdrawal'));
                break;
            case TransactionType::DEPOSIT:
                $txt = sprintf('<i class="fa fa-long-arrow-right fa-fw" title="%s"></i>', (string)trans('firefly.deposit'));
                break;
            case TransactionType::TRANSFER:
                $txt = sprintf('<i class="fa fa-fw fa-exchange" title="%s"></i>', (string)trans('firefly.transfer'));
                break;
            case TransactionType::OPENING_BALANCE:
                $txt = sprintf('<i class="fa-fw fa fa-star-o" title="%s"></i>', (string)trans('firefly.opening_balance'));
                break;
            case TransactionType::RECONCILIATION:
                $txt = sprintf('<i class="fa-fw fa fa-calculator" title="%s"></i>', (string)trans('firefly.reconciliation_transaction'));
                break;
            default:
                $txt = '';
                break;
        }

        return $txt;
    }

    /**
     * @param TransactionModel $transaction
     *
     * @return string
     */
    public function isReconciled(TransactionModel $transaction): string
    {
        $icon = '';
        if (1 === (int)$transaction->reconciled) {
            $icon = '<i class="fa fa-check"></i>';
        }

        return $icon;
    }

    /**
     * Returns an icon when the transaction is a split transaction.
     *
     * @param TransactionModel $transaction
     *
     * @return string
     */
    public function isSplit(TransactionModel $transaction): string
    {
        $res = '';
        if (true === $transaction->is_split) {
            $res = '<i class="fa fa-fw fa-share-alt" aria-hidden="true"></i>';
        }

        if (null === $transaction->is_split) {
            $journalId = (int)$transaction->journal_id;
            $count     = TransactionModel::where('transaction_journal_id', $journalId)->whereNull('deleted_at')->count();
            if ($count > 2) {
                $res = '<i class="fa fa-fw fa-share-alt" aria-hidden="true"></i>';
            }
        }

        return $res;
    }

    /**
     * @param TransactionModel $transaction
     *
     * @return string
     */
    public function sourceAccount(TransactionModel $transaction): string
    {
        if (TransactionType::RECONCILIATION === $transaction->transaction_type_type) {
            return '&mdash;';
        }

        // if the amount is negative, assume that the current account (the one in $transaction) is indeed the source account.
        $name          = $transaction->account_name;
        $transactionId = (int)$transaction->account_id;
        $type          = $transaction->account_type;
        $iban          = $transaction->account_iban;

        // name is present in object, use that one:
        if (null !== $transaction->opposing_account_id && 1 === bccomp($transaction->transaction_amount, '0')) {
            $name          = $transaction->opposing_account_name;
            $transactionId = (int)$transaction->opposing_account_id;
            $type          = $transaction->opposing_account_type;
            $iban          = $transaction->opposing_account_iban;
        }
        // Find the opposing account and use that one:
        if (null === $transaction->opposing_account_id && 1 === bccomp($transaction->transaction_amount, '0')) {
            $journalId = $transaction->journal_id;
            /** @var TransactionModel $other */
            $other         = TransactionModel::where('transaction_journal_id', $journalId)->where('transactions.id', '!=', $transaction->id)
                                             ->where('amount', '=', bcmul($transaction->transaction_amount, '-1'))->where(
                    'identifier',
                    $transaction->identifier
                )
                                             ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                                             ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                                             ->first(['transactions.account_id', 'accounts.encrypted', 'accounts.name', 'account_types.type']);
            $name          = $other->name;
            $transactionId = $other->account_id;
            $type          = $other->type;
        }

        if (AccountType::CASH === $type) {
            $txt = '<span class="text-success">(' . trans('firefly.cash') . ')</span>';

            return $txt;
        }

        $txt = sprintf('<a title="%3$s" href="%2$s">%1$s</a>', e($name), route('accounts.show', [$transactionId]), $iban);

        return $txt;
    }
}
