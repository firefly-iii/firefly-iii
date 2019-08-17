<?php

/**
 * User.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

use Eloquent;
use Exception;
use FireflyIII\Events\RequestedNewPassword;
use FireflyIII\Models\Account;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\Role;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Passport\Client;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Token;
use Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class User.
 *
 * @property int                                                                                $id
 * @property string                                                                             $email
 * @property bool                                                                               $isAdmin used in admin user
 *           controller.
 * @property bool                                                                                                           $has2FA  used in admin user
 *           controller.
 * @property array                                                                                                          $prefs   used in admin user
 *           controller.
 * @property string                                                                                                         password
 * @property string                                                                                                         $mfa_secret
 * @property Collection                                                                                                     roles
 * @property string                                                                                                         blocked_code
 * @property bool                                                            blocked
 * @property Carbon|null                                 $created_at
 * @property Carbon|null                                 $updated_at
 * @property string|null                                                     $remember_token
 * @property string|null                                                     $reset
 * @property-read \Illuminate\Database\Eloquent\Collection|Account[]         $accounts
 * @property-read \Illuminate\Database\Eloquent\Collection|Attachment[]      $attachments
 * @property-read \Illuminate\Database\Eloquent\Collection|AvailableBudget[] $availableBudgets
 * @property-read \Illuminate\Database\Eloquent\Collection|Bill[]            $bills
 * @property-read \Illuminate\Database\Eloquent\Collection|Budget[]          $budgets
 * @property-read \Illuminate\Database\Eloquent\Collection|Category[]        $categories
 * @property-read \Illuminate\Database\Eloquent\Collection|Client[]                     $clients
 * @property-read \Illuminate\Database\Eloquent\Collection|CurrencyExchangeRate[]          $currencyExchangeRates
 * @property-read \Illuminate\Database\Eloquent\Collection|ImportJob[]                          $importJobs
 * @property-read DatabaseNotificationCollection|DatabaseNotification[]                         $notifications
 * @property-read \Illuminate\Database\Eloquent\Collection|PiggyBank[]                          $piggyBanks
 * @property-read \Illuminate\Database\Eloquent\Collection|Preference[]                         $preferences
 * @property-read \Illuminate\Database\Eloquent\Collection|Recurrence[]                  $recurrences
 * @property-read \Illuminate\Database\Eloquent\Collection|RuleGroup[]                               $ruleGroups
 * @property-read \Illuminate\Database\Eloquent\Collection|Rule[]                                                           $rules
 * @property-read \Illuminate\Database\Eloquent\Collection|Tag[]                                                            $tags
 * @property-read \Illuminate\Database\Eloquent\Collection|Token[]                                                          $tokens
 * @property-read \Illuminate\Database\Eloquent\Collection|TransactionGroup[]                                               $transactionGroups
 * @property-read \Illuminate\Database\Eloquent\Collection|TransactionJournal[]                                             $transactionJournals
 * @property-read \Illuminate\Database\Eloquent\Collection|Transaction[]                                                    $transactions
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User query()
 * @method static Builder|User whereBlocked($value)
 * @method static Builder|User whereBlockedCode($value)
 * @method static Builder|User whereCreatedAt($value)
 * @method static Builder|User whereEmail($value)
 * @method static Builder|User whereId($value)
 * @method static Builder|User wherePassword($value)
 * @method static Builder|User whereRememberToken($value)
 * @method static Builder|User whereReset($value)
 * @method static Builder|User whereUpdatedAt($value)
 * @mixin Eloquent
 */
class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'blocked'    => 'boolean',
        ];
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
     * @param string $value
     *
     * @return User
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): User
    {
        if (auth()->check()) {
            $userId = (int)$value;
            $user   = self::find($userId);
            if (null !== $user) {
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
        return $this->hasMany(Account::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to attachments
     *
     * @return HasMany
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to available budgets
     *
     * @return HasMany
     */
    public function availableBudgets(): HasMany
    {
        return $this->hasMany(AvailableBudget::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to bills.
     *
     * @return HasMany
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to budgets.
     *
     * @return HasMany
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to categories
     *
     * @return HasMany
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
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
     * Generates access token.
     *
     * @return string
     * @throws Exception
     */
    public function generateAccessToken(): string
    {
        $bytes = random_bytes(16);

        return bin2hex($bytes);
    }

    /**
     * @codeCoverageIgnore
     * Link to import jobs.
     *
     * @return HasMany
     */
    public function importJobs(): HasMany
    {
        return $this->hasMany(ImportJob::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to piggy banks.
     *
     * @return HasManyThrough
     */
    public function piggyBanks(): HasManyThrough
    {
        return $this->hasManyThrough(PiggyBank::class, Account::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to preferences.
     *
     * @return HasMany
     */
    public function preferences(): HasMany
    {
        return $this->hasMany(Preference::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to recurring transactions.
     *
     * @return HasMany
     */
    public function recurrences(): HasMany
    {
        return $this->hasMany(Recurrence::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to roles.
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to rule groups.
     *
     * @return HasMany
     */
    public function ruleGroups(): HasMany
    {
        return $this->hasMany(RuleGroup::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to rules.
     *
     * @return HasMany
     */
    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class);
    }

    /**
     * @codeCoverageIgnore
     * Send the password reset notification.
     *
     * @param string $token
     */
    public function sendPasswordResetNotification($token): void
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
        return $this->hasMany(Tag::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to transaction groups.
     *
     * @return HasMany
     */
    public function transactionGroups(): HasMany
    {
        return $this->hasMany(TransactionGroup::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to transaction journals.
     *
     * @return HasMany
     */
    public function transactionJournals(): HasMany
    {
        return $this->hasMany(TransactionJournal::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to transactions.
     *
     * @return HasManyThrough
     */
    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough(Transaction::class, TransactionJournal::class);
    }
}
