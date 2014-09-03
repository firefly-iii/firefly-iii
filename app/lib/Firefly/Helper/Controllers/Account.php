<?php

namespace Firefly\Helper\Controllers;

/**
 * Class Account
 *
 * @package Firefly\Helper\Controllers
 */
class Account implements AccountInterface
{

    /**
     * @param \Account $account
     * @return \TransactionJournal|null
     */
    public function openingBalanceTransaction(\Account $account)
    {
        return \TransactionJournal::withRelevantData()
                                  ->account($account)
                                  ->leftJoin('transaction_types', 'transaction_types.id', '=',
                'transaction_journals.transaction_type_id')
                                  ->where('transaction_types.type', 'Opening balance')
                                  ->first(['transaction_journals.*']);
    }

    /**
     * Since it is entirely possible the database is messed up somehow it might be that a transaction
     * journal has only one transaction. This is mainly caused by wrong deletions and other artefacts from the past.
     *
     * If it is the case, we remove $item and continue like nothing ever happened. This will however,
     * mess up some statisics but we can live with that. We might be needing some cleanup routine in the future.
     *
     * For now, we simply warn the user of this.
     *
     * @param \Account $account
     * @param $perPage
     * @return array|mixed
     * @throws \Firefly\Exception\FireflyException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function show(\Account $account, $perPage)
    {
        $start = \Session::get('start');
        $end   = \Session::get('end');
        $stats = [
            'accounts' => []
        ];
        $items = [];

        // build a query:
        $query = \TransactionJournal::withRelevantData()
                                    ->defaultSorting()
                                    ->account($account)
                                    ->after($start)
                                    ->before($end);
        // filter some:
        switch (\Input::get('type')) {
            case 'transactions':
                $query->transactionTypes(['Deposit', 'Withdrawal']);
                break;
            case 'transfers':
                $query->transactionTypes(['Transfer']);
                break;
        }

        switch (\Input::get('show')) {
            case 'expenses':
            case 'out':
                $query->lessThan(0);
                break;
            case 'income':
            case 'in':
                $query->moreThan(0);
                break;
        }


        // build paginator:
        $totalItems = $query->count();
        $page       = max(1, intval(\Input::get('page')));
        $skip       = ($page - 1) * $perPage;
        $result     = $query->skip($skip)->take($perPage)->get(['transaction_journals.*']);


        // get the relevant budgets, categories and accounts from this list:
        /** @var $item \TransactionJournal */
        foreach ($result as $index => $item) {

            foreach ($item->components as $component) {
                $stats[$component->class][$component->id] = $component;
            }

            if (count($item->transactions) < 2) {
                \Session::flash('warning', 'Some transactions are incomplete; they will not be shown.');
                unset($result[$index]);
                continue;
            }
            $items[]                             = $item;
            $fromAccount                         = $item->transactions[0]->account;
            $toAccount                           = $item->transactions[1]->account;
            $stats['accounts'][$fromAccount->id] = $fromAccount;
            $stats['accounts'][$toAccount->id]   = $toAccount;
        }
        $paginator = \Paginator::make($items, $totalItems, $perPage);
        unset($result, $page, $item, $fromAccount, $toAccount);


        // statistics (transactions)
        $trIn   = floatval(\Transaction::before($end)->after($start)->account($account)->moreThan(0)
                                       ->transactionTypes(['Deposit', 'Withdrawal'])->sum('transactions.amount'));
        $trOut  = floatval(\Transaction::before($end)->after($start)->account($account)->lessThan(0)
                                       ->transactionTypes(['Deposit', 'Withdrawal'])->sum('transactions.amount'));
        $trDiff = $trIn + $trOut;

        // statistics (transfers)
        $trfIn   = floatval(\Transaction::before($end)->after($start)->account($account)->moreThan(0)
                                        ->transactionTypes(['Transfer'])->sum('transactions.amount'));
        $trfOut  = floatval(\Transaction::before($end)->after($start)->account($account)->lessThan(0)
                                        ->transactionTypes(['Transfer'])->sum('transactions.amount'));
        $trfDiff = $trfIn + $trfOut;

        $stats['period'] = [
            'in'     => $trIn,
            'out'    => $trOut,
            'diff'   => $trDiff,
            't_in'   => $trfIn,
            't_out'  => $trfOut,
            't_diff' => $trfDiff

        ];

        $return = [
            'journals'   => $paginator,
            'statistics' => $stats
        ];

        return $return;
    }
} 