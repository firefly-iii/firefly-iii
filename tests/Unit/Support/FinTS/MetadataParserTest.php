<?php
/**
 * FinTS.php
 * Copyright (c) 2018 https://github.com/bnw
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

namespace Tests\Unit\Support\FinTS;

use Fhp\Model\StatementOfAccount\Transaction as FinTSTransaction;

use FireflyIII\Support\FinTS\MetadataParser;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MetadataParserTest extends TestCase
{

    /** @var MetadataParser */
    private $metadataParser;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', \get_class($this)));
        $this->metadataParser = new MetadataParser();
    }

    public function testDescriptionIsCorrectlyExtractedFromBeginning()
    {
        $transaction = $this->createTransactionWithDescription1('SVWZ+DescriptionABWA+xxx');
        $this->assertEquals('Description', $this->metadataParser->getDescription($transaction));
    }

    public function testDescriptionIsCorrectlyExtractedFromMiddle()
    {
        $transaction = $this->createTransactionWithDescription1('EREF+AbcCRED+DE123SVWZ+DescriptionABWA+Ghi');
        $this->assertEquals('Description', $this->metadataParser->getDescription($transaction));
    }

    public function testDescriptionIsCorrectlyExtractedFromEnd()
    {
        $transaction = $this->createTransactionWithDescription1('EREF+AbcCRED+DE123SVWZ+Description');
        $this->assertEquals('Description', $this->metadataParser->getDescription($transaction));
    }

    private function createTransactionWithDescription1(string $description1)
    {
        $transaction = $this->mock(FinTSTransaction::class);
        $transaction->shouldReceive('getDescription1')->atLeast()->once()->andReturn($description1);
        return $transaction;
    }
}
