<?php

namespace FireflyIII\Helpers\Csv\Converter;

use Carbon\Carbon;
use Session;

/**
 * Class Date
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class Date extends BasicConverter implements ConverterInterface
{

    /**
     * @return Carbon
     */
    public function convert()
    {
        $format = Session::get('csv-date-format');

        $date = Carbon::createFromFormat($format, $this->value);

        return $date;
    }
}
