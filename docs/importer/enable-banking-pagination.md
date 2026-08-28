# Enable Banking pagination — continuation_key handling for #12532

Firefly III issue [#12532](https://github.com/firefly-iii/firefly-iii/issues/12532) was filed in `firefly-iii/firefly-iii` but the pagination bug lives in `firefly-iii/data-importer` (`app/Services/EnableBanking/Request/GetTransactionsRequest.php`).

## Bug

* `date_range=all` sends no `date_from` on the first page.
* Enable Banking embeds its own default `dateFrom` inside the returned `continuation_key` (`base64(json{params})`).
* The importer reused the original (empty) `date_from` on the second page (`?continuation_key=...` without `date_from`), so Enable Banking returned 422 "dateFrom in request is not the same as in continuationKey" and the importer silently dropped the account (`TransactionProcessor` logged only a warning, then `continue`).

## Fix

`GetTransactionsRequest::get()` now decodes the key's first segment, extracts `params`, maps `dateFrom→date_from` / `dateTo→date_to`, and overrules the current query before the follow-up `authenticatedGet()`. `null` values remove the param to match the key's absence; invalid keys are ignored safely.

The server side was also fixed at Enable Banking (key now mirrors the request), but client-side overruling makes pagination robust for history-heavy accounts and prevents silent data loss. The importer also promotes the 422 to an error so the job does not complete successfully when an account is skipped.

See `firefly-iii/data-importer` patch `app/Services/EnableBanking/Request/GetTransactionsRequest.php` and regression tests `tests/Unit/Services/EnableBanking/Request/GetTransactionsRequestTest.php` (mocked second-page asserts `date_from` from key, camelCase mapping, null removal, invalid-key resilience).

## References

* Issue https://github.com/firefly-iii/firefly-iii/issues/12532
* Data-importer commit `f0922aaa` (base) → fix adds 37 lines in `GetTransactionsRequest.php`
