<?php namespace FireflyIII\Models;

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
