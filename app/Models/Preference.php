<?php
/**
 * Preference.php
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

use Crypt;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Log;

/**
 * Class Preference
 *
 * @package FireflyIII\Models
 */
class Preference extends Model
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

    /** @var array */
    protected $fillable = ['user_id', 'data', 'name', 'data'];

    /**
     * @param $value
     *
     * @return mixed
     * @throws FireflyException
     */
    public function getDataAttribute($value)
    {
        try {
            $data = Crypt::decrypt($value);
        } catch (DecryptException $e) {
            Log::error('Could not decrypt preference.', ['id' => $this->id, 'name' => $this->name, 'data' => $value]);
            throw new FireflyException(
                sprintf('Could not decrypt preference #%d. If this error persists, please run "php artisan cache:clear" on the command line.', $this->id)
            );
        }
        $unserialized = false;
        try {
            $unserialized = unserialize($data);
        } catch (Exception $e) {
            // don't care, assume is false.
        }
        if (!($unserialized === false)) {
            return $unserialized;
        }

        return json_decode($data, true);
    }

    /**
     * @param $value
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = Crypt::encrypt(serialize($value));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }
}
