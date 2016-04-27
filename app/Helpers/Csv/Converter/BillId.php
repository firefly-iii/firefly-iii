<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;

/**
 * Class BillId
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class BillId extends BasicConverter implements ConverterInterface
{

    /**
     * @return Bill
     */
    public function convert(): Bill
    {
        /** @var BillRepositoryInterface $repository */
        $repository = app('FireflyIII\Repositories\Bill\BillRepositoryInterface');
        $value      = isset($this->mapped[$this->index][$this->value]) ? $this->mapped[$this->index][$this->value] : $this->value;
        $bill       = $repository->find($value);

        return $bill;
    }
}
