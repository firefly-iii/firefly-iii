<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;

/**
 * Class BillName
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class BillName extends BasicConverter implements ConverterInterface
{

    /**
     * @return Bill
     */
    public function convert(): Bill
    {
        /** @var BillRepositoryInterface $repository */
        $repository = app('FireflyIII\Repositories\Bill\BillRepositoryInterface');

        $bill = null;
        // is mapped? Then it's easy!
        if (isset($this->mapped[$this->index][$this->value])) {
            $bill = $repository->find($this->mapped[$this->index][$this->value]);
        } else {
            $bills = $repository->getBills();
            /** @var Bill $bill */
            foreach ($bills as $bill) {
                if ($bill->name == $this->value) {
                    return $bill;
                }
            }
        }

        return $bill;
    }
}
