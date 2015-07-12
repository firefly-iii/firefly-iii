<?php

namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use FireflyIII\Models\Bill;

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
    public function convert()
    {
        // is mapped? Then it's easy!
        if (isset($this->mapped[$this->index][$this->value])) {
            $bill = Auth::user()->bills()->find($this->mapped[$this->index][$this->value]);
        } else {
            $bill = Auth::user()->bills()->find($this->value);
        }

        return $bill;
    }
}
