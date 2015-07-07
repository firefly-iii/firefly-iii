<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 05/07/15
 * Time: 19:20
 */

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