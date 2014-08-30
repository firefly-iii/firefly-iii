<?php

namespace Firefly\Helper\Controllers;

use Firefly\Exception\FireflyException;

/**
 * Class Account
 *
 * @package Firefly\Helper\Controllers
 */
class Account implements AccountInterface
{
    /**
     * @param \Account $account
     *
     * @return mixed
     */
    public function openingBalanceTransaction(\Account $account)
    {
        return \TransactionJournal::
            withRelevantData()->account($account)
                                  ->leftJoin('transaction_types', 'transaction_types.id', '=',
                'transaction_journals.transaction_type_id')
                                  ->where('transaction_types.type', 'Opening balance')
                                  ->first(['transaction_journals.*']);
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
        $end   = \Session::get('end');
        $stats = [
            'budgets'    => [],
            'categories' => [],
            'accounts'   => []
        ];
        $items = [];


        // build a query:
        $query = \TransactionJournal::withRelevantData()->defaultSorting()->account($account)->after($start)
                                    ->before($end);
        // filter some:
        if (\Input::get('type')) {
            switch (\Input::get('type')) {
                case 'transactions':
                    $query->transactionTypes(['Deposit', 'Withdrawal']);
                    break;
                case 'transfers':
                    $query->transactionTypes(['Transfer']);
                    break;
                default:
                    throw new FireflyException('No case for type "' . \Input::get('type') . '"!');
                    break;
            }
        }

        if (\Input::get('show')) {
            switch (\Input::get('show')) {
                case 'expenses':
                case 'out':
                    $query->lessThan(0);
                    break;
                case 'income':
                case 'in':
                    $query->moreThan(0);
                    break;
                default:
                    throw new FireflyException('No case for show "' . \Input::get('show') . '"!');
                    break;
            }
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
                if ($component->class == 'Budget') {
                    $stats['budgets'][$component->id] = $component;
                }
                if ($component->class == 'Category') {
                    $stats['categories'][$component->id] = $component;
                }
            }
            // since it is entirely possible the database is messed up somehow
            // it might be that a transaction journal has only one transaction.
            // this is mainly caused by wrong deletions and other artefacts from the past.
            // if it is the case, we remove $item and continue like nothing ever happened.

            // this will however, mess up some statisics but we can live with that.
            // we might be needing some cleanup routine in the future.

            // for now, we simply warn the user of this.

            if (count($item->transactions) < 2) {
                \Session::flash('warning',
                    'Some transactions are incomplete; they will not be shown. Statistics may differ.');
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