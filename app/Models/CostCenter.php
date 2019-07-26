<?php
/**
 * CostCenter.php
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

namespace FireflyIII\Models;

use Carbon\Carbon;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class CostCenter.
 *
 * @property string      $name
 * @property int         $id
 * @property float       $spent // used in costCenter reports
 * @property Carbon|null lastActivity
 * @property bool        encrypted
 * @property User        $user
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 */
class CostCenter extends Model
{
    use SoftDeletes;

    protected $table = 'cost_center';

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
            'encrypted'  => 'boolean',
        ];
    /** @var array Fields that can be filled */
    protected $fillable = ['user_id', 'name'];
    /** @var array Hidden from view */
    protected $hidden = ['encrypted'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return CostCenter
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): CostCenter
    {
        if (auth()->check()) {
            $costCenterId = (int)$value;
            /** @var User $user */
            $user = auth()->user();
            /** @var CostCenter $costCenter */
            $costCenter = $user->costCenters()->find($costCenterId);
            if (null !== $costCenter) {
                return $costCenter;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsToMany
     */
    public function transactionJournals(): BelongsToMany
    {
        return $this->belongsToMany(TransactionJournal::class, 'cost_center_transaction_journal', 'cost_center_id');
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsToMany
     */
    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(Transaction::class, 'cost_center_transaction', 'cost_center_id');
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
