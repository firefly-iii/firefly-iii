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
 * @property string                $data_encrypted
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
    protected $hidden   = ['data_encrypted', 'name_encrypted'];

    /**
     * @param $value
     *
     * @return mixed
     */
    public function getDataAttribute($value)
    {
        if (is_null($this->data_encrypted)) {
            return json_decode($value);
        }
        $data = Crypt::decrypt($this->data_encrypted);

        return json_decode($data);
    }

    /**
     * @param $value
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data']           = '';
        $this->attributes['data_encrypted'] = Crypt::encrypt(json_encode($value));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }

}
