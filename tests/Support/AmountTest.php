<?php

namespace Tests\Support;

use FireflyIII\Support\Amount;
use Tests\TestCase;
use Steam;

/**
 * Class AmountTest
 * TODO move to correct directory.
 */
class AmountTest extends TestCase
{
    /**
     * Set up test
     */
    public function setUp(): void
    {
        self::markTestIncomplete('Incomplete for refactor.');

        return;
    }
    /**
     * @dataProvider getTestLocales
     * @param string $locale
     * @param string $expectedAmount
     * @param string $symbol
     * @param int $decimalPlaces
     * @param string $amount
     */
    public function testFormatFlat(string $locale, string $expectedAmount, string $symbol, int $decimalPlaces, string $amount)
    {
        $this->mockDefaultConfiguration();
        Steam::shouldReceive('getLocale')->andReturn($locale);
        Steam::shouldReceive('getLocaleArray')->andReturn([$locale . ".UTF-8"]);

        $amountObj = new Amount();
        $result = $amountObj->formatFlat($symbol, $decimalPlaces, $amount, false);
        $this->assertEquals($expectedAmount, $result);
    }

    public function getTestLocales()
    {
        return [
            ['en_US', '£6,000.00', '£', 2, '6000.00000000'],
            ['en_US', '-£6,000.00', '£', 2, '-6000.00000000'],
            ['en_US', '$6,000.00', '$', 2, '6000.00000000'],
            ['en_US', '-$6,000.00', '$', 2, '-6000.00000000'],
            ['en_GB', '£6,000.00', '£', 2, '6000.00000000'],
            ['en_GB', '-£6,000.00', '£', 2, '-6000.00000000'],
            ['en_GB', '$6,000.00', '$', 2, '6000.00000000'],
            ['en_GB', '-$6,000.00', '$', 2, '-6000.00000000'],
            ['cs_CZ', '6 000,00 Kč', 'Kč', 2, '6000.00000000'],
            ['cs_CZ', '-6 000,00 Kč', 'Kč', 2, '-6000.00000000'],
            ['el_GR', '6.000,00 €', '€', 2, '6000.00000000'],
            ['el_GR', '-6.000,00 €', '€', 2, '-6000.00000000'],
            ['es_ES', '6.000,00 €', '€', 2, '6000.00000000'],
            ['es_ES', '-6.000,00 €', '€', 2, '-6000.00000000'],
            ['de_DE', '6.000,00 €', '€', 2, '6000.00000000'],
            ['de_DE', '-6.000,00 €', '€', 2, '-6000.00000000'],
            ['fr_FR', '6 000,00 €', '€', 2, '6000.00000000'],
            ['fr_FR', '-6 000,00 €', '€', 2, '-6000.00000000'],
            ['it_IT', '6.000,00 €', '€', 2, '6000.00000000'],
            ['it_IT', '-6.000,00 €', '€', 2, '-6000.00000000'],
            ['nb_NO', 'kr 6 000,00', 'kr', 2, '6000.00000000'],
            ['nb_NO', '−kr 6 000,00', 'kr', 2, '-6000.00000000'],
            ['nl_NL', '€ 6.000,00', '€', 2, '6000.00000000'],
            ['nl_NL', '€ -6.000,00', '€', 2, '-6000.00000000'],
            ['pl_PL', '6 000,00 zł', 'zł', 2, '6000.00000000'],
            ['pl_PL', '-6 000,00 zł', 'zł', 2, '-6000.00000000'],
            ['pt_BR', 'R$ 6.000,00', 'R$', 2, '6000.00000000'],
            ['pt_BR', '-R$ 6.000,00', 'R$', 2, '-6000.00000000'],
            ['ro_RO', '6.000,00 lei', 'lei', 2, '6000.00000000'],
            ['ro_RO', '-6.000,00 lei', 'lei', 2, '-6000.00000000'],
            ['ru_RU', '6 000,00 ₽', '₽', 2, '6000.00000000'],
            ['ru_RU', '-6 000,00 ₽', '₽', 2, '-6000.00000000'],
            ['zh_TW', 'NT$6,000.00', 'NT$', 2, '6000.00000000'],
            ['zh_TW', '-NT$6,000.00', 'NT$', 2, '-6000.00000000'],
            ['zh_CN', '¥6,000.00', '¥', 2, '6000.00000000'],
            ['zh_CN', '-¥6,000.00', '¥', 2, '-6000.00000000'],
            ['hu_HU', '6 000,00 Ft', 'Ft', 2, '6000.00000000'],
            ['hu_HU', '-6 000,00 Ft', 'Ft', 2, '-6000.00000000'],
            ['sv_SE', '6 000,00 kr', 'kr', 2, '6000.00000000'],
            ['sv_SE', '−6 000,00 kr', 'kr', 2, '-6000.00000000'],
            ['fi_FI', '6 000,00 €', '€', 2, '6000.00000000'],
            ['fi_FI', '−6 000,00 €', '€', 2, '-6000.00000000'],
            ['vi_VN', '6.000,00 đ', 'đ', 2, '6000.00000000'],
            ['vi_VN', '-6.000,00 đ', 'đ', 2, '-6000.00000000'],
        ];
    }
}
