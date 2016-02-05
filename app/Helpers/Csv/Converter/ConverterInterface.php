<?php
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
    public function setField($field);

    /**
     * @param int $index
     */
    public function setIndex($index);

    /**
     * @param array $mapped
     */
    public function setMapped($mapped);

    /**
     * @param string $value
     */
    public function setValue($value);

}
