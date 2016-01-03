<?php namespace FireflyIII\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FireflyIII\Models\Component
 *
 * @property integer $id
 * @property Carbon  $created_at
 * @property Carbon  $updated_at
 * @property Carbon  $deleted_at
 * @property string  $name
 * @property integer $user_id
 * @property string  $class
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
