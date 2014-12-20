<?php
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletingTrait;
use Watson\Validating\ValidatingTrait;
/**
 * Class TransactionCurrency
 */
class TransactionCurrency extends Eloquent
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