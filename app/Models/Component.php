<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Component extends Model
{
    use SoftDeletes;

    public function getDates()
    {
        return ['created_at', 'updated_at', 'deleted_at'];
    }
}
