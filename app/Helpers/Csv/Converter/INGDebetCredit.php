<?php
declare(strict_types = 1);
/**
 * INGDebetCredit.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;


/**
 * Class INGDebetCredit
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class INGDebetCredit extends BasicConverter implements ConverterInterface
{


    /**
     * @return int
     */
    public function convert()
    {
        if ($this->value === 'Af') {
            return -1;
        }

        return 1;
    }
}
