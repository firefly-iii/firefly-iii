<?php
/**
 * PiggyBank.php
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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class PiggyBank.
 *
 * @property Carbon                                                                                 $targetdate
 * @property Carbon                                                                                 $startdate
 * @property string                                                                                 $targetamount
 * @property int                                                                                    $id
 * @property string                                $name
 * @property Account                               $account
 * @property Carbon                                $updated_at
 * @property Carbon                                $created_at
 * @property int                                   $order
 * @property bool                                  $active
 * @property int                                   $account_id
 * @property bool                                  encrypted
 * @property \Illuminate\Support\Carbon|null       $deleted_at
 * @property-read Collection|Note[]                $notes
 * @property-read Collection|PiggyBankEvent[]      $piggyBankEvents
 * @property-read Collection|PiggyBankRepetition[] $piggyBankRepetitions
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank newQuery()
 * @method static Builder|PiggyBank onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereStartdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereTargetamount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereTargetdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PiggyBank whereUpdatedAt($value)
 * @method static Builder|PiggyBank withTrashed()
 * @method static Builder|PiggyBank withoutTrashed()
 * @mixin Eloquent
 * @property-read Collection|Attachment[] $attachments
 * @property-read int|null                $attachments_count
 * @property-read int|null                $notes_count
 * @property-read int|null                $piggy_bank_events_count
 * @property-read int|null                $piggy_bank_repetitions_count
 */
class PiggyBank extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'startdate'  => 'date',
            'targetdate' => 'date',
            'order'      => 'int',
            'active'     => 'boolean',
            'encrypted'  => 'boolean',
        ];
    /** @var array Fields that can be filled */
    protected $fillable = ['name', 'account_id', 'order', 'targetamount', 'startdate', 'targetdate', 'active'];
    /** @var array Hidden from view */
    protected $hidden = ['targetamount_encrypted', 'encrypted'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @throws NotFoundHttpException
     * @return PiggyBank
     */
    public static function routeBinder(string $value): PiggyBank
    {
        if (auth()->check()) {
            $piggyBankId = (int) $value;
            $piggyBank   = self::where('piggy_banks.id', $piggyBankId)
                               ->leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id')
                               ->where('accounts.user_id', auth()->user()->id)->first(['piggy_banks.*']);
            if (null !== $piggyBank) {
                return $piggyBank;
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
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @codeCoverageIgnore
     * Get all of the piggy bank's notes.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /**
     * @codeCoverageIgnore
     * @return HasMany
     */
    public function piggyBankEvents(): HasMany
    {
        return $this->hasMany(PiggyBankEvent::class);
    }

    /**
     * @codeCoverageIgnore
     * @return HasMany
     */
    public function piggyBankRepetitions(): HasMany
    {
        return $this->hasMany(PiggyBankRepetition::class);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     */
    public function setTargetamountAttribute($value): void
    {
        $this->attributes['targetamount'] = (string) $value;
    }
}
