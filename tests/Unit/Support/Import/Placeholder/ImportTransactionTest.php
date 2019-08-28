<?php
/**
 * ImportTransactionTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Support\Import\Placeholder;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Support\Import\Placeholder\ColumnValue;
use FireflyIII\Support\Import\Placeholder\ImportTransaction;
use Log;
use Tests\TestCase;

/**
 * Class ImportTransactionTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ImportTransactionTest extends TestCase
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
     * Test what happens when you set the account-id using a ColumnValue.
     * Since this field can be mapped. Test with both the mapped and unmapped variant.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testACVAccountIdMapped(): void
    {
        $columnValue = new ColumnValue;
        $columnValue->setRole('account-id');
        $columnValue->setOriginalRole('account-name');
        $columnValue->setValue('Checking Account');
        $columnValue->setMappedValue(1);

        $importTransaction = new ImportTransaction;
        $this->assertEquals(0, $importTransaction->accountId);
        try {
            $importTransaction->addColumnValue($columnValue);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($columnValue->getMappedValue(), $importTransaction->accountId);
    }

    /**
     * Test what happens when you set the account-id using a ColumnValue.
     * Since this field can be mapped. Test with both the mapped and unmapped variant.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testACVAccountIdUnmapped(): void
    {
        $columnValue = new ColumnValue;
        $columnValue->setRole('account-id');
        $columnValue->setValue('1');

        $importTransaction = new ImportTransaction;
        $this->assertEquals(0, $importTransaction->accountId);
        try {
            $importTransaction->addColumnValue($columnValue);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals((int)$columnValue->getValue(), $importTransaction->accountId);
    }

    /**
     * Test what happens when you set the bill-id using a ColumnValue.
     * Since this field can be mapped. Test with both the mapped and unmapped variant.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testACVBillIdMapped(): void
    {
        $columnValue = new ColumnValue;
        $columnValue->setRole('bill-id');
        $columnValue->setOriginalRole('bill-name');
        $columnValue->setValue('Some Bill');
        $columnValue->setMappedValue(2);

        $importTransaction = new ImportTransaction;
        $this->assertEquals(0, $importTransaction->billId);
        try {
            $importTransaction->addColumnValue($columnValue);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($columnValue->getMappedValue(), $importTransaction->billId);
    }

    /**
     * Test what happens when you set the bill-id using a ColumnValue.
     * Since this field can be mapped. Test with both the mapped and unmapped variant.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testACVBillIdUnmapped(): void
    {
        $columnValue = new ColumnValue;
        $columnValue->setRole('bill-id');
        $columnValue->setValue('2');

        $importTransaction = new ImportTransaction;
        $this->assertEquals(0, $importTransaction->billId);
        try {
            $importTransaction->addColumnValue($columnValue);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals((int)$columnValue->getValue(), $importTransaction->billId);
    }

    /**
     * Test what happens when you set the budget-id using a ColumnValue.
     * Since this field can be mapped. Test with both the mapped and unmapped variant.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testACVBudgetIdMapped(): void
    {
        $columnValue = new ColumnValue;
        $columnValue->setRole('budget-id');
        $columnValue->setOriginalRole('budget-name');
        $columnValue->setValue('Some Budget');
        $columnValue->setMappedValue(3);

        $importTransaction = new ImportTransaction;
        $this->assertEquals(0, $importTransaction->budgetId);
        try {
            $importTransaction->addColumnValue($columnValue);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($columnValue->getMappedValue(), $importTransaction->budgetId);
    }

    /**
     * Test what happens when you set the budget-id using a ColumnValue.
     * Since this field can be mapped. Test with both the mapped and unmapped variant.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testACVBudgetIdUnmapped(): void
    {
        $columnValue = new ColumnValue;
        $columnValue->setRole('budget-id');
        $columnValue->setValue('3');

        $importTransaction = new ImportTransaction;
        $this->assertEquals(0, $importTransaction->budgetId);
        try {
            $importTransaction->addColumnValue($columnValue);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals((int)$columnValue->getValue(), $importTransaction->budgetId);
    }

    /**
     * Test what happens when you set the category-id using a ColumnValue.
     * Since this field can be mapped. Test with both the mapped and unmapped variant.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testACVCategoryIdMapped(): void
    {
        $columnValue = new ColumnValue;
        $columnValue->setRole('category-id');
        $columnValue->setOriginalRole('category-name');
        $columnValue->setValue('Some category');
        $columnValue->setMappedValue(5);

        $importTransaction = new ImportTransaction;
        $this->assertEquals(0, $importTransaction->categoryId);
        try {
            $importTransaction->addColumnValue($columnValue);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($columnValue->getMappedValue(), $importTransaction->categoryId);
    }

    /**
     * Test what happens when you set the category-id using a ColumnValue.
     * Since this field can be mapped. Test with both the mapped and unmapped variant.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testACVCategoryIdUnmapped(): void
    {
        $columnValue = new ColumnValue;
        $columnValue->setRole('category-id');
        $columnValue->setValue('5');

        $importTransaction = new ImportTransaction;
        $this->assertEquals(0, $importTransaction->categoryId);
        try {
            $importTransaction->addColumnValue($columnValue);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals((int)$columnValue->getValue(), $importTransaction->categoryId);
    }

    /**
     * Test what happens when you set the currency-id using a ColumnValue.
     * Since this field can be mapped. Test with both the mapped and unmapped variant.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testACVCurrencyIdMapped(): void
    {
        $columnValue = new ColumnValue;
        $columnValue->setRole('currency-id');
        $columnValue->setOriginalRole('currency-code');
        $columnValue->setValue('EUR');
        $columnValue->setMappedValue(4);

        $importTransaction = new ImportTransaction;
        $this->assertEquals(0, $importTransaction->currencyId);
        try {
            $importTransaction->addColumnValue($columnValue);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($columnValue->getMappedValue(), $importTransaction->currencyId);
    }

    /**
     * Test what happens when you set the currency-id using a ColumnValue.
     * Since this field can be mapped. Test with both the mapped and unmapped variant.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testACVCurrencyIdUnmapped(): void
    {
        $columnValue = new ColumnValue;
        $columnValue->setRole('currency-id');
        $columnValue->setValue('4');

        $importTransaction = new ImportTransaction;
        $this->assertEquals(0, $importTransaction->currencyId);
        try {
            $importTransaction->addColumnValue($columnValue);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals((int)$columnValue->getValue(), $importTransaction->currencyId);
    }

    /**
     * Test what happens when you set the foreign-currency-id using a ColumnValue.
     * Since this field can be mapped. Test with both the mapped and unmapped variant.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testACVForeignCurrencyIdMapped(): void
    {
        $columnValue = new ColumnValue;
        $columnValue->setRole('foreign-currency-id');
        $columnValue->setOriginalRole('foreign-currency-code');
        $columnValue->setValue('USD');
        $columnValue->setMappedValue(6);

        $importTransaction = new ImportTransaction;
        $this->assertEquals(0, $importTransaction->foreignCurrencyId);
        try {
            $importTransaction->addColumnValue($columnValue);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($columnValue->getMappedValue(), $importTransaction->foreignCurrencyId);
    }

    /**
     * Test what happens when you set the category-id using a ColumnValue.
     * Since this field can be mapped. Test with both the mapped and unmapped variant.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testACVForeignCurrencyIdUnmapped(): void
    {
        $columnValue = new ColumnValue;
        $columnValue->setRole('foreign-currency-id');
        $columnValue->setValue('6');

        $importTransaction = new ImportTransaction;
        $this->assertEquals(0, $importTransaction->foreignCurrencyId);
        try {
            $importTransaction->addColumnValue($columnValue);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals((int)$columnValue->getValue(), $importTransaction->foreignCurrencyId);
    }


    /**
     * Test what happens when you set the opposing-id using a ColumnValue.
     * Since this field can be mapped. Test with both the mapped and unmapped variant.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testACVOpposingIdMapped(): void
    {
        $columnValue = new ColumnValue;
        $columnValue->setRole('opposing-id');
        $columnValue->setOriginalRole('opposing-name');
        $columnValue->setValue('Some Opposing');
        $columnValue->setMappedValue(7);

        $importTransaction = new ImportTransaction;
        $this->assertEquals(0, $importTransaction->opposingId);
        try {
            $importTransaction->addColumnValue($columnValue);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($columnValue->getMappedValue(), $importTransaction->opposingId);
    }

    /**
     * Test what happens when you set the opposing-id using a ColumnValue.
     * Since this field can be mapped. Test with both the mapped and unmapped variant.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testACVOpposingIdUnmapped(): void
    {
        $columnValue = new ColumnValue;
        $columnValue->setRole('opposing-id');
        $columnValue->setValue('7');

        $importTransaction = new ImportTransaction;
        $this->assertEquals(0, $importTransaction->opposingId);
        try {
            $importTransaction->addColumnValue($columnValue);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals((int)$columnValue->getValue(), $importTransaction->opposingId);
    }

    /**
     * Test various unmapped fields, and the result that the ImportTransaction should display.
     *
     * Put into one big test to save time.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testACVOtherValues(): void
    {
        $tests = [
            'account-iban'          => 'accountIban',
            'account-name'          => 'accountName',
            'account-bic'           => 'accountBic',
            'account-number'        => 'accountNumber',
            'amount_debit'          => 'amountDebit',
            'amount_credit'         => 'amountCredit',
            'amount_negated'        => 'amountNegated',
            'amount'                => 'amount',
            'amount_foreign'        => 'foreignAmount',
            'bill-name'             => 'billName',
            'budget-name'           => 'budgetName',
            'category-name'         => 'categoryName',
            'currency-code'         => 'currencyCode',
            'currency-name'         => 'currencyName',
            'currency-symbol'       => 'currencySymbol',
            'external-id'           => 'externalId',
            'foreign-currency-code' => 'foreignCurrencyCode',
            'date-transaction'      => 'date',
            'opposing-iban'         => 'opposingIban',
            'opposing-name'         => 'opposingName',
            'opposing-bic'          => 'opposingBic',
            'opposing-number'       => 'opposingNumber',
        ];
        foreach ($tests as $role => $field) {
            // generate random value
            $value = bin2hex(random_bytes(16));

            // put into column value:
            $columnValue = new ColumnValue;
            $columnValue->setRole($role);
            $columnValue->setValue($value);

            // first test should always return NULL
            $importTransaction = new ImportTransaction;
            $this->assertNull($importTransaction->$field);


            try {
                $importTransaction->addColumnValue($columnValue);
            } catch (FireflyException $e) {
                $this->assertTrue(false, $e->getMessage());
            }

            // after setting, should return value.
            $this->assertEquals($value, $importTransaction->$field);

        }


    }

    /**
     * Basic amount info. Should return something like '1.0'.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testCalculateAmountBasic(): void
    {
        $importTransaction         = new ImportTransaction;
        $importTransaction->amount = '1.23';
        try {
            $this->assertEquals('1.23', $importTransaction->calculateAmount());
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * Basic amount info. Should return something like '1.0'.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testCalculateAmountCredit(): void
    {
        $importTransaction               = new ImportTransaction;
        $importTransaction->amountCredit = '1.56';
        try {
            $this->assertEquals('1.56', $importTransaction->calculateAmount());
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * Basic amount info. Should return something like '1.0'.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testCalculateAmountDebit(): void
    {
        $importTransaction              = new ImportTransaction;
        $importTransaction->amountDebit = '1.01';
        try {
            $this->assertEquals('-1.01', $importTransaction->calculateAmount());
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * With no amount data, object should return ''
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testCalculateAmountEmpty(): void
    {
        $importTransaction = new ImportTransaction;
        try {
            $this->assertEquals('', $importTransaction->calculateAmount());
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * Basic amount info with negative modifier (Rabobank D)
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testCalculateAmountNeg(): void
    {
        $importTransaction                                    = new ImportTransaction;
        $importTransaction->amount                            = '2.99';
        $importTransaction->modifiers['generic-debit-credit'] = 'D';
        try {
            $this->assertEquals('-2.99', $importTransaction->calculateAmount());
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * Basic amount info. Should return something like '1.0'.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testCalculateAmountNegatedNegative(): void
    {
        $importTransaction                = new ImportTransaction;
        $importTransaction->amountNegated = '-1.56';
        try {
            $this->assertEquals('1.56', $importTransaction->calculateAmount());
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * Basic amount info. Should return something like '1.0'.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testCalculateAmountNegatedPositive(): void
    {
        $importTransaction                = new ImportTransaction;
        $importTransaction->amountNegated = '1.56';
        try {
            $this->assertEquals('-1.56', $importTransaction->calculateAmount());
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * Basic amount info with positive modifier (Rabobank C)
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testCalculateAmountPos(): void
    {
        $importTransaction                                 = new ImportTransaction;
        $importTransaction->amount                         = '-2.17';
        $importTransaction->modifiers['rabo-debit-credit'] = 'C';
        try {
            $this->assertEquals('2.17', $importTransaction->calculateAmount());
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * Debit Credit indicator is special.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testDebitCredit(): void
    {
        $columnValue = new ColumnValue;
        $columnValue->setRole('generic-debit-credit');
        $columnValue->setValue('Af');

        $importTransaction = new ImportTransaction;
        $this->assertCount(0, $importTransaction->modifiers);
        try {
            $importTransaction->addColumnValue($columnValue);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(1, $importTransaction->modifiers);
        $this->assertEquals('Af', $importTransaction->modifiers['generic-debit-credit']);
    }

    /**
     * Description should be appended.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testDescription(): void
    {
        $one = new ColumnValue;
        $one->setRole('description');
        $one->setValue('A');

        $two = new ColumnValue;
        $two->setRole('description');
        $two->setValue('B');

        $importTransaction = new ImportTransaction;
        $this->assertEquals('', $importTransaction->description);
        try {
            $importTransaction->addColumnValue($one);
            $importTransaction->addColumnValue($two);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals('A B', $importTransaction->description);
    }

    /**
     * Basic foreign amount info. Should return something like '1.0'.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testForeignAmountBasic(): void
    {
        $importTransaction                = new ImportTransaction;
        $importTransaction->foreignAmount = '1.23';
        $this->assertEquals('1.23', $importTransaction->calculateForeignAmount());
    }

    /**
     * Basic foreign amount info. Should return something like '1.0'.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testForeignAmountEmpty(): void
    {
        $importTransaction = new ImportTransaction;
        $this->assertEquals('', $importTransaction->calculateForeignAmount());
    }

    /**
     * Foreign amount with modifier that should make it negative again.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testForeignAmountModNeg(): void
    {
        $importTransaction                                    = new ImportTransaction;
        $importTransaction->foreignAmount                     = '6.77';
        $importTransaction->modifiers['generic-debit-credit'] = 'D';
        $this->assertEquals('-6.77', $importTransaction->calculateForeignAmount());
    }

    /**
     * Foreign amount with modifier that should make it positive again.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testForeignAmountModPos(): void
    {
        $importTransaction                                    = new ImportTransaction;
        $importTransaction->foreignAmount                     = '-5.77';
        $importTransaction->modifiers['generic-debit-credit'] = 'C';
        $this->assertEquals('5.77', $importTransaction->calculateForeignAmount());
    }

    /**
     * Basic foreign amount info. Should return something like '1.0'.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testForeignAmountNeg(): void
    {
        $importTransaction                = new ImportTransaction;
        $importTransaction->foreignAmount = '-4.56';
        $this->assertEquals('-4.56', $importTransaction->calculateForeignAmount());
    }

    /**
     * Ignore field must be ignored.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testIgnore(): void
    {
        $columnValue = new ColumnValue;
        $columnValue->setRole('_ignore');
        $columnValue->setValue('Bla bla bla');

        $importTransaction = new ImportTransaction;
        try {
            $importTransaction->addColumnValue($columnValue);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertTrue(true);
    }

    /**
     * Set a meta value, see what happens. Any will do.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testMetaValue(): void
    {
        $columnValue = new ColumnValue;
        $columnValue->setRole('date_process');
        $columnValue->setValue('2018-01-01');

        $importTransaction = new ImportTransaction;
        $this->assertCount(0, $importTransaction->meta);
        try {
            $importTransaction->addColumnValue($columnValue);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(1, $importTransaction->meta);
        $this->assertEquals($columnValue->getValue(), $importTransaction->meta['date_process']);
    }

    /**
     * Description should be appended.
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testNote(): void
    {
        $one = new ColumnValue;
        $one->setRole('note');
        $one->setValue('A');

        $two = new ColumnValue;
        $two->setRole('note');
        $two->setValue('B');

        $importTransaction = new ImportTransaction;
        $this->assertEquals('', $importTransaction->note);
        try {
            $importTransaction->addColumnValue($one);
            $importTransaction->addColumnValue($two);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals('A B', $importTransaction->note);
    }

    /**
     * Test tags with a comma separator
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testTagsComma(): void
    {
        $one = new ColumnValue;
        $one->setRole('tags-comma');
        $one->setValue('a,b,c');

        $two = new ColumnValue;
        $two->setRole('tags-comma');
        $two->setValue('d,e,c');

        $importTransaction = new ImportTransaction;
        $this->assertCount(0, $importTransaction->tags);
        try {
            $importTransaction->addColumnValue($one);
            $importTransaction->addColumnValue($two);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(5, $importTransaction->tags);
        $this->assertEquals('a', $importTransaction->tags[0]);
    }

    /**
     * Test tags with a space separator
     *
     * @covers \FireflyIII\Support\Import\Placeholder\ImportTransaction
     */
    public function testTagsSpace(): void
    {
        $one = new ColumnValue;
        $one->setRole('tags-space');
        $one->setValue('a b c');

        $two = new ColumnValue;
        $two->setRole('tags-space');
        $two->setValue('d e c');

        $importTransaction = new ImportTransaction;
        $this->assertCount(0, $importTransaction->tags);
        try {
            $importTransaction->addColumnValue($one);
            $importTransaction->addColumnValue($two);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(5, $importTransaction->tags);
        $this->assertEquals('a', $importTransaction->tags[0]);
    }

}
