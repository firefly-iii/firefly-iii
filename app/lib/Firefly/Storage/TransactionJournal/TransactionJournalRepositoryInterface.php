<?php

namespace Firefly\Storage\TransactionJournal;


interface TransactionJournalRepositoryInterface
{

    public function createSimpleJournal(\Account $from, \Account $to, $description, $amount, \Carbon\Carbon $date);

} 