<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionRelation extends Model
{

    public function getDates()
    {
        return ['created_at', 'updated_at'];
    }

}
