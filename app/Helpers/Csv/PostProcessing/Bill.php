<?php
/**
 * Bill.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\PostProcessing;

/**
 * Class Bill
 *
 * @package FireflyIII\Helpers\Csv\PostProcessing
 */
class Bill implements PostProcessorInterface
{

    /** @var  array */
    protected $data;

    /**
     * @return array
     */
    public function process(): array
    {

        // get bill id.
        if (!is_null($this->data['bill']) && !is_null($this->data['bill']->id)) {
            $this->data['bill-id'] = $this->data['bill']->id;
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
