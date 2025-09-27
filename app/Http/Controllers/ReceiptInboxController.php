<?php
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use FireflyIII\Models\Receipt;

final class ReceiptInboxController extends Controller
{
    public function index(Request $request)
    {
        abort_unless((bool) Config::get('receipt.enabled'), 404);

        $assetAccounts   = $this->fetchAccounts('asset');
        $expenseAccounts = $this->fetchAccounts('expense');

        return view('receipts.inbox', [
            'parsed'          => null,
            'assetAccounts'   => $assetAccounts,
            'expenseAccounts' => $expenseAccounts,
            'error'           => null,
            'results'         => null,
            's3info'          => null,
        ]);
    }

    public function parse(Request $request)
    {
        abort_unless((bool) Config::get('receipt.enabled'), 404);

        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,pdf'],
        ]);

        $file = $request->file('file');

        // --- SAVE ORIGINAL TO S3 ---
        $disk   = (string) Config::get('receipt.s3_disk', 'receipts');
        $prefix = (string) Config::get('receipt.s3_prefix', 'receipts/');

        $ext    = $file->getClientOriginalExtension() ?: $file->extension();
        $uid    = (string) Str::uuid();
        $fname  = $uid . ($ext ? ('.' . strtolower($ext)) : '');
        $s3Key  = $prefix . $fname;

        Storage::disk($disk)->putFileAs('', $file, $s3Key);

        $s3Info = [
            's3_key' => $s3Key,
            'mime'   => $file->getClientMimeType(),
            'size'   => $file->getSize(),
        ];

        try {
            $parserUrl = rtrim((string) Config::get('receipt.parser_url'), '/');

            $resp = Http::attach(
                'file',
                fopen($file->getRealPath(), 'r'),
                $file->getClientOriginalName()
            )->post($parserUrl . '/parse');

            if (!$resp->ok()) {
                return $this->renderWithError(
                    'Failed to parse receipt: ' . $resp->status() . ' ' . $resp->body(),
                    $s3Info
                );
            }

            $parsed = $resp->json() ?: [];

            // fallback ל-UUID אם ה-parser לא החזיר receipt_id
            $parsed['receipt_id'] = $parsed['receipt_id'] ?? $uid;

            // נעביר ל-view גם את פרטי ה-S3
            $parsed['_s3'] = $s3Info;

            $assetAccounts   = $this->fetchAccounts('asset');
            $expenseAccounts = $this->fetchAccounts('expense');

            return view('receipts.inbox', [
                'parsed'          => $parsed,
                'assetAccounts'   => $assetAccounts,
                'expenseAccounts' => $expenseAccounts,
                'error'           => null,
                'results'         => null,
                's3info'          => $s3Info, // כדי שהטופס יכלול hidden fields
            ]);
        } catch (\Throwable $e) {
            Log::error('receipt-parse failed', ['e' => $e]);
            return $this->renderWithError('Unexpected error while parsing: ' . $e->getMessage(), $s3Info);
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

            // מידע ה-S3 מהשלב של parse (hidden fields)
            '_s3.s3_key'         => ['required','string'],
            '_s3.mime'           => ['nullable','string'],
            '_s3.size'           => ['nullable','integer'],
        ]);

        $date        = $data['purchase_date'] ?: now()->toDateString();
        $currency    = $data['currency'] ?: 'ILS';
        $description = $data['merchant'] ?: 'Receipt';

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
                return $this->renderWithError('Missing FIREFLY_PERSONAL_TOKEN in .env', $data['_s3'] ?? null);
            }

            $resp = Http::withToken($token)
                ->acceptJson()
                ->post($apiBase . '/api/v1/transactions', $payload);

            if (!$resp->ok()) {
                return $this->renderWithError(
                    'Failed to create transaction: ' . $resp->status() . ' ' . $resp->body(),
                    $data['_s3'] ?? null
                );
            }

            // חילוץ מזהה קבוצת העסקאות ושמירה בבסיס
            $respBody = $resp->json();
            $groupId  = data_get($respBody, 'data.id') ?? data_get($respBody, 'data.0.id');

            Receipt::updateOrCreate(
                [
                    'user_id'    => Auth::id(),
                    'receipt_id' => (string) $data['receipt_id'],
                ],
                [
                    'merchant'      => $data['merchant'] ?? null,
                    'total_amount'  => $data['total_amount'] ?? null,
                    'currency'      => $data['currency'] ?? null,
                    'purchase_date' => $data['purchase_date'] ?? null,
                    'vat_amount'    => $data['vat_amount'] ?? null,
                    's3_key'        => (string) data_get($data, '_s3.s3_key'),
                    'mime'          => (string) data_get($data, '_s3.mime'),
                    'size'          => (int)   data_get($data, '_s3.size'),
                    'transaction_group_id' => $groupId ? (string) $groupId : null,
                ]
            );

            $assetAccounts   = $this->fetchAccounts('asset');
            $expenseAccounts = $this->fetchAccounts('expense');

            return view('receipts.inbox', [
                'parsed'          => null,
                'assetAccounts'   => $assetAccounts,
                'expenseAccounts' => $expenseAccounts,
                'error'           => null,
                'results'         => null,
                's3info'          => null,
                'success'         => 'Transaction created successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('receipt-post failed', ['e' => $e]);
            return $this->renderWithError('Unexpected error while posting: ' . $e->getMessage(), $data['_s3'] ?? null);
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
                ->get($apiBase . '/api/v1/accounts', ['type' => $type, 'limit' => 200]);

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

    private function renderWithError(string $msg, ?array $s3info = null)
    {
        $assetAccounts   = $this->fetchAccounts('asset');
        $expenseAccounts = $this->fetchAccounts('expense');

        return view('receipts.inbox', [
            'parsed'          => null,
            'assetAccounts'   => $assetAccounts,
            'expenseAccounts' => $expenseAccounts,
            'error'           => $msg,
            'results'         => null,
            's3info'          => $s3info,
        ]);
    }

    public function search(Request $request)
    {
        abort_unless((bool) Config::get('receipt.enabled'), 404);

        $request->validate([
            'id'       => ['nullable','string'],
            'merchant' => ['nullable','string'],
        ]);

        $q = Receipt::query()->where('user_id', Auth::id());

        if ($request->filled('id')) {
            $q->where('receipt_id', (string) $request->string('id'));
        }
        if ($request->filled('merchant')) {
            $q->where('merchant', 'like', '%'.(string) $request->string('merchant').'%');
        }

        $results = $q->orderByDesc('created_at')->limit(100)->get();

        $assetAccounts   = $this->fetchAccounts('asset');
        $expenseAccounts = $this->fetchAccounts('expense');

        return view('receipts.inbox', [
            'parsed'          => null,
            'assetAccounts'   => $assetAccounts,
            'expenseAccounts' => $expenseAccounts,
            'error'           => null,
            'results'         => $results,
            's3info'          => null,
        ]);
    }

    public function show(string $receiptId)
    {
        abort_unless((bool) Config::get('receipt.enabled'), 404);

        $receipt = Receipt::where('user_id', Auth::id())
            ->where('receipt_id', $receiptId)
            ->firstOrFail();

        $disk    = (string) Config::get('receipt.s3_disk', 'receipts');
        $seconds = (int) Config::get('receipt.signed_url_seconds', 900);

        $url = Storage::disk($disk)->temporaryUrl($receipt->s3_key, now()->addSeconds($seconds));

        return response()->json([
            'receipt'      => $receipt,
            'download_url' => $url,
        ]);
    }

    public function download(string $receiptId)
    {
        abort_unless((bool) Config::get('receipt.enabled'), 404);

        $receipt = Receipt::where('user_id', Auth::id())
            ->where('receipt_id', $receiptId)
            ->firstOrFail();

        $disk = (string) Config::get('receipt.s3_disk', 'receipts');

        return Storage::disk($disk)->download($receipt->s3_key);
    }
}
