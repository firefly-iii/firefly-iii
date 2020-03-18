<?php
/**
 * TransactionGroup.php
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
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


/**
 * Class TransactionGroup.
 *
 * @property int                                                                $id
 * @property Carbon|null                                    $created_at
 * @property Carbon|null                                    $updated_at
 * @property Carbon|null                                    $deleted_at
 * @property int                                                                $user_id
 * @property string|null                                                        $title
 * @property-read Collection|TransactionJournal[] $transactionJournals
 * @property-read User                                                          $user
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionGroup newQuery()
 * @method static Builder|TransactionGroup onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionGroup query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionGroup whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionGroup whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionGroup whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionGroup whereUserId($value)
 * @method static Builder|TransactionGroup withTrashed()
 * @method static Builder|TransactionGroup withoutTrashed()
 * @mixin Eloquent
 * @property string                                                                                amount
 * @property string                                                                                foreign_amount
 * @property int                                                                                   transaction_group_id
 * @property int                                                                                   transaction_journal_id
 * @property string                                                                                transaction_group_title
 */
class TransactionGroup extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'id'         => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'title'      => 'string',
            'date'       => 'datetime',
        ];

    /** @var array Fields that can be filled */
    protected $fillable = ['user_id', 'title'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @throws NotFoundHttpException
     * @return TransactionGroup
     */
    public static function routeBinder(string $value): TransactionGroup
    {
        if (auth()->check()) {
            $groupId = (int) $value;
            /** @var User $user */
            $user = auth()->user();
            /** @var TransactionGroup $group */
            $group = $user->transactionGroups()
                          ->with(['transactionJournals', 'transactionJournals.transactions'])
                          ->where('transaction_groups.id', $groupId)->first(['transaction_groups.*']);
            if (null !== $group) {
                return $group;
            }
        }

        throw new NotFoundHttpException;
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
