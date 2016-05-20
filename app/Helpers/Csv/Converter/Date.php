<?php
/**
 * Date.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use InvalidArgumentException;
use Log;

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
    public function convert(): Carbon
    {
        $format = session('csv-date-format');
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
