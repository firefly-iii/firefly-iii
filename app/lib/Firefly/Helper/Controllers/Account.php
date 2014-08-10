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
     *
     * @return array|mixed
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

    /**
     * @param \Account $account
     * @param          $perPage
     *
     * @return mixed|void
     */
    public function show(\Account $account, $perPage)
    {
        $start = \Session::get('start');
        $end = \Session::get('end');
        $stats = [
            'budgets'    => [],
            'categories' => [],
            'accounts'   => []
        ];
        $items = [];


        // build a query:
        $query = \TransactionJournal::with(
            ['transactions'                        => function ($q) {
                    $q->orderBy('amount', 'ASC');
                }, 'transactiontype', 'components' => function ($q) {
                    $q->orderBy('class');
                }, 'transactions.account.accounttype']
        )->orderBy('date', 'DESC')->leftJoin(
                'transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id'
            )->where('transactions.account_id', $account->id)->where('date', '>=', $start->format('Y-m-d'))->where(
                'date', '<=', $end->format('Y-m-d')
            )->orderBy('transaction_journals.id', 'DESC');


        // build paginator:
        $totalItems = $query->count();
        $page = intval(\Input::get('page')) > 1 ? intval(\Input::get('page')) : 1;
        $skip = ($page - 1) * $perPage;
        $result = $query->skip($skip)->take($perPage)->get(['transaction_journals.*']);
        // in the mean time, build list of categories, budgets and other accounts:

        /** @var $item \TransactionJournal */
        foreach ($result as $item) {
            $items[] = $item;
            foreach ($item->components as $component) {
                if ($component->class == 'Budget') {
                    $stats['budgets'][$component->id] = $component;
                }
                if ($component->class == 'Category') {
                    $stats['categories'][$component->id] = $component;
                }
            }
            $fromAccount = $item->transactions[0]->account;
            $toAccount = $item->transactions[1]->account;
            $stats['accounts'][$fromAccount->id] = $fromAccount;
            $stats['accounts'][$toAccount->id] = $toAccount;
        }
        unset($result, $page);
        $paginator = \Paginator::make($items, $totalItems, $perPage);

        // statistics
        $stats['period']['in'] = floatval(
            \Transaction::where('account_id', $account->id)->where('amount', '>', 0)->leftJoin(
                'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
            )->leftJoin(
                    'transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id'
                )->whereIn('transaction_types.type', ['Deposit', 'Withdrawal'])->where(
                    'transaction_journals.date', '>=', $start->format('Y-m-d')
                )->where('transaction_journals.date', '<=', $end->format('Y-m-d'))->sum('amount')
        );


        $stats['period']['out'] = floatval(
            \Transaction::where('account_id', $account->id)->where('amount', '<', 0)->leftJoin(
                'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
            )->leftJoin(
                    'transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id'
                )->whereIn('transaction_types.type', ['Deposit', 'Withdrawal'])->where(
                    'transaction_journals.date', '>=', $start->format('Y-m-d')
                )->where('transaction_journals.date', '<=', $end->format('Y-m-d'))->sum('amount')
        );
        $stats['period']['diff'] = $stats['period']['in'] + $stats['period']['out'];

        $stats['period']['t_in'] = floatval(
            \Transaction::where('account_id', $account->id)->where('amount', '>', 0)->leftJoin(
                'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
            )->leftJoin(
                    'transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id'
                )->where('transaction_types.type', 'Transfer')->where(
                    'transaction_journals.date', '>=', $start->format('Y-m-d')
                )->where('transaction_journals.date', '<=', $end->format('Y-m-d'))->sum('amount')
        );

        $stats['period']['t_out'] = floatval(
            \Transaction::where('account_id', $account->id)->where('amount', '<', 0)->leftJoin(
                'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
            )->leftJoin(
                    'transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id'
                )->where('transaction_types.type', 'Transfer')->where(
                    'transaction_journals.date', '>=', $start->format('Y-m-d')
                )->where('transaction_journals.date', '<=', $end->format('Y-m-d'))->sum('amount')
        );

        $stats['period']['t_diff'] = $stats['period']['t_in'] + $stats['period']['t_out'];


        $return = [
            'journals'   => $paginator,
            'statistics' => $stats
        ];

        return $return;


    }
} 