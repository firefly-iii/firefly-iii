<?php
/**
 * AmountTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\Import\Converter;

use FireflyIII\Import\Converter\Amount;
use Tests\TestCase;

/**
 * Class AmountTest
 *
 * @package Tests\Unit\Import\Converter
 */
class AmountTest extends TestCase
{
    /**
     * @covers \FireflyIII\Import\Converter\Amount::convert()
     */
    public function testConvert()
    {
        $values = [
            '0'        => '0',
            '0.0'      => '0',
            '0.1'      => '0.1',
            '.2'       => '0.2',
            '0.01'     => '0.01',
            '1'        => '1',
            '1.0'      => '1',
            '1.1'      => '1.1',
            '1.12'     => '1.12',
            '1.10'     => '1.1',
            '12'       => '12',
            '12.3'     => '12.3',
            '12.34'    => '12.34',
            '123'      => '123',
            '123.4'    => '123.4',
            '123.45'   => '123.45',
            '1234'     => '1234',
            '1234.5'   => '1234.5',
            '1234.56'  => '1234.56',
            '1 234'    => '1234',
            '1 234.5'  => '1234.5',
            '1 234.56' => '1234.56',
            '1,234'    => '1234',
            '1,234.5'  => '1234.5',
            '1,234.56' => '1234.56',
            '0,0'      => '0',
            '0,1'      => '0.1',
            ',2'       => '0.2',
            '0,01'     => '0.01',
            '1,0'      => '1',
            '1,1'      => '1.1',
            '1,12'     => '1.12',
            '1,10'     => '1.1',
            '12,3'     => '12.3',
            '12,34'    => '12.34',
            '123,4'    => '123.4',
            '123,45'   => '123.45',
            '1234,5'   => '1234.5',
            '1234,56'  => '1234.56',
            '1 234,5'  => '1234.5',
            '1 234,56' => '1234.56',
            '1.234'    => '1234',
            '1.234,5'  => '1234.5',
            '1.234,56' => '1234.56',

        ];
        foreach ($values as $value => $expected) {
            $converter = new Amount;
            $result    = $converter->convert($value);
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * @covers \FireflyIII\Import\Converter\Amount::convert()
     */
    public function testConvertNull()
    {
        $converter = new Amount;
        $result    = $converter->convert(null);
        $this->assertEquals('0', $result);
    }


}