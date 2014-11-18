<?php
use FireflyIII\Shared\SingleTableInheritanceEntity;


/**
 * Component
 *
 * @property integer                                                             $id
 * @property \Carbon\Carbon                                                      $created_at
 * @property \Carbon\Carbon                                                      $updated_at
 * @property string                                                              $name
 * @property integer                                                             $user_id
 * @property string                                                              $class
 * @property-read \Illuminate\Database\Eloquent\Collection|\Limit[]              $limits
 * @property-read \Illuminate\Database\Eloquent\Collection|\TransactionJournal[] $transactionjournals
 * @property-read \Illuminate\Database\Eloquent\Collection|\Transaction[]        $transactions
 * @property-read \User                                                          $user
 * @method static \Illuminate\Database\Query\Builder|\Component whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Component whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Component whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Component whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\Component whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\Component whereClass($value)
 */
class Component extends SingleTableInheritanceEntity
{

    public static $rules
                                 = [
            'user_id' => 'exists:users,id|required',
            'name'    => 'required|between:1,100|alphabasic',
            'class'   => 'required',
        ];
    protected     $fillable      = ['name', 'user_id'];
    protected     $subclassField = 'class';
    protected     $table         = 'components';

    /**
     * TODO remove this method in favour of something in the FireflyIII libraries.
     *
     * @return Carbon
     */
    public function lastActionDate()
    {
        $transaction = $this->transactionjournals()->orderBy('updated_at', 'DESC')->first();
        if (is_null($transaction)) {
            return null;
        }

        return $transaction->date;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function transactionjournals()
    {
        return $this->belongsToMany('TransactionJournal', 'component_transaction_journal', 'component_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function transactions()
    {
        return $this->belongsToMany('Transaction');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('User');
    }

} 