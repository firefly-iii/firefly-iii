<?php


/**
 * TransactionType
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $type
 * @property-read \Illuminate\Database\Eloquent\Collection|\TransactionJournal[] $transactionJournals
 * @method static \Illuminate\Database\Query\Builder|\TransactionType whereId($value) 
 * @method static \Illuminate\Database\Query\Builder|\TransactionType whereCreatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\TransactionType whereUpdatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\TransactionType whereType($value) 
 */
class TransactionType extends Eloquent {
    public function transactionJournals() {
        return $this->hasMany('TransactionJournal');
    }

    public static $factory = [
        'type' => 'string'
    ];

} 