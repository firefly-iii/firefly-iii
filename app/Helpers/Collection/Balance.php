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


}
