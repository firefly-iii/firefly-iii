<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 05/07/15
 * Time: 05:49
 */

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