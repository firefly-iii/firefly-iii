<?php


namespace Firefly\Storage\RecurringTransaction;

use Carbon\Carbon;

class EloquentRecurringTransactionRepository implements RecurringTransactionRepositoryInterface
{

    public function get() {
        return \Auth::user()->recurringtransactions()->get();
    }

}