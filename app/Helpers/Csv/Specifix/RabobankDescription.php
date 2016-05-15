<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Specifix;

/**
 * Class RabobankDescription
 *
 * @package FireflyIII\Helpers\Csv\Specifix
 */
class RabobankDescription extends Specifix implements SpecifixInterface
{
    /** @var array */
    protected $data;

    /** @var array */
    protected $row;

    /**
     * RabobankDescription constructor.
     */
    public function __construct()
    {
        $this->setProcessorType(self::POST_PROCESSOR);
    }


    /**
     * @return array
     */
    public function fix(): array
    {
        $this->rabobankFixEmptyOpposing();

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

    /**
     * Fixes Rabobank specific thing.
     */
    protected function rabobankFixEmptyOpposing()
    {
        if (is_string($this->data['opposing-account-name']) && strlen($this->data['opposing-account-name']) == 0) {
            $this->data['opposing-account-name'] = $this->row[10];

            $this->data['description'] = trim(str_replace($this->row[10], '', $this->data['description']));
        }

    }


}
