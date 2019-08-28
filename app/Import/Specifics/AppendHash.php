<?php
/**
 * AppendHash.php
 * Copyright (C) 2016 https://github.com/viraptor.
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Import\Specifics;

/**
 * Class AppendHash.
 *
 * Appends a column with a consistent hash for duplicate transactions.
 */
class AppendHash implements SpecificInterface
{
    /** @var array Counter for each line. */
    public $lines_counter = array();

    /**
     * Description of the current specific.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public static function getDescription(): string
    {
        return 'import.specific_hash_descr';
    }

    /**
     * Name of the current specific.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public static function getName(): string
    {
        return 'import.specific_hash_name';
    }

    /**
     * Run the specific code.
     *
     * @param array $row
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function run(array $row): array
    {
        $representation = join(",", array_values($row));
        if (array_key_exists($representation, $this->lines_counter)) {
            $this->lines_counter[$representation] += 1;
        } else {
            $this->lines_counter[$representation] = 1;
        }
        $to_hash = $representation . "," . $this->lines_counter[$representation];

        array_push($row, hash("sha256", $to_hash));
        return $row;
    }
}
