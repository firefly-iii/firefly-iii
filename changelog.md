# Change Log

All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## 6.2.18 - 2025-06-20

### Changed

- Give more details about OAuth errors.
- Currency seeder adds "RMB" instead of "CNY" for Chinese Yuan.

### Fixed

- #10454
- #10470
- Remove some leftover debug info

## 6.2.17 - 2025-06-12

### Changed

- Firefly III will trim account numbers with spaces for better matching during imports
- Running balance is only updated when amounts or accounts change.

### Fixed

- Transactions can't be linked to a piggy  bank twice.
- [Issue 10229](https://github.com/firefly-iii/firefly-iii/issues/10229) (Please show transaction ID in the Meta information) reported by @srikakulamts
- [Issue 10382](https://github.com/firefly-iii/firefly-iii/issues/10382) (Have a link to search page on mobile layout) reported by @sergeolkhovik
- [Issue 10399](https://github.com/firefly-iii/firefly-iii/issues/10399) (Can not apply rules to future dates) reported by @sanderr
- [Issue 10403](https://github.com/firefly-iii/firefly-iii/issues/10403) (Piggy banks over multiple accounts are too restrictive) reported by @sanderr
- [Issue 10413](https://github.com/firefly-iii/firefly-iii/issues/10413) (Piggy Bank math seems broken, deductions are not registered, Saved so far 0 but balance is positive) reported by @Skiri-ki
- [Issue 10419](https://github.com/firefly-iii/firefly-iii/issues/10419) (Add quarters to report view to prefill date ranges) reported by @othmar52
- [Issue 10426](https://github.com/firefly-iii/firefly-iii/issues/10426) (bcadd(): Argument #1 ($num1) is not well-formed) reported by @grgar
- [Discussion 10427](https://github.com/orgs/firefly-iii/discussions/10427) ("Stop processing" doesn't seem to work? (6.2.12)) started by @rarosalion
- [Issue 10432](https://github.com/firefly-iii/firefly-iii/issues/10432) (Error during upgrade 6.1.25 → 6.2.16: $piggyBank->account is null) reported by @Digi92
- [Issue 10433](https://github.com/firefly-iii/firefly-iii/issues/10433) (500 error when using forgot password link (running in Docker)) reported by @jegelandsdal
- [Issue 10441](https://github.com/firefly-iii/firefly-iii/issues/10441) (Missing spent per day info on mismatching budget limit periods) reported by @Kaotic3

### API

- Transactions now contain balance before/after.

## 6.2.16 - 2025-xx-xx

### Fixed

- [Issue 10367](https://github.com/firefly-iii/firefly-iii/issues/10367) (Delete account results in error) reported by @sawyert

## 6.2.15 - 2025-05-26

### Fixed

- Restore missing facades.

## 6.2.14 - 2025-05-26

### Fixed

- [Issue 10356](https://github.com/firefly-iii/firefly-iii/issues/10356) (Call to undefined method createPersonalAccessClient()) reported by @JC5
- [Issue 10357](https://github.com/firefly-iii/firefly-iii/issues/10357) (Target AuthorizationViewResponse is not instantiable) reported by @JC5

## 6.2.13 - 2025-05-25

### Fixed

- [Issue 10114](https://github.com/firefly-iii/firefly-iii/issues/10114) (Budget for foreign currency not getting updated when a transaction refers to it) reported by @srikakulamts
- [Issue 10180](https://github.com/firefly-iii/firefly-iii/issues/10180) (missing SPENT info) reported by @frank-bg
- [Issue 10197](https://github.com/firefly-iii/firefly-iii/issues/10197) (Add rule number) reported by @gogoizzy
- [PR 10203](https://github.com/firefly-iii/firefly-iii/pull/10203) (Fix Ukrainian language names) reported by @den-is
- [Issue 10265](https://github.com/firefly-iii/firefly-iii/issues/10265) (Liabilities, foreign currencies have some issues (payment/exchange with native currency)) reported by @jgmm81
- [Issue 10290](https://github.com/firefly-iii/firefly-iii/issues/10290) (edit multiple transactions individually ignores reconciled state) reported by @goebeler
- [Issue 10302](https://github.com/firefly-iii/firefly-iii/issues/10302) (Attempt to read property "id" on null Error) reported by @XBadPlayerX
- [Issue 10305](https://github.com/firefly-iii/firefly-iii/issues/10305) (The info icon used to display transactions is not clickable in the default financial reports "budgets by accounts"-section's sum column) reported by @goebeler
- [Issue 10308](https://github.com/firefly-iii/firefly-iii/issues/10308) (Liabilities, with some details (payment/exchange with native currency)) reported by @jgmm81
- [Issue 10320](https://github.com/firefly-iii/firefly-iii/issues/10320) (Button to update transaction keeps being disabled after correcting form errors) reported by @Astro1247
- [Issue 10336](https://github.com/firefly-iii/firefly-iii/issues/10336) (New install recovering backup from 5.7.18 fails to run.  ($param) must be of type int, string given in orrectsUnevenAmount.php) reported by @perezalvarezhi

## 6.2.12 - 2025-04-21

### Fixed

- [Issue 9755](https://github.com/firefly-iii/firefly-iii/issues/9755) (Unable to create transactions with non-native currency accounts when "display amounts in native currency" is enabled) reported by @dicksonleong
- [Issue 9867](https://github.com/firefly-iii/firefly-iii/issues/9867) (Transactions from Jan 31 being counted in February) reported by @edbingo
- [Issue 9878](https://github.com/firefly-iii/firefly-iii/issues/9878) (Piggy bank currency - wrong setting displayed or setting not saved) reported by @dethegeek
- [Issue 10068](https://github.com/firefly-iii/firefly-iii/issues/10068) (Export Data isn't exporting all transactions in the data) reported by @firsttiger
- [Discussion 10162](https://github.com/orgs/firefly-iii/discussions/10162) (Reverse proxy and `X-Forwarded-Prefix` header) started by @frenchu

## 6.2.11 - 2025-04-21

### Added

- Support for Persian (`fa_IR`)
- Add expiry details for personal access tokens

### Changed

- [PR 10039](https://github.com/firefly-iii/firefly-iii/pull/10039) (update check: consider cron succesfull when disabled or too frequent) reported by @ovv
- Update currency list and update exchange rates

### Fixed

- [Issue 9398](https://github.com/firefly-iii/firefly-iii/issues/9398) (Expand email settings to allow self-signed certificates) reported by @SoulSeekkor
- [Issue 9858](https://github.com/firefly-iii/firefly-iii/issues/9858) (Homepage "left to spend" count two times an expense with "foreign amount") reported by @M4xS0ch
- [Issue 10015](https://github.com/firefly-iii/firefly-iii/issues/10015) ("It looks like this IBAN is already in use." when editing asset account.) reported by @wolph
- [Issue 10025](https://github.com/firefly-iii/firefly-iii/issues/10025) (Liabilities not counted in income and expenses) reported by @BhasherBEL
- [Issue 10068](https://github.com/firefly-iii/firefly-iii/issues/10068) (Export Data isn't exporting all transactions in the data) reported by @firsttiger
- [Issue 10069](https://github.com/firefly-iii/firefly-iii/issues/10069) (Undefined array key "foreign_currency_decimal_places") reported by @akong-carbon6
- [Issue 10114](https://github.com/firefly-iii/firefly-iii/issues/10114) (Budget for foreign currency not getting updated when a transaction refers to it) reported by @srikakulamts
- [Issue 10150](https://github.com/firefly-iii/firefly-iii/issues/10150) (Mass deletion of initial balance throws 404 and doesn't delete) reported by @Tyler-Angell

## 6.2.10 - 2025-03-22

### Added

- [PR 9903](https://github.com/firefly-iii/firefly-iii/pull/9903) (Add support for PHP function `strpos` in expressions) reported by @lompi

### Changed

- [Issue 9972](https://github.com/firefly-iii/firefly-iii/issues/9972) (Important optimization improvement: enable opcache) reported by @jgoclawski
- Greatly improved speed of account overview
- Better validation of environment variables

### Fixed

- [Issue 9770](https://github.com/firefly-iii/firefly-iii/issues/9770) (User ntfy notification forbidden 403) reported by @qck4fun
- [Issue 9895](https://github.com/firefly-iii/firefly-iii/issues/9895) (Account Balance not updating in Transaction tab) reported by @StoicaRemus
- [Issue 9906](https://github.com/firefly-iii/firefly-iii/issues/9906) (404 Not Found when deleting rule group) reported by @EnochPrime
- [Issue 9908](https://github.com/firefly-iii/firefly-iii/issues/9908) (HTTP 500 on tags) reported by @wuvs
- [Issue 9914](https://github.com/firefly-iii/firefly-iii/issues/9914) (adding money to piggy bank via API broken) reported by @4e868df3
- [Issue 9941](https://github.com/firefly-iii/firefly-iii/issues/9941) (has_any_subscription search filter not valid) reported by @PhilWun
- [Issue 9948](https://github.com/firefly-iii/firefly-iii/issues/9948) (typo in docker example env file) reported by @amenekowo
- [Issue 9954](https://github.com/firefly-iii/firefly-iii/issues/9954) (Creating Bills via API is broken (optional fields are required)) reported by @jsegido
- [Discussion 9970](https://github.com/orgs/firefly-iii/discussions/9970) (Category report with tags?) started by @luddeluddis
- [Issue 9876](https://github.com/firefly-iii/firefly-iii/issues/9876) (data/bulkUpdateTransactions POST should not requires Content-Type header in request as request body must be empty) reported by @bouil
- [Issue 10007](https://github.com/firefly-iii/firefly-iii/issues/10007) (Wrong currency showed in dashboard for "Subscriptions to pay" when subscription paid with different currency) reported by @Astro1247

### API

- [Issue 9902](https://github.com/firefly-iii/firefly-iii/issues/9902) (BIC number no longer returned by the /accounts API endpoint) reported by @dawid-czarnecki


## 6.2.9 - 2025-02-22

### Fixed

- [Issue 9861](https://github.com/firefly-iii/firefly-iii/issues/9861) (lower piggy amount when full creates double audit log entry) reported by @4e868df3
- [Issue 9862](https://github.com/firefly-iii/firefly-iii/issues/9862) (Can't retrieve all accounts with the same name via API) reported by @Toshik1978
- [Issue 9863](https://github.com/firefly-iii/firefly-iii/issues/9863) (User preferences reset after restart) reported by @mico28
- [Issue 9868](https://github.com/firefly-iii/firefly-iii/issues/9868) (API: `TransactionSplit` -> `transaction_journal_id` returns int, not String) reported by @dreautall
- [Issue 9871](https://github.com/firefly-iii/firefly-iii/issues/9871) (include net worth is ignored in the API - from PICO developer) reported by @fate8383
- [Issue 9882](https://github.com/firefly-iii/firefly-iii/issues/9882) (Reconciliation bug on Docker instance) reported by @benjaminteyssier
- [Issue 9884](https://github.com/firefly-iii/firefly-iii/issues/9884) (Data import, currency #0 not found) reported by @ragnarkarlsson
- [Issue 9885](https://github.com/firefly-iii/firefly-iii/issues/9885) (Invalid server configuration: missing required package) reported by @EricVanCaenenberghe
- [Issue 9887](https://github.com/firefly-iii/firefly-iii/issues/9887) (Creating a Liability Account Throws TypeError in TransactionJournalFactory::setUser()) reported by @mikeashi

## 6.2.8 - 2025-02-22

### Fixed

- [Issue 9861](https://github.com/firefly-iii/firefly-iii/issues/9861) (lower piggy amount when full creates double audit log entry) reported by @4e868df3
- [Issue 9862](https://github.com/firefly-iii/firefly-iii/issues/9862) (Can't retrieve all accounts with the same name via API) reported by @Toshik1978
- [Issue 9863](https://github.com/firefly-iii/firefly-iii/issues/9863) (User preferences reset after restart) reported by @mico28
- [Issue 9868](https://github.com/firefly-iii/firefly-iii/issues/9868) (API: `TransactionSplit` -> `transaction_journal_id` returns int, not String) reported by @dreautall
- [Issue 9871](https://github.com/firefly-iii/firefly-iii/issues/9871) (include net worth is ignored in the API - from PICO developer) reported by @fate8383
- [Issue 9882](https://github.com/firefly-iii/firefly-iii/issues/9882) (Reconciliation bug on Docker instance) reported by @benjaminteyssier
- [Issue 9884](https://github.com/firefly-iii/firefly-iii/issues/9884) (Data import, currency #0 not found) reported by @ragnarkarlsson
- [Issue 9885](https://github.com/firefly-iii/firefly-iii/issues/9885) (Invalid server configuration: missing required package) reported by @EricVanCaenenberghe
- [Issue 9887](https://github.com/firefly-iii/firefly-iii/issues/9887) (Creating a Liability Account Throws TypeError in TransactionJournalFactory::setUser()) reported by @mikeashi

## 6.2.7 - 2025-02-19

### Changed

- Optimised Account and Transaction API endpoints, should be a lot faster
- Optimized account deletion, should be a lot faster

### Fixed

- [Issue 9803](https://github.com/firefly-iii/firefly-iii/issues/9803) (Left to spend - All negativ after update.) reported by @nedsined
- [Issue 9835](https://github.com/firefly-iii/firefly-iii/issues/9835) (Failed to create transaction in recurring transactions on 6.2.6) reported by @hhl5350
- [Issue 9842](https://github.com/firefly-iii/firefly-iii/issues/9842) (Net worth on dashboard does not go up to the end of month for the current month) reported by @standingduck3
- [Issue 9848](https://github.com/firefly-iii/firefly-iii/issues/9848) (Failed to export accounts data) reported by @Jaeger87
- [Issue 9855](https://github.com/firefly-iii/firefly-iii/issues/9855) (Demo Website not working) reported by @xfarrow

## 6.2.6 - 2025-02-13

### Fixed

- [Issue 9797](https://github.com/firefly-iii/firefly-iii/issues/9797) (All account charts show a horizontal line) reported by @avee87
- [Issue 9806](https://github.com/firefly-iii/firefly-iii/issues/9806) (Exchange Rates table fails to load when language is not English) reported by @polter-rnd
- [Issue 9807](https://github.com/firefly-iii/firefly-iii/issues/9807) (Start Date of Reconciliation Period Incorrectly Excludes Transactions for That Day) reported by @pwschattenberg
- [Issue 9808](https://github.com/firefly-iii/firefly-iii/issues/9808) (Default financial report line graph shows a different balance than the text) reported by @mtaygur
- [Issue 9810](https://github.com/firefly-iii/firefly-iii/issues/9810) (Foreign amount not taken into consideration for budget spent/leaving) reported by @M4xS0ch
- [Issue 9821](https://github.com/firefly-iii/firefly-iii/issues/9821) (piggy events api 500 error) reported by @4e868df3
- [Issue 9826](https://github.com/firefly-iii/firefly-iii/issues/9826) (Wrong account balance) reported by @fabienfitoussi

## 6.2.5 - 2025-02-08

### Fixed

- [Issue 9736](https://github.com/firefly-iii/firefly-iii/issues/9736) (Wrong `finalAccountBalance` result) reported by @gthbusrr
- [Issue 9747](https://github.com/firefly-iii/firefly-iii/issues/9747) (Data entry issues with exchange rates) reported by @Azmodeszer
- [Issue 9769](https://github.com/firefly-iii/firefly-iii/issues/9769) ("Your accounts" graph shows changes at wrong dates - not fixed by v6.2.4) reported by @SteffoSpieler
- [Discussion 9780](https://github.com/orgs/firefly-iii/discussions/9780) (Rules or webhook precedence?) started by @joeshmoe57
- [Issue 9781](https://github.com/firefly-iii/firefly-iii/issues/9781) (Search key `has_any_external_url:false` returns all transactions) reported by @joeshmoe57
- [Issue 9783](https://github.com/firefly-iii/firefly-iii/issues/9783) (Subscriptions: Make "Not expected this period" and "expected x days from now" different colors) reported by @SteffoSpieler
- [Issue 9784](https://github.com/firefly-iii/firefly-iii/issues/9784) (Transfers with external currency not considered for account balance?) reported by @pvieira84
- [Issue 9786](https://github.com/firefly-iii/firefly-iii/issues/9786) (The error 500 information page has non-clickable links to github and the debug page) reported by @tjmv
- [Issue 9787](https://github.com/firefly-iii/firefly-iii/issues/9787) (Twig general template error formatting TransactionCurrency on main page) reported by @tjmv
- [Issue 9789](https://github.com/firefly-iii/firefly-iii/issues/9789) (Can't open expense and revenue accounts view) reported by @puffer-duck

## 6.2.4 - 2025-02-05

> ⚠️ _Most pressing issues are fixed. Please open [an issue here](https://github.com/firefly-iii/firefly-iii/issues/new?template=bug.yml) if you run into problems._


### Fixed

- [Issue 9327](https://github.com/firefly-iii/firefly-iii/issues/9327) (Add Link to Search-Page to the help file) reported by @nottheend
- [Issue 9713](https://github.com/firefly-iii/firefly-iii/issues/9713) (Many decimal points in amounts) reported by @memo-567
- [Issue 9736](https://github.com/firefly-iii/firefly-iii/issues/9736) (Wrong `finalAccountBalance` result) reported by @gthbusrr
- [Discussion 9737](https://github.com/orgs/firefly-iii/discussions/9737) (API returns 0 as current balance) started by @eps90
- [Issue 9745](https://github.com/firefly-iii/firefly-iii/issues/9745) (Type mismatch in period overview) reported by @electrofloat
- [Issue 9747](https://github.com/firefly-iii/firefly-iii/issues/9747) (Data entry issues with exchange rates) reported by @Azmodeszer
- [Issue 9751](https://github.com/firefly-iii/firefly-iii/issues/9751) (Net worth changes since 6.2 update) reported by @ahmaddxb
- [Issue 9754](https://github.com/firefly-iii/firefly-iii/issues/9754) (Deleting account - Attachments remain) reported by @memo-567
- [Issue 9762](https://github.com/firefly-iii/firefly-iii/issues/9762) (Piggy bank show: start/target date not displayed) reported by @Simeam
- Various other balance related fixes.
- Hotfix for account charts.

## 6.2.3 - 2025-02-05

> ⚠️ _Most pressing issues are fixed. Please open [an issue here](https://github.com/firefly-iii/firefly-iii/issues/new?template=bug.yml) if you run into problems._

### Fixed

- [Issue 9327](https://github.com/firefly-iii/firefly-iii/issues/9327) (Add Link to Search-Page to the help file) reported by @nottheend
- [Issue 9713](https://github.com/firefly-iii/firefly-iii/issues/9713) (Many decimal points in amounts) reported by @memo-567
- [Issue 9736](https://github.com/firefly-iii/firefly-iii/issues/9736) (Wrong `finalAccountBalance` result) reported by @gthbusrr
- [Discussion 9737](https://github.com/orgs/firefly-iii/discussions/9737) (API returns 0 as current balance) started by @eps90
- [Issue 9745](https://github.com/firefly-iii/firefly-iii/issues/9745) (Type mismatch in period overview) reported by @electrofloat
- [Issue 9747](https://github.com/firefly-iii/firefly-iii/issues/9747) (Data entry issues with exchange rates) reported by @Azmodeszer
- [Issue 9751](https://github.com/firefly-iii/firefly-iii/issues/9751) (Net worth changes since 6.2 update) reported by @ahmaddxb
- [Issue 9754](https://github.com/firefly-iii/firefly-iii/issues/9754) (Deleting account - Attachments remain) reported by @memo-567
- [Issue 9762](https://github.com/firefly-iii/firefly-iii/issues/9762) (Piggy bank show: start/target date not displayed) reported by @Simeam
- Various other balance related fixes.

## 6.2.2 - 2025-02-02

> ⚠️ _This release comes with many changes, small and large. I expect you will run into issue, and I appreciate your feedback and your patience as I fix them. I've tested many things, but I'm 100% sure I've missed things. Please open [an issue here](https://github.com/firefly-iii/firefly-iii/issues/new?template=bug.yml) if you run into problems._

### Fixed

- [Issue 9713](https://github.com/firefly-iii/firefly-iii/issues/9713) (Many decimal points in amounts) reported by @memo-567
- [Discussion 9727](https://github.com/orgs/firefly-iii/discussions/9727) (Odd behavior from graphs) started by @jbrandwi
- [Issue 9729](https://github.com/firefly-iii/firefly-iii/issues/9729) (Type mismatch in transaction collector) reported by @electrofloat
- [Issue 9730](https://github.com/firefly-iii/firefly-iii/issues/9730) (API: `PiggyBankAccountRead.id` should return string, returns int) reported by @dreautall
- [Issue 9731](https://github.com/firefly-iii/firefly-iii/issues/9731) ($userGroup must not be accessed before initialization) reported by @Azmodeszer
- [Issue 9732](https://github.com/firefly-iii/firefly-iii/issues/9732) (Editing dates of a piggy bank does not save) reported by @Simeam
- [Issue 9736](https://github.com/firefly-iii/firefly-iii/issues/9736) (Wrong `finalAccountBalance` result) reported by @gthbusrr
- [Issue 9738](https://github.com/firefly-iii/firefly-iii/issues/9738) (Missing pagination controls on exchange rate page) reported by @dicksonleong
- [Discussion 9744](https://github.com/orgs/firefly-iii/discussions/9744) (Downloading exchange rates results in 404 error) started by @electrofloat

### Added

- [PR 9743](https://github.com/firefly-iii/firefly-iii/pull/9743) (Feature nordic currencies) reported by @mansehr


## 6.2.1 - 2025-02-01

> ⚠️ _This release comes with many changes, small and large. I expect you will run into issue, and I appreciate your feedback and your patience as I fix them. I've tested many things, but I'm 100% sure I've missed things. Please open [an issue here](https://github.com/firefly-iii/firefly-iii/issues/new?template=bug.yml) if you run into problems._

### Fixed

- [Issue 9714](https://github.com/firefly-iii/firefly-iii/issues/9714) (current transaction is aborted, commands ignored until end of transaction block) reported by @captainark
- [Issue 9717](https://github.com/firefly-iii/firefly-iii/issues/9717) (API api/v1/accounts broken) reported by @brot
- [Issue 9719](https://github.com/firefly-iii/firefly-iii/issues/9719) (A couple of TODOs left in resources/views/list/groups.twig) reported by @lostfocus
- [Issue 9720](https://github.com/firefly-iii/firefly-iii/issues/9720) (Piggy Bank with no Target amount: unable to add money) reported by @maxsmooth
- [Issue 9721](https://github.com/firefly-iii/firefly-iii/issues/9721) (Documentation on exchange-rates page leads to non-existent page) reported by @electrofloat
- [Issue 9722](https://github.com/firefly-iii/firefly-iii/issues/9722) (Error 500 on tags page after enabling native currency) reported by @lostfocus
- [Issue 9723](https://github.com/firefly-iii/firefly-iii/issues/9723) (Convert to native routine is not called when convert to native is activated) reported by @JC5
- [Issue 9726](https://github.com/firefly-iii/firefly-iii/issues/9726) (Postgres cannot deal with "virtual_balance" column) reported by @dicksonleong

## 6.2.0 - 2025-01-31

> ⚠️ _This release comes with many changes, small and large. I expect you will run into issue, and I appreciate your feedback and your patience as I fix them. I've tested many things, but I'm 100% sure I've missed things. Please open [an issue here](https://github.com/firefly-iii/firefly-iii/issues/new?template=bug.yml) if you run into problems._

### Added

- Multi-currency support. If you set `ENABLE_EXCHANGE_RATES=true` and optionally `ENABLE_EXTERNAL_RATES=true` Firefly III will have the ability to calculate all foreign currencies back to your native currency. This is a work in progress, not all fields and all places will support this yet. Please check out the [documentation](https://docs.firefly-iii.org/explanation/financial-concepts/exchange-rates/).
- There is notifications support for Nfty, Pushover, Slack and Discord.
- There are many new security related notifications.
- [Issue 5523](https://github.com/firefly-iii/firefly-iii/issues/5523) (Add comment on a budget for a given month) reported by @n-serrette
- [Issue 5532](https://github.com/firefly-iii/firefly-iii/issues/5532) (Asset prices and exchange rates) reported by @svozniuk
- [Issue 6314](https://github.com/firefly-iii/firefly-iii/issues/6314) (Currencies and exchange rates) reported by @JC5
- [Issue 6557](https://github.com/firefly-iii/firefly-iii/issues/6557) (Piggy Banks - Draw Funds from Multiple Accounts) reported by @BugPhobic
- [Issue 6760](https://github.com/firefly-iii/firefly-iii/issues/6760) (Add a new trigger for automated rules) reported by @Gsyltc
- [Issue 7945](https://github.com/firefly-iii/firefly-iii/issues/7945) ("Rules" that only trigger manually) reported by @SekoiaTree
- [Issue 8307](https://github.com/firefly-iii/firefly-iii/issues/8307) (Notification support for Ntfy (and other push notification tools)) reported by @ragnarkarlsson
- [Issue 8531](https://github.com/firefly-iii/firefly-iii/issues/8531) (Add `notes` to transaction audit report) reported by @clouserw
- [Discussion 9576](https://github.com/orgs/firefly-iii/discussions/9576) (How to Mark Reconciled Transactions as Unreconciled) started by @softpoint-arch
- [Issue 9667](https://github.com/firefly-iii/firefly-iii/issues/9667) (Exported data only exports negative amounts?) reported by @electricmessiah
- [Issue 9586](https://github.com/firefly-iii/firefly-iii/issues/9586) (Non en_US translated string in sign-up mail) reported by @benni347
- [PR 9598](https://github.com/firefly-iii/firefly-iii/pull/9598) (New Query Parser for Search Engine and Rules) reported by @Sobuno

### Changed

- Firefly III requires PHP 8.4.
- Docker container no longer runs under root.
- "Bills" are now called "subscriptions" to better reflect their purpose.
- Rename "administration" to "settings" to prevent confusion with "financial administrations"
- Rename 'default currency' to 'native currency'
- Move native currency setting to financial administration edit screen to better reflect where it belongs
- [Issue 9501](https://github.com/firefly-iii/firefly-iii/issues/9501) (PHP8.4 support) reported by @JC5
- [Issue 9683](https://github.com/firefly-iii/firefly-iii/issues/9683) (500 viewing inactive liabilities) reported by @stuzer05

### Removed

- Removed support for PHP 8.3 and lower.
- Removed Docker support for linux/arm/v7, linux/arm/v8 and linux/386. Sorry. 

### Fixed

- [Issue 9532](https://github.com/firefly-iii/firefly-iii/issues/9532) (ReportSum Integrity Check fails due to empty foreign_amount) reported by @SircasticFox
- [Issue 7288](https://github.com/firefly-iii/firefly-iii/issues/7288) (currentMonthStart/currentMonthEnd not working for no-budget view) reported by @bradsk88
- [Issue 9704](https://github.com/firefly-iii/firefly-iii/issues/9704) (Piggy banks widget displays only main currency for different currencies) reported by @vayakovlev 

### API

- API changes related to new features are [documented](https://api-docs.firefly-iii.org/).
- New endpoint for multiple financial administrations ("user groups").
- The change from "default currency" (user) to "native currency" (financial administration) is slowly being reflected in the API. Please report issues.
- You can change the "transaction type" of an existing transaction if you submit a new `type` and the correct source and destination account names or IDs.

## 6.1.25 - 2024-12-19

### Fixed

- [Issue 9492](https://github.com/firefly-iii/firefly-iii/issues/9492) (API: `PUT /api/v1/transactions/ID` does not work with only `source_name`) reported by @dreautall
- [Issue 9497](https://github.com/firefly-iii/firefly-iii/issues/9497) ('Attempt to read property "user" on null' error when running `php artisan firefly-iii:upgrade-database` on updating to v6.1.24) reported by @ChrisCarini
- [Discussion 9546](https://github.com/orgs/firefly-iii/discussions/9546) (Using the Data Importer to import data is very slowly？) started by @shangyuok

## 6.1.24 - 2024-11-24

### Fixed

- [Issue 9491](https://github.com/firefly-iii/firefly-iii/issues/9491) (Transactions created at midnight on the first day of a month are not listed correctly) reported by @Neroxeles

## 6.1.23 - 2024-11-23

### Added

- Expand (future) timezone support.
- [Issue 9451](https://github.com/firefly-iii/firefly-iii/issues/9451) (Trigger "Has at least this many attachments" is misnamed?) reported by @Kvan7
- [Issue 9458](https://github.com/firefly-iii/firefly-iii/issues/9458) (The add transaction failed) reported by @wqwwffsytxwp
- [Issue 9466](https://github.com/firefly-iii/firefly-iii/issues/9466) (First instance of recurring transaction not firing automatically or manually) reported by @makobot-sh
- [PR 9468](https://github.com/firefly-iii/firefly-iii/pull/9468) (Transaction Model: explicitly cast decimal to string) reported by @yparitcher
- [Issue 9477](https://github.com/firefly-iii/firefly-iii/issues/9477) (Default Financial report: Income vs Expense has missing months when multiple currencies are used) reported by @thomase1234
- [PR 9488](https://github.com/firefly-iii/firefly-iii/pull/9488) (fix: set dest foreign_amount and foreign_currency_id for foreign transfers) reported by @antoniomrfranco
- [PR 9483](https://github.com/firefly-iii/firefly-iii/pull/9483) (fix: include foreign_amount in transaction sum calculation) reported by @antoniomrfranco
- Broken links in readme.

## 6.1.22 - 2024-11-07

### Added

- [Discussion 8092](https://github.com/orgs/firefly-iii/discussions/8092) (Fresh Install - Register -> 403 Error - Forbidden) started by @pheonix-devapps
- [Issue 9183](https://github.com/firefly-iii/firefly-iii/issues/9183) (2FA security improvements) reported by @JC5
- Firefly III stores timezone data in a separate field, preparing for a switch to UTC (in the database).

### Fixed

- [Issue 9106](https://github.com/firefly-iii/firefly-iii/issues/9106) (Inactive accounts are inaccessible when no active accounts of that type exist) reported by @codemicro
- [Issue 9147](https://github.com/firefly-iii/firefly-iii/issues/9147) (Store/Update Bill API end_date and extension_date cant be null in request) reported by @jkano
- [Issue 9175](https://github.com/firefly-iii/firefly-iii/issues/9175) ("Attempt to read property "type" on null" when mass editing transactions) reported by @Still34
- [Issue 9225](https://github.com/firefly-iii/firefly-iii/issues/9225) (Liability amount due calculated incorrectly on liabilities list when the liability is settled with a transfer to another liability) reported by @uumas
- [Discussion 9234](https://github.com/orgs/firefly-iii/discussions/9234) (Unsupported cipher or incorrect key length ( first run )) started by @spectroman
- [Issue 9236](https://github.com/firefly-iii/firefly-iii/issues/9236) (Autocomplete not working for rrules having the bill as a trigger) reported by @pvieira84
- [Issue 9282](https://github.com/firefly-iii/firefly-iii/issues/9282) (Default report - no transactions for no budget) reported by @rymrg 
- [Issue 9294](https://github.com/firefly-iii/firefly-iii/issues/9294) (Repetition counts ignored for recurring transactions) reported by @Syncena
- [Issue 9303](https://github.com/firefly-iii/firefly-iii/issues/9303) (Rules > Rule > Action) reported by @EricVanCaenenberghe
- [Issue 9305](https://github.com/firefly-iii/firefly-iii/issues/9305) (Recurring transactions get group title on overview page) reported by @zeitwidrig
- [Discussion 9324](https://github.com/orgs/firefly-iii/discussions/9324) (Consistent behavior accross DB Engines) started by @stackcoder
- [Issue 9360](https://github.com/firefly-iii/firefly-iii/issues/9360) (Date incorrectly shown) reported by @enboig
- [Issue 9389](https://github.com/firefly-iii/firefly-iii/issues/9389) (Budget and Bill field on Recurring transactions not updating) reported by @HHUBSS
- [Issue 9416](https://github.com/firefly-iii/firefly-iii/issues/9416) (Linking Transaction to Bill doesn't mark as paid for 31st) reported by @harrhunt
- [Issue 9427](https://github.com/firefly-iii/firefly-iii/issues/9427) (The standard financial report does not show all transactions for the income categories) reported by @Neroxeles
- [Issue 9443](https://github.com/firefly-iii/firefly-iii/issues/9443) (Budget report on inactive budget gives a 404) reported by @adyanth
- [Issue 9444](https://github.com/firefly-iii/firefly-iii/issues/9444) (Printing a page does not include dates) reported by @cachho 
- [Issue 9447](https://github.com/firefly-iii/firefly-iii/issues/9447) (Transaction doesn't show up when attaching HTML file) reported by @Marc928132

### API

- API version is no longer distinguished from Firefly III version. API jumps from v2.1.0 to v6.1.22
- API v2 is cleaned up and misses a few previously available endpoints. They will be added in the future.
- [Discussion 9271](https://github.com/orgs/firefly-iii/discussions/9271) (/v2/chart/balance/balance ignoring the `period` parameter) started by @victorbalssa

## 6.1.21 - 2024-09-30

### Added

- Enabled the expression engine built by @michaelhthomas. Read more about it in [the documentation](https://docs.firefly-iii.org/references/firefly-iii/rule-expressions/).
- Add running balance data, see if it can be used in the layout in the future.
- [PR 9160](https://github.com/firefly-iii/firefly-iii/pull/9160) (add test cases for api/v1/autocomplete/CategoryController) reported by @tasnim0tantawi
- [PR 9178](https://github.com/firefly-iii/firefly-iii/pull/9178) (Add  test cases for Api\V1\Controllers\Autocomplete\BillController & BudgetController) reported by @tasnim0tantawi
- [PR 9171](https://github.com/firefly-iii/firefly-iii/pull/9171) (Add about test) reported by @mzhubail

### Changed

- [PR 9096](https://github.com/firefly-iii/firefly-iii/pull/9096) (chore: fix some comments) reported by @withbest

### Fixed

- [Issue 9078](https://github.com/firefly-iii/firefly-iii/issues/9078) (bcadd exception while using POST transactions) reported by @dbtdsilva
- [Discussion 9080](https://github.com/orgs/firefly-iii/discussions/9080) (Incorrect sorting on expense accounts) started by @pc-zookeeper
- [Issue 9084](https://github.com/firefly-iii/firefly-iii/issues/9084) (API Call for bills/nextExpectedMatch does not update) reported by @marcelweikum
- [Issue 9103](https://github.com/firefly-iii/firefly-iii/issues/9103) (Default Currency does not apply to Accounts.) reported by @chrisgriff1512
- [Issue 9140](https://github.com/firefly-iii/firefly-iii/issues/9140) (Dashboard 'Today' option chooses 1st of month (not current date)) reported by @PAS-BC
- [PR 9179](https://github.com/firefly-iii/firefly-iii/pull/9179) (fix Navigation.php MTD logic to make tests pass.) reported by @tasnim0tantawi
- [PR 9239](https://github.com/firefly-iii/firefly-iii/pull/9239) (Fix webhook index page when Firefly is not served at root) reported by @jfpedroza
- [Issue 9168](https://github.com/firefly-iii/firefly-iii/issues/9168) (Custom logout URL doesn't work.) reported by @JC5
- [Issue 9155](https://github.com/firefly-iii/firefly-iii/issues/9155) (internal_reference_is does not correctly match numeric internal references) reported by @Lrns123
- [Issue 9275](https://github.com/firefly-iii/firefly-iii/issues/9275) (Long wait when editing a transaction) reported by @JC5
- [Issue 9278](https://github.com/firefly-iii/firefly-iii/issues/9278) (Update to v6.1.20 changed Balance of Account) reported by @JeuJeus
- [Issue 9281](https://github.com/firefly-iii/firefly-iii/issues/9281) (Update to v6.1.20 leads to a type error) reported by @krakonos1602

### API

- Expand v2 API

## 6.1.20 - 2024-09-29

### Added

- Enabled the expression engine built by @michaelhthomas. Read more about it in [the documentation](https://docs.firefly-iii.org/references/firefly-iii/rule-expressions/).
- Add running balance data, see if it can be used in the layout in the future.
- [PR 9160](https://github.com/firefly-iii/firefly-iii/pull/9160) (add test cases for api/v1/autocomplete/CategoryController) reported by @tasnim0tantawi
- [PR 9178](https://github.com/firefly-iii/firefly-iii/pull/9178) (Add  test cases for Api\V1\Controllers\Autocomplete\BillController & BudgetController) reported by @tasnim0tantawi
- [PR 9171](https://github.com/firefly-iii/firefly-iii/pull/9171) (Add about test) reported by @mzhubail

### Changed

- [PR 9096](https://github.com/firefly-iii/firefly-iii/pull/9096) (chore: fix some comments) reported by @withbest

### Fixed

- [Issue 9078](https://github.com/firefly-iii/firefly-iii/issues/9078) (bcadd exception while using POST transactions) reported by @dbtdsilva
- [Discussion 9080](https://github.com/orgs/firefly-iii/discussions/9080) (Incorrect sorting on expense accounts) started by @pc-zookeeper
- [Issue 9084](https://github.com/firefly-iii/firefly-iii/issues/9084) (API Call for bills/nextExpectedMatch does not update) reported by @marcelweikum
- [Issue 9103](https://github.com/firefly-iii/firefly-iii/issues/9103) (Default Currency does not apply to Accounts.) reported by @chrisgriff1512
- [Issue 9140](https://github.com/firefly-iii/firefly-iii/issues/9140) (Dashboard 'Today' option chooses 1st of month (not current date)) reported by @PAS-BC
- [PR 9179](https://github.com/firefly-iii/firefly-iii/pull/9179) (fix Navigation.php MTD logic to make tests pass.) reported by @tasnim0tantawi
- [PR 9239](https://github.com/firefly-iii/firefly-iii/pull/9239) (Fix webhook index page when Firefly is not served at root) reported by @jfpedroza
- [Issue 9168](https://github.com/firefly-iii/firefly-iii/issues/9168) (Custom logout URL doesn't work.) reported by @JC5
- [Issue 9155](https://github.com/firefly-iii/firefly-iii/issues/9155) (internal_reference_is does not correctly match numeric internal references) reported by @Lrns123

### API

- Expand v2 API

## 6.1.19 - 2024-07-20

### Fixed

- [Issue 8844](https://github.com/firefly-iii/firefly-iii/issues/8844) (Split recurring transaction gets wrong (split) titles) reported by @dreautall
- [Issue 8981](https://github.com/firefly-iii/firefly-iii/issues/8981) (bcadd() error during Docker container startup) reported by @NoiTheCat
- [Issue 8986](https://github.com/firefly-iii/firefly-iii/issues/8986) (Search with "internal_reference_is" finds all transactions with full word of search string) reported by @baflo
- [Issue 9009](https://github.com/firefly-iii/firefly-iii/issues/9009) (Incorrect Amount Calculation in Reconciliation for Bank Account A) reported by @realzsan3
- [Issue 9021](https://github.com/firefly-iii/firefly-iii/issues/9021) (Incorrect "Expected Withdrawals" for Daily Recurring Transactions) reported by @xMarcii
- [Issue 9022](https://github.com/firefly-iii/firefly-iii/issues/9022) (Calendar Not Showing Green Fields for Recurring Transactions) reported by @xMarcii
- Improved currency exchange rate downloader

## 6.1.18 - 2024-06-19

### Fixed

- [Issue 8978](https://github.com/firefly-iii/firefly-iii/issues/8978) (Error! Internal Firefly III Exception: bcadd(): Argument #2 ($num2) is not well-formed) reported by @el-rhazi
- [Issue 8977](https://github.com/firefly-iii/firefly-iii/issues/8977) (Data Importer: "500 Server Error" with Firefly III v6.1.17) reported by @qtdzz

### Security

- [CVE-2024-37893](https://www.cve.org/CVERecord?id=CVE-2024-37893)

## 6.1.17 - 2024-06-16

### Added

- New routine that calculates account balances, first start could take a while.

### Removed

- Removed auto-generated language files.

### Fixed

- [Issue 8907](https://github.com/firefly-iii/firefly-iii/issues/8907) (Error when adding initial balance: bcadd(): Argument #2 ($num2) must be of type string, int given) reported by @wnklmnn
- [Issue 8911](https://github.com/firefly-iii/firefly-iii/issues/8911) (Docker container startup very slow) reported by @daften
- [PR 8929](https://github.com/firefly-iii/firefly-iii/pull/8929) (icon title chgd from Deposit to Transfer) reported by @stevewasiura
- [PR 8930](https://github.com/firefly-iii/firefly-iii/pull/8930) (icon title chgd from Deposit to Transfer) reported by @stevewasiura
- [PR 8951](https://github.com/firefly-iii/firefly-iii/pull/8951) (add icon for delete action) reported by @stevewasiura
- [PR 8957](https://github.com/firefly-iii/firefly-iii/pull/8957) (Remove nesting level for markdown) reported by @JeroenED
- [Issue 8958](https://github.com/firefly-iii/firefly-iii/issues/8958) (Weird line appears above the UI when clicking on matching transactions for a rule) reported by @avee87
- [Issue 8893](https://github.com/firefly-iii/firefly-iii/issues/8893) (API: `reconciled: false` does not have precedence) reported by @dreautall
- [Issue 8954](https://github.com/firefly-iii/firefly-iii/issues/8954) (Wrong calculation of transaction without category) reported by @anarion80
- [Issue 8927](https://github.com/firefly-iii/firefly-iii/issues/8927) (Converting deposit to transfer can set incorrect transaction currency) reported by @avee87
- Various issues in release train.
- There is a confirmation again before you delete data using the page in your profile

### Security

- Two (undisclosed) MFA bypass errors, reported by @Skelmis. Disclosure will follow in a few weeks.

### API

- Expand v2 chart API

## 6.1.16 - 2024-05-20

### Added

- Added [THANKS.md] to give credit to all developers who help with the development of Firefly III.

### Changed

- New data model for "account balance" makes it easier to calculate and use multi-currency accounts. Not yet in use.

### Fixed

- [Issue 8840](https://github.com/firefly-iii/firefly-iii/issues/8840) (Budget page crash) reported by @JcMinarro
- [Issue 8863](https://github.com/firefly-iii/firefly-iii/issues/8863) (Empty webhooks page) reported by @mrahmadt
- [Issue 8867](https://github.com/firefly-iii/firefly-iii/issues/8867) (SQL Integrity constraint violation when inserting into budget_limits) reported by @HedgehogRidingAnOwl 
- [Issue 8858](https://github.com/firefly-iii/firefly-iii/issues/8858) (A single Account constantly loses its Account NUmber / IBAN ) reported by @ypsilonkah

### API

- New filters for the v2 autocomplete endpoints.
- Various attempts to make a better v2 accounts endpoint.

## 6.1.15 - 2024-04-24

### Fixed

- [Issue 8812](https://github.com/firefly-iii/firefly-iii/issues/8812) (Login with `AUTHENTICATION_GUARD=remote_user_guard` fails due to missing UserGroup) reported by @nebulade

## 6.1.14 - 2024-04-24

### Changed
- You may have to define again which asset accounts you want to see on the dashboard. Sorry about that.
- Expanded some database models.
- Limit the number of error messages Firefly III will send (so Mailgun keeps liking me).
- [PR 8746](https://github.com/firefly-iii/firefly-iii/pull/8746) (Set date to now when cloning journal) reported by @imlonghao

### Fixed

- [Issue 8748](https://github.com/firefly-iii/firefly-iii/issues/8748) (Release tarballs mistakenly include the `.zip` artifact) reported by @sudoBash418
- [Discussion 8750](https://github.com/orgs/firefly-iii/discussions/8750) (API To change  transaction fails to find destination_id) started by @soloam 
- [Issue 8779](https://github.com/firefly-iii/firefly-iii/issues/8779) (Change Password Form not working ≥  6.1.11) reported by @jemtz-deleon
- [Issue 8781](https://github.com/firefly-iii/firefly-iii/issues/8781) (Bill information missing in /api/v1/search/transactions responses) reported by @daanvanberkel
- [Issue 8752](https://github.com/firefly-iii/firefly-iii/issues/8752) (Transactions reorder not work (error 404)) reported by @BoGnY
- [Issue 8613](https://github.com/firefly-iii/firefly-iii/issues/8613) (Some minor color issues) reported by @rumpff
- [Issue 8776](https://github.com/firefly-iii/firefly-iii/issues/8776) (report-data/category/expenses has wrong sums with specific date range) reported by @bouil

### API

- [Issue 8804](https://github.com/firefly-iii/firefly-iii/issues/8804) (Unable to create rules with negation via API) reported by @tailg8nj

## 6.1.13 - 2024-04-01

### Added

- sha256 checksums for the release files
- git HEAD added to the release files for easier validation

### Changed

- Updated pages in the `v2`-layout

### Fixed

- [Issue 8648](https://github.com/firefly-iii/firefly-iii/issues/8648) (Crashes during initial setup with PG 16 dbs) reported by @Lysholm
- [Issue 8725](https://github.com/firefly-iii/firefly-iii/issues/8725) (API: Call to `api/v1/bills` without arguments fails) reported by @dreautall
- [Issue 8732](https://github.com/firefly-iii/firefly-iii/issues/8732) (Error "Division by zero" when opening the "Budget" section) reported by @mrResident
- [PR 8735](https://github.com/firefly-iii/firefly-iii/pull/8735) (Fix `Division error by zero` in budget views) reported by @mansuf

## 6.1.12 - 2024-03-21

### Fixed

- Exclude debug files from release zip file
- Drop a duplicate index
- Replace broken Laravel Passport commands
- [Issue 8692](https://github.com/firefly-iii/firefly-iii/issues/8692) (passport:install step issue when upgrading to 6.1.11) reported by @captainark
- [Discussion 8694](https://github.com/orgs/firefly-iii/discussions/8694) (Getting error on accessing admin panel of Firefly 6.1.11) started by @jameswill

## 6.1.11 - 2024-03-20

### Added

- New expression engine by @michaelhthomas, still disabled though.
- Missing database indexes to speed up performance.
- A button to the experimental `v2`-layout to go back to `v1`.

### Changed

- New login/register screens
- New CSP headers

### Deprecated

- Dropped all old v3 code.
- Dropped all generated JS and CSS, thanks @paulius-valiunas!

### Fixed

- [Discussion 8569](https://github.com/orgs/firefly-iii/discussions/8569) (What is classed as an "automatic transaction" when it comes to notifications?) started by @digitlength
- [Issue 8608](https://github.com/firefly-iii/firefly-iii/issues/8608) (404 error when deleting a category) reported by @Jademalo
- [Issue 8616](https://github.com/firefly-iii/firefly-iii/issues/8616) (Create right now option for recurring transaction missing during weekend) reported by @Transportman
- [PR 8634](https://github.com/firefly-iii/firefly-iii/pull/8634) ([trivial] fix broken link in readme) reported by @WardenJakx
- [Issue 8632](https://github.com/firefly-iii/firefly-iii/issues/8632) (No search results returned when using `tag_contains` and `tag_starts`) reported by @Call-Me-G-Now
- [Issue 8663](https://github.com/firefly-iii/firefly-iii/issues/8663) (Graph error on Reports) reported by @nicolopozzato
- [Issue 8671](https://github.com/firefly-iii/firefly-iii/issues/8671) (Rule with -has_any_category:true trigger not triggering) reported by @pvieira84
- [Issue 8672](https://github.com/firefly-iii/firefly-iii/issues/8672) (Can't remove foreign amount using the trash icon) reported by @danielnetop
- [Issue 8668](https://github.com/firefly-iii/firefly-iii/issues/8668) (Not possible to upload CSV file as an attachment) reported by @dbtdsilva

### Removed

- Support for Mandrill because the necessary packages aren't maintained anymore.

## 6.1.10 - 2024-03-03

### Added

- Add missing translations for rule page.

### Changed

- The update checker can also deal with development releases
- Rule actions no longer complain when the category is already set

### Removed

- Unused translation on budget page

### Fixed

- [Issue 8521](https://github.com/firefly-iii/firefly-iii/issues/8521) (Total buget bar is missing when using SQLite) reported by @matlink
- [Issue 8544](https://github.com/firefly-iii/firefly-iii/issues/8544) (Recurring transaction calendar preview is not working properly) reported by @Maxco10 
- [Issue 8555](https://github.com/firefly-iii/firefly-iii/issues/8555) (Has no budget becomes has no category) reported by @Weiming-Hu 
- [Discussion 8557](https://github.com/orgs/firefly-iii/discussions/8557) ("Delete ALL your transactions" also removes all asset opening balance information) started by @digitlength
- [Issue 8575](https://github.com/firefly-iii/firefly-iii/issues/8575) (Creating rule from bill no longer pre-fills triggers and actions) reported by @jpelgrom
- [Issue 8578](https://github.com/firefly-iii/firefly-iii/issues/8578) (Display Bug: foreign currency is red & negative in deposits) reported by @dreautall
- Errors in incoming webhook URLs are properly caught

### Security

- Improved Host header validation to prevent a potential attack, reported by Raqib Iskenderli 

## 6.1.9 - 2024-02-06

### Fixed

- [Issue 8499](https://github.com/firefly-iii/firefly-iii/issues/8499) (Wrong version number after update to v6.1.8) reported by @memo-567
- [Issue 8501](https://github.com/firefly-iii/firefly-iii/issues/8501) (Bulk delete page links to wrong tx) reported by @likuilin

## 6.1.8 - 2024-02-04

### Added

- Added a trigger for v2 layouts that helps with debugging.
- [Issue 8431](https://github.com/firefly-iii/firefly-iii/issues/8431) (The Opening/ Virtual Balance must less than or equal 100001709) reported by @binhtran1604
- [Issue 8457](https://github.com/firefly-iii/firefly-iii/issues/8457) (Budgets - missing summary from the bottom) reported by @g7xtr

### Removed

- Reference to the "huntr" bug bounty platform, which is now some shitty AI scam.

### Fixed

- [PR 8432](https://github.com/firefly-iii/firefly-iii/pull/8432) (Update favicons.twig) reported by @stevietv
- [Issue 8433](https://github.com/firefly-iii/firefly-iii/issues/8433) (may be a wrong calculation) reported by @PterX
- [Issue 8442](https://github.com/firefly-iii/firefly-iii/issues/8442) (v6.1.7 - Not expected this period) reported by @poudenes
- [Discussion 8445](https://github.com/orgs/firefly-iii/discussions/8445) (Offering to Contribute to Firefly Documentation) reported by @poupouproject
- [Issue 8446](https://github.com/firefly-iii/firefly-iii/issues/8446) (There is an extra X ending symbol here) reported by @PterX
- [Issue 8467](https://github.com/firefly-iii/firefly-iii/issues/8467) (API Endpoint /data/export/rules produces errorneous CSV output) reported by @not1q84-1
- [Issue 8472](https://github.com/firefly-iii/firefly-iii/issues/8472) (When left to spend is 0, the info box is red) reported by @nicosomb
- [Issue 8471](https://github.com/firefly-iii/firefly-iii/issues/8471) (Left to spend is not the same on dashboard and on budget page) reported by @nicosomb
- [PR 8477](https://github.com/firefly-iii/firefly-iii/pull/8477) (Bump actions/checkout from 3 to 4) reported by @dependabot[bot]
- [Issue 8497](https://github.com/firefly-iii/firefly-iii/issues/8497) (has_any_category:false not possible as a rule) reported by @shrippen

### Security

- [GHSA-29w6-c52g-m8jc](https://github.com/firefly-iii/firefly-iii/security/advisories/GHSA-29w6-c52g-m8jc) Demo users could trick each other into downloading poisoned CSV files, reported by @red5us

## 6.1.7 - 2024-01-21

### Added

- Layout `v2` has some new features
- [Issue 8369](https://github.com/firefly-iii/firefly-iii/issues/8369) (Additional reconcile link) reported by @chevdor

### Fixed

- [Issue 8352](https://github.com/firefly-iii/firefly-iii/issues/8352) (Modifying the direction of a transfer between liabilities yields no effect) reported by @Ezwen
- [PR 8370](https://github.com/firefly-iii/firefly-iii/pull/8370) (Fix various typos) reported by @luzpaz
- [Issue 8377](https://github.com/firefly-iii/firefly-iii/issues/8377) (Query on multiple tags returns duplicates) reported by @chevdor
- [Issue 8374](https://github.com/firefly-iii/firefly-iii/issues/8374) (Error Graph Income vs. expenses Reports page) reported by @nicolopozzato
- [Issue 8390](https://github.com/firefly-iii/firefly-iii/issues/8390) (Rule with destination_account_is 'not' is never returning a result.) reported by @EricVanCaenenberghe
- [Issue 8349](https://github.com/firefly-iii/firefly-iii/issues/8349) (Currencies not saving correctly) reported by @r1bas4
- [Issue 8418](https://github.com/firefly-iii/firefly-iii/issues/8418) (Unable to create rule with trigger having type has_no_budget via the API ) reported by @tailg8nj
- [Issue 8425](https://github.com/firefly-iii/firefly-iii/issues/8425) (Error from the net-worth endpoint with  `Trailing data`.) reported by @chevdor
- [Issue 8427](https://github.com/firefly-iii/firefly-iii/issues/8427) (Broken batch application of non-strict rules with triggers with stop processing) reported by @alexschlueter
- Various Carbon `createFromFormat` issues fixed.

## 6.1.6 - 2024-01-07

### Fixed

- Type validation error

## 6.1.5 - 2024-01-07

### Added

- More audit logs
- Sanity check in date ranges
- More uniform length and size validations

### Changed

- Slightly changed text, thanks @maureenferreira!

### Fixed

- [Issue 8328](https://github.com/firefly-iii/firefly-iii/issues/8328) Some extra fixes for non-zero foreign amounts
- Updated links in `.env.example`, thanks @lemuelroberto!

## 6.1.4 - 2024-01-03

### Fixed

- [Issue 8328](https://github.com/firefly-iii/firefly-iii/issues/8328) Asking for non-zero foreign amount despite not being used

## 6.1.3 - 2024-01-03

### Fixed

- [Issue 8326](https://github.com/firefly-iii/firefly-iii/issues/8326) Asking for non-zero foreign amount despite not being used

## 6.1.2 - 2024-01-03

### Changed

- [Issue 8304](https://github.com/firefly-iii/firefly-iii/issues/8304) Several issues with searching for and displaying of tag-related transactions

### Removed

- Double reference to webhooks in the menu

### Fixed

- [Issue 8297](https://github.com/firefly-iii/firefly-iii/issues/8297) Division by zero
- [Issue 8320](https://github.com/firefly-iii/firefly-iii/issues/8320) nullpointer in new layout
- [Issue 8321](https://github.com/firefly-iii/firefly-iii/issues/8321) Networth checkbox for expense and revenue accounts removed
- Long date ranges will throw an error
-
- Max sizes and reasonable limits for most numbers and strings
- Links in readme to documentation.

### Security

- Webhooks now properly disabled in the UI.
- [Issue 8322](https://github.com/firefly-iii/firefly-iii/issues/8322) Duplicate detection did not distinguish between users

## 6.1.1 - 2023-12-27

### Changed

- Rule overview is lower in height.

### Removed

- Removed fixed sidebar again

### Fixed

- Nullpointer in rule trigger render code
- [Issue 8272](https://github.com/firefly-iii/firefly-iii/issues/8272) The sum for expected bills in a group includes unexpected bills as well
- [Issue 8273](https://github.com/firefly-iii/firefly-iii/issues/8273) Frontpage preferences indicate all accounts are shown on the frontpage, even when not true
- [Issue 8274](https://github.com/firefly-iii/firefly-iii/issues/8274) Semi specific dates do not work correctly with the "Transaction date is.." rule trigger
- [Issue 8277](https://github.com/firefly-iii/firefly-iii/issues/8277) Expected bill next month, but shown as not expected
- [Issue 8278](https://github.com/firefly-iii/firefly-iii/issues/8278) Net worth is empty in the dashboard due to division by zero
- [Issue 8281](https://github.com/firefly-iii/firefly-iii/issues/8281) Database CPU utilization after v6.1.0 upgrade
- [Issue 8291](https://github.com/firefly-iii/firefly-iii/issues/8291) Multiple "Any tag is" (negated or not) rule triggers don't all apply in strict mode

### Security

- HTML Injection Vulnerability in webhooks code, discovered by @stefan-schiller-sonarsource from Sonar. Thanks!

### API

- [Issue 8282](https://github.com/firefly-iii/firefly-iii/issues/8282) Update transaction via API does not update the "updated_at" parameter

## 6.1.0 - 2023-12-17

> ⚠️⚠️ This release required **PHP 8.3.0** and will not work on earlier releases of PHP ⚠️⚠️

### Added

- [Issue 7571](https://github.com/firefly-iii/firefly-iii/issues/7571) More tag search options
- [Issue 7781](https://github.com/firefly-iii/firefly-iii/issues/7781) Nice wrapper script for artisan commands
- UI also supports time for transactions

### Changed

- ⚠️⚠️ Requires PHP8.3 ⚠️⚠️
- [Issue 8148](https://github.com/firefly-iii/firefly-iii/issues/8148) Slovenian language updates
- [Issue 8023](https://github.com/firefly-iii/firefly-iii/issues/8023) Top bar is now fixed in place
- Completely rewrote the documentation.

### Deprecated

- ⚠️⚠️ Removed support for PHP 8.2 ⚠️⚠️

### Fixed

- [Issue 8106](https://github.com/firefly-iii/firefly-iii/issues/8106) [issue 8195](https://github.com/firefly-iii/firefly-iii/issues/8195) [issue 8163](https://github.com/firefly-iii/firefly-iii/issues/8163) Various changes and fixes to bill date calculation
- [Issue 8137](https://github.com/firefly-iii/firefly-iii/issues/8137) Fix uneven amount error from cron job
- [Issue 8192](https://github.com/firefly-iii/firefly-iii/issues/8192) No matching transactions found.Rule with trigger NOT Transaction is reconciled returns
- [Issue 8207](https://github.com/firefly-iii/firefly-iii/issues/8207) Broken links, thanks @Maxco10!
- [Issue 8138](https://github.com/firefly-iii/firefly-iii/issues/8138) Reconciled transactions can't be "store(d) as new"
- [Issue 7716](https://github.com/firefly-iii/firefly-iii/issues/7716) Removed bar in budget overview
- [Issue 8251](https://github.com/firefly-iii/firefly-iii/issues/8251) Removing a budget would not remove available budget

### API

- [Issue 8022](https://github.com/firefly-iii/firefly-iii/issues/8022) API chart expansions
- [Issue 8106](https://github.com/firefly-iii/firefly-iii/issues/8106) API reports empty string instead of NULL

## 6.0.30 - 2023-10-29

### Fixed

- Missing method after refactoring.

## 6.0.29 - 2023-10-29

### Fixed

- Null pointer in bill overview

## 6.0.28 - 2023-10-29

### Added

- [Issue 8076](https://github.com/firefly-iii/firefly-iii/issues/8076) Added a "Clone and edit"-button
- [Issue 7204](https://github.com/firefly-iii/firefly-iii/issues/7204) Added the ability to customize the URL protocol types Firefly III accepts
- [Issue 8098](https://github.com/firefly-iii/firefly-iii/issues/8098) More tests in the navigation class, thanks @tonicospinelli!

### Changed

- Refactored the Actions of GitHub
- The transaction currencies are now linked to users, and can be enabled per user
- A few upgrade commands are refactored
- You can no longer edit vital parts of reconciled transactions

### Deprecated

- Remove old v3 layout.

### Fixed

- Bad math in the order of piggy banks
- [Issue 8084](https://github.com/firefly-iii/firefly-iii/issues/8084) @JoSchrader fixed an issue with a duplicate button
- [Issue 8103](https://github.com/firefly-iii/firefly-iii/issues/8103) Bulk edit would not accept transaction descriptions longer than 255 characters
- [Issue 8099](https://github.com/firefly-iii/firefly-iii/issues/8099) The bill index would never skip the number of periods you indicated
- [Issue 8069](https://github.com/firefly-iii/firefly-iii/issues/8069) Rule descriptions would always "1" as description. Thanks @Maxco10!

### API

- API will no longer accept changes to amount and account fields for reconciled transactions

## v6.0.27 - 2023-10-16

### Added

- [Issue 8004](https://github.com/firefly-iii/firefly-iii/issues/8004) Warning in entrypoint script for missing variables.

### Changed

- Experimental database validation command.
- Add some values to the debug form.
- Better debug logs at various places

### Fixed

- [Issue 8020](https://github.com/firefly-iii/firefly-iii/issues/8020), [issue 8028](https://github.com/firefly-iii/firefly-iii/issues/8028) Liability calculation edge case found by @tieu1991
- [Issue 7655](https://github.com/firefly-iii/firefly-iii/issues/7655), [issue 8026](https://github.com/firefly-iii/firefly-iii/issues/8026) Bill date calculation edge case found by @devfaz
- [Issue 8051](https://github.com/firefly-iii/firefly-iii/issues/8051) Null pointer when deleting account
- [Issue 8041](https://github.com/firefly-iii/firefly-iii/issues/8041) Confusing chart is no longer confusing
- [Issue 8050](https://github.com/firefly-iii/firefly-iii/issues/8050) Path is normal for page 2.
- [Issue 8057](https://github.com/firefly-iii/firefly-iii/issues/8057) negative query parameters are handled correctly.

### API (v2.0.10)

- All endpoints (v1 and v2) should now respect the `?limit=` param.

## 6.0.26 - 2023-09-24

### Fixed

- [Issue 7986](https://github.com/firefly-iii/firefly-iii/issues/7986), [issue 7992](https://github.com/firefly-iii/firefly-iii/issues/7992) Fix exception when calling specific end points
- [Issue 7990](https://github.com/firefly-iii/firefly-iii/issues/7990) Remove unused translations

## 6.0.25 - 2023-09-24

### Changed

- v2 index has better overview of bills (now called subscriptions)

### Deprecated

- My attempt to build the `v3`-layout is now officially deprecated and removed. To see the new layout (in beta), use `FIREFLY_III_LAYOUT=v2`.

### Fixed

- [Issue 7970](https://github.com/firefly-iii/firefly-iii/issues/7970) Bad redirect for mass edit/delete forms.
- [Issue 7983](https://github.com/firefly-iii/firefly-iii/issues/7983) Bad math in the calculation of liabilities
- [Issue 7973](https://github.com/firefly-iii/firefly-iii/issues/7973) Bad account validation broke certain imports
- [Issue 7981](https://github.com/firefly-iii/firefly-iii/issues/7981) Menu had a bad link, thanks @Maxco10!
- Slack alerts now use the correct URL
- Better htaccess files thanks to Softaculous.

### Security

- Change htaccess rules so certain files can't be accessed.

### API

- [Issue 7972](https://github.com/firefly-iii/firefly-iii/issues/7972) The API needed start
  *and* end parameters for transactions, this is no longer the case.
- New APIs for user group and rights management. Not yet documented.

## 6.0.24 - 2023-09-16

### Fixed

- [Issue 7920](https://github.com/firefly-iii/firefly-iii/issues/7920) Issues with automatic budgets
- [Issue 7940](https://github.com/firefly-iii/firefly-iii/issues/7940) Edge cases in the data import routine
- [Issue 7963](https://github.com/firefly-iii/firefly-iii/issues/7963) Fix audit items for rules
- Fixed all issues with relative URLs (which I caused myself)

### API

- [Issue 7944](https://github.com/firefly-iii/firefly-iii/issues/7944) Make sure all IDs are strings in the API

## 6.0.23 - 2023-09-04

### Changed

- New debug information tables are in HTML

### Fixed

- Remove extra slashes from paths, breaking CSS

## 6.0.22 - 2023-09-02

### API

- [Issue 7917](https://github.com/firefly-iii/firefly-iii/issues/7917) Fixed an API issue where submitting an account name would not be accepted.

## 6.0.21 - 2023-09-02

### Added

- Rules will now report failures if a Slack/Discord notification channel is configured
- Notifications can be sent to Discord
- Beta layout `v2`, activate with `FIREFLY_III_LAYOUT=v2`

### Changed

- Audit log settings are changed, refer to the `.env.example`-file.
- Many URLs are new rendered as relative URLs.

### Fixed

- [Issue 7853](https://github.com/firefly-iii/firefly-iii/issues/7853) Left to spend on main page shows incorrect value
- [Issue 7883](https://github.com/firefly-iii/firefly-iii/issues/7883) Missing translation
- [Issue 7910](https://github.com/firefly-iii/firefly-iii/issues/7910) Type format error
- Home page respects account order
- JS errors for users using Firefly III in a subdir.

### API

- Bumped to v2.0.6 but only so the docs match again.

## 6.0.20 - 2023-08-13

### Fixed

- [Issue 7787](https://github.com/firefly-iii/firefly-iii/issues/7787) Possible issue when deleting multiple tags from a transaction.
- [Issue 7792](https://github.com/firefly-iii/firefly-iii/issues/7792) Search for tags was broken in rules
- [Issue 7803](https://github.com/firefly-iii/firefly-iii/issues/7803) @zqye fixed an issue where the cron job would fire when not necessary.
- [Issue 7771](https://github.com/firefly-iii/firefly-iii/issues/7771) Unclear use of language in rule trigger
- [Issue 7818](https://github.com/firefly-iii/firefly-iii/issues/7818) Amount was negative instead of positive.
- [Issue 7810](https://github.com/firefly-iii/firefly-iii/issues/7810) Bad math
- Asset accounts will correctly show transaction groups

### API

- Lots of new, undocumented v2 API endpoints.
- [Issue 7845](https://github.com/firefly-iii/firefly-iii/issues/7845) Could not reconcile over API

## 6.0.19 - 2023-07-29

### Fixed

- [Issue 7577](https://github.com/firefly-iii/firefly-iii/issues/7577) Firefly III can't search for backward slashes in identifiers
- [Issue 7762](https://github.com/firefly-iii/firefly-iii/issues/7762) User can't create access token

## 6.0.18 - 2023-07-19

### Fixed

- Slack messages would fail if not configured.
- Bill display would include transactions from the previous period.
- Debug information left in bill overview.
- [Issue 7694](https://github.com/firefly-iii/firefly-iii/issues/7694) Missing CSS info in dark mode.
- [Issue 7706](https://github.com/firefly-iii/firefly-iii/issues/7706) Deleting a budget would not reset the available amount.
- [Issue 7749](https://github.com/firefly-iii/firefly-iii/issues/7749) Account overview would show just 1 transaction from a split of multiple.

## 6.0.17 - 2023-07-16

### Added

- New date calculation code and tests, thanks to @tonicospinelli!

### Removed

- Heroku support

### Fixed

- [Issue 7704](https://github.com/firefly-iii/firefly-iii/issues/7704) Date issues with bills
- Cache issue in budgets
- Fixed the account validation for transfer transactions

### API

- Various fields would not accept `null` values

## 6.0.16 - 2023-06-28

### Changed

- Better IBAN and account validation for new (API) transactions.

### Fixed

- Better transaction split validation in API.
- [Issue 7683](https://github.com/firefly-iii/firefly-iii/issues/7683) Date validation in recurring transaction form.
- [Issue 7686](https://github.com/firefly-iii/firefly-iii/issues/7686) Low contrast in dark mode, thanks @MateusBMP!
- [Issue 7655](https://github.com/firefly-iii/firefly-iii/issues/7655) Bad date display in bills

## 6.0.15 - 2023-06-22

### Fixed

- [Issue 7678](https://github.com/firefly-iii/firefly-iii/issues/7678) Missing argument in postgres maintenance code
  breaks startup.

## 6.0.14 - 2023-06-22

### Added

- Editing some fields will generate audit logs visible when you view a transaction. The number of fields monitored will
  increase over time

### Changed

- Account validation includes IBANs now, this helps the data importer
- Unified and cleaned up all command line output

### Fixed

- [Issue 7630](https://github.com/firefly-iii/firefly-iii/issues/7630) Errors when upgrading using SQLite
- [Issue 7642](https://github.com/firefly-iii/firefly-iii/issues/7642) nn_NO wasn't available for users
- [Issue 7609](https://github.com/firefly-iii/firefly-iii/issues/7609), [issue 7659](https://github.com/firefly-iii/firefly-iii/issues/7659)
  Rule execution form was broken
- [Issue 7677](https://github.com/firefly-iii/firefly-iii/issues/7677) Amount was negative instead of positive in view
- [Issue 7649](https://github.com/firefly-iii/firefly-iii/issues/7649) Bill edit screen would always suggest "daily"
  repeat frequency
- Nullpointer in bill repository class
- Missing param in rule action, thanks @timendum!
- Missing attachment overview in recurring transactions

## v6.0.13 - 2023-06-12

### Fixed

- [Issue 7641](https://github.com/firefly-iii/firefly-iii/issues/7641) Crash with AUTHENTICATION_GUARD=web

## v6.0.12 - 2023-06-12

### Changed

- Command output and logo on the terminal improved.

### Fixed

- [Issue 7557](https://github.com/firefly-iii/firefly-iii/issues/7557) `firefly-iii:upgrade-database` step issue
- [Issue 7572](https://github.com/firefly-iii/firefly-iii/issues/7572) Paid bill shown in wrong currency
- [Issue 7593](https://github.com/firefly-iii/firefly-iii/issues/7593) Fix URLs in .env.example, thanks @josephbadow
- [Issue 7620](https://github.com/firefly-iii/firefly-iii/issues/7620) Issues with light mode
- [Issue 7618](https://github.com/firefly-iii/firefly-iii/issues/7618) Can't log out when using remote auth
- [Issue 7613](https://github.com/firefly-iii/firefly-iii/issues/7613) Can't save piggy bank attachments

### API

- [Issue 7588](https://github.com/firefly-iii/firefly-iii/issues/7588) v1/recurrences not able to handle bills on
  store & update
- [Issue 7589](https://github.com/firefly-iii/firefly-iii/issues/7589) v1/recurrences fails when updating a split
  transaction

## v6.0.11 - 2023-05-28

### Added

- 🇰🇷 Korean translations!
- A new "adjusted" auto-budget type that will correct itself after
  spending. [Read more](https://docs.firefly-iii.org/xfirefly-iii/financial-concepts/organizing/#adjusted-and-correct-for-overspending)
- [Issue 6631](https://github.com/firefly-iii/firefly-iii/issues/6631) Can now link withdrawals and deposits to piggy
  banks, thanks @ChrisWin22!

### Changed

- "Balance" is now called "In + out this period" so it's more clear what it means.

### Removed

- Some superfluous logging.

### Fixed

- An intermittent issue came up where people would suffer from badly rounded numbers.
  The root cause has been fixed. Open a discussion if this affects you, a fix for your data is available.
- The API cron job would not run all available cron commands.
- Debug page would always report midnight
- [Issue 7514](https://github.com/firefly-iii/firefly-iii/issues/7514) DB error when upgrading to 6.0.10
- [Issue 7516](https://github.com/firefly-iii/firefly-iii/issues/7516) Webhook: wrong JSON transaction amount
- [Issue 7522](https://github.com/firefly-iii/firefly-iii/issues/7522) Time related events cause a timeout
- [Issue 7541](https://github.com/firefly-iii/firefly-iii/issues/7541) Login screen display glitch
- [Issue 7549](https://github.com/firefly-iii/firefly-iii/issues/7549) Account creation duplicate checking fails for
  German umlaut
- [Issue 7546](https://github.com/firefly-iii/firefly-iii/issues/7546) Version link doesn't work
- [Issue 7547](https://github.com/firefly-iii/firefly-iii/issues/7547) Rule fails to convert "Withdrawal from X to Y"
  to "Transfer from Y to X"

### API

- [Issue 7505](https://github.com/firefly-iii/firefly-iii/issues/7505) Several API schema dates have been fixed, thanks
  @nagyv!

## v6.0.10 - 2023-05-14

### Added

- The debug screen will also report on the build version of the BASE image.

### Changed

- Health check will also check if the database is up.
- [Issue 7461](https://github.com/firefly-iii/firefly-iii/issues/7461) MFA field will now autofocus, thanks @eandersons!

### Removed

- IBAN check no longer triggers on empty IBANs

### Fixed

- Account validation when you only submit an IBAN.
- [Issue 7478](https://github.com/firefly-iii/firefly-iii/issues/7478) [issue 7457](https://github.com/firefly-iii/firefly-iii/issues/7457)
  Various fixes in budget limit and available amount management.
- [Issue 7446](https://github.com/firefly-iii/firefly-iii/issues/7446) Bills "Next expected match" was incorrect
- [Issue 7456](https://github.com/firefly-iii/firefly-iii/issues/7456) Missing date calculation fields.
- [Issue 7448](https://github.com/firefly-iii/firefly-iii/issues/7448) [issue 7444](https://github.com/firefly-iii/firefly-iii/issues/7444)
  Dark mode bad CSS

## 6.0.9 - 2023-04-29

### Added

- Better length validation for text fields.

### Changed

- Better calculation of available budget

### Fixed

- [Issue 7377](https://github.com/firefly-iii/firefly-iii/issues/7377) Tag search was broken
- [Issue 7389](https://github.com/firefly-iii/firefly-iii/issues/7389) Bug in charts
- [Issue 7394](https://github.com/firefly-iii/firefly-iii/issues/7394) unique iban check was broken
- [Issue 7427](https://github.com/firefly-iii/firefly-iii/issues/7427) API would not accept page 18 and up.
- [Issue 7410](https://github.com/firefly-iii/firefly-iii/issues/7410) Various dark mode color fixes
- Old documentation links fixed by @mindlessroman and @noxonad!

## 6.0.8 - 2023-04-16

### Added

- [Issue 7351](https://github.com/firefly-iii/firefly-iii/issues/7351) Optional command to force the decimal size.
- [Issue 7352](https://github.com/firefly-iii/firefly-iii/issues/7352) Optional command to force the migrations.
- [Issue 7354](https://github.com/firefly-iii/firefly-iii/issues/7354) The new v3 layout will redirect to the index when
  unauthenticated, thanks @corcom!

### Fixed

- [Issue 7349](https://github.com/firefly-iii/firefly-iii/issues/7349) Missing tables in PostgreSQL script.
- [Issue 7358](https://github.com/firefly-iii/firefly-iii/issues/7358) Could not create liabilities with a pre-set
  amount.
- Fix date field in bill warning mail.
- Fix installer script.
- Remove attachment paperclip from transactions with deleted attachments.

### API

- [Issue 7347](https://github.com/firefly-iii/firefly-iii/issues/7347) API made rules would be inactive by default.

## v6.0.7 - 2023-04-09

### Added

- Lots of error catching in DB migrations for smoother upgrades.
- New command `firefly-iii:force-migration` which will force database migrations to run. It will probably also destroy
  your database so don't use it.
- You can now force light/dark mode in your settings.

### Fixed

- [Issue 7137](https://github.com/firefly-iii/firefly-iii/issues/7137) Inconsistent rule test form
- [Issue 7320](https://github.com/firefly-iii/firefly-iii/issues/7320) Standard email values so less errors
- [Issue 7311](https://github.com/firefly-iii/firefly-iii/issues/7311) Fix issue with date validation
- [Issue 7310](https://github.com/firefly-iii/firefly-iii/issues/7310) Better color contrast in dark mode.

### API

- [Issue 7308](https://github.com/firefly-iii/firefly-iii/issues/7308) Could not set current amount for certain piggy
  banks

## v6.0.6 - 2023-04-02

### Changed

- Database migrations are capped at 12 decimals.
- Currency processing is capped at 12 decimals.
- Mail errors no longer crash the app but report the error in logs
- Disabled Sonarcloud runs

### Fixed

- "Change transaction type"-rule actions would create a new expense account instead of finding a liability.
- New users from remote user repositories would not be able to create new asset accounts.
- Firefly III would create "Loan" instead of "Expense account" when faced with unknown accounts during API calls.
- Icons would not show up in the minimized left-hand menu.
- Contrast for dark mode improved.
- Better credit calculation for liabilities in case of complex transactions.

### API

- Fixed: Could not give piggy bank an unlimited amount.
- [Issue 7335](https://github.com/firefly-iii/firefly-iii/issues/7335) Fix upload of attachments, thanks @fengkaijia

## v6.0.5 - 2023-03-19

### Changed

- Mathematical accuracy is set to 12 decimals. This is accurate enough for most currencies and prevents rounding issues
  for systems that don't support more.

### Fixed

- [Issue 7227](https://github.com/firefly-iii/firefly-iii/issues/7227) Could not set webhooks to the correct trigger.
- [Issue 7221](https://github.com/firefly-iii/firefly-iii/issues/7221) Could not see the result of a rule test.

## v6.0.4 - 2023-03-13

### Fixed

- [Issue 7214](https://github.com/firefly-iii/firefly-iii/issues/7214) Import issue blocking multi currency transactions

## v6.0.3 - 2023-03-13

### Fixed

- [Issue 7201](https://github.com/firefly-iii/firefly-iii/issues/7201) Security-related console automatically command
  runs before a database is set, and may error out.

## v6.0.2 - 2023-03-11

### Fixed

- [Issue 7186](https://github.com/firefly-iii/firefly-iii/issues/7186) Fix broken date range
- [Issue 7188](https://github.com/firefly-iii/firefly-iii/issues/7188) Fix broken search
- [Issue 7189](https://github.com/firefly-iii/firefly-iii/issues/7189) Too strict account validation
- [Issue 7142](https://github.com/firefly-iii/firefly-iii/issues/7142) Better contrast in dark mode

## 6.0.1 - 2023-03-11

### Changed

- [Issue 7129](https://github.com/firefly-iii/firefly-iii/issues/7129) Catch common email errors as log errors.

### Fixed

- [Issue 7109](https://github.com/firefly-iii/firefly-iii/issues/7109) Fix CSS in subdirectories, thanks @GaneshKandu
- [Issue 7112](https://github.com/firefly-iii/firefly-iii/issues/7112) Version number parsing
- [Issue 6985](https://github.com/firefly-iii/firefly-iii/issues/6985) Mandrill mail support
- [Issue 7131](https://github.com/firefly-iii/firefly-iii/issues/7131) Fix account sorting, thanks @lflare
- [Issue 7130](https://github.com/firefly-iii/firefly-iii/issues/7130) Fix missing date range parsers
- [Issue 7156](https://github.com/firefly-iii/firefly-iii/issues/7156) Default values for email settings break tokens
- [Issue 7140](https://github.com/firefly-iii/firefly-iii/issues/7140) Header with charset would break API validation
- [Issue 7144](https://github.com/firefly-iii/firefly-iii/issues/7144) Debug page could not handle missing log files
- [Issue 7159](https://github.com/firefly-iii/firefly-iii/issues/7159) Bad parsing in success messages
- [Issue 7104](https://github.com/firefly-iii/firefly-iii/issues/7104) Missing colors in dark mode
- [Issue 7120](https://github.com/firefly-iii/firefly-iii/issues/7120) Missing borders in dark mode
- [Issue 7156](https://github.com/firefly-iii/firefly-iii/issues/7156) Bad HTML parsing in transaction form
- [Issue 7166](https://github.com/firefly-iii/firefly-iii/issues/7166) Rule trigger would trigger on the wrong
  transaction set
- [Issue 7112](https://github.com/firefly-iii/firefly-iii/issues/7112) Content filter would strip emojis
- [Issue 7175](https://github.com/firefly-iii/firefly-iii/issues/7175) Could not delete user invite
- [Issue 7177](https://github.com/firefly-iii/firefly-iii/issues/7177) Missing currency info would break cron job

### API

- [Issue 7127](https://github.com/firefly-iii/firefly-iii/issues/7127) Expand API with new option for "destroy" button.
- [Issue 7124](https://github.com/firefly-iii/firefly-iii/issues/7124) API would not break on missing foreign currency
  information

## 6.0.0 - 2023-03-03

This is release

**6.0.0** of Firefly III.

### Warnings

- The upgrade may not be backwards compatible for people who manage outgoing loans (money borrowed to other people).
  Transactions will be removed or changed. See [this Gist](https://gist.github.com/JC5/909385c5086f9e07ba2c32e047446d68)
  for more information.
- You will need to make a backup of your database.
- You must use PHP 8.2 or use the Docker container.

### Notes

- The new
    *
  *v3
  ** layout is not yet finished, and it should
    *
  *not
  ** be used to edit or add data.

### Added

- Introduce Jetbrains Qodana code scanning for code quality.
- Reintroduced PHPUnit tests
- Added a warning for people using the "remote user guard" in combination with Personal Access Tokens.
- Improved validation across the board.
- First code to validate a user's financial administration
- Dark mode CSS
- New language: Catalan
- "Working" beta of the new layout under `/v3/`
- There is a page for webhooks.
- [Issue 4975](https://github.com/firefly-iii/firefly-iii/issues/4975) Rules can copy/move description to notes and vice
  versa
- [Issue 5031](https://github.com/firefly-iii/firefly-iii/issues/5031) You can invite users to your installation when
  registration is off
- [Issue 5213](https://github.com/firefly-iii/firefly-iii/issues/5213) You can trigger recurring transactions beforehand
- [Issue 5592](https://github.com/firefly-iii/firefly-iii/issues/5592) Transactions have a little history box to show
  how rules changed them
- [Issue 5752](https://github.com/firefly-iii/firefly-iii/issues/5752) Firefly III can send Slack notifications instead
  of emails
- [Issue 5862](https://github.com/firefly-iii/firefly-iii/issues/5862) Search can filter on reconciled transactions
- [Issue 6086](https://github.com/firefly-iii/firefly-iii/issues/6086) All search filters can be negative by putting `-`
  in front of them
- [Issue 6441](https://github.com/firefly-iii/firefly-iii/issues/6441) Buttons to purge deleted data, which is easy for
  data imports
- [Issue 6457](https://github.com/firefly-iii/firefly-iii/issues/6457) Rule trigger 'transaction exists', that will
  always trigger
- [Issue 6526](https://github.com/firefly-iii/firefly-iii/issues/6526) Option to disable rules and/or webhooks when
  saving transactions
- [Issue 6605](https://github.com/firefly-iii/firefly-iii/issues/6605) You can search for external ID values

### Changed

- Completely rewritten documentation at https://docs.firefly-iii.org
- Bad escape in JS code has been fixed.
- Added date validation in routes for better script kiddie protection
- Shorter titles in object groups
- Piggy bank actions are created correctly
- Some bad spelling in a header check
- Various errors no longer throw a 500 but a 422 (validation failed)
- The translations now have a warning in the comments so people don't submit translations.
- [Issue 6824](https://github.com/firefly-iii/firefly-iii/issues/6824) Fix issue with bills.
- [Issue 6828](https://github.com/firefly-iii/firefly-iii/issues/6828) Catch bad number in API
- [Issue 6829](https://github.com/firefly-iii/firefly-iii/issues/6829) Better error catching in API
- [Issue 6831](https://github.com/firefly-iii/firefly-iii/issues/6831) TypeError when using remote authentication
- [Issue 6834](https://github.com/firefly-iii/firefly-iii/issues/6834) Will use IBAN in account names if account exists
  already with a different IBAN
- [Issue 6842](https://github.com/firefly-iii/firefly-iii/issues/6842) Switch from expense to revenue when importing
  data.
- [Issue 6855](https://github.com/firefly-iii/firefly-iii/issues/6855) Do not validate currency if currency is NULL,
  thanks @eps90!
- [Issue 6869](https://github.com/firefly-iii/firefly-iii/issues/6869) Liability created via API is not applying opening
  balance.
- [Issue 6870](https://github.com/firefly-iii/firefly-iii/issues/6870) Old inactive recurring transactions do not lose
  categories when the categories are deleted
- [Issue 6974](https://github.com/firefly-iii/firefly-iii/issues/6974) Auto budget amount fix.
- [Issue 6876](https://github.com/firefly-iii/firefly-iii/issues/6876) Date field is validated in recurring transactions
- [Issue 6581](https://github.com/firefly-iii/firefly-iii/issues/6581) Fields were not cleared in the transaction screen
  in some cases

### Fixed

- [Issue 7079](https://github.com/firefly-iii/firefly-iii/issues/7079) Bad date range in chart

### Removed

- [Issue 4198](https://github.com/firefly-iii/firefly-iii/issues/4198) The total available budget amount bar on
  the `/budgets` page is no longer manageable but will be auto-calculated
- Cryptocurrencies in default currency set
- Unused environment variables

### API

- URLs with underscores in them have been updated to use dashes instead (`piggy_banks` -> `piggy-banks`).
- [Issue 6130](https://github.com/firefly-iii/firefly-iii/issues/6130) You can now create a reconciliation transaction
- New `v2` endpoints, see new documentation at https://api-docs.firefly-iii.org
- Various API fixes

### Security

- [Issue 6826](https://github.com/firefly-iii/firefly-iii/issues/6826) Hide 2FA information when printing, thanks
  @jstebenne!
- Blocked users can access API, and users can unblock themselves using the API. This was reported in CVE-2023-0298.
- Several other low-key fixes.

## 6.0.0-beta.2 - 2023-02-20

### Warnings

- ⚠️ Make a backup of your database first!
- ⚠️ This version requires
    *
  *PHP
  8.2
  **.

You can access the new V3 layout under `/v3/`. If you decide to use or test it:

- ⚠️ Read the instructions under the ☠️ icon FIRST.
- ⚠️ The new layout is not yet finished. Use it to change your data at your own risk.

### Added

- Add max upload to debug page.

### Fixed

- Missing indexes in bill overview.
- Various dark mode fixes

### Security

- Bad escape in transaction currencies could cause XSS attacks.

### API

- All v1 and v2 routes checked and documented properly.

## 6.0.0-beta.1 - 2023-02-12

### Warnings

- ⚠️ Make a backup of your database first!
- ⚠️ This version requires
    *
  *PHP
  8.2
  **.

You can access the new V3 layout under `/v3/`. If you decide to use or test it:

- ⚠️ Read the instructions under the ☠️ icon FIRST.
- ⚠️ The new layout is not yet finished. Use it to change your data at your own risk.

### Added

- Introduce Jetbrains Qodana code scanning
- Reintroduced test framework

### Fixed

- [Issue 6834](https://github.com/firefly-iii/firefly-iii/issues/6834) Better check on IBANs
- Various small bugs

## 6.0.0-alpha.2 - 2023-02-05

### Warnings

- ⚠️ Make a backup of your database first!
- ⚠️ This version requires
    *
  *PHP
  8.2
  **.

You can access the new V3 layout under `/v3/`. If you decide to use or test it:

- ⚠️ Read the instructions under the ☠️ icon FIRST.
- ⚠️ The new layout is not yet finished. Use it to change your data at your own risk.

### Added

- Warning for remote user guard
- Improved validation
- Can now validate a user's financial administration
- Dark mode CSS

### Changed

- Various errors no longer throw a 500 but a 422 (validation failed)

### Removed

- Cryptocurrencies in default currency set
- Unused environment variables

### Fixed

- Bad escape in JS code.
- [Issue 6869](https://github.com/firefly-iii/firefly-iii/issues/6869) Liability created via API is not applying opening
  balance.
- [Issue 6870](https://github.com/firefly-iii/firefly-iii/issues/6870) Old inactive recurring transactions do not lose
  categories when the categories are deleted
- [Issue 6876](https://github.com/firefly-iii/firefly-iii/issues/6876) Date field is validated in recurring transactions
- [Issue 6974](https://github.com/firefly-iii/firefly-iii/issues/6974) Auto budget amount fix.
- Date validation in routes
- Shorter titles in object groups

### API

- Various API fixes

## 6.0.0-alpha.1 - 2023-01-16

This is the first release of the new 6.0.0 series of Firefly III. It should upgrade the database automatically BUT
please make a backup of your database first! I guarantee nothing.

This release was previously tagged "5.8.0" but due to backwards incompatible changes in liability management and a
destructive upgrade process, this is now "6.0.0". It will again be alpha.1.

### Warnings

- ⚠️ Make a backup of your database first!
- ⚠️ This version requires
    *
  *PHP
  8.2
  **.

You can access the new V3 layout under `/v3/`. If you decide to use or test it:

- ⚠️ Read the instructions under the ☠️ icon FIRST.
- ⚠️ The new layout is not yet finished. Use it to change your data at your own risk.

### Added

- ⚠️ Upgrade code for liability management. See
  also [this Gist](https://gist.github.com/JC5/909385c5086f9e07ba2c32e047446d68).
- New language: Catalan!

### Changed

- The translations now have a warning in the comments so people don't submit translations.

### Fixed

- [Issue 6824](https://github.com/firefly-iii/firefly-iii/issues/6824) Fix issue with bills.
- [Issue 6828](https://github.com/firefly-iii/firefly-iii/issues/6828) Catch bad number in API
- [Issue 6829](https://github.com/firefly-iii/firefly-iii/issues/6829) Better error catching in API
- [Issue 6831](https://github.com/firefly-iii/firefly-iii/issues/6831) TypeError when using remote authentication
- [Issue 6834](https://github.com/firefly-iii/firefly-iii/issues/6834) Will use IBAN in account names if account exists
  already with a different IBAN
- [Issue 6842](https://github.com/firefly-iii/firefly-iii/issues/6842) Switch from expense to revenue when importing
  data.
- [Issue 6855](https://github.com/firefly-iii/firefly-iii/issues/6855) Do not validate currency if currency is NULL,
  thanks @eps90!
- Piggy bank actions are created correctly
- Bad spelling in header check

### Security

- [Issue 6826](https://github.com/firefly-iii/firefly-iii/issues/6826) Hide 2FA information when printing, thanks
  @jstebenne!
- Blocked users can access API, and users can unblock themselves using the API. This was reported in CVE-2023-0298.

## 5.8.0-alpha.1 - 2023-01-08

This is the first release of the new 5.8.0 series of Firefly III. It should upgrade the database automatically BUT
make a backup of your database first! I guarantee nothing.

### Warnings

- ⚠️ Make a backup of your database first!
- ⚠️ This version requires
    *
  *PHP
  8.2
  **.

You can access the new V3 layout under `/v3/`. If you decide to use or test it:

- ⚠️ Read the instructions under the ☠️ icon FIRST.
- ⚠️ The new layout is not yet finished. Use it to change your data at your own risk.

### Added

Lots of new stuff that I invite you to test and break.

- [Issue 4975](https://github.com/firefly-iii/firefly-iii/issues/4975) Rules can copy/move description to notes and vice
  versa
- [Issue 5031](https://github.com/firefly-iii/firefly-iii/issues/5031) You can invite users to your installation when
  registration is off
- [Issue 5213](https://github.com/firefly-iii/firefly-iii/issues/5213) You can trigger recurring transactions beforehand
- [Issue 5592](https://github.com/firefly-iii/firefly-iii/issues/5592) Transactions have a little history box to show
  how rules changed them
- [Issue 5752](https://github.com/firefly-iii/firefly-iii/issues/5752) Firefly III can send Slack notifications instead
  of emails
- [Issue 5862](https://github.com/firefly-iii/firefly-iii/issues/5862) Search can filter on reconciled transactions
- [Issue 6086](https://github.com/firefly-iii/firefly-iii/issues/6086) All search filters can be negative by putting `-`
  in front of them
- [Issue 6441](https://github.com/firefly-iii/firefly-iii/issues/6441) Buttons to purge deleted data, which is easy for
  data imports
- [Issue 6457](https://github.com/firefly-iii/firefly-iii/issues/6457) Rule trigger 'transaction exists', that will
  always trigger
- [Issue 6526](https://github.com/firefly-iii/firefly-iii/issues/6526) Option to disable rules and/or webhooks when
  saving transactions
- [Issue 6605](https://github.com/firefly-iii/firefly-iii/issues/6605) You can search for external ID values
- Working beta of the new layout under `/v3/`
- New authentication screens that support dark mode.
- There is a page for webhooks.

### Changed

- Firefly III requires PHP 8.2
- Liabilities are no longer part of your net worth.
- Liabilities no longer need two transactions to be managed properly (see the documentation)

### Removed

- [Issue 4198](https://github.com/firefly-iii/firefly-iii/issues/4198) The total available budget amount bar on
  the `/budgets` page is no longer manageable but will be auto-calculated

### Fixed

Not many bugfixes (yet).

- [Issue 6581](https://github.com/firefly-iii/firefly-iii/issues/6581) Fields were not cleared in the transaction screen
  in some cases

### API

New `/v2/` endpoints are being implemented that prepare the application for (among other things) the ability
to manage multiple financial administrations. The documentation for these endpoints will be at
https://api-docs.firefly-iii.org/.

- [Issue 6130](https://github.com/firefly-iii/firefly-iii/issues/6130) You can now create a reconciliation transaction

## 5.7.18 - 2023-01-03

### Fixed

- [Issue 6775](https://github.com/firefly-iii/firefly-iii/issues/6775) OAuth authentication was broken for Authelia and
  other remote user providers.
- [Issue 6787](https://github.com/firefly-iii/firefly-iii/issues/6787) SQLite value conversion broke several functions

## 5.7.17 - 2022-12-30

### Fixed

- [Issue 6742](https://github.com/firefly-iii/firefly-iii/issues/6742) Error when a rule tries to add or remove an
  amount from a piggy bank
- [Issue 6743](https://github.com/firefly-iii/firefly-iii/issues/6743) Error when opening piggy bank overview
- [Issue 6753](https://github.com/firefly-iii/firefly-iii/issues/6753) Rules are not finding any transactions with
  trigger 'Amount is greater than 0'

## 5.7.16 - 2022-12-25

### Added

- You can now search for SEPA CT, thanks @dasJ!

### Changed

- Links go to [Mastodon](https://fosstodon.org/@ff3), not Twitter.
- Most if not all remaining float values removed. None were used in financial math.
- Expand Laravel Passport settings.

### Fixed

- [Issue 6597](https://github.com/firefly-iii/firefly-iii/issues/6597) Edit existing split transaction's source did not
  work properly.
- [Issue 6610](https://github.com/firefly-iii/firefly-iii/issues/6610) Fix search for attachments
- [Issue 6625](https://github.com/firefly-iii/firefly-iii/issues/6625) Page of the links is not displayed due to an
  error
- [Issue 6701](https://github.com/firefly-iii/firefly-iii/issues/6701) Ensure remote_guard_alt_email if changed, thanks
  @nebulade!
- Remove some null pointers in the code.
- Add missing locale data
- Fixed typo, thx @charlesteets!
- Various issues with piggy banks
- Clear cache after a transaction is deleted.
- Be more clear about registrations being disabled.

### Security

- Updated all packages and dependencies.

### API

- Fix API endpoint that would not accept two of the same dates.

## 5.7.15 - 2022-11-02

### Fixed

- You can no longer set the currency of expense and revenue accounts.
- Form elements are not spell checked anymore (privacy).
- [Issue 6556](https://github.com/firefly-iii/firefly-iii/issues/6556) Wrong value used in bill chart
- [Issue 6564](https://github.com/firefly-iii/firefly-iii/issues/6564) Right-Align numbers to match monetary value
  digits
- [Issue 6589](https://github.com/firefly-iii/firefly-iii/issues/6589) Webhook not fired after destroying transaction
- Add missing locale data

## 5.7.14 - 2022-10-19

### Fixed

- Bulk editing transactions works.
- Negative budgets no longer work.

## 5.7.13 - 2022-10-17

### Added

- [Issue 6502](https://github.com/firefly-iii/firefly-iii/issues/6502) A few students from @D7032E-Group-6 added MTD and
  YTD, thanks!

### Fixed

- [Issue 6461](https://github.com/firefly-iii/firefly-iii/issues/6461) Broken link in `/public` directory warning.
- [Issue 6475](https://github.com/firefly-iii/firefly-iii/issues/6475) Method name mixup.
- [Issue 6471](https://github.com/firefly-iii/firefly-iii/issues/6471) Fix float conversion
- [Issue 6510](https://github.com/firefly-iii/firefly-iii/issues/6510) Destroy transaction now also triggers liability
  recalculation.
- Amount check for budget amounts was too low.
- Some other small fixes

### API

- [Issue 6481](https://github.com/firefly-iii/firefly-iii/issues/6481) Mixup in API validation, fixed by @janw

## 5.7.12 - 2022-09-12

### Fixed

- [Issue 6287](https://github.com/firefly-iii/firefly-iii/issues/6287) Catch error when trying to email with invalid
  settings.
- [Issue 6423](https://github.com/firefly-iii/firefly-iii/issues/6423) Fix redis error, thanks @canoine!
- [Issue 6421](https://github.com/firefly-iii/firefly-iii/issues/6421) Fix issue with SQLite.
- [Issue 6379](https://github.com/firefly-iii/firefly-iii/issues/6379) Fix issue when user has lots of currencies but
  short list settings.
- [Issue 6333](https://github.com/firefly-iii/firefly-iii/issues/6333) Fix broken chart for reconciliation.
- [Issue 6332](https://github.com/firefly-iii/firefly-iii/issues/6332) Fix issue with uploading zipped PDF's.

## 5.7.11 - 2022-09-05

### Added

- [Issue 6254](https://github.com/firefly-iii/firefly-iii/issues/6254) Use Piggy Bank's start date in monthly suggestion
  by @rickdoesdev
- Add best practices badge.
- Various sanity checks on large amounts.

### Removed

- Service worker is removed.

### Fixed

- [Issue 6260](https://github.com/firefly-iii/firefly-iii/issues/6260)
- [Issue 6271](https://github.com/firefly-iii/firefly-iii/issues/6271) Improve settings for Redis, by @canoine
- [Issue 6283](https://github.com/firefly-iii/firefly-iii/issues/6283) Convert to deposit means the transaction loses
  its bill.
- Fix issue with foreign currencies in transaction form.
- Fix various issues with SQLite.
- [Issue 6379](https://github.com/firefly-iii/firefly-iii/issues/6379) Some foreign currencies not list for setting on
  new transactions
- Make 2FA code + validation more robust. Thanks to @jtmoss3991, @timaschew and @Ottega.

## 5.7.10 - 2022-07-16

### Fixed

- [Issue 6122](https://github.com/firefly-iii/firefly-iii/issues/6122) Type error on data import and display
- SQLite query issues fixed
- Fix nullpointer.
- [Issue 6168](https://github.com/firefly-iii/firefly-iii/issues/6168) Missing date overview in no-category list.
- [Issue 6165](https://github.com/firefly-iii/firefly-iii/issues/6165) Account numbers could not be shared between
  expense and revenue accounts.
- [Issue 6150](https://github.com/firefly-iii/firefly-iii/issues/6150) The first remote user would not get admin.
- [Issue 6118](https://github.com/firefly-iii/firefly-iii/issues/6118) Piggy bank events would not get copied when
  transaction was copied.

### Security

- Update packages

## 5.7.9 - 2022-06-01

### Fixed

- Symfony 6.1 requires PHP 8.1, so back to 6.0 for the time being.

## 5.7.8 - 2022-06-01

### Fixed

- Symfony 6.1 requires PHP 8.1, so back to 6.0 for the time being.

## 5.7.7 - 2022-06-01

### Fixed

- Fixed an issue where the login form would overflow a database field.
- [Issue 6113](https://github.com/firefly-iii/firefly-iii/issues/6113) Fix issue with number formatting.
- [Issue 5996](https://github.com/firefly-iii/firefly-iii/issues/5996) Catch bad library

### Added

- @turrisxyz added a dependency review, thanks!

## 5.7.6 - 2022-05-19

### Fixed

- [Issue 6058](https://github.com/firefly-iii/firefly-iii/issues/6058) Bad type-casting could break Firefly III on Home
  Assistant.
- [Issue 6059](https://github.com/firefly-iii/firefly-iii/issues/6059) Fix issue with missing list of bills when
  creating a recurring transaction from a transaction.
- Added missing DB integrity checks.

### Security

- Updated various packages

## 5.7.5 - 2022-05-06

### Fixed

- Fixed an issue where missing method names would break the API.
- [Issue 6040](https://github.com/firefly-iii/firefly-iii/issues/6040) Could not add or remove money from piggy banks
  without a target.
- [Issue 6009](https://github.com/firefly-iii/firefly-iii/issues/6009) `has_no_attachments:true` would not return
  transactions with
  *deleted* transactions.
- [Issue 6050](https://github.com/firefly-iii/firefly-iii/issues/6050) ja_JP is part of the Docker image

## 5.7.4 - 2022-05-03

### Fixed

- Fixed issue in method names.

## 5.7.3 - 2022-05-03

### Fixed

- Searching for `updated_at_before` and `created_at_before` works again.
- [Issue 6000](https://github.com/firefly-iii/firefly-iii/issues/6000) Bad math when dealing with multi-currency
  reconciliation.
- Remove unused CSS
- Fix bad migration.

### API

- Add error code to error message.

## 5.7.2 - 2022-04-13

### Fixed

- Not configuring email would break registration.
- Extra validation on piggy bank amounts.

## 5.7.1 - 2022-04-05

### Fixed

- Fixes an issue with showing piggy banks
- [Issue 5961](https://github.com/firefly-iii/firefly-iii/issues/5961) Fixes an issue registering new users

## 5.7.0 - 2022-04-04

- ⚠️ This release no longer supports LDAP.
- ⚠️ This is the last release that supports PHP 8.0
- 👍 Want to try the new v3 layout? At your own risk, browse to `/v3/`.

Please refer to the [documentation](https://docs.firefly-iii.org/xfirefly-iii/) and support channels if you run into
problems:

- [Gitter.im](https://gitter.im/firefly-iii/firefly-iii)
- [Twitter](https://twitter.com/Firefly_III/)
- [GitHub Issues](https://github.com/firefly-iii/firefly-iii/issues)
- [GitHub Discussions](https://github.com/firefly-iii/firefly-iii/discussions)

### Added

- Error email message now includes HTTP headers.
- [Issue 5373](https://github.com/firefly-iii/firefly-iii/issues/5373) You can give budgets notes, although they're not
  visible yet.
- [Issue 5648](https://github.com/firefly-iii/firefly-iii/issues/5648) The Docker image supports custom locales,
  see `.env.example` for instructions.
- [Issue 3984](https://github.com/firefly-iii/firefly-iii/issues/3984) [issue 5636](https://github.com/firefly-iii/firefly-iii/issues/5636) [issue 4903](https://github.com/firefly-iii/firefly-iii/issues/4903) [issue 5326](https://github.com/firefly-iii/firefly-iii/issues/5326)
  Lots of new search and rule operators. For the full list,
  see [search.php](https://github.com/firefly-iii/firefly-iii/blob/main/config/search.php) (a bit technical).
- [Issue 5269](https://github.com/firefly-iii/firefly-iii/issues/5269) It's possible to add piggy banks that have no
  explicit target amount goal.
- [Issue 4893](https://github.com/firefly-iii/firefly-iii/issues/4893) Bills can be given an end date and an extension
  date and will warn you about those dates.

### Changed

- [Issue 5757](https://github.com/firefly-iii/firefly-iii/issues/5757) Upgrade to Laravel 9.

### Deprecated

- [Issue 5911](https://github.com/firefly-iii/firefly-iii/issues/5911) Removed support for LDAP.

### Fixed

- [Issue 5810](https://github.com/firefly-iii/firefly-iii/issues/5810) Could not search for `no_notes:true` in some
  cases.
- [Issue 5869](https://github.com/firefly-iii/firefly-iii/issues/5869) Converting transactions would sometimes fail.
- [Issue 5870](https://github.com/firefly-iii/firefly-iii/issues/5870) Fixed broken link to instructions.
- [Issue 5903](https://github.com/firefly-iii/firefly-iii/issues/5903) API budget limits was broken due to upgraded
  package.
- [Issue 5852](https://github.com/firefly-iii/firefly-iii/issues/5852) It was not possible to recreate a currency.
- [Issue 5882](https://github.com/firefly-iii/firefly-iii/issues/5882) `no_external_url:true` was broken.
- [Issue 5770](https://github.com/firefly-iii/firefly-iii/issues/5770) Liabilities spent amount would be doubled.
- [Issue 4013](https://github.com/firefly-iii/firefly-iii/issues/4013) Date in email message was not localized.
- [Issue 5949](https://github.com/firefly-iii/firefly-iii/issues/5949) Deleting a transaction would sometimes send you
  back to a 404.

## x.x.x - 20xx-xx-xx

### Added

- Initial release.

### Changed

- Initial release.

### Deprecated

- Initial release.

### Removed

- Initial release.

### Fixed

- Initial release.

### Security

- Initial release.

### API

- Initial release.

# Full change log

Can be found here: https://docs.firefly-iii.org/references/firefly-iii/changelog/


