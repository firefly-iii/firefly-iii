<?php

use Illuminate\Database\Eloquent\SoftDeletingTrait;
use Watson\Validating\ValidatingTrait;
use \Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TransactionType
 */
class TransactionType extends Eloquent
{
    use SoftDeletingTrait, ValidatingTrait;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionJournals()
    {
        return $this->hasMany('TransactionJournal');
    }

} 