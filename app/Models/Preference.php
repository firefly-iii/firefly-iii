<?php
/**
 * Preference.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Models;

use Crypt;
use FireflyIII\Exceptions\FireflyException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;

/**
 * FireflyIII\Models\Preference
 *
 * @property integer               $id
 * @property \Carbon\Carbon        $created_at
 * @property \Carbon\Carbon        $updated_at
 * @property integer               $user_id
 * @property string                $name
 * @property string                $name_encrypted
 * @property string                $data
 * @property-read \FireflyIII\User $user
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Preference whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Preference whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Preference whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Preference whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Preference whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Preference whereNameEncrypted($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Preference whereData($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Preference whereDataEncrypted($value)
 * @mixin \Eloquent
 */
class Preference extends Model
{

    protected $dates    = ['created_at', 'updated_at'];
    protected $fillable = ['user_id', 'data', 'name'];

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
            throw new FireflyException('Could not decrypt preference #' . $this->id . '.');
        }

        return json_decode($data);
    }

    /**
     * @param $value
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = Crypt::encrypt(json_encode($value));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }

}
