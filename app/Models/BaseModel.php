<?php
/**
 * BaseModel.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * Class BaseModel
 *
 * @package FireflyIII\Models
 */
class BaseModel extends Model
{
    /**
     * @param $query
     * @param $table
     *
     * @return bool
     */
    public static function isJoined($query, $table)
    {
        $joins = $query->getQuery()->joins;
        if($joins == null) {
            return false;
        }
        foreach ($joins as $join) {
            if ($join->table == $table) {
                return true;
            }
        }
        return false;
    }
}