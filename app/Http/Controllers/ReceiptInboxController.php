<?php
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

final class ReceiptInboxController extends Controller
{
    public function index(Request $request)
    {
        abort_unless((bool) Config::get('receipt.enabled'), 404);

        // טען רשימות חשבונות להצגה ב-Dropdown (asset / expense)
        $assetAccounts   = $this->fetchAccounts('asset');
        $expenseAccounts = $this->fetchAccounts('expense');

        return view('receipts.inbox', [
            'parsed'          => null,
            'assetAccounts'   => $assetAccounts,
            'expenseAccounts' => $expenseAccounts,
            'error'           => null,
        ]);
    }

    public function parse(Request $request)
    {
        abort_unless((bool) Config::get('receipt.enabled'), 404);

        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,pdf'],
        ]);

        $file = $request->file('file');

        try {
            $parserUrl = rtrim((string) Config::get('receipt.parser_url'), '/');

            $resp = Http::attach(
                'file',
                fopen($file->getRealPath(), 'r'),
                $file->getClientOriginalName()
            )->post($parserUrl . '/parse');

            if (!$resp->ok()) {
                return $this->renderWithError('Failed to parse receipt: ' . $resp->status() . ' ' . $resp->body());
            }

            $parsed = $resp->json();

            // טען שוב חשבונות לטופס
            $assetAccounts   = $this->fetchAccounts('asset');
            $expenseAccounts = $this->fetchAccounts('expense');

            return view('receipts.inbox', [
                'parsed'          => $parsed,
                'assetAccounts'   => $assetAccounts,
                'expenseAccounts' => $expenseAccounts,
                'error'           => null,
            ]);
        } catch (\Throwable $e) {
            Log::error('receipt-parse failed', ['e' => $e]);
            return $this->renderWithError('Unexpected error while parsing: ' . $e->getMessage());
        }
    }

    public function post(Request $request)
    {
        abort_unless((bool) Config::get('receipt.enabled'), 404);

        $data = $request->validate([
            'receipt_id'         => ['required', 'string'],
            'merchant'           => ['nullable', 'string'],
            'total_amount'       => ['required', 'numeric'],
            'currency'           => ['nullable', 'string'],
            'purchase_date'      => ['nullable', 'string'], // YYYY-MM-DD
            'vat_amount'         => ['nullable', 'numeric'],
            'raw_text'           => ['nullable', 'string'],
            'asset_account_id'   => ['required', 'string'],
            'expense_account_id' => ['required', 'string'],
        ]);

        $date        = $data['purchase_date'] ?: now()->toDateString();
        $currency    = $data['currency'] ?: 'ILS';
        $description = $data['merchant'] ?: 'Receipt';

        // payload לפי Firefly API
        $payload = [
            'apply_rules'  => true,
            'group_title'  => $description,
            'transactions' => [[
                'type'           => 'withdrawal',
                'date'           => $date,
                'amount'         => (string) $data['total_amount'],
                'currency_code'  => $currency,
                'description'    => $description,
                'source_id'      => $data['asset_account_id'],
                'destination_id' => $data['expense_account_id'],
            ]],
        ];

        try {
            $apiBase = rtrim((string) Config::get('receipt.firefly_api_base'), '/');
            $token   = (string) Config::get('receipt.firefly_token');

            if ($token === '') {
                return $this->renderWithError('Missing FIREFLY_PERSONAL_TOKEN in .env');
            }

            $resp = Http::withToken($token)
                ->acceptJson()
                ->post($apiBase . '/api/v1/transactions', $payload);

            if (!$resp->ok()) {
                return $this->renderWithError('Failed to create transaction: ' . $resp->status() . ' ' . $resp->body());
            }

            // הצלחה: נחזיר שוב את המסך עם הודעת הצלחה
            $assetAccounts   = $this->fetchAccounts('asset');
            $expenseAccounts = $this->fetchAccounts('expense');

            return view('receipts.inbox', [
                'parsed'          => null,
                'assetAccounts'   => $assetAccounts,
                'expenseAccounts' => $expenseAccounts,
                'error'           => null,
                'success'         => 'Transaction created successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('receipt-post failed', ['e' => $e]);
            return $this->renderWithError('Unexpected error while posting: ' . $e->getMessage());
        }
    }

    /**
     * @return array<int, array{id:string,name:string}>
     */
private function fetchAccounts(string $type): array
{
    try {
        $apiBase = rtrim((string) Config::get('receipt.firefly_api_base'), '/');
        $token   = (string) Config::get('receipt.firefly_token');

        if ($apiBase === '' || $token === '') {
            Log::warning('ReceiptInbox: missing API base or token');
            return [];
        }

        $resp = Http::withToken($token)
            ->acceptJson()
            ->get($apiBase.'/api/v1/accounts', ['type' => $type, 'limit' => 200]);

        if (!$resp->ok()) {
            Log::warning('ReceiptInbox: API non-OK', ['status' => $resp->status()]);
            return [];
        }

        $data = $resp->json('data') ?? [];
        return array_map(static fn($it) => [
            'id'   => (string)($it['id'] ?? ''),
            'name' => (string)($it['attributes']['name'] ?? ''),
        ], $data);
    } catch (\Throwable $e) {
        Log::error('ReceiptInbox: fetchAccounts failed', ['e' => $e->getMessage()]);
        return [];
    }
}

    private function renderWithError(string $msg)
    {
        $assetAccounts   = $this->fetchAccounts('asset');
        $expenseAccounts = $this->fetchAccounts('expense');

        return view('receipts.inbox', [
            'parsed'          => null,
            'assetAccounts'   => $assetAccounts,
            'expenseAccounts' => $expenseAccounts,
            'error'           => $msg,
        ]);
    }
}
