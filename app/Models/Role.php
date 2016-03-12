<?php
declare(strict_types = 1);
namespace FireflyIII\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


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
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\User[] $users
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Role whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Role whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Role whereDisplayName($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Role whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Role whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Role extends Model
{

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany('FireflyIII\User');
    }

}
