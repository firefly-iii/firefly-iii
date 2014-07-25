<?php

namespace Firefly\Storage\TransactionJournal;


interface TransactionJournalRepositoryInterface
{
    public function createSimpleJournal(\Account $from, \Account $to, $description, $amount, \Carbon\Carbon $date);

    public function get();

    public function find($journalId);

    public function getByAccountInDateRange(\Account $account, $count = 25, \Carbon\Carbon $start, \Carbon\Carbon $end);

    public function getByAccountAndDate(\Account $account, \Carbon\Carbon $date);

    public function getByDateRange(\Carbon\Carbon $start, \Carbon\Carbon $end);

    public function paginate($count = 25);

}