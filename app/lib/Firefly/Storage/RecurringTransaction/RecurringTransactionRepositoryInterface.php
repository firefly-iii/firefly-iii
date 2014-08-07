<?php


namespace Firefly\Storage\RecurringTransaction;


interface RecurringTransactionRepositoryInterface
{

    public function get();

    public function store($data);

    public function destroy(\RecurringTransaction $recurringTransaction);


} 