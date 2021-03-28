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

use Eloquent;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\Bill
 *
 * @property int                                  $id
 * @property Carbon|null                          $created_at
 * @property Carbon|null                          $updated_at
 * @property Carbon|null                          $deleted_at
 * @property int                                  $user_id
 * @property int|null                             $transaction_currency_id
 * @property string                               $name
 * @property string                               $match
 * @property string                               $amount_min
 * @property string                               $amount_max
 * @property Carbon                               $date
 * @property string|null                          $end_date
 * @property string|null                          $extension_date
 * @property string                               $repeat_freq
 * @property int                                  $skip
 * @property bool                                 $automatch
 * @property bool                                 $active
 * @property bool                                 $name_encrypted
 * @property bool                                 $match_encrypted
 * @property int                                  $order
 * @property-read Collection|Attachment[]         $attachments
 * @property-read int|null                        $attachments_count
 * @property-read Collection|Note[]               $notes
 * @property-read int|null                        $notes_count
 * @property-read Collection|ObjectGroup[]        $objectGroups
 * @property-read int|null                        $object_groups_count
 * @property-read TransactionCurrency|null        $transactionCurrency
 * @property-read Collection|TransactionJournal[] $transactionJournals
 * @property-read int|null                        $transaction_journals_count
 * @property-read User                            $user
 * @method static \Illuminate\Database\Eloquent\Builder|Bill newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Bill newQuery()
 * @method static Builder|Bill onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Bill query()
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereAmountMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereAmountMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereAutomatch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereExtensionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereMatch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereMatchEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereNameEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereRepeatFreq($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereSkip($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereTransactionCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bill whereUserId($value)
 * @method static Builder|Bill withTrashed()
 * @method static Builder|Bill withoutTrashed()
 * @mixin Eloquent
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
     * @return Bill
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): Bill
    {
        if (auth()->check()) {
            $billId = (int)$value;
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
        $this->attributes['amount_max'] = (string)$value;
    }

    /**
     * @param $value
     *
     * @codeCoverageIgnore
     */
    public function setAmountMinAttribute($value): void
    {
        $this->attributes['amount_min'] = (string)$value;
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
