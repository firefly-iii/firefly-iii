<?php

namespace FireflyIII\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Zizaco\Entrust\EntrustPermission;

/**
 * FireflyIII\Models\Permission
 *
 * @property integer                $id
 * @property string                 $name
 * @property string                 $display_name
 * @property string                 $description
 * @property Carbon                 $created_at
 * @property Carbon                 $updated_at
 * @property-read Collection|Role[] $roles
 */
class Permission extends EntrustPermission
{
}
