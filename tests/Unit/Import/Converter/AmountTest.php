<?php
/**
 * AmountTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Import\Converter;

use FireflyIII\Import\Converter\Amount;
use Log;
use Tests\TestCase;

/**
 * Class AmountTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AmountTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }


    /**
     * @covers \FireflyIII\Import\Converter\Amount
     */
    public function testConvert(): void
    {
        $values = [
            '0'                       => '0',
            '0.0'                     => '0.0',
            '0.1'                     => '0.1',
            '.2'                      => '0.2',
            '0.01'                    => '0.01',
            '1'                       => '1',
            '1.0'                     => '1.0',
            '1.1'                     => '1.1',
            '1.12'                    => '1.12',
            '1.10'                    => '1.10',
            '12'                      => '12',
            '12.3'                    => '12.3',
            '12.34'                   => '12.34',
            '123'                     => '123',
            '123.4'                   => '123.4',
            '123.45'                  => '123.45',
            '1234'                    => '1234',
            '1234.5'                  => '1234.5',
            '1234.56'                 => '1234.56',
            '1 234'                   => '1234',
            '1 234.5'                 => '1234.5',
            '1 234.56'                => '1234.56',
            '1,234'                   => '1234',
            '1,234.5'                 => '1234.5',
            '1,234.56'                => '1234.56',
            '123,456,789'             => '123456789',
            '0,0'                     => '0.0',
            '0,1'                     => '0.1',
            ',2'                      => '0.2',
            '0,01'                    => '0.01',
            '1,0'                     => '1.0',
            '1,1'                     => '1.1',
            '1,12'                    => '1.12',
            '1,10'                    => '1.10',
            '12,3'                    => '12.3',
            '12,34'                   => '12.34',
            '123,4'                   => '123.4',
            '123,45'                  => '123.45',
            '1234,5'                  => '1234.5',
            '1234,56'                 => '1234.56',
            '1 234,5'                 => '1234.5',
            '1 234,56'                => '1234.56',
            '1.234'                   => '1.234', // will no longer match as 1234, but as 1.234
            '1.234,5'                 => '1234.5',
            '1.234,56'                => '1234.56',
            // many decimals
            '2.00'                    => '2.00',
            '3.000'                   => '3.000',
            '4.0000'                  => '4.0000',
            '5.000'                   => '5.000',
            '6.0000'                  => '6.0000',
            '7.200'                   => '7.200',
            '8.2000'                  => '8.2000',
            '9.330'                   => '9.330',
            '10.3300'                 => '10.3300',
            '11.444'                  => '11.444',
            '12.4440'                 => '12.4440',
            '13.5555'                 => '13.5555',
            '14.45678'                => '14.45678',
            '15.456789'               => '15.456789',
            '16.4567898'              => '16.4567898',
            '17.34567898'             => '17.34567898',
            '18.134567898'            => '18.134567898',
            '19.1634567898'           => '19.1634567898',
            '20.16334567898'          => '20.16334567898',
            '21.16364567898'          => '21.16364567898',
            '22.163644567898'         => '22.163644567898',
            '22.1636445670069'        => '22.1636445670069',
            // many decimals, mixed, large numbers
            '63522.00'                => '63522.00',
            '63523.000'               => '63523.000',
            '63524.0000'              => '63524.0000',
            '63525.000'               => '63525.000',
            '63526.0000'              => '63526.0000',
            '63527.200'               => '63527.200',
            '63528.2000'              => '63528.2000',
            '63529.330'               => '63529.330',
            '635210.3300'             => '635210.3300',
            '635211.444'              => '635211.444',
            '635212.4440'             => '635212.4440',
            '635213.5555'             => '635213.5555',
            '635214.45678'            => '635214.45678',
            '635215.456789'           => '635215.456789',
            '635216.4567898'          => '635216.4567898',
            '635217.34567898'         => '635217.34567898',
            '635218.134567898'        => '635218.134567898',
            '635219.1634567898'       => '635219.1634567898',
            '635220.16334567898'      => '635220.16334567898',
            '635221.16364567898'      => '635221.16364567898',
            '635222.163644567898'     => '635222.163644567898',
            // many decimals, mixed, also mixed thousands separators
            '63 522.00'               => '63522.00',
            '63 523.000'              => '63523.000',
            '63,524.0000'             => '63524.0000',
            '63 525.000'              => '63525.000',
            '63,526.0000'             => '63526.0000',
            '63 527.200'              => '63527.200',
            '63 528.2000'             => '63528.2000',
            '63 529.330'              => '63529.330',
            '63,5210.3300'            => '635210.3300',
            '63,5211.444'             => '635211.444',
            '63 5212.4440'            => '635212.4440',
            '163 5219.1634567898'     => '1635219.1634567898',
            '444 163 5219.1634567898' => '4441635219.1634567898',
            '-0.34918323'             => '-0.34918323',
            '0.208'                   => '0.208',
            '-0.15'                   => '-0.15',
            '-0.03881677'             => '-0.03881677',
            '0.33'                    => '0.33',
            '-0.1'                    => '-0.1',
            '0.01124'                 => '0.01124',
            '-0.01124'                => '-0.01124',
            '0.115'                   => '0.115',
            '-0.115'                  => '-0.115',
            '1.33'                    => '1.33',
            '$1.23'                   => '1.23',
            '€1,44'                   => '1.44',
            '(33.52)'                 => '-33.52',
            '€(63.12)'                => '-63.12',
            '($182.77)'               => '-182.77',

            // double minus because why the hell not
            '--0.03881677'            => '0.03881677',
            '--0.33'                  => '0.33',
            '--$1.23'                 => '1.23',
            '--63 5212.4440'          => '635212.4440',
            '--,2'                    => '0.2',

            // Postbank (DE) tests
            '1.000,00 €'              => '1000.00',
            '120,34 €'                => '120.34',
            '-120,34 €'               => '-120.34',
            '-1.000,00 €'             => '-1000.00',
        ];
        foreach ($values as $value => $expected) {
            $converter = new Amount;
            $result    = $converter->convert($value);
            $this->assertEquals($expected, $result, sprintf('The original value was "%s", returned was "%s"', $value, $result));
        }
    }

    /**
     * @covers \FireflyIII\Import\Converter\Amount
     */
    public function testConvertNull(): void
    {
        $converter = new Amount;
        $result    = $converter->convert(null);
        $this->assertEquals('0', $result);
    }
}
