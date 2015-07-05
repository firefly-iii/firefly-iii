<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 05/07/15
 * Time: 05:49
 */

namespace FireflyIII\Helpers\Csv\Converter;

use FireflyIII\Models\Account;

/**
 * Class Amount
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class Amount extends BasicConverter implements ConverterInterface
{

    /**
     * @return Account|null
     */
    public function convert()
    {
        if (is_numeric($this->value)) {
            return $this->value;
        }

        return 0;
    }
}