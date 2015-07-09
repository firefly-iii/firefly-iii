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
    /** @var string */
    protected $field;
    /** @var int */
    protected $index;
    /** @var  array */
    protected $mapped;
    /** @var string */
    protected $role;
    /** @var string */
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
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param string $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param int $index
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
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param string $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }


}
