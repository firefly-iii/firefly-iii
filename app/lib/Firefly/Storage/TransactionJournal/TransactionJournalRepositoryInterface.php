<?php

namespace Firefly\Storage\TransactionJournal;


interface TransactionJournalRepositoryInterface
{
    public function createSimpleJournal(\Account $from, \Account $to, $description, $amount, \Carbon\Carbon $date);

    public function get();

    public function find($journalId);

    public function getByAccount(\Account $account, $count = 25);

    public function getByAccountAndDate(\Account $account, \Carbon\Carbon $date);

    public function getByDateRange(\Carbon\Carbon $start, \Carbon\Carbon $end);

}