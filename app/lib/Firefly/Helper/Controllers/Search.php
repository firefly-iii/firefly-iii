<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 21/09/14
 * Time: 20:58
 */

namespace Firefly\Helper\Controllers;

/**
 * Class Search
 *
 * @package Firefly\Helper\Controllers
 */
class Search implements SearchInterface
{

    /**
     * @param array $words
     */
    public function transactions(array $words)
    {
        $query = \TransactionJournal::withRelevantData();

        $fullCount = $query->count();

        $query->where(
            function ($q) use ($words) {
                foreach ($words as $word) {
                    $q->orWhere('description', 'LIKE', '%' . e($word) . '%');
                }
            }
        );
        $count = $query->count();
        $set = $query->get();
        /*
         * Build something with JSON?
         */
        return $set;
    }

} 