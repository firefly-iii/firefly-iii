<?php

namespace FireflyIII\Report;

use Illuminate\Support\Collection;

/**
 * Class ReportHelper
 *
 * @package FireflyIII\Report
 */
class ReportHelper implements ReportHelperInterface
{
    /**
     * Only return the top X entries, group the rest by amount
     * and described as 'Others'. id = 0 as well
     *
     * @param array $array
     * @param int   $limit
     *
     * @return array
     */
    public function limitArray(array $array, $limit = 10)
    {
        $others = [
            'name'   => 'Others',
            'amount' => 0
        ];
        $return = [];
        $count  = 0;
        foreach ($array as $id => $entry) {
            if ($count < ($limit - 1)) {
                $return[$id] = $entry;
            } else {
                $others['amount'] += $entry['amount'];
            }

            $count++;
        }
        $return[0] = $others;

        return $return;

    }

    /**
     * Turns a collection into an array. Needs the field 'id' for the key,
     * and saves only 'name' and 'amount' as a subarray.
     *
     * @param Collection $collection
     *
     * @return array
     */
    public function makeArray(Collection $collection)
    {
        $array = [];
        foreach ($collection as $entry) {
            $entry->spent = isset($entry->spent) ? floatval($entry->spent) : 0.0;
            $id           = intval($entry->id);
            if (isset($array[$id])) {
                $array[$id]['amount'] += floatval($entry->amount);
                $array[$id]['spent'] += floatval($entry->spent);
            } else {
                $array[$id] = [
                    'amount' => floatval($entry->amount),
                    'spent'  => floatval($entry->spent),
                    'name'   => $entry->name
                ];
            }
        }

        return $array;
    }

    /**
     * Merges two of the arrays as defined above. Can't handle more (yet)
     *
     * @param array $one
     * @param array $two
     *
     * @return array
     */
    public function mergeArrays(array $one, array $two)
    {
        foreach ($two as $id => $value) {
            // $otherId also exists in $one:
            if (isset($one[$id])) {
                $one[$id]['amount'] += $value['amount'];
                $one[$id]['spent'] += $value['spent'];
            } else {
                $one[$id] = $value;
            }
        }

        return $one;
    }

    /**
     * Sort an array where all 'amount' keys are negative floats.
     *
     * @param array $array
     *
     * @return array
     */
    public function sortNegativeArray(array $array)
    {
        uasort(
            $array, function ($left, $right) {
            if ($left['amount'] == $right['amount']) {
                return 0;
            }

            return ($left['amount'] < $right['amount']) ? -1 : 1;
        }
        );

        return $array;
    }
}