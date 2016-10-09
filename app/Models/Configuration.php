<?php
/**
 * Configuration.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FireflyIII\Models\Configuration
 *
 * @mixin \Eloquent
 * @property integer        $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property string         $name
 * @property string         $data
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Configuration whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Configuration whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Configuration whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Configuration whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Configuration whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Configuration whereData($value)
 */
class Configuration extends Model
{
    use SoftDeletes;

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
    protected $table = 'configuration';

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
        $this->attributes['data'] = json_encode($value);
    }


}
