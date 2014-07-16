<?php

namespace Firefly\Storage\TransactionJournal;


interface TransactionJournalRepositoryInterface
{
    public function createSimpleJournal(\Account $from, \Account $to, $description, $amount, \Carbon\Carbon $date);

    public function get();

    public function find($journalId);

    public function getByAccount(\Account $account, $count = 25);

    public function homeBudgetChart(\Carbon\Carbon $start, \Carbon\Carbon $end);

    public function homeCategoryChart(\Carbon\Carbon $start, \Carbon\Carbon $end);

    public function homeBeneficiaryChart(\Carbon\Carbon $start, \Carbon\Carbon $end);

    public function homeComponentChart(\Carbon\Carbon $start, \Carbon\Carbon $end, $chartType);

}