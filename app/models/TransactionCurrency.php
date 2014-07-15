<?php


/**
 * TransactionCurrency
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $code
 * @property-read \Illuminate\Database\Eloquent\Collection|\TransactionJournal[] $transactionJournals
 * @method static \Illuminate\Database\Query\Builder|\TransactionCurrency whereId($value) 
 * @method static \Illuminate\Database\Query\Builder|\TransactionCurrency whereCreatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\TransactionCurrency whereUpdatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\TransactionCurrency whereCode($value) 
 */
class TransactionCurrency extends Eloquent
{

    public static $factory
        = [
            'code' => 'string'
        ];

    public function transactionJournals()
    {
        return $this->hasMany('TransactionJournal');
    }

} 