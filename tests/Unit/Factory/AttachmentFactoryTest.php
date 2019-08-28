<?php
/**
 * AttachmentFactoryTest.php
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

namespace Tests\Unit\Factory;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AttachmentFactory;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Log;
use Tests\TestCase;

/**
 *
 * Class AttachmentFactoryTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AttachmentFactoryTest extends TestCase
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
     * @covers \FireflyIII\Factory\AttachmentFactory
     */
    public function testCreate(): void
    {

        $journal = $this->getRandomWithdrawal();
        $data    = [
            'model_id' => $journal->id,
            'model'    => TransactionJournal::class,
            'filename' => 'testfile.pdf',
            'title'    => 'File name',
            'notes'    => 'Some notes',
        ];

        /** @var AttachmentFactory $factory */
        $factory = app(AttachmentFactory::class);
        $factory->setUser($this->user());
        try {
            $result = $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($data['title'], $result->title);
        $this->assertEquals(1, $result->notes()->count());


    }

    /**
     * @covers \FireflyIII\Factory\AttachmentFactory
     */
    public function testCreateTransaction(): void
    {

        $journal     = $this->getRandomWithdrawal();
        $transaction = $journal->transactions()->first();
        $data        = [
            'model_id' => $transaction->id,
            'model'    => Transaction::class,
            'filename' => 'testfile.pdf',
            'title'    => 'File name',
            'notes'    => 'Some notes',
        ];

        /** @var AttachmentFactory $factory */
        $factory = app(AttachmentFactory::class);
        $factory->setUser($this->user());
        try {
            $result = $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($data['title'], $result->title);
        $this->assertEquals($result->attachable_id, $journal->id);
        $this->assertEquals(1, $result->notes()->count());


    }


    /**
     * @covers \FireflyIII\Factory\AttachmentFactory
     */
    public function testCreateTransactionAppendModel(): void
    {

        $journal     = $this->getRandomWithdrawal();
        $transaction = $journal->transactions()->first();
        $data        = [
            'model_id' => $transaction->id,
            'model'    => 'Transaction',
            'filename' => 'testfile.pdf',
            'title'    => 'File name',
            'notes'    => 'Some notes',
        ];

        /** @var AttachmentFactory $factory */
        $factory = app(AttachmentFactory::class);
        $factory->setUser($this->user());
        try {
            $result = $factory->create($data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($data['title'], $result->title);
        $this->assertEquals($result->attachable_id, $journal->id);
        $this->assertEquals(1, $result->notes()->count());


    }
}
