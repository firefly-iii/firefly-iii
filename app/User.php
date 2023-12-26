<?php

/**
 * User.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII;

use Carbon\Carbon;
use Eloquent;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Events\RequestedNewPassword;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\Role;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\UserGroup;
use FireflyIII\Models\UserRole;
use FireflyIII\Models\Webhook;
use FireflyIII\Notifications\Admin\TestNotification;
use FireflyIII\Notifications\Admin\UserInvitation;
use FireflyIII\Notifications\Admin\UserRegistration;
use FireflyIII\Notifications\Admin\VersionCheckResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Passport\Client;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Token;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class User.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @property int|string                                                      $id
 * @property string                                                          $email
 * @property bool                                                            $isAdmin
 * @property bool                                                            $has2FA
 * @property array                                                           $prefs
 * @property string                                                          $password
 * @property string                                                          $mfa_secret
 * @property Collection                                                      $roles
 * @property string                                                          $blocked_code
 * @property bool                                                            $blocked
 * @property null|Carbon                                                     $created_at
 * @property null|Carbon                                                     $updated_at
 * @property null|string                                                     $remember_token
 * @property null|string                                                     $reset
 * @property Account[]|\Illuminate\Database\Eloquent\Collection              $accounts
 * @property Attachment[]|\Illuminate\Database\Eloquent\Collection           $attachments
 * @property AvailableBudget[]|\Illuminate\Database\Eloquent\Collection      $availableBudgets
 * @property Bill[]|\Illuminate\Database\Eloquent\Collection                 $bills
 * @property Budget[]|\Illuminate\Database\Eloquent\Collection               $budgets
 * @property Category[]|\Illuminate\Database\Eloquent\Collection             $categories
 * @property Client[]|\Illuminate\Database\Eloquent\Collection               $clients
 * @property CurrencyExchangeRate[]|\Illuminate\Database\Eloquent\Collection $currencyExchangeRates
 * @property DatabaseNotification[]|DatabaseNotificationCollection           $notifications
 * @property \Illuminate\Database\Eloquent\Collection|PiggyBank[]            $piggyBanks
 * @property \Illuminate\Database\Eloquent\Collection|Preference[]           $preferences
 * @property \Illuminate\Database\Eloquent\Collection|Recurrence[]           $recurrences
 * @property \Illuminate\Database\Eloquent\Collection|RuleGroup[]            $ruleGroups
 * @property \Illuminate\Database\Eloquent\Collection|Rule[]                 $rules
 * @property \Illuminate\Database\Eloquent\Collection|Tag[]                  $tags
 * @property \Illuminate\Database\Eloquent\Collection|Token[]                $tokens
 * @property \Illuminate\Database\Eloquent\Collection|TransactionGroup[]     $transactionGroups
 * @property \Illuminate\Database\Eloquent\Collection|TransactionJournal[]   $transactionJournals
 * @property \Illuminate\Database\Eloquent\Collection|Transaction[]          $transactions
 *
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
 *
 * @property null|string $objectguid
 * @property null|int    $accounts_count
 * @property null|int    $attachments_count
 * @property null|int    $available_budgets_count
 * @property null|int    $bills_count
 * @property null|int    $budgets_count
 * @property null|int    $categories_count
 * @property null|int    $clients_count
 * @property null|int    $currency_exchange_rates_count
 * @property null|int    $notifications_count
 * @property null|int    $piggy_banks_count
 * @property null|int    $preferences_count
 * @property null|int    $recurrences_count
 * @property null|int    $roles_count
 * @property null|int    $rule_groups_count
 * @property null|int    $rules_count
 * @property null|int    $tags_count
 * @property null|int    $tokens_count
 * @property null|int    $transaction_groups_count
 * @property null|int    $transaction_journals_count
 * @property null|int    $transactions_count
 *
 * @method static Builder|User whereMfaSecret($value)
 * @method static Builder|User whereObjectguid($value)
 *
 * @property null|string $provider
 *
 * @method static Builder|User whereProvider($value)
 *
 * @property \Illuminate\Database\Eloquent\Collection|ObjectGroup[] $objectGroups
 * @property null|int                                               $object_groups_count
 * @property \Illuminate\Database\Eloquent\Collection|Webhook[]     $webhooks
 * @property null|int                                               $webhooks_count
 * @property null|string                                            $two_factor_secret
 * @property null|string                                            $two_factor_recovery_codes
 * @property null|string                                            $guid
 * @property null|string                                            $domain
 *
 * @method static Builder|User whereDomain($value)
 * @method static Builder|User whereGuid($value)
 * @method static Builder|User whereTwoFactorRecoveryCodes($value)
 * @method static Builder|User whereTwoFactorSecret($value)
 *
 * @property null|int                                                   $user_group_id
 * @property GroupMembership[]|\Illuminate\Database\Eloquent\Collection $groupMemberships
 * @property null|int                                                   $group_memberships_count
 * @property null|UserGroup                                             $userGroup
 *
 * @method static Builder|User whereUserGroupId($value)
 *
 * @property \Illuminate\Database\Eloquent\Collection<int, TransactionCurrency> $currencies
 * @property null|int                                                           $currencies_count
 *
 * @mixin Eloquent
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use Notifiable;

    protected $casts
                        = [
                            'created_at' => 'datetime',
                            'updated_at' => 'datetime',
                            'blocked'    => 'boolean',
                        ];
    protected $fillable = ['email', 'password', 'blocked', 'blocked_code'];
    protected $hidden   = ['password', 'remember_token'];
    protected $table    = 'users';

    /**
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            $userId = (int)$value;
            $user   = self::find($userId);
            if (null !== $user) {
                return $user;
            }
        }

        throw new NotFoundHttpException();
    }

    /**
     * Link to accounts.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Link to attachments
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Link to available budgets
     */
    public function availableBudgets(): HasMany
    {
        return $this->hasMany(AvailableBudget::class);
    }

    /**
     * Link to bills.
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Link to budgets.
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * Link to categories
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Link to currencies
     */
    public function currencies(): BelongsToMany
    {
        return $this->belongsToMany(TransactionCurrency::class)->withTimestamps()->withPivot('user_default');
    }

    /**
     * Link to currency exchange rates
     */
    public function currencyExchangeRates(): HasMany
    {
        return $this->hasMany(CurrencyExchangeRate::class);
    }

    /**
     * Generates access token.
     *
     * @throws \Exception
     */
    public function generateAccessToken(): string
    {
        $bytes = random_bytes(16);

        return bin2hex($bytes);
    }

    /**
     * A safe method that returns the user's current administration ID (group ID).
     *
     * @throws FireflyException
     */
    public function getAdministrationId(): int
    {
        $groupId = (int)$this->user_group_id;
        if (0 === $groupId) {
            throw new FireflyException('User has no administration ID.');
        }

        return $groupId;
    }

    /**
     * Get the models LDAP domain.
     *
     * @return string
     *
     * @deprecated
     */
    public function getLdapDomain()
    {
        return $this->{$this->getLdapDomainColumn()};
    }

    /**
     * Get the database column name of the domain.
     *
     * @return string
     *
     * @deprecated
     */
    public function getLdapDomainColumn()
    {
        return 'domain';
    }

    /**
     * Get the models LDAP GUID.
     *
     * @return string
     *
     * @deprecated
     */
    public function getLdapGuid()
    {
        return $this->{$this->getLdapGuidColumn()};
    }

    /**
     * Get the models LDAP GUID database column name.
     *
     * @return string
     *
     * @deprecated
     */
    public function getLdapGuidColumn()
    {
        return 'objectguid';
    }

    /**
     * Does the user have role X in group Y, or is the user the group owner of has full rights to the group?
     *
     * If $allowOverride is set to true, then the roles FULL or OWNER will also be checked,
     * which means that in most cases the user DOES have access, regardless of the original role submitted in $role.
     */
    public function hasRoleInGroupOrOwner(UserGroup $userGroup, UserRoleEnum $role): bool
    {
        $roles = [$role->value, UserRoleEnum::OWNER->value, UserRoleEnum::FULL->value];

        return $this->hasAnyRoleInGroup($userGroup, $roles);
    }

    /**
     * Does the user have role X in group Y?
     */
    public function hasSpecificRoleInGroup(UserGroup $userGroup, UserRoleEnum $role): bool
    {
        return $this->hasAnyRoleInGroup($userGroup, [$role]);
    }

    public function groupMemberships(): HasMany
    {
        return $this->hasMany(GroupMembership::class)->with(['userGroup', 'userRole']);
    }

    /**
     * Link to object groups.
     */
    public function objectGroups(): HasMany
    {
        return $this->hasMany(ObjectGroup::class);
    }

    /**
     * Link to piggy banks.
     */
    public function piggyBanks(): HasManyThrough
    {
        return $this->hasManyThrough(PiggyBank::class, Account::class);
    }

    /**
     * Link to preferences.
     */
    public function preferences(): HasMany
    {
        return $this->hasMany(Preference::class);
    }

    /**
     * Link to recurring transactions.
     */
    public function recurrences(): HasMany
    {
        return $this->hasMany(Recurrence::class);
    }

    /**
     * Get the notification routing information for the given driver.
     *
     * @param string            $driver
     * @param null|Notification $notification
     *
     * @return mixed
     */
    public function routeNotificationFor($driver, $notification = null)
    {
        $method = 'routeNotificationFor'.Str::studly($driver);
        if (method_exists($this, $method)) {
            return $this->{$method}($notification); // @phpstan-ignore-line
        }
        $email = $this->email;
        // see if user has alternative email address:
        $pref = app('preferences')->getForUser($this, 'remote_guard_alt_email');
        if (null !== $pref) {
            $email = $pref->data;
        }
        // if user is demo user, send to owner:
        if ($this->hasRole('demo')) {
            $email = config('firefly.site_owner');
        }

        return match ($driver) {
            'database' => $this->notifications(),
            'mail'     => $email,
            default    => null,
        };
    }

    /**
     * This method refers to the "global" role a user can have, outside of any group they may be part of.
     */
    public function hasRole(string $role): bool
    {
        return 1 === $this->roles()->where('name', $role)->count();
    }

    /**
     * Link to roles.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Route notifications for the Slack channel.
     */
    public function routeNotificationForSlack(Notification $notification): string
    {
        // this check does not validate if the user is owner, Should be done by notification itself.
        $res = app('fireflyconfig')->get('slack_webhook_url', '')->data;
        if (is_array($res)) {
            $res = '';
        }
        $res = (string)$res;
        if ($notification instanceof TestNotification) {
            return $res;
        }
        if ($notification instanceof UserInvitation) {
            return $res;
        }
        if ($notification instanceof UserRegistration) {
            return $res;
        }
        if ($notification instanceof VersionCheckResult) {
            return $res;
        }
        $pref = app('preferences')->getForUser($this, 'slack_webhook_url', '')->data;
        if (is_array($pref)) {
            return '';
        }

        return (string)$pref;
    }

    /**
     * Link to rule groups.
     */
    public function ruleGroups(): HasMany
    {
        return $this->hasMany(RuleGroup::class);
    }

    /**
     * Link to rules.
     */
    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class);
    }

    // start LDAP related code

    /**
     * Send the password reset notification.
     *
     * @param string $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $ipAddress = \Request::ip();

        event(new RequestedNewPassword($this, $token, $ipAddress));
    }

    /**
     * Set the models LDAP domain.
     *
     * @param string $domain
     *
     * @deprecated
     */
    public function setLdapDomain($domain): void
    {
        $this->{$this->getLdapDomainColumn()} = $domain;
    }

    /**
     * Set the models LDAP GUID.
     *
     * @param string $guid
     *
     * @deprecated
     */
    public function setLdapGuid($guid): void
    {
        $this->{$this->getLdapGuidColumn()} = $guid;
    }

    /**
     * Link to tags.
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * Link to transaction groups.
     */
    public function transactionGroups(): HasMany
    {
        return $this->hasMany(TransactionGroup::class);
    }

    /**
     * Link to transaction journals.
     */
    public function transactionJournals(): HasMany
    {
        return $this->hasMany(TransactionJournal::class);
    }

    /**
     * Link to transactions.
     */
    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough(Transaction::class, TransactionJournal::class);
    }

    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }

    /**
     * Link to webhooks
     */
    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    /**
     * Does the user have role X, Y or Z in group A?
     */
    private function hasAnyRoleInGroup(UserGroup $userGroup, array $roles): bool
    {
        app('log')->debug(sprintf('in hasAnyRoleInGroup(%s)', implode(', ', $roles)));

        /** @var Collection $dbRoles */
        $dbRoles = UserRole::whereIn('title', $roles)->get();
        if (0 === $dbRoles->count()) {
            app('log')->error(sprintf('Could not find role(s): %s. Probably migration mishap.', implode(', ', $roles)));

            return false;
        }
        $dbRolesIds    = $dbRoles->pluck('id')->toArray();
        $dbRolesTitles = $dbRoles->pluck('title')->toArray();

        /** @var Collection $groupMemberships */
        $groupMemberships = $this->groupMemberships()
            ->whereIn('user_role_id', $dbRolesIds)
            ->where('user_group_id', $userGroup->id)->get()
        ;
        if (0 === $groupMemberships->count()) {
            app('log')->error(sprintf(
                'User #%d "%s" does not have roles %s in user group #%d "%s"',
                $this->id,
                $this->email,
                implode(', ', $roles),
                $userGroup->id,
                $userGroup->title
            ));

            return false;
        }
        foreach ($groupMemberships as $membership) {
            app('log')->debug(sprintf(
                'User #%d "%s" has role "%s" in user group #%d "%s"',
                $this->id,
                $this->email,
                $membership->userRole->title,
                $userGroup->id,
                $userGroup->title
            ));
            if (in_array($membership->userRole->title, $dbRolesTitles, true)) {
                app('log')->debug(sprintf('Return true, found role "%s"', $membership->userRole->title));

                return true;
            }
        }
        app('log')->error(sprintf(
            'User #%d "%s" does not have roles %s in user group #%d "%s"',
            $this->id,
            $this->email,
            implode(', ', $roles),
            $userGroup->id,
            $userGroup->title
        ));

        return false;
    }
}
