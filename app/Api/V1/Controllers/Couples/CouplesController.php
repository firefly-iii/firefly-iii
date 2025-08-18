<?php

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Couples;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction; // Import the Transaction model
use FireflyIII\Models\PiggyBank; // Import the PiggyBank model
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CouplesController extends Controller
{
    public function state(): JsonResponse
    {
        $user = auth()->user();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Income
        $revenueAccounts = $user->accounts()->accountTypeIn([AccountTypeEnum::REVENUE->value])->get();
        $income = 0;
        foreach ($revenueAccounts as $account) {
            $transactions = $account->transactions()
                ->whereHas('transactionJournal', function ($query) use ($startOfMonth, $endOfMonth) {
                    $query->where('date', '>=', $startOfMonth)
                        ->where('date', '<=', $endOfMonth)
                        ->whereHas('transactionType', function ($q) {
                            $q->where('type', TransactionTypeEnum::DEPOSIT->value);
                        });
                })
                ->get();
            $income += $transactions->sum('amount');
        }

        // Expenses
        $p1Transactions = $user->transactions()
            ->whereHas('tags', function ($query) {
                $query->where('tag', 'couple-p1');
            })
            ->whereHas('transactionJournal', function ($query) use ($startOfMonth, $endOfMonth) {
                $query->where('date', '>=', $startOfMonth)
                    ->where('date', '<=', $endOfMonth);
            })
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'description' => $transaction->description,
                    'amount' => $transaction->amount,
                ];
            });

        $p2Transactions = $user->transactions()
            ->whereHas('tags', function ($query) {
                $query->where('tag', 'couple-p2');
            })
            ->whereHas('transactionJournal', function ($query) use ($startOfMonth, $endOfMonth) {
                $query->where('date', '>=', $startOfMonth)
                    ->where('date', '<=', $endOfMonth);
            })
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'description' => $transaction->description,
                    'amount' => $transaction->amount,
                ];
            });

        $sharedTransactions = $user->transactions()
            ->whereHas('tags', function ($query) {
                $query->where('tag', 'couple-shared');
            })
            ->whereHas('transactionJournal', function ($query) use ($startOfMonth, $endOfMonth) {
                $query->where('date', '>=', $startOfMonth)
                    ->where('date', '<=', $endOfMonth);
            })
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'description' => $transaction->description,
                    'amount' => $transaction->amount,
                ];
            });
            
        $unassignedTransactions = $user->transactions()
            ->whereDoesntHave('tags', function ($query) {
                $query->whereIn('tag', ['couple-p1', 'couple-p2', 'couple-shared']);
            })
            ->whereHas('transactionJournal', function ($query) use ($startOfMonth, $endOfMonth) {
                $query->where('date', '>=', $startOfMonth)
                    ->where('date', '<=', $endOfMonth);
            })
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'description' => $transaction->description,
                    'amount' => $transaction->amount,
                ];
            });

        // Goals
        $goals = $user->piggyBanks()->get()->map(function ($piggyBank) {
            return [
                'name' => $piggyBank->name,
                'amount' => $piggyBank->target_amount,
                'saved' => $piggyBank->accounts->sum('pivot.current_amount'),
                'date' => $piggyBank->target_date ? $piggyBank->target_date->format('Y-m-d') : null,
            ];
        });


        $state = [
            'person1' => [
                'name' => $user->name,
                'income' => $income,
                'transactions' => $p1Transactions,
            ],
            'person2' => [
                'name' => 'Partner', // TODO: Make this configurable
                'income' => 4000, // TODO: Get income from Firefly III
                'transactions' => $p2Transactions,
            ],
            'shared' => [
                'name' => 'Shared Expenses',
                'transactions' => $sharedTransactions,
                'contributionType' => 'incomeProportion',
                'person1CustomPercent' => 50,
            ],
            'unassigned' => [
                'name' => 'Unassigned Expenses',
                'transactions' => $unassignedTransactions,
            ],
            'goals' => $goals,
            'settings' => [
                'currency' => 'USD',
                'period' => 'monthly',
            ],
        ];

        return new JsonResponse($state);
    }

    public function storeTransaction(Request $request): JsonResponse
    {
        $user = auth()->user();
        $description = $request->input('description');
        $amount = $request->input('amount');
        $column = $request->input('column'); // e.g., 'person1', 'person2', 'shared', 'unassigned'

        // Determine the tag based on the column
        $tag = null;
        if ($column === 'person1') {
            $tag = 'couple-p1';
        } elseif ($column === 'person2') {
            $tag = 'couple-p2';
        } elseif ($column === 'shared') {
            $tag = 'couple-shared';
        }

        // Create a new transaction journal
        $journal = new TransactionJournal();
        $journal->user_id = $user->id;
        $journal->description = $description;
        $journal->amount = $amount;
        $journal->date = Carbon::now();
        $journal->transaction_type_id = TransactionTypeEnum::WITHDRAWAL->value; // Assuming it's an expense
        $journal->save();

        // Create a new transaction
        $transaction = new Transaction(); // Fully qualify to avoid conflict with TransactionJournal
        $transaction->account_id = $user->accounts()->accountTypeIn([AccountTypeEnum::EXPENSE->value])->first()->id; // Assuming an expense account
        $transaction->transaction_journal_id = $journal->id;
        $transaction->amount = $amount;
        $transaction->save();

        // Attach the tag if applicable
        if ($tag) {
            $tagModel = Tag::firstOrCreate(['tag' => $tag]);
            $journal->tags()->attach($tagModel->id);
        }

        return new JsonResponse(['message' => 'Transaction created successfully', 'transaction' => $transaction->toArray()], 201);
    }

    public function updateTransaction(Request $request, Transaction $transaction): JsonResponse
    {
        $user = auth()->user();

        // Ensure the transaction belongs to the authenticated user
        if ($transaction->transactionJournal->user_id !== $user->id) {
            return new JsonResponse(['message' => 'Unauthorized'], 403);
        }

        $description = $request->input('description');
        $amount = $request->input('amount');

        $journal = $transaction->transactionJournal;
        $journal->description = $description;
        $journal->amount = $amount;
        $journal->save();

        $transaction->amount = $amount;
        $transaction->save();

        return new JsonResponse(['message' => 'Transaction updated successfully', 'transaction' => $transaction->toArray()]);
    }

    public function deleteTransaction(Transaction $transaction): JsonResponse
    {
        $user = auth()->user();

        // Ensure the transaction belongs to the authenticated user
        if ($transaction->transactionJournal->user_id !== $user->id) {
            return new JsonResponse(['message' => 'Unauthorized'], 403);
        }

        $journal = $transaction->transactionJournal;
        $journal->delete(); // Soft delete the journal, which will also soft delete the transaction

        return new JsonResponse(['message' => 'Transaction deleted successfully']);
    }

    public function updateTransactionTag(Request $request, Transaction $transaction): JsonResponse
    {
        $user = auth()->user();

        // Ensure the transaction belongs to the authenticated user
        if ($transaction->transactionJournal->user_id !== $user->id) {
            return new JsonResponse(['message' => 'Unauthorized'], 403);
        }

        $column = $request->input('column'); // e.g., 'person1', 'person2', 'couple-shared', 'unassigned'

        // Remove existing couple-related tags
        $journal = $transaction->transactionJournal;
        $existingCoupleTags = $journal->tags()->whereIn('tag', ['couple-p1', 'couple-p2', 'couple-shared'])->get();
        foreach ($existingCoupleTags as $tag) {
            $journal->tags()->detach($tag->id);
        }

        // Determine the new tag based on the column
        $newTag = null;
        if ($column === 'person1') {
            $newTag = 'couple-p1';
        } elseif ($column === 'person2') {
            $newTag = 'couple-p2';
        } elseif ($column === 'shared') {
            $newTag = 'couple-shared';
        }

        // Attach the new tag if applicable
        if ($newTag) {
            $tagModel = Tag::firstOrCreate(['tag' => $newTag]);
            $journal->tags()->attach($tagModel->id);
        }

        return new JsonResponse(['message' => 'Transaction tag updated successfully']);
    }

    public function storeGoal(Request $request): JsonResponse
    {
        $user = auth()->user();
        $name = $request->input('name');
        $amount = $request->input('amount');
        $date = $request->input('date');

        $piggyBank = new PiggyBank();
        $piggyBank->user_id = $user->id;
        $piggyBank->name = $name;
        $piggyBank->target_amount = $amount;
        $piggyBank->target_date = Carbon::parse($date);
        $piggyBank->save();

        return new JsonResponse(['message' => 'Goal created successfully', 'goal' => $piggyBank->toArray()], 201);
    }
}