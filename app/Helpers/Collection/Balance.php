<?php
/**
 * Balance.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Helpers\Collection;

use Illuminate\Support\Collection;

/**
 *
 * Class Balance
 *
 * @package FireflyIII\Helpers\Collection
 */
class Balance
{

    /** @var  BalanceHeader */
    protected $balanceHeader;

    /** @var  Collection */
    protected $balanceLines;

    /**
     *
     */
    public function __construct()
    {
        $this->balanceLines = new Collection;
    }

    /**
     * @param BalanceLine $line
     */
    public function addBalanceLine(BalanceLine $line)
    {
        $this->balanceLines->push($line);
    }

    /**
     * @return BalanceHeader
     */
    public function getBalanceHeader(): BalanceHeader
    {
        return $this->balanceHeader ?? new BalanceHeader;
    }

    /**
     * @param BalanceHeader $balanceHeader
     */
    public function setBalanceHeader(BalanceHeader $balanceHeader)
    {
        $this->balanceHeader = $balanceHeader;
    }

    /**
     * @return Collection
     */
    public function getBalanceLines(): Collection
    {
        return $this->balanceLines;
    }

    /**
     * @param Collection $balanceLines
     */
    public function setBalanceLines(Collection $balanceLines)
    {
        $this->balanceLines = $balanceLines;
    }
}
