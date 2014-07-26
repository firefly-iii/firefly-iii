<?php

namespace Firefly\Helper\Controllers;

use Illuminate\Database\Eloquent\Collection;

/**
 * Class Account
 *
 * @package Firefly\Helper\Controllers
 */
class Account implements AccountInterface
{
    /**
     * @param Collection $accounts
     */
    public function index(Collection $accounts)
    {

        $list = [
            'personal'      => [],
            'beneficiaries' => [],
            'initial'       => [],
            'cash'          => []
        ];
        foreach ($accounts as $account) {

            switch ($account->accounttype->description) {
                case 'Default account':
                    $list['personal'][] = $account;
                    break;
                case 'Cash account':
                    $list['cash'][] = $account;
                    break;
                case 'Initial balance account':
                    $list['initial'][] = $account;
                    break;
                case 'Beneficiary account':
                    $list['beneficiaries'][] = $account;
                    break;

            }
        }
        return $list;

    }

    /**
     * @param \Account $account
     *
     * @return mixed
     */
    public function openingBalanceTransaction(\Account $account)
    {
        $transactionType = \TransactionType::where('type', 'Opening balance')->first();
        return \TransactionJournal::
            with(
                ['transactions' => function ($q) {
                        $q->orderBy('amount', 'ASC');
                    }]
            )->where('transaction_type_id', $transactionType->id)
            ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->where('transactions.account_id', $account->id)->first(['transaction_journals.*']);

    }
} 