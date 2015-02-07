<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class Component extends Model
{

    public function getDates()
    {
        return ['created_at', 'updated_at', 'deleted_at'];
    }
}
