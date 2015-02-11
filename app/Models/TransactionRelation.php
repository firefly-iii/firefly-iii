<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TransactionRelation
 *
 * @package FireflyIII\Models
 */
class TransactionRelation extends Model
{

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at'];
    }

}
