<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

/**
 * Class BasicConverter
 *
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
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
    protected $value;

    /**
     * @return array
     */
    public function getData(): array
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
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @param string $field
     */
    public function setField(string $field)
    {
        $this->field = $field;
    }

    /**
     * @return int
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * @param int $index
     */
    public function setIndex(int $index)
    {
        $this->index = $index;
    }

    /**
     * @return array
     */
    public function getMapped(): array
    {
        return $this->mapped;
    }

    /**
     * @param array $mapped
     */
    public function setMapped(array $mapped)
    {
        $this->mapped = $mapped;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value)
    {
        $this->value = $value;
    }


}
