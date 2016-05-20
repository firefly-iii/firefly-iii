<?php
/**
 * ConverterInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

/**
 * Interface ConverterInterface
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
interface ConverterInterface
{

    /**
     * @return mixed
     */
    public function convert();

    /**
     * @param array $data
     */
    public function setData(array $data);

    /**
     * @param string $field
     *
     */
    public function setField(string $field);

    /**
     * @param int $index
     */
    public function setIndex(int $index);

    /**
     * @param array $mapped
     */
    public function setMapped(array $mapped);

    /**
     * @param string $value
     */
    public function setValue(string $value);

}
