<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Component
 *
 * @codeCoverageIgnore 
 * @package FireflyIII\Models
 * @property integer $id 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property \Carbon\Carbon $deleted_at 
 * @property string $name 
 * @property integer $user_id 
 * @property string $class 
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Component whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Component whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Component whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Component whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Component whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Component whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Component whereClass($value)
 */
class Component extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'name', 'class'];

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'deleted_at'];
    }
}
