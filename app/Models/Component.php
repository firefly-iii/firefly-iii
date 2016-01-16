<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Component
 *
 * @package FireflyIII\Models
 */
class Component extends Model
{
    protected $fillable = ['user_id', 'name', 'class'];

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'deleted_at'];
    }

}
