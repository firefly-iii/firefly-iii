<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Mapper;

/**
 * Interface MapperInterface
 *
 * @package FireflyIII\Helpers\Csv\Mapper
 */
interface MapperInterface
{
    /**
     * @return array
     */
    public function getMap(): array;
}
