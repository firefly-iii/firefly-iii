<?php
/**
 * AbnAmroDescription.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Specifics;

/**
 * Class AbnAmroDescription
 *
 * @package FireflyIII\Import\Specifics
 */
class AbnAmroDescription implements SpecificInterface
{

    /**
     * @return string
     */
    static public function getName(): string
    {
        return 'ABN Amro description';
    }

    /**
     * @return string
     */
    static public function getDescription(): string
    {
        return 'Fixes possible problems with ABN Amro descriptions.';
    }

    /**
     * @param array $row
     *
     * @return array
     */
    public function run(array $row): array
    {
        return $row;
    }
}