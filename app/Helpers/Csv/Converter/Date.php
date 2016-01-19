<?php

namespace FireflyIII\Helpers\Csv\Converter;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use InvalidArgumentException;
use Log;
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
     * @throws FireflyException
     */
    public function convert()
    {
        $format = Session::get('csv-date-format');
        try {
            $date = Carbon::createFromFormat($format, $this->value);
        } catch (InvalidArgumentException $e) {
            Log::error('Date conversion error: ' . $e->getMessage() . '. Value was "' . $this->value . '", format was "' . $format . '".');

            $message = trans('firefly.csv_date_parse_error', ['format' => $format, 'value' => $this->value]);

            throw new FireflyException($message);
        }


        return $date;
    }
}
