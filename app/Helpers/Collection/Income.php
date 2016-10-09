<?php
/**
 * Income.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Collection;

use Illuminate\Support\Collection;
use stdClass;

/**
 *
 * Class Income
 *
 * @package FireflyIII\Helpers\Collection
 */
class Income
{

    /** @var Collection */
    protected $incomes;
    /** @var string */
    protected $total = '0';

    /**
     *
     */
    public function __construct()
    {
        $this->incomes = new Collection;
    }

    /**
     * @param stdClass $entry
     */
    public function addOrCreateIncome(stdClass $entry)
    {
        $this->incomes->put($entry->id, $entry);

    }

    /**
     * @param string $add
     */
    public function addToTotal(string $add)
    {
        $add         = strval(round($add, 2));
        $this->total = bcadd($this->total, $add);
    }

    /**
     * @return Collection
     */
    public function getIncomes(): Collection
    {
        $set = $this->incomes->sortByDesc(
            function (stdClass $object) {
                return $object->amount;
            }
        );

        return $set;
    }

    /**
     * @return string
     */
    public function getTotal(): string
    {
        return strval(round($this->total, 2));
    }


}
