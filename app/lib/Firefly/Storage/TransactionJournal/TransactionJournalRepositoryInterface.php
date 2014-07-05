<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 03/07/14
 * Time: 15:22
 */

namespace Firefly\Storage\TransactionJournal;


interface TransactionJournalRepositoryInterface {

    public function createSimpleJournal(\Account $from, \Account $to, $description, $amount, \Carbon\Carbon $date);

} 