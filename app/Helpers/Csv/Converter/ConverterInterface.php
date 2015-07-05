<?php

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
     * @param int $index
     */
    public function setIndex($index);

    /**
     * @param array $mapped
     */
    public function setMapped($mapped);

    /**
     * @param string $role
     */
    public function setRole($role);

    /**
     * @param string $value
     */
    public function setValue($value);

    /**
     * @param array $data
     */
    public function setData(array $data);

    /**
     * @param string $field
     *
     */
    public function setField($field);

}