<?php
/**
 * Bill.php
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

namespace FireflyIII\Models;

use Carbon\Carbon;
use Eloquent;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Bill.
 *
 * @property bool                                                                                  $active
 * @property int                                                                                   $transaction_currency_id
 * @property string                                                                                $amount_min
 * @property string                                                                                $amount_max
 * @property int                                                                                   $id
 * @property string                                                                                $name
 * @property Collection                                                                            $notes
 * @property TransactionCurrency                                                                   $transactionCurrency
 * @property Carbon                                                                                $created_at
 * @property Carbon                                                                                $updated_at
 * @property Carbon                                                                                $date
 * @property string                                                                                $repeat_freq
 * @property int                                                                                   $skip
 * @property bool                                                               $automatch
 * @property User                                                               $user
 * @property string                                                             $match
 * @property bool                                                               match_encrypted
 * @property bool                                                               name_encrypted
 * @SuppressWarnings (PHPMD.CouplingBetweenObjects)
 * @property \Illuminate\Support\Carbon|null                                    $deleted_at
 * @property int                                                                $user_id
 * @property-read \Illuminate\Database\Eloquent\Collection|Attachment[]         $attachments
 * @property-read \Illuminate\Database\Eloquent\Collection|TransactionJournal[] $transactionJournals
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|Bill newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Bill newQuery()
 * @method static Builder|Bill onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Bill query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereAmountMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereAmountMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereAutomatch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereMatch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereMatchEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereNameEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereRepeatFreq($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereSkip($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereTransactionCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereUserId($value)
 * @method static Builder|Bill withTrashed()
 * @method static Builder|Bill withoutTrashed()
 * @mixin Eloquent
 * @property-read int|null $attachments_count
 * @property-read int|null $notes_count
 * @property-read int|null $transaction_journals_count
 * @property bool $name_encrypted
 * @property bool $match_encrypted
 */
class Bill extends Model
{
    use SoftDeletes;
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at'      => 'datetime',
            'updated_at'      => 'datetime',
            'deleted_at'      => 'datetime',
            'date'            => 'date',
            'skip'            => 'int',
            'automatch'       => 'boolean',
            'active'          => 'boolean',
            'name_encrypted'  => 'boolean',
            'match_encrypted' => 'boolean',
        ];

    /** @var array Fields that can be filled */
    protected $fillable
        = ['name', 'match', 'amount_min', 'user_id', 'amount_max', 'date', 'repeat_freq', 'skip',
           'automatch', 'active', 'transaction_currency_id'];
    /** @var array Hidden from view */
    protected $hidden = ['amount_min_encrypted', 'amount_max_encrypted', 'name_encrypted', 'match_encrypted'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @throws NotFoundHttpException
     * @return Bill
     */
    public static function routeBinder(string $value): Bill
    {
        if (auth()->check()) {
            $billId = (int) $value;
            /** @var User $user */
            $user = auth()->user();
            /** @var Bill $bill */
            $bill = $user->bills()->find($billId);
            if (null !== $bill) {
                return $bill;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * @return MorphMany
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * @codeCoverageIgnore
     * Get all of the notes.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /**
     * Get all of the tags for the post.
     */
    public function objectGroups()
    {
        return $this->morphToMany(ObjectGroup::class, 'object_groupable');
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     */
    public function setAmountMaxAttribute($value): void
    {
        $this->attributes['amount_max'] = (string) $value;
    }

    /**
     * @param $value
     *
     * @codeCoverageIgnore
     */
    public function setAmountMinAttribute($value): void
    {
        $this->attributes['amount_min'] = (string) $value;
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function transactionCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class);
    }

    /**
     * @codeCoverageIgnore
     * @return HasMany
     */
    public function transactionJournals(): HasMany
    {
        return $this->hasMany(TransactionJournal::class);
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
