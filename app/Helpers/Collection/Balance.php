<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Collection;

use Illuminate\Support\Collection;

/**
 * @codeCoverageIgnore
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
    public function getBalanceHeader()
    {
        return $this->balanceHeader;
    }

    /**
     * @param BalanceHeader $balanceHeader
     */
    public function setBalanceHeader(BalanceHeader $balanceHeader)
    {
        $this->balanceHeader = $balanceHeader;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getBalanceLines()
    {
        return $this->balanceLines;
    }


}
