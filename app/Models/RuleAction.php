<?php
/**
 * RuleAction.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class RuleAction
 *
 * @package FireflyIII\Models
 */
class RuleAction extends Model
{
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at'      => 'date',
            'updated_at'      => 'date',
            'active'          => 'boolean',
            'order'           => 'int',
            'stop_processing' => 'boolean',
        ];
    /** @var array */
    protected $dates = ['created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function rule()
    {
        return $this->belongsTo('FireflyIII\Models\Rule');
    }
}
