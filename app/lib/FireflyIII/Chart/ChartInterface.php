<?php

namespace FireflyIII\Chart;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Interface ChartInterface
 *
 * @package FireflyIII\Chart
 */
interface ChartInterface
{
    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getCategorySummary(Carbon $start, Carbon $end);

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getBillsSummary(Carbon $start, Carbon $end);

}
