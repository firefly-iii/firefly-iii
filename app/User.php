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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII;

use FireflyIII\Events\RequestedNewPassword;
use FireflyIII\Models\CurrencyExchangeRate;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Log;
use Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class User.
 */
class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

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
     * @param        $guard
     * @param string $value
     *
     * @return User
     */
    public static function routeBinder(string $value): User
    {
        if (auth()->check()) {
            $userId = intval($value);
            $user   = self::find($userId);
            if (!is_null($user)) {
                return $user;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * Link to accounts.
     *
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
        try {
            $this->roles()->attach($role);
        } catch (QueryException $e) {
            // don't care
            Log::info(sprintf('Query exception when giving user a role: %s', $e->getMessage()));
        }
    }

    /**
     * @codeCoverageIgnore
     * Link to attachments
     *
     * @return HasMany
     */
    public function attachments(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Attachment');
    }

    /**
     * @codeCoverageIgnore
     * Link to available budgets
     *
     * @return HasMany
     */
    public function availableBudgets(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\AvailableBudget');
    }

    /**
     * @codeCoverageIgnore
     * Link to bills.
     *
     * @return HasMany
     */
    public function bills(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Bill');
    }

    /**
     * @codeCoverageIgnore
     * Link to budgets.
     *
     * @return HasMany
     */
    public function budgets(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Budget');
    }

    /**
     * @codeCoverageIgnore
     * Link to categories
     *
     * @return HasMany
     */
    public function categories(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Category');
    }

    /**
     * @codeCoverageIgnore
     * Link to currency exchange rates
     *
     * @return HasMany
     */
    public function currencyExchangeRates(): HasMany
    {
        return $this->hasMany(CurrencyExchangeRate::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to export jobs
     *
     * @return HasMany
     */
    public function exportJobs(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\ExportJob');
    }

    /**
     * @codeCoverageIgnore
     * Generates access token.
     *
     * @return string
     */
    public function generateAccessToken(): string
    {
        $bytes = random_bytes(16);

        return strval(bin2hex($bytes));
    }

    /**
     * @codeCoverageIgnore
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
     * @codeCoverageIgnore
     * Link to import jobs.
     *
     * @return HasMany
     */
    public function importJobs(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\ImportJob');
    }

    /**
     * @codeCoverageIgnore
     * Link to piggy banks.
     *
     * @return HasManyThrough
     */
    public function piggyBanks(): HasManyThrough
    {
        return $this->hasManyThrough('FireflyIII\Models\PiggyBank', 'FireflyIII\Models\Account');
    }

    /**
     * @codeCoverageIgnore
     * Link to preferences.
     *
     * @return HasMany
     */
    public function preferences(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Preference');
    }

    /**
     * @codeCoverageIgnore
     * Link to roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany('FireflyIII\Models\Role');
    }

    /**
     * @codeCoverageIgnore
     * Link to rule groups.
     *
     * @return HasMany
     */
    public function ruleGroups(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\RuleGroup');
    }

    /**
     * @codeCoverageIgnore
     * Link to rules.
     *
     * @return HasMany
     */
    public function rules(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Rule');
    }

    /**
     * @codeCoverageIgnore
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
     * @codeCoverageIgnore
     * Link to tags.
     *
     * @return HasMany
     */
    public function tags(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Tag');
    }

    /**
     * @codeCoverageIgnore
     * Link to transaction journals.
     *
     * @return HasMany
     */
    public function transactionJournals(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\TransactionJournal');
    }

    /**
     * @codeCoverageIgnore
     * Link to transactions.
     *
     * @return HasManyThrough
     */
    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough('FireflyIII\Models\Transaction', 'FireflyIII\Models\TransactionJournal');
    }
}
