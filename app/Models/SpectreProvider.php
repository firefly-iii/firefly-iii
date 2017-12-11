<?php
declare(strict_types=1);

namespace FireflyIII\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class SpectreProvider
 */
class SpectreProvider extends Model
{
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'spectre_id'      => 'int',
            'created_at'      => 'datetime',
            'updated_at'      => 'datetime',
            'deleted_at'      => 'datetime',
            'interactive'     => 'boolean',
            'automatic_fetch' => 'boolean',
            'data'            => 'array',
        ];

    protected $fillable = ['spectre_id', 'code', 'mode', 'name', 'status', 'interactive', 'automatic_fetch', 'country_code', 'data'];

}