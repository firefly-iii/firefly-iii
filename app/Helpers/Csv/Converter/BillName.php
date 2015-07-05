<?php

namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use FireflyIII\Models\Bill;

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
    public function convert()
    {
        $bill = null;
        // is mapped? Then it's easy!
        if (isset($this->mapped[$this->index][$this->value])) {
            $bill = Auth::user()->bills()->find($this->mapped[$this->index][$this->value]);
        } else {

            $bills = Auth::user()->bills()->get();
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