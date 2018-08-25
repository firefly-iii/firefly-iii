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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Helpers\Collection;

use Illuminate\Support\Collection;

/**
 * Class Balance.
 *
 * @codeCoverageIgnore
 */
class Balance
{
    /** @var BalanceHeader Header row. */
    protected $balanceHeader;

    /** @var Collection Collection of lines. */
    protected $balanceLines;

    /**
     * Balance constructor.
     */
    public function __construct()
    {
        $this->balanceLines = new Collection;
    }

    /**
     * Add a line.
     *
     * @param BalanceLine $line
     */
    public function addBalanceLine(BalanceLine $line): void
    {
        $this->balanceLines->push($line);
    }

    /**
     * Get the header.
     *
     * @return BalanceHeader
     */
    public function getBalanceHeader(): BalanceHeader
    {
        return $this->balanceHeader ?? new BalanceHeader;
    }

    /**
     * Set the header.
     *
     * @param BalanceHeader $balanceHeader
     */
    public function setBalanceHeader(BalanceHeader $balanceHeader): void
    {
        $this->balanceHeader = $balanceHeader;
    }

    /**
     * Get all lines.
     *
     * @return Collection
     */
    public function getBalanceLines(): Collection
    {
        return $this->balanceLines;
    }

    /**
     * Set all lines.
     *
     * @param Collection $balanceLines
     */
    public function setBalanceLines(Collection $balanceLines): void
    {
        $this->balanceLines = $balanceLines;
    }
}
