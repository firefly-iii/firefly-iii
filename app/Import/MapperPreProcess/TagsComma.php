<?php
/**
 * TagsComma.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Import\MapperPreProcess;

/**
 * Class TagsComma.
 */
class TagsComma implements PreProcessorInterface
{
    /**
     * Explode and filter list of comma separated tags.
     *
     * @param string $value
     *
     * @return array
     */
    public function run(string $value): array
    {
        $set = explode(',', $value);
        $set = array_map('trim', $set);
        $set = array_filter($set, '\strlen');

        return array_values($set);
    }
}
