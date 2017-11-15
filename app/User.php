<?php
/**
 * User.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII;

use FireflyIII\Events\RequestedNewPassword;
use FireflyIII\Models\CurrencyExchangeRate;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Request;

/**
 * Class User.
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
    public function availableBudgets(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\AvailableBudget');
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
    public function currencyExchangeRates(): HasMany
    {
        return $this->hasMany(CurrencyExchangeRate::class);
    }

    /**
     * @return HasMany
     */
    public function exportJobs(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\ExportJob');
    }

    /**
     * @return string
     */
    public function generateAccessToken(): string
    {
        $bytes = random_bytes(16);

        return strval(bin2hex($bytes));
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
            if ($role->name === $name) {
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
     * Send the password reset notification.
     *
     * @param string $token
     */
    public function sendPasswordResetNotification($token)
    {
        $ipAddress = Request::ip();

        event(new RequestedNewPassword($this, $token, $ipAddress));
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
