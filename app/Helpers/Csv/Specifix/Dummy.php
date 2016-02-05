<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Specifix;

/**
 * Class Dummy
 *
 * @package FireflyIII\Helpers\Csv\Specifix
 */
class Dummy extends Specifix implements SpecifixInterface
{
    /** @var array */
    protected $data;

    /** @var array */
    protected $row;

    /**
     * Dummy constructor.
     */
    public function __construct()
    {
        $this->setProcessorType(self::POST_PROCESSOR);
    }

    /**
     * @return array
     */
    public function fix()
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
     * @param array $row
     */
    public function setRow(array $row)
    {
        $this->row = $row;
    }


}
