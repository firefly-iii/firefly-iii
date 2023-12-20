<?php
/**
 * Recurrence.php
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
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\Support\Models\ReturnsIntegerUserIdTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\Recurrence
 *
 * @property int                                $id
 * @property null|Carbon                        $created_at
 * @property null|Carbon                        $updated_at
 * @property null|Carbon                        $deleted_at
 * @property int                                $user_id
 * @property int                                $transaction_type_id
 * @property string                             $title
 * @property string                             $description
 * @property null|Carbon                        $first_date
 * @property null|Carbon                        $repeat_until
 * @property null|Carbon                        $latest_date
 * @property int|string                         $repetitions
 * @property bool                               $apply_rules
 * @property bool                               $active
 * @property Attachment[]|Collection            $attachments
 * @property null|int                           $attachments_count
 * @property Collection|Note[]                  $notes
 * @property null|int                           $notes_count
 * @property Collection|RecurrenceMeta[]        $recurrenceMeta
 * @property null|int                           $recurrence_meta_count
 * @property Collection|RecurrenceRepetition[]  $recurrenceRepetitions
 * @property null|int                           $recurrence_repetitions_count
 * @property Collection|RecurrenceTransaction[] $recurrenceTransactions
 * @property null|int                           $recurrence_transactions_count
 * @property TransactionCurrency                $transactionCurrency
 * @property TransactionType                    $transactionType
 * @property User                               $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Recurrence newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Recurrence newQuery()
 * @method static Builder|Recurrence                               onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Recurrence query()
 * @method static \Illuminate\Database\Eloquent\Builder|Recurrence whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recurrence whereApplyRules($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recurrence whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recurrence whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recurrence whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recurrence whereFirstDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recurrence whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recurrence whereLatestDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recurrence whereRepeatUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recurrence whereRepetitions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recurrence whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recurrence whereTransactionTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recurrence whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recurrence whereUserId($value)
 * @method static Builder|Recurrence                               withTrashed()
 * @method static Builder|Recurrence                               withoutTrashed()
 *
 * @property int $user_group_id
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Recurrence whereUserGroupId($value)
 *
 * @mixin Eloquent
 */
class Recurrence extends Model
{
    use ReturnsIntegerIdTrait;
    use ReturnsIntegerUserIdTrait;
    use SoftDeletes;

    protected $casts
        = [
            'created_at'   => 'datetime',
            'updated_at'   => 'datetime',
            'deleted_at'   => 'datetime',
            'title'        => 'string',
            'id'           => 'int',
            'description'  => 'string',
            'first_date'   => 'date',
            'repeat_until' => 'date',
            'latest_date'  => 'date',
            'repetitions'  => 'int',
            'active'       => 'bool',
            'apply_rules'  => 'bool',
        ];

    protected $fillable
        = ['user_id', 'transaction_type_id', 'title', 'description', 'first_date', 'repeat_until', 'latest_date', 'repetitions', 'apply_rules', 'active'];

    /** @var string The table to store the data in */
    protected $table = 'recurrences';

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            $recurrenceId = (int)$value;

            /** @var User $user */
            $user = auth()->user();

            /** @var null|Recurrence $recurrence */
            $recurrence = $user->recurrences()->find($recurrenceId);
            if (null !== $recurrence) {
                return $recurrence;
            }
        }

        throw new NotFoundHttpException();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get all the notes.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    public function recurrenceMeta(): HasMany
    {
        return $this->hasMany(RecurrenceMeta::class);
    }

    public function recurrenceRepetitions(): HasMany
    {
        return $this->hasMany(RecurrenceRepetition::class);
    }

    public function recurrenceTransactions(): HasMany
    {
        return $this->hasMany(RecurrenceTransaction::class);
    }

    public function transactionCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class);
    }

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }

    protected function transactionTypeId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }
}
