<?php
namespace FireflyIII\Models;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Zizaco\Entrust\EntrustRole;

/**
 * FireflyIII\Models\Role
 *
 * @property integer                            $id
 * @property string                             $name
 * @property string                             $display_name
 * @property string                             $description
 * @property Carbon                             $created_at
 * @property Carbon                             $updated_at
 * @property-read Collection|\FireflyIII\User[] $users
 * @property-read Collection|Permission[]       $perms
 */
class Role extends EntrustRole
{
}
