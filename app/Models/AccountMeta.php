<?php
/**
 * AccountMeta.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class AccountMeta.
 *
 * @property string $data
 * @property string $name
 * @property int    $account_id
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \FireflyIII\Models\Account $account
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AccountMeta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AccountMeta newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AccountMeta query()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AccountMeta whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AccountMeta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AccountMeta whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AccountMeta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AccountMeta whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\AccountMeta whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AccountMeta extends Model
{
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    /** @var array Fields that can be filled */
    protected $fillable = ['account_id', 'name', 'data'];
    /** @var string The table to store the data in */
    protected $table = 'account_meta';

    /**
     * @return BelongsTo
     * @codeCoverageIgnore
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @param $value
     *
     * @codeCoverageIgnore
     * @return mixed
     */
    public function getDataAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * @param $value
     *
     * @codeCoverageIgnore
     */
    public function setDataAttribute($value): void
    {
        $this->attributes['data'] = json_encode($value);
    }
}
