<?php
/**
 * Component.php
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

/**
 * Class Component
 *
 * @property int            $transaction_journal_id
 * @package FireflyIII\Models
 * @property integer        $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property string         $name
 * @property integer        $user_id
 * @property string         $class
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Component whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Component whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Component whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Component whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Component whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Component whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Component whereClass($value)
 * @mixin \Eloquent
 */
class Component extends Model
{
    protected $dates    = ['created_at', 'updated_at', 'deleted_at'];
    protected $fillable = ['user_id', 'name', 'class'];

}
