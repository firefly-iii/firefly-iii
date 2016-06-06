<?php
/**
 * Description.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

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
    public function process(): array
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
