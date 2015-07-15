<?php

namespace FireflyIII\Models;

use Zizaco\Entrust\EntrustPermission;

/**
 * Class Permission
 *
 * @package FireflyIII\Models
 * @property integer        $id
 * @property string         $name
 * @property string         $display_name
 * @property string         $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Config::get('entrust.role[] $roles
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Permission whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Permission whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Permission whereDisplayName($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Permission whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Permission whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Permission whereUpdatedAt($value)
 */
class Permission extends EntrustPermission
{
}
