<?php
declare(strict_types = 1);

namespace FireflyIII\Models;

use Zizaco\Entrust\EntrustPermission;

/**
 * FireflyIII\Models\Permission
 *
 * @property integer                                              $id
 * @property string                                               $name
 * @property string                                               $display_name
 * @property string                                               $description
 * @property \Carbon\Carbon                                       $created_at
 * @property \Carbon\Carbon                                       $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|Role[] $roles
 */
class Permission extends EntrustPermission
{
}
