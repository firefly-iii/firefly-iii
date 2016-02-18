<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Collection;

use Illuminate\Support\Collection;

/**
 * @codeCoverageIgnore
 * Class Account
 *
 * @package FireflyIII\Helpers\Collection
 */
class Account
{

    /** @var Collection */
    protected $accounts;
    /** @var string */
    protected $difference;
    /** @var string */
    protected $end;
    /** @var string */
    protected $start;

    /**
     * @return Collection
     */
    public function getAccounts(): Collection
    {
        return $this->accounts;
    }

    /**
     * @param Collection $accounts
     */
    public function setAccounts(Collection $accounts)
    {
        $this->accounts = $accounts;
    }

    /**
     * @return string
     */
    public function getDifference(): string
    {
        return $this->difference;
    }

    /**
     * @param string $difference
     */
    public function setDifference(string $difference)
    {
        $this->difference = $difference;
    }

    /**
     * @return string
     */
    public function getEnd(): string
    {
        return $this->end;
    }

    /**
     * @param string $end
     */
    public function setEnd(string $end)
    {
        $this->end = $end;
    }

    /**
     * @return string
     */
    public function getStart(): string
    {
        return $this->start;
    }

    /**
     * @param string $start
     */
    public function setStart(string $start)
    {
        $this->start = $start;
    }


}
