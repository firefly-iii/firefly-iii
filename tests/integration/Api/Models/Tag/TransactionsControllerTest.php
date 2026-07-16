<?php

/*
 * TransactionsControllerTest.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace Tests\integration\Api\Models\Tag;

use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Override;
use Tests\integration\TestCase;

/**
 * @internal
 *
 * @covers \FireflyIII\Api\V1\Controllers\Models\Tag\TransactionsController
 */
final class TransactionsControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tag $tag;
    private User $user;
    private TransactionJournal $journalA;
    private TransactionJournal $journalB;
    private TransactionJournal $otherUserJournal;

    public function testAttachAddsTagToOwnedJournals(): void
    {
        $response = $this->postJson($this->attachUrl(), ['transaction_journal_ids' => [$this->journalA->id, $this->journalB->id]]);

        $response->assertOk();
        $response->assertJson(['data' => [
            'attached'         => [$this->journalA->id, $this->journalB->id],
            'already_attached' => [],
            'invalid'          => [],
        ]]);
        $this->assertDatabaseHas('tag_transaction_journal', ['tag_id' => $this->tag->id, 'transaction_journal_id' => $this->journalA->id]);
        $this->assertDatabaseHas('tag_transaction_journal', ['tag_id' => $this->tag->id, 'transaction_journal_id' => $this->journalB->id]);
    }

    public function testAttachIsIdempotentForAlreadyAttachedJournal(): void
    {
        $this->tag->transactionJournals()->attach($this->journalA->id);

        $response = $this->postJson($this->attachUrl(), ['transaction_journal_ids' => [$this->journalA->id]]);

        $response->assertOk();
        $response->assertJson(['data' => [
            'attached'         => [],
            'already_attached' => [$this->journalA->id],
            'invalid'          => [],
        ]]);
        $this->assertDatabaseCount('tag_transaction_journal', 1);
    }

    public function testAttachRejectsJournalsNotOwnedByUser(): void
    {
        $response = $this->postJson($this->attachUrl(), ['transaction_journal_ids' => [$this->journalA->id, $this->otherUserJournal->id]]);

        $response->assertOk();
        $response->assertJson(['data' => [
            'attached'         => [$this->journalA->id],
            'already_attached' => [],
            'invalid'          => [$this->otherUserJournal->id],
        ]]);
        $this->assertDatabaseMissing('tag_transaction_journal', ['transaction_journal_id' => $this->otherUserJournal->id]);
    }

    public function testAttachFailsValidationWhenJournalIdsMissing(): void
    {
        $response = $this->postJson($this->attachUrl(), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['transaction_journal_ids']);
    }

    public function testAttachFailsValidationWhenJournalIdsContainsNonInteger(): void
    {
        $response = $this->postJson($this->attachUrl(), ['transaction_journal_ids' => ['not-an-id']]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['transaction_journal_ids.0']);
    }

    public function testAttachHandlesMixOfValidAlreadyAttachedAndInvalidIdsInSingleCall(): void
    {
        $this->tag->transactionJournals()->attach($this->journalA->id);

        $response = $this->postJson($this->attachUrl(), ['transaction_journal_ids' => [
            $this->journalA->id,
            $this->journalB->id,
            $this->otherUserJournal->id,
        ]]);

        $response->assertOk();
        $response->assertJson(['data' => [
            'attached'         => [$this->journalB->id],
            'already_attached' => [$this->journalA->id],
            'invalid'          => [$this->otherUserJournal->id],
        ]]);
    }

    public function testDetachRemovesTagFromOwnedJournals(): void
    {
        $this->tag->transactionJournals()->attach([$this->journalA->id, $this->journalB->id]);

        $response = $this->postJson($this->detachUrl(), ['transaction_journal_ids' => [$this->journalA->id]]);

        $response->assertOk();
        $response->assertJson(['data' => [
            'detached'     => [$this->journalA->id],
            'not_attached' => [],
            'invalid'      => [],
        ]]);
        $this->assertDatabaseMissing('tag_transaction_journal', ['tag_id' => $this->tag->id, 'transaction_journal_id' => $this->journalA->id]);
        $this->assertDatabaseHas('tag_transaction_journal', ['tag_id' => $this->tag->id, 'transaction_journal_id' => $this->journalB->id]);
    }

    public function testDetachReportsNotAttachedJournals(): void
    {
        $response = $this->postJson($this->detachUrl(), ['transaction_journal_ids' => [$this->journalA->id]]);

        $response->assertOk();
        $response->assertJson(['data' => [
            'detached'     => [],
            'not_attached' => [$this->journalA->id],
            'invalid'      => [],
        ]]);
    }

    public function testDetachRejectsJournalsNotOwnedByUser(): void
    {
        $response = $this->postJson($this->detachUrl(), ['transaction_journal_ids' => [$this->otherUserJournal->id]]);

        $response->assertOk();
        $response->assertJson(['data' => [
            'detached'     => [],
            'not_attached' => [],
            'invalid'      => [$this->otherUserJournal->id],
        ]]);
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser();
        Passport::actingAs($this->user);

        $this->tag              = Tag::create(['user_id' => $this->user->id, 'tag' => 'automation', 'tag_mode' => 'nothing']);
        $this->journalA         = $this->createJournal($this->user, 'Journal A');
        $this->journalB         = $this->createJournal($this->user, 'Journal B');

        $otherUser               = User::create(['email' => 'other@email.com', 'password' => 'password']);
        $this->otherUserJournal  = $this->createJournal($otherUser, 'Other users journal');
    }

    private function attachUrl(): string
    {
        return route('api.v1.tags.transactions.attach', ['tagOrId' => $this->tag->id]);
    }

    private function detachUrl(): string
    {
        return route('api.v1.tags.transactions.detach', ['tagOrId' => $this->tag->id]);
    }

    private function createJournal(User $user, string $description): TransactionJournal
    {
        $type     = TransactionType::where('type', TransactionTypeEnum::WITHDRAWAL->value)->first();
        $currency = TransactionCurrency::where('code', 'EUR')->first();

        return TransactionJournal::create([
            'user_id'                 => $user->id,
            'transaction_type_id'     => $type->id,
            'transaction_currency_id' => $currency->id,
            'description'             => $description,
            'date'                    => now(),
            'tag_count'               => 0,
        ]);
    }
}
