<?php
namespace FireflyIII\Helpers\Csv\Specifix;

/**
 * Interface SpecifixInterface
 *
 * @package FireflyIII\Helpers\Csv\Specifix
 */
interface SpecifixInterface
{
    /**
     * Implement bank and locale related fixes.
     */
    public function fix();

    /**
     * @param array $data
     */
    public function setData($data);

    /**
     * @param array $row
     */
    public function setRow($row);
}