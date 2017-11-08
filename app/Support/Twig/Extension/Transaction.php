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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Twig\Extension;

use FireflyIII\Models\AccountType;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Transaction as TransactionModel;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionType;
use FireflyIII\Support\SingleCacheProperties;
use Lang;
use Twig_Extension;

/**
 * Class Transaction
 *
 * @package FireflyIII\Support\Twig\Extension
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
        $cache = new SingleCacheProperties;
        $cache->addProperty('transaction-amount');
        $cache->addProperty($transaction->id);
        $cache->addProperty($transaction->updated_at);
        if ($cache->has()) {
            return $cache->get();
        }

        $amount   = bcmul(app('steam')->positive(strval($transaction->transaction_amount)), '-1');
        $format   = '%s';
        $coloured = true;

        if ($transaction->transaction_type_type === TransactionType::DEPOSIT) {
            $amount = bcmul($amount, '-1');
        }

        if ($transaction->transaction_type_type === TransactionType::TRANSFER) {
            $amount   = app('steam')->positive($amount);
            $coloured = false;
            $format   = '<span class="text-info">%s</span>';
        }
        if ($transaction->transaction_type_type === TransactionType::OPENING_BALANCE) {
            $amount = strval($transaction->transaction_amount);
        }

        $currency                 = new TransactionCurrency;
        $currency->symbol         = $transaction->transaction_currency_symbol;
        $currency->decimal_places = $transaction->transaction_currency_dp;
        $str                      = sprintf($format, app('amount')->formatAnything($currency, $amount, $coloured));


        if (!is_null($transaction->transaction_foreign_amount)) {
            $amount = bcmul(app('steam')->positive(strval($transaction->transaction_foreign_amount)), '-1');
            if ($transaction->transaction_type_type === TransactionType::DEPOSIT) {
                $amount = bcmul($amount, '-1');
            }


            if ($transaction->transaction_type_type === TransactionType::TRANSFER) {
                $amount   = app('steam')->positive($amount);
                $coloured = false;
                $format   = '<span class="text-info">%s</span>';
            }

            $currency                 = new TransactionCurrency;
            $currency->symbol         = $transaction->foreign_currency_symbol;
            $currency->decimal_places = $transaction->foreign_currency_dp;
            $str                      .= ' (' . sprintf($format, app('amount')->formatAnything($currency, $amount, $coloured)) . ')';
        }
        $cache->store($str);

        return $str;
    }

    /**
     * @param array $transaction
     *
     * @return string
     */
    public function amountArray(array $transaction): string
    {
        $cache = new SingleCacheProperties;
        $cache->addProperty('transaction-array-amount');
        $cache->addProperty($transaction['source_id']);
        $cache->addProperty($transaction['destination_id']);
        $cache->addProperty($transaction['updated_at']);
        if ($cache->has()) {
            return $cache->get();
        }

        // first display amount:
        $amount                       = $transaction['journal_type'] === TransactionType::WITHDRAWAL ? $transaction['source_amount']
            : $transaction['destination_amount'];
        $fakeCurrency                 = new TransactionCurrency;
        $fakeCurrency->decimal_places = $transaction['transaction_currency_dp'];
        $fakeCurrency->symbol         = $transaction['transaction_currency_symbol'];
        $string                       = app('amount')->formatAnything($fakeCurrency, $amount, true);

        // then display (if present) the foreign amount:
        if (!is_null($transaction['foreign_source_amount'])) {
            $amount                       = $transaction['journal_type'] === TransactionType::WITHDRAWAL ? $transaction['foreign_source_amount']
                : $transaction['foreign_destination_amount'];
            $fakeCurrency                 = new TransactionCurrency;
            $fakeCurrency->decimal_places = $transaction['foreign_currency_dp'];
            $fakeCurrency->symbol         = $transaction['foreign_currency_symbol'];
            $string                       .= ' (' . app('amount')->formatAnything($fakeCurrency, $amount, true) . ')';
        }
        $cache->store($string);

        return $string;
    }

    /**
     * @param TransactionModel $transaction
     *
     * @return string
     */
    public function budgets(TransactionModel $transaction): string
    {
        $cache = new SingleCacheProperties;
        $cache->addProperty('transaction-budgets');
        $cache->addProperty($transaction->id);
        $cache->addProperty($transaction->updated_at);
        if ($cache->has()) {
            return $cache->get();
        }

        // journal has a budget:
        if (isset($transaction->transaction_journal_budget_id)) {
            $name = app('steam')->tryDecrypt($transaction->transaction_journal_budget_name);
            $txt  = sprintf('<a href="%s" title="%s">%s</a>', route('budgets.show', [$transaction->transaction_journal_budget_id]), $name, $name);
            $cache->store($txt);

            return $txt;
        }

        // transaction has a budget
        if (isset($transaction->transaction_budget_id)) {
            $name = app('steam')->tryDecrypt($transaction->transaction_budget_name);
            $txt  = sprintf('<a href="%s" title="%s">%s</a>', route('budgets.show', [$transaction->transaction_budget_id]), $name, $name);
            $cache->store($txt);

            return $txt;
        }

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

            $txt = join(', ', $str);
            $cache->store($txt);

            return $txt;
        }
        $txt = '';
        $cache->store($txt);

        return $txt;

    }

    /**
     * @param TransactionModel $transaction
     *
     * @return string
     */
    public function categories(TransactionModel $transaction): string
    {
        $cache = new SingleCacheProperties;
        $cache->addProperty('transaction-categories');
        $cache->addProperty($transaction->id);
        $cache->addProperty($transaction->updated_at);
        if ($cache->has()) {
            return $cache->get();
        }

        // journal has a category:
        if (isset($transaction->transaction_journal_category_id)) {
            $name = app('steam')->tryDecrypt($transaction->transaction_journal_category_name);
            $txt  = sprintf('<a href="%s" title="%s">%s</a>', route('categories.show', [$transaction->transaction_journal_category_id]), $name, $name);
            $cache->store($txt);

            return $txt;
        }

        // transaction has a category:
        if (isset($transaction->transaction_category_id)) {
            $name = app('steam')->tryDecrypt($transaction->transaction_category_name);
            $txt  = sprintf('<a href="%s" title="%s">%s</a>', route('categories.show', [$transaction->transaction_category_id]), $name, $name);
            $cache->store($txt);

            return $txt;
        }

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

            $txt = join(', ', $str);
            $cache->store($txt);

            return $txt;
        }

        $txt = '';
        $cache->store($txt);

        return $txt;
    }

    /**
     * @param TransactionModel $transaction
     *
     * @return string
     */
    public function description(TransactionModel $transaction): string
    {
        $cache = new SingleCacheProperties;
        $cache->addProperty('description');
        $cache->addProperty($transaction->id);
        $cache->addProperty($transaction->updated_at);
        if ($cache->has()) {
            return $cache->get();
        }
        $description = $transaction->description;
        if (strlen(strval($transaction->transaction_description)) > 0) {
            $description = $transaction->transaction_description . '(' . $transaction->description . ')';
        }

        $cache->store($description);

        return $description;
    }

    /**
     * @param TransactionModel $transaction
     *
     * @return string
     */
    public function destinationAccount(TransactionModel $transaction): string
    {
        $cache = new SingleCacheProperties;
        $cache->addProperty('transaction-destination');
        $cache->addProperty($transaction->id);
        $cache->addProperty($transaction->updated_at);
        if ($cache->has()) {
            return $cache->get();
        }

        $name          = app('steam')->tryDecrypt($transaction->account_name);
        $transactionId = intval($transaction->account_id);
        $type          = $transaction->account_type;

        // name is present in object, use that one:
        if (bccomp($transaction->transaction_amount, '0') === -1 && !is_null($transaction->opposing_account_id)) {
            $name          = $transaction->opposing_account_name;
            $transactionId = intval($transaction->opposing_account_id);
            $type          = $transaction->opposing_account_type;
        }

        // Find the opposing account and use that one:
        if (bccomp($transaction->transaction_amount, '0') === -1 && is_null($transaction->opposing_account_id)) {
            // if the amount is negative, find the opposing account and use that one:
            $journalId = $transaction->journal_id;
            /** @var TransactionModel $other */
            $other         = TransactionModel::where('transaction_journal_id', $journalId)->where('transactions.id', '!=', $transaction->id)
                                             ->where('amount', '=', bcmul($transaction->transaction_amount, '-1'))->where(
                    'identifier', $transaction->identifier
                )
                                             ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                                             ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                                             ->first(['transactions.account_id', 'accounts.encrypted', 'accounts.name', 'account_types.type']);
            $name          = app('steam')->tryDecrypt($other->name);
            $transactionId = $other->account_id;
            $type          = $other->type;
        }

        if ($type === AccountType::CASH) {
            $txt = '<span class="text-success">(' . trans('firefly.cash') . ')</span>';
            $cache->store($txt);

            return $txt;
        }

        $txt = sprintf('<a title="%1$s" href="%2$s">%1$s</a>', e($name), route('accounts.show', [$transactionId]));
        $cache->store($txt);

        return $txt;
    }

    /**
     * @param TransactionModel $transaction
     *
     * @return string
     */
    public function hasAttachments(TransactionModel $transaction): string
    {
        $cache = new SingleCacheProperties;
        $cache->addProperty('attachments');
        $cache->addProperty($transaction->id);
        $cache->addProperty($transaction->updated_at);
        if ($cache->has()) {
            return $cache->get();
        }
        $journalId = intval($transaction->journal_id);
        $count     = Attachment::whereNull('deleted_at')
                               ->where('attachable_type', 'FireflyIII\Models\TransactionJournal')
                               ->where('attachable_id', $journalId)
                               ->count();
        if ($count > 0) {
            $res = sprintf('<i class="fa fa-paperclip" title="%s"></i>', Lang::choice('firefly.nr_of_attachments', $count, ['count' => $count]));
            $cache->store($res);

            return $res;
        }

        $res = '';
        $cache->store($res);

        return $res;
    }

    /**
     * @param TransactionModel $transaction
     *
     * @return string
     */
    public function icon(TransactionModel $transaction): string
    {
        $cache = new SingleCacheProperties;
        $cache->addProperty('icon');
        $cache->addProperty($transaction->id);
        $cache->addProperty($transaction->updated_at);
        if ($cache->has()) {
            return $cache->get();
        }

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
                $txt = sprintf('<i class="fa-fw fa fa-star-o" title="%s"></i>', trans('firefly.openingBalance'));
                break;
            default:
                $txt = '';
                break;
        }
        $cache->store($txt);

        return $txt;
    }

    /**
     * @param TransactionModel $transaction
     *
     * @return string
     */
    public function isReconciled(TransactionModel $transaction): string
    {
        $cache = new SingleCacheProperties;
        $cache->addProperty('transaction-reconciled');
        $cache->addProperty($transaction->id);
        $cache->addProperty($transaction->updated_at);
        if ($cache->has()) {
            return $cache->get();
        }
        $icon = '';
        if (intval($transaction->reconciled) === 1) {
            $icon = '<i class="fa fa-check"></i>';
        }

        $cache->store($icon);

        return $icon;
    }

    /**
     * @param TransactionModel $transaction
     *
     * @return string
     */
    public function isSplit(TransactionModel $transaction): string
    {
        $cache = new SingleCacheProperties;
        $cache->addProperty('split');
        $cache->addProperty($transaction->id);
        $cache->addProperty($transaction->updated_at);
        if ($cache->has()) {
            return $cache->get();
        }
        $journalId = intval($transaction->journal_id);
        $count     = TransactionModel::where('transaction_journal_id', $journalId)->whereNull('deleted_at')->count();
        if ($count > 2) {
            $res = '<i class="fa fa-fw fa-share-alt" aria-hidden="true"></i>';
            $cache->store($res);

            return $res;
        }

        $res = '';
        $cache->store($res);

        return $res;
    }

    /**
     * @param TransactionModel $transaction
     *
     * @return string
     */
    public function sourceAccount(TransactionModel $transaction): string
    {
        $cache = new SingleCacheProperties;
        $cache->addProperty('transaction-source');
        $cache->addProperty($transaction->id);
        $cache->addProperty($transaction->updated_at);
        if ($cache->has()) {
            return $cache->get();
        }

        // if the amount is negative, assume that the current account (the one in $transaction) is indeed the source account.
        $name          = app('steam')->tryDecrypt($transaction->account_name);
        $transactionId = intval($transaction->account_id);
        $type          = $transaction->account_type;

        // name is present in object, use that one:
        if (bccomp($transaction->transaction_amount, '0') === 1 && !is_null($transaction->opposing_account_id)) {
            $name          = $transaction->opposing_account_name;
            $transactionId = intval($transaction->opposing_account_id);
            $type          = $transaction->opposing_account_type;
        }
        // Find the opposing account and use that one:
        if (bccomp($transaction->transaction_amount, '0') === 1 && is_null($transaction->opposing_account_id)) {
            $journalId = $transaction->journal_id;
            /** @var TransactionModel $other */
            $other         = TransactionModel::where('transaction_journal_id', $journalId)->where('transactions.id', '!=', $transaction->id)
                                             ->where('amount', '=', bcmul($transaction->transaction_amount, '-1'))->where(
                    'identifier', $transaction->identifier
                )
                                             ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                                             ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                                             ->first(['transactions.account_id', 'accounts.encrypted', 'accounts.name', 'account_types.type']);
            $name          = app('steam')->tryDecrypt($other->name);
            $transactionId = $other->account_id;
            $type          = $other->type;
        }

        if ($type === AccountType::CASH) {
            $txt = '<span class="text-success">(' . trans('firefly.cash') . ')</span>';
            $cache->store($txt);

            return $txt;
        }

        $txt = sprintf('<a title="%1$s" href="%2$s">%1$s</a>', e($name), route('accounts.show', [$transactionId]));
        $cache->store($txt);

        return $txt;
    }
}