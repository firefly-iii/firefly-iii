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
interface PostProcessorInterface {

    /**
     * @param array $data
     */
    public function setData(array $data);

    /**
     * @return array
     */
    public function process();
}