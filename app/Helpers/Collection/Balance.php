<?php
/**
 * Balance.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
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
