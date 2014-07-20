<?php


/**
 * Component
 *
 * @property integer                                                             $id
 * @property \Carbon\Carbon                                                      $created_at
 * @property \Carbon\Carbon                                                      $updated_at
 * @property string                                                              $name
 * @property integer                                                             $user_id
 * @property string                                                              $class
 * @property-read \Illuminate\Database\Eloquent\Collection|\Transaction[]        $transactions
 * @property-read \Illuminate\Database\Eloquent\Collection|\TransactionJournal[] $transactionjournals
 * @property-read \User                                                          $user
 * @method static \Illuminate\Database\Query\Builder|\Component whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Component whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Component whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Component whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\Component whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\Component whereClass($value)
 * @property-read \Limit $limits
 */
class Component extends Firefly\Database\SingleTableInheritanceEntity
{

    public static $rules
        = [
            'user_id' => 'exists:users,id|required',
            'name'    => 'required|between:1,255',
            'class'   => 'required',
        ];
    public static $factory
        = [
            'name'    => 'string',
            'user_id' => 'factory|User',
        ];
    protected $table = 'components';
    protected $subclassField = 'class';

    public function transactions()
    {
        return $this->belongsToMany('Transaction');
    }

    public function limits()
    {
        return $this->belongsTo('Limit');
    }

    public function transactionjournals()
    {
        return $this->belongsToMany('TransactionJournal');
    }

    public function user()
    {
        return $this->belongsTo('User');
    }

} 