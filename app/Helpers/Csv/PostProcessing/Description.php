<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\PostProcessing;

/**
 * Class Description
 *
 * @package FireflyIII\Helpers\Csv\PostProcessing
 */
class Description implements PostProcessorInterface
{

    /** @var  array */
    protected $data;

    /**
     * @return array
     */
    public function process()
    {
        $description               = $this->data['description'] ?? '';
        $this->data['description'] = trim($description);
        if (strlen($this->data['description']) == 0) {
            $this->data['description'] = trans('firefly.csv_empty_description');
        }


        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {

        $this->data = $data;
    }
}
