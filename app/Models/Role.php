<?php
namespace FireflyIII\Models;


use Zizaco\Entrust\EntrustRole;

/**
 * Class Role
 *
 * @package FireflyIII\Models
 * @property integer $id 
 * @property string $name 
 * @property string $display_name 
 * @property string $description 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property-read \Illuminate\Database\Eloquent\Collection|\Config::get('auth.model[] $users 
 * @property-read \Illuminate\Database\Eloquent\Collection|\Config::get('entrust.permission[] $perms 
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Role whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Role whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Role whereDisplayName($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Role whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Role whereUpdatedAt($value)
 */
class Role extends EntrustRole
{
}
