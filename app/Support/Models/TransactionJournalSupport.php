<?php
/**
 * TransactionJournalSupport.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Support\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TransactionJournalSupport
 *
 * @package FireflyIII\Support\Models
 */
class TransactionJournalSupport extends Model
{
    /**
     * @param Builder $query
     * @param string  $table
     *
     * @return bool
     */
    public static function isJoined(Builder $query, string $table):bool
    {
        $joins = $query->getQuery()->joins;
        if (is_null($joins)) {
            return false;
        }
        foreach ($joins as $join) {
            if ($join->table === $table) {
                return true;
            }
        }

        return false;
    }

}