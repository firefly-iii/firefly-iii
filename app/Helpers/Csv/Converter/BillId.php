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

        // is mapped? Then it's easy!
        if (isset($this->mapped[$this->index][$this->value])) {
            $bill = $repository->find($this->mapped[$this->index][$this->value]);
        } else {
            $bill = $repository->find($this->value);
        }

        return $bill;
    }
}
