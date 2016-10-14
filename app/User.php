<?php
/**
 * User.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);


namespace FireflyIII;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Class User
 *
 * @package FireflyIII
 * @property integer                                                                                                        $id
 * @property \Carbon\Carbon                                                                                                 $created_at
 * @property \Carbon\Carbon                                                                                                 $updated_at
 * @property string                                                                                                         $email
 * @property string                                                                                                         $password
 * @property string                                                                                                         $remember_token
 * @property string                                                                                                         $reset
 * @property boolean                                                                                                        $blocked
 * @property string                                                                                                         $blocked_code
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Account[]                                     $accounts
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Attachment[]                                  $attachments
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Bill[]                                        $bills
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Budget[]                                      $budgets
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Category[]                                    $categories
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\ExportJob[]                                   $exportJobs
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\ImportJob[]                                   $importJobs
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\PiggyBank[]                                   $piggyBanks
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Preference[]                                  $preferences
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Role[]                                        $roles
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\RuleGroup[]                                   $ruleGroups
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Rule[]                                        $rules
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Tag[]                                         $tags
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\TransactionJournal[]                          $transactionJournals
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Transaction[]                                 $transactions
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $unreadNotifications
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\User whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\User whereEmail($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\User wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\User whereRememberToken($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\User whereReset($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\User whereBlocked($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\User whereBlockedCode($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['email', 'password', 'blocked', 'blocked_code'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * @return HasMany
     */
    public function accounts(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Account');
    }

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * Full credit goes to: https://github.com/Zizaco/entrust
     *
     * @param mixed $role
     */
    public function attachRole($role)
    {
        if (is_object($role)) {
            $role = $role->getKey();
        }

        if (is_array($role)) {
            $role = $role['id'];
        }

        $this->roles()->attach($role);
    }

    /**
     * @return HasMany
     */
    public function attachments(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Attachment');
    }

    /**
     * @return HasMany
     */
    public function bills(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Bill');
    }

    /**
     * @return HasMany
     */
    public function budgets(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Budget');
    }

    /**
     * @return HasMany
     */
    public function categories(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Category');
    }

    /**
     * @return HasMany
     */
    public function exportJobs(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\ExportJob');
    }

    /**
     * Checks if the user has a role by its name.
     *
     * Full credit goes to: https://github.com/Zizaco/entrust
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasRole(string $name): bool
    {

        foreach ($this->roles as $role) {
            if ($role->name == $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return HasMany
     */
    public function importJobs(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\ImportJob');
    }

    /**
     * @return HasManyThrough
     */
    public function piggyBanks(): HasManyThrough
    {
        return $this->hasManyThrough('FireflyIII\Models\PiggyBank', 'FireflyIII\Models\Account');
    }

    /**
     * @return HasMany
     */
    public function preferences(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Preference');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany('FireflyIII\Models\Role');
    }

    /**
     * @return HasMany
     */
    public function ruleGroups(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\RuleGroup');
    }

    /**
     * @return HasMany
     */
    public function rules(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Rule');
    }

    /**
     * @return HasMany
     */
    public function tags(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Tag');
    }

    /**
     * @return HasMany
     */
    public function transactionJournals(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\TransactionJournal');
    }

    /**
     * @return HasManyThrough
     */
    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough('FireflyIII\Models\Transaction', 'FireflyIII\Models\TransactionJournal');
    }


}
