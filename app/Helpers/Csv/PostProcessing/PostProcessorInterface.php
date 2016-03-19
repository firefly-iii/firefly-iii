<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\PostProcessing;


/**
 * Interface PostProcessorInterface
 *
 * @package FireflyIII\Helpers\Csv\PostProcessing
 */
interface PostProcessorInterface
{

    /**
     * @return array
     */
    public function process();

    /**
     * @param array $data
     */
    public function setData(array $data);
}
