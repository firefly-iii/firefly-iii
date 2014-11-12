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
                                 = ['user_id' => 'exists:users,id|required', 'name' => ['required', 'between:1,100', 'min:1', 'alphabasic'],
                                    'class'   => 'required',];
    protected     $fillable      = ['name', 'user_id'];
    protected     $subclassField = 'class';
    protected     $table         = 'components';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function limits()
    {
        return $this->hasMany('Limit');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function transactionjournals()
    {
        return $this->belongsToMany('TransactionJournal');
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