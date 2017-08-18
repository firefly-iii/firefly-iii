<?php
/**
 * Preference.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
            'created_at' => 'date',
            'updated_at' => 'date',
        ];
    protected $dates    = ['created_at', 'updated_at'];
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
