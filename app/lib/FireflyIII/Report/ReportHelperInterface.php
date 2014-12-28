<?php

namespace FireflyIII\Report;

use Illuminate\Support\Collection;

/**
 * Interface ReportHelperInterface
 *
 * @package FireflyIII\Report
 */
interface ReportHelperInterface
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
    public function limitArray(array $array, $limit = 10);

    /**
     * Turns a collection into an array. Needs the field 'id' for the key,
     * and saves 'name', 'amount','spent' (if present) as a subarray.
     *
     * @param Collection $collection
     *
     * @return array
     */
    public function makeArray(Collection $collection);

    /**
     * Merges two of the arrays as defined above. Can't handle more (yet)
     *
     * @param array $one
     * @param array $two
     *
     * @return array
     */
    public function mergeArrays(array $one, array $two);

    /**
     * Sort an array where all 'amount' keys are negative floats.
     *
     * @param array $array
     *
     * @return array
     */
    public function sortNegativeArray(array $array);

    /**
     * Sort an array where all 'amount' keys are positive floats.
     *
     * @param array $array
     *
     * @return array
     */
    public function sortArray(array $array);

}