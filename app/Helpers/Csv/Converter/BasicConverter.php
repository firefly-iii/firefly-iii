<?php

namespace FireflyIII\Helpers\Csv\Converter;

/**
 * Class BasicConverter
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class BasicConverter
{
    /** @var  array */
    protected $data;
    protected $index;
    /** @var  array */
    protected $mapped;
    protected $role;
    protected $value;

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param mixed $index
     */
    public function setIndex($index)
    {
        $this->index = $index;
    }

    /**
     * @return array
     */
    public function getMapped()
    {
        return $this->mapped;
    }

    /**
     * @param array $mapped
     */
    public function setMapped($mapped)
    {
        $this->mapped = $mapped;
    }

    /**
     * @return mixed
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param mixed $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }


}