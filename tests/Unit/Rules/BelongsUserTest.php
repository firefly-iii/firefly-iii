<?php
declare(strict_types=1);
/**
 * BelongsUserTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Rules;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Rules\BelongsUser;
use Log;
use Tests\TestCase;

/**
 * Class BelongsUserTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BelongsUserTest extends TestCase
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
     * @covers \FireflyIII\Rules\BelongsUser
     */
    public function testBillId(): void
    {
        $attribute = 'bill_id';
        $bill      = $this->getRandomBill();
        $value     = $bill->id;

        $this->be($this->user());
        $engine = new BelongsUser;
        try {
            $this->assertTrue($engine->passes($attribute, $value));
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Rules\BelongsUser
     */
    public function testBillIdFalse(): void
    {
        $attribute = 'bill_id';
        $value     = '-1';

        $this->be($this->user());
        $engine = new BelongsUser;
        try {
            $this->assertFalse($engine->passes($attribute, $value));
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Rules\BelongsUser
     */
    public function testBillName(): void
    {
        $attribute = 'bill_name';
        $bill      = $this->getRandomBill();
        $value     = $bill->name;

        $this->be($this->user());
        $engine = new BelongsUser;
        try {
            $this->assertTrue($engine->passes($attribute, $value));
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Rules\BelongsUser
     */
    public function testBillNameFalse(): void
    {
        $attribute = 'bill_name';
        $value     = 'Some random name';

        $this->be($this->user());
        $engine = new BelongsUser;
        try {
            $this->assertFalse($engine->passes($attribute, $value));
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }


    /**
     * @covers \FireflyIII\Rules\BelongsUser
     */
    public function testAccountIdFalse(): void
    {
        $attribute = 'source_id';
        $value     = '-1';

        $this->be($this->user());
        $engine = new BelongsUser;
        try {
            $this->assertFalse($engine->passes($attribute, $value));
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }


    /**
     * @covers \FireflyIII\Rules\BelongsUser
     */
    public function testAccountId(): void
    {
        $attribute = 'destination_id';
        $asset =$this->getRandomAsset();
        $value     = $asset->id;

        $this->be($this->user());
        $engine = new BelongsUser;
        try {
            $this->assertTrue($engine->passes($attribute, $value));
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Rules\BelongsUser
     */
    public function testBudgetId(): void
    {
        $attribute = 'budget_id';
        $budget    = $this->getRandomBudget();
        $value     = $budget->id;

        $this->be($this->user());
        $engine = new BelongsUser;
        try {
            $this->assertTrue($engine->passes($attribute, $value));
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Rules\BelongsUser
     */
    public function testBudgetIdFalse(): void
    {
        $attribute = 'budget_id';
        $value     = '-1';

        $this->be($this->user());
        $engine = new BelongsUser;
        try {
            $this->assertFalse($engine->passes($attribute, $value));
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Rules\BelongsUser
     */
    public function testBudgetName(): void
    {
        $attribute = 'budget_name';
        $budget    = $this->getRandomBudget();
        $value     = $budget->name;

        $this->be($this->user());
        $engine = new BelongsUser;
        try {
            $this->assertTrue($engine->passes($attribute, $value));
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Rules\BelongsUser
     */
    public function testBudgetNameFalse(): void
    {
        $attribute = 'budget_name';
        $value     = 'Some random budget';

        $this->be($this->user());
        $engine = new BelongsUser;
        try {
            $this->assertFalse($engine->passes($attribute, $value));
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Rules\BelongsUser
     */
    public function testCategoryId(): void
    {
        $attribute = 'category_id';
        $category  = $this->getRandomCategory();
        $value     = $category->id;

        $this->be($this->user());
        $engine = new BelongsUser;
        try {
            $this->assertTrue($engine->passes($attribute, $value));
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Rules\BelongsUser
     */
    public function testCategoryIdFalse(): void
    {
        $attribute = 'category_id';
        $value     = '-1';

        $this->be($this->user());
        $engine = new BelongsUser;
        try {
            $this->assertFalse($engine->passes($attribute, $value));
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Rules\BelongsUser
     */
    public function testPiggyBankId(): void
    {
        $attribute = 'piggy_bank_id';
        $piggyBank = $this->getRandomPiggyBank();
        $value     = $piggyBank->id;

        $this->be($this->user());
        $engine = new BelongsUser;
        try {
            $this->assertTrue($engine->passes($attribute, $value));
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Rules\BelongsUser
     */
    public function testPiggyBankIdFalse(): void
    {
        $attribute = 'piggy_bank_id';
        $value     = '-1';

        $this->be($this->user());
        $engine = new BelongsUser;
        try {
            $this->assertFalse($engine->passes($attribute, $value));
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Rules\BelongsUser
     */
    public function testPiggyBankIdLongAttribute(): void
    {
        $attribute = 'a.b.piggy_bank_id';
        $piggyBank = $this->getRandomPiggyBank();
        $value     = $piggyBank->id;

        $this->be($this->user());
        $engine = new BelongsUser;
        try {
            $this->assertTrue($engine->passes($attribute, $value));
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Rules\BelongsUser
     */
    public function testPiggyBankName(): void
    {
        $attribute = 'piggy_bank_name';
        $piggyBank = $this->getRandomPiggyBank();
        $value     = $piggyBank->name;

        $this->be($this->user());
        $engine = new BelongsUser;
        try {
            $this->assertTrue($engine->passes($attribute, $value));
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Rules\BelongsUser
     */
    public function testPiggyBankNameFalse(): void
    {
        $attribute = 'piggy_bank_name';
        $value     = 'Some random name';

        $this->be($this->user());
        $engine = new BelongsUser;
        try {
            $this->assertFalse($engine->passes($attribute, $value));
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

}
