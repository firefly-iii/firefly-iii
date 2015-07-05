<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 05/07/15
 * Time: 05:42
 */

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
     * @param $index
     */
    public function setIndex($index);

    /**
     * @param $mapped
     */
    public function setMapped($mapped);

    /**
     * @param $role
     */
    public function setRole($role);

    /**
     * @param $value
     */
    public function setValue($value);

    /**
     * @param array $data
     *
     * @return mixed
     */
    public function setData(array $data);

}