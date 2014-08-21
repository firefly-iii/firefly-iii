<?php


/**
 * AccountType
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $description
 * @property-read \Illuminate\Database\Eloquent\Collection|\Account[] $accounts
 * @method static \Illuminate\Database\Query\Builder|\AccountType whereId($value) 
 * @method static \Illuminate\Database\Query\Builder|\AccountType whereCreatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\AccountType whereUpdatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\AccountType whereDescription($value) 
 */
class AccountType extends Eloquent
{

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accounts()
    {
        return $this->hasMany('Account');
    }
} 