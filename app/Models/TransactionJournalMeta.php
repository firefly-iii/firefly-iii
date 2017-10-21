<?php
/**
 * TransactionJournalMeta.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TransactionJournalMeta
 *
 * @package FireflyIII\Models
 */
class TransactionJournalMeta extends Model
{

    use SoftDeletes;
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
                        = [
            'created_at' => 'date',
            'updated_at' => 'date',
            'deleted_at' => 'date',
        ];
    protected $dates    = ['created_at', 'updated_at', 'deleted_at'];
    protected $fillable = ['transaction_journal_id', 'name', 'data', 'hash'];
    protected $table    = 'journal_meta';

    /**
     * @param $value
     *
     * @return mixed
     */
    public function getDataAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * @param $value
     */
    public function setDataAttribute($value)
    {
        $data                     = json_encode($value);
        $this->attributes['data'] = $data;
        $this->attributes['hash'] = hash('sha256', $data);
    }

    /**
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionJournal(): BelongsTo
    {
        return $this->belongsTo('FireflyIII\Models\TransactionJournal');
    }
}
