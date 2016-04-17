<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Specifix;

/**
 * Interface SpecifixInterface
 *
 * @package FireflyIII\Helpers\Csv\Specifix
 */
interface SpecifixInterface
{
    const PRE_PROCESSOR  = 1;
    const POST_PROCESSOR = 2;

    /**
     * Implement bank and locale related fixes.
     */
    public function fix();

    /**
     * @return int
     */
    public function getProcessorType(): int;

    /**
     * @param array $data
     */
    public function setData(array $data);

    /**
     * @param int $processorType
     *
     * @return $this
     */
    public function setProcessorType(int $processorType);

    /**
     * @param array $row
     */
    public function setRow(array $row);
}
