<?php

/*
 * Field.php
 * Copyright (c) 2021 james@firefly-iii.org
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
namespace Tests\Objects;

use Closure;

/**
 * Class Field
 */
class Field
{
    public ?Closure $expectedReturn;
    public string   $expectedReturnType;
    public string   $fieldTitle;
    public string   $fieldType;
    public ?array   $ignorableFields;
    public string   $title;

    /**
     * Field constructor.
     */
    public function __construct()
    {
        $this->expectedReturnType = 'equal'; // or 'callback'
        $this->expectedReturn     = null; // or the callback
        $this->ignorableFields    = []; // something like transactions/0/currency_code
        //$optionalField->ignorableFields    = ['some_field', 'transactions/0/another_field', 'rules/2/another_one',]; // something like transactions/0/currency_code
    }

    /**
     * @param string $title
     * @param string $type
     *
     * @return static
     */
    public static function createBasic(string $title, string $type): self
    {
        $field             = new self;
        $field->title      = $title;
        $field->fieldTitle = $title;
        $field->fieldType  = $type;

        return $field;
    }

}
