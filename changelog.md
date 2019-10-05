# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [4.8.1.2 (API 0.10.3)] - 2019-10-05

Firefly III v4.8.1.2 and onwards are licensed under the GNU Affero General 
Public License. This will not meaningfully change Firefly III. This 
particular license has some extra provisions that protect web-applications
such as this one. You can read the full license on the website of GNU.

https://www.gnu.org/licenses/agpl-3.0.html

### Added
- [Issue 2589](https://github.com/firefly-iii/firefly-iii/issues/2589) Can now search using `created_on:2019-10-22` and `updated_on:2019-10-22`.
- [Issue 2494](https://github.com/firefly-iii/firefly-iii/issues/2494) Add account balance to the dropdown.
- [Issue 2603](https://github.com/firefly-iii/firefly-iii/issues/2603) New keywords for reports.
- [Issue 2618](https://github.com/firefly-iii/firefly-iii/issues/2618) Page navigation in the footer of transaction lists.
- Option in your profile to delete meta-data from your administration.
- Add average to some reports.

### Changed
- [Issue 2593](https://github.com/firefly-iii/firefly-iii/issues/2593) The budget overview is now fully multi-currency.
- [Issue 2613](https://github.com/firefly-iii/firefly-iii/issues/2613) Improved Mailgun configuration options.
- [Issue 2510](https://github.com/firefly-iii/firefly-iii/issues/2510) Maximum transaction description length is 1000 now.
- [Issue 2616](https://github.com/firefly-iii/firefly-iii/issues/2616) Docker instances should remember their OAuth tokens and keys better (even after a restart)
- [Issue 2675](https://github.com/firefly-iii/firefly-iii/issues/2675) Some spelling in the English is fixed.

### Removed
- [Issue 2677](https://github.com/firefly-iii/firefly-iii/issues/2677) Superfluous help popup.

### Fixed
- [Issue 2572](https://github.com/firefly-iii/firefly-iii/issues/2572) Sometimes users would get 404's after deleting stuff. 
- [Issue 2587](https://github.com/firefly-iii/firefly-iii/issues/2587) Users would be redirected to JSON endpoints.
- [Issue 2596](https://github.com/firefly-iii/firefly-iii/issues/2596) Could not remove the last tag from a transaction.
- [Issue 2598](https://github.com/firefly-iii/firefly-iii/issues/2598) Fix an issue where foreign amounts were displayed incorrectly.
- [Issue 2599](https://github.com/firefly-iii/firefly-iii/issues/2599) Could add negative amounts to piggy banks and game the system.
- [Issue 2560](https://github.com/firefly-iii/firefly-iii/issues/2560) Search supports møre chäracters.
- [Issue 2626](https://github.com/firefly-iii/firefly-iii/issues/2626) Budgets would display amounts with too many decimals.
- [Issue 2629](https://github.com/firefly-iii/firefly-iii/issues/2629) [issue 2639](https://github.com/firefly-iii/firefly-iii/issues/2639) [issue 2640](https://github.com/firefly-iii/firefly-iii/issues/2640) [issue 2643](https://github.com/firefly-iii/firefly-iii/issues/2643) Line-breaks were not properly rendered in markdown.
- [Issue 2623](https://github.com/firefly-iii/firefly-iii/issues/2623) Budget spent line would make the start of the month twice.
- [Issue 2624](https://github.com/firefly-iii/firefly-iii/issues/2624) Editing a budget would redirect you to the wrong page.
- [Issue 2633](https://github.com/firefly-iii/firefly-iii/issues/2633) New transaction form sorts budgets wrong.
- [Issue 2567](https://github.com/firefly-iii/firefly-iii/issues/2567) Could not unlink bills.
- [Issue 2647](https://github.com/firefly-iii/firefly-iii/issues/2647) Date issue in category overview
- [Issue 2657](https://github.com/firefly-iii/firefly-iii/issues/2657) Possible fix for issue with transaction overview.
- [Issue 2658](https://github.com/firefly-iii/firefly-iii/issues/2658) Fixed overview of recurring transactions.
- [Issue 2480](https://github.com/firefly-iii/firefly-iii/issues/2480) SQLite can't handle a lot of variables so big update queries are now executed in chunks.
- [Issue 2683](https://github.com/firefly-iii/firefly-iii/issues/2683) Link to the wrong transaction.


### Security
- [Issue 2687](https://github.com/firefly-iii/firefly-iii/issues/2687) Budget overview shows budget limit totals for all users, not just the logged-in user.

### API
- [Issue 2609](https://github.com/firefly-iii/firefly-iii/issues/2609) Summary endpoint would not always give the correct results.
- [Issue 2638](https://github.com/firefly-iii/firefly-iii/issues/2638) Link to correct journal in API.
- [Issue 2606](https://github.com/firefly-iii/firefly-iii/issues/2606) Budget endpoint gave error.
- [Issue 2637](https://github.com/firefly-iii/firefly-iii/issues/2637) Transaction / piggy bank event endpoint now returns results.
- An undocumented end point that allows you to search for accounts. Still a bit experimental.
  Use: /api/v1/search/accounts?query=something&field=all (all,iban,id,number)

## [4.8.1.1 (API 0.10.2)] - 2019-09-12

### Changed
- Add some sensible maximum amounts to form inputs.

### Fixed
- [Issue 2561](https://github.com/firefly-iii/firefly-iii/issues/2561) Fixes a query error on the /tags page that affected some MySQL users.
- [Issue 2563](https://github.com/firefly-iii/firefly-iii/issues/2563) Two destination fields when editing a recurring transaction.
- [Issue 2564](https://github.com/firefly-iii/firefly-iii/issues/2564) Ability to browse pages in the search results.
- [Issue 2573](https://github.com/firefly-iii/firefly-iii/issues/2573) Could not submit an transaction update after an error was corrected.
- [Issue 2577](https://github.com/firefly-iii/firefly-iii/issues/2577) Upgrade routine would wrongly store the categories of split transactions.
- [Issue 2590](https://github.com/firefly-iii/firefly-iii/issues/2590) Fix an issue in the audit report.
- [Issue 2592](https://github.com/firefly-iii/firefly-iii/issues/2592) Fix an issue with YNAB import.
- [Issue 2597](https://github.com/firefly-iii/firefly-iii/issues/2597) Fix an issue where users could not delete currencies.

## [4.8.1 (API 0.10.2)] - 2019-09-08

Firefly III 4.8.1 requires PHP 7.3.

### Added
- Support for Greek
- [Issue 2383](https://github.com/firefly-iii/firefly-iii/issues/2383) Some tables in reports now also report percentages.
- [Issue 2389](https://github.com/firefly-iii/firefly-iii/issues/2389) Add category / budget information to transaction lists.
- [Issue 2464](https://github.com/firefly-iii/firefly-iii/issues/2464) Can now search for tag.
- [Issue 2466](https://github.com/firefly-iii/firefly-iii/issues/2466) Can order recurring transactions in a more useful manner.
- [Issue 2497](https://github.com/firefly-iii/firefly-iii/issues/2497) Transaction creation moment in hover of tag title.
- [Issue 2471](https://github.com/firefly-iii/firefly-iii/issues/2471) Added date tag to table cells.

### Changed
- [Issue 2285](https://github.com/firefly-iii/firefly-iii/issues/2285) Rule handling is now uniform across the app.
- [Issue 2231](https://github.com/firefly-iii/firefly-iii/issues/2231) You can now also use the `DATABASE_URL` for MySQL connections.
- [Issue 2291](https://github.com/firefly-iii/firefly-iii/issues/2291) All reports are now properly multi-currency.
- [Issue 2481](https://github.com/firefly-iii/firefly-iii/issues/2481) As part of the removal of local encryption, uploads and imports are no longer encrypted.
- [Issue 2495](https://github.com/firefly-iii/firefly-iii/issues/2495) A better message of transaction submission.
- [Issue 2506](https://github.com/firefly-iii/firefly-iii/issues/2506) Some bugs in tag report fixed.
- [Issue 2510](https://github.com/firefly-iii/firefly-iii/issues/2510) All transaction descriptions cut off at 255 chars.
- Changing your language preference invites you to submit corrections to [Crowdin](https://crowdin.com/project/firefly-iii).
- Better sum in bill view.
- Clean up docker files for flawless operation.

### Removed
- The bunq API has changed, and support for bunq has been disabled.

### Fixed
- [Issue 2470](https://github.com/firefly-iii/firefly-iii/issues/2470) Bad links for transactions.
- [Issue 2480](https://github.com/firefly-iii/firefly-iii/issues/2480) Large queries would break in SQLite.
- [Issue 2484](https://github.com/firefly-iii/firefly-iii/issues/2484) Transaction description auto-complete.
- [Issue 2487](https://github.com/firefly-iii/firefly-iii/issues/2487) Fix issues with FinTS
- [Issue 2488](https://github.com/firefly-iii/firefly-iii/issues/2488) 404 after deleting a tag.
- [Issue 2490](https://github.com/firefly-iii/firefly-iii/issues/2490) "Reset form after submission" doesn't work.
- [Issue 2492](https://github.com/firefly-iii/firefly-iii/issues/2492) After submitting and fixing an error, the error is persistent.
- [Issue 2493](https://github.com/firefly-iii/firefly-iii/issues/2493) Auto detect transaction type is a bit better now.
- [Issue 2498](https://github.com/firefly-iii/firefly-iii/issues/2498) Pressing enter in some fields breaks the form.
- [Issue 2499](https://github.com/firefly-iii/firefly-iii/issues/2499) Auto-complete issues in transaction link form.
- [Issue 2500](https://github.com/firefly-iii/firefly-iii/issues/2500) Issue when submitting edited transactions.
- [Issue 2501](https://github.com/firefly-iii/firefly-iii/issues/2501) Better error messages for empty submissions.
- [Issue 2508](https://github.com/firefly-iii/firefly-iii/issues/2508) Can remove category from transaction.
- [Issue 2516](https://github.com/firefly-iii/firefly-iii/issues/2516) Can no longer import transactions with no amount.
- [Issue 2518](https://github.com/firefly-iii/firefly-iii/issues/2518) Link in balance box goes to current period.
- [Issue 2521](https://github.com/firefly-iii/firefly-iii/issues/2521) Foreign transaction currency is hidden when the user hasn't enabled foreign currencies.
- [Issue 2522](https://github.com/firefly-iii/firefly-iii/issues/2522) Some reports were missing the "overspent" field.
- [Issue 2526](https://github.com/firefly-iii/firefly-iii/issues/2526) It was impossible to remove the budget of a transaction.
- [Issue 2527](https://github.com/firefly-iii/firefly-iii/issues/2527) Some bulk edits were buggy.
- [Issue 2539](https://github.com/firefly-iii/firefly-iii/issues/2539) Fixed a typo.
- [Issue 2545](https://github.com/firefly-iii/firefly-iii/issues/2545) Deleted tags would still show up.
- [Issue 2547](https://github.com/firefly-iii/firefly-iii/issues/2547) Changing the opening balance to 0 will now remove it.
- [Issue 2549](https://github.com/firefly-iii/firefly-iii/issues/2549) Can now clone transactions again.
- [Issue 2550](https://github.com/firefly-iii/firefly-iii/issues/2550) Added missing locales for moment.js
- [Issue 2553](https://github.com/firefly-iii/firefly-iii/issues/2553) Fixed an issue with split transactions.
- [Issue 2555](https://github.com/firefly-iii/firefly-iii/issues/2555) Better error for when you submit the same account twice.
- [Issue 2439](https://github.com/firefly-iii/firefly-iii/issues/2439) SQL error in API post new user
- ... and many other bugs.

### API
- [Issue 2475](https://github.com/firefly-iii/firefly-iii/issues/2475) Tags are now the same for all views.
- [Issue 2476](https://github.com/firefly-iii/firefly-iii/issues/2476) Amount is now represented equally in all views.
- [Issue 2477](https://github.com/firefly-iii/firefly-iii/issues/2477) Rules are easier to update.
- [Issue 2483](https://github.com/firefly-iii/firefly-iii/issues/2483) Several consistencies fixed.
- [Issue 2484](https://github.com/firefly-iii/firefly-iii/issues/2484) Transaction link view fixed.
- [Issue 2557](https://github.com/firefly-iii/firefly-iii/issues/2557) Fix for issue in summary API
- No longer have to submit mandatory fields to account end point. Just submit the field you wish to update, the rest will be untouched.
- Rules will no longer list the "user-action" trigger Rules will have a "moment" field that says either "update-journal" or "store-journal".

## [4.8.0.3 (API 0.10.1)] - 2019-08-23

Fixes many other issues in the previous release.

### Added
- Autocomplete for transaction description.

### Fixed
- [Issue 2438](https://github.com/firefly-iii/firefly-iii/issues/2438) Some balance issues when working with multiple currencies (a known issue)
- [Issue 2425](https://github.com/firefly-iii/firefly-iii/issues/2425) Transaction edit/create form is weird with the enter button
- [Issue 2424](https://github.com/firefly-iii/firefly-iii/issues/2424) auto complete tab doesn't work.
- [Issue 2441](https://github.com/firefly-iii/firefly-iii/issues/2441) Inconsistent character limit for currencies.
- [Issue 2443](https://github.com/firefly-iii/firefly-iii/issues/2443) 500 error when submitting budgets
- [Issue 2446](https://github.com/firefly-iii/firefly-iii/issues/2446) Can't update current amount for piggy bank
- [Issue 2440](https://github.com/firefly-iii/firefly-iii/issues/2440) Errors when interacting with recurring transactions
- [Issue 2439](https://github.com/firefly-iii/firefly-iii/issues/2439) SQL error in API post new user
- Transaction report (after import, over email) is mostly empty
- Mass edit checkboxes doesn't work in a tag overview
- [Issue 2437](https://github.com/firefly-iii/firefly-iii/issues/2437) CPU issues when viewing accounts, probably run-away queries.
- [Issue 2432](https://github.com/firefly-iii/firefly-iii/issues/2432) Can't disable all currencies except one / can't disable EUR and switch to something else.
- Option to edit the budget is gone from edit transaction form.
- [Issue 2453](https://github.com/firefly-iii/firefly-iii/issues/2453) Search view things
- [Issue 2449](https://github.com/firefly-iii/firefly-iii/issues/2449) Can't add invoice date.
- [Issue 2448](https://github.com/firefly-iii/firefly-iii/issues/2448) Bad link in transaction overview
- [Issue 2447](https://github.com/firefly-iii/firefly-iii/issues/2447) Bad link in bill overview

### API
- Improvements to various API end-points. Docs are updated.

## [4.8.0.2 (API 0.10.0)] - 2019-08-17

Fixes many other issues in the previous release.

### Changed
- Make many report boxes multi-currency.

### Fixed
- [Issue 2203](https://github.com/firefly-iii/firefly-iii/issues/2203) Reconciliation inconsistencies.
- [Issue 2392](https://github.com/firefly-iii/firefly-iii/issues/2392) Bad namespace leads to installation errors.
- [Issue 2393](https://github.com/firefly-iii/firefly-iii/issues/2393) Missing budget selector.
- [Issue 2402](https://github.com/firefly-iii/firefly-iii/issues/2402) bad amounts in default report
- [Issue 2405](https://github.com/firefly-iii/firefly-iii/issues/2405) Due date can't be edited.
- [Issue 2404](https://github.com/firefly-iii/firefly-iii/issues/2404) bad page indicator in the "no category" transaction overview.
- [Issue 2407](https://github.com/firefly-iii/firefly-iii/issues/2407) Fix recurring transaction dates
- [Issue 2410](https://github.com/firefly-iii/firefly-iii/issues/2410) Transaction links inconsistent
- [Issue 2414](https://github.com/firefly-iii/firefly-iii/issues/2414) Can't edit recurring transactions
- [Issue 2415](https://github.com/firefly-iii/firefly-iii/issues/2415) Return here + reset form results in empty transaction form
- [Issue 2416](https://github.com/firefly-iii/firefly-iii/issues/2416) Some form inconsistencies.
- [Issue 2418](https://github.com/firefly-iii/firefly-iii/issues/2418) Reports are inaccurate or broken.
- [Issue 2422](https://github.com/firefly-iii/firefly-iii/issues/2422) PHP error when matching transactions.
- [Issue 2423](https://github.com/firefly-iii/firefly-iii/issues/2423) Reports are inaccurate or broken.
- [Issue 2426](https://github.com/firefly-iii/firefly-iii/issues/2426) Inconsistent documentation and instructions.
- [Issue 2427](https://github.com/firefly-iii/firefly-iii/issues/2427) Deleted account and "initial balance" accounts may appear in dropdowns.
- [Issue 2428](https://github.com/firefly-iii/firefly-iii/issues/2428) Reports are inaccurate or broken. 
- [Issue 2429](https://github.com/firefly-iii/firefly-iii/issues/2429) Typo leads to SQL errors in available budgets API
- [Issue 2431](https://github.com/firefly-iii/firefly-iii/issues/2431) Issues creating new recurring transactions.
- [Issue 2434](https://github.com/firefly-iii/firefly-iii/issues/2434) You can edit the initial balance transaction but it fails to save.
- ARM build should work now.

### API
- [Issue 2429](https://github.com/firefly-iii/firefly-iii/issues/2429) Typo leads to SQL errors in available budgets API

## [4.8.0.1 (API 0.10.0)] - 2019-08-12

Fixes the most pressing issues found in the previous release.

### Fixed
- The balance box on the dashboard shows only negative numbers, skewing the results.
- Selecting or using tags in new transactions results in an error.
- Editing a transaction with tags will drop the tags from the transaction.
- [Issue 2382](https://github.com/firefly-iii/firefly-iii/issues/2382) Ranger config
- [Issue 2384](https://github.com/firefly-iii/firefly-iii/issues/2384) When upgrading manually, you may see: `The command "generate-keys" does not exist.`
- [Issue 2385](https://github.com/firefly-iii/firefly-iii/issues/2385) When upgrading manually, the firefly:verify command may fail to run.
- [Issue 2388](https://github.com/firefly-iii/firefly-iii/issues/2388) When registering as a new user, leaving the opening balance at 0 will give you an error.
- [Issue 2395](https://github.com/firefly-iii/firefly-iii/issues/2395) Editing split transactions is broken.
- [Issue 2397](https://github.com/firefly-iii/firefly-iii/issues/2397) Transfers are stored the wrong way around.
- [Issue 2399](https://github.com/firefly-iii/firefly-iii/issues/2399) Not all account balances are updated after you create a new transaction.
- [Issue 2401](https://github.com/firefly-iii/firefly-iii/issues/2401) Could not delete a split from a split transaction.

## [4.8.0 (API 0.10.0)] - 2019-08-09

A huge change that introduces significant database and API changes. Read more about it [in this Patreon post](https://www.patreon.com/posts/29044368).

### Open and known issues
- The "new transaction"-form isn't translated.
- You can't drag and drop transactions.
- You can't clone transactions.

### Added
- Hungarian translation!

### Changed
- New database model that changes the concept of "split transactions";
- New installation routine with rewritten database integrity tests and upgrade code;
- Rewritten screen to create transactions which will now completely rely on the API;
- Most terminal commands now have the prefix `firefly-iii`.
- New MFA code that will generate backup codes for you and is more robust. MFA will have to be re-enabled for ALL users.

### Deprecated
- This will probably be the last Firefly III version to have import routines for files, Bunq and others. These will be moved to separate applications that use the Firefly III API.

### Removed
- The export function has been removed.

### Fixed
- [Issue 1652](https://github.com/firefly-iii/firefly-iii/issues/1652), new strings to use during the import.
- [Issue 1860](https://github.com/firefly-iii/firefly-iii/issues/1860), fixing the default currency not being on top in a JSON box.
- [Issue 2031](https://github.com/firefly-iii/firefly-iii/issues/2031), a fix for Triodos imports.
- [Issue 2153](https://github.com/firefly-iii/firefly-iii/issues/2153), problems with editing credit cards.
- [Issue 2179](https://github.com/firefly-iii/firefly-iii/issues/2179), consistent and correct redirect behavior.
- [Issue 2180](https://github.com/firefly-iii/firefly-iii/issues/2180), API issues with foreign amounts.
- [Issue 2187](https://github.com/firefly-iii/firefly-iii/issues/2187), bulk editing reconciled transactions was broken.
- [Issue 2188](https://github.com/firefly-iii/firefly-iii/issues/2188), redirect loop in bills
- [Issue 2189](https://github.com/firefly-iii/firefly-iii/issues/2189), bulk edit could not handle tags.
- [Issue 2203](https://github.com/firefly-iii/firefly-iii/issues/2203), [issue 2208](https://github.com/firefly-iii/firefly-iii/issues/2208), [issue 2352](https://github.com/firefly-iii/firefly-iii/issues/2352), reconciliation fixes
- [Issue 2204](https://github.com/firefly-iii/firefly-iii/issues/2204), transaction type fix
- [Issue 2211](https://github.com/firefly-iii/firefly-iii/issues/2211), mass edit fixes.
- [Issue 2212](https://github.com/firefly-iii/firefly-iii/issues/2212), bug in the API when deleting objects.
- [Issue 2214](https://github.com/firefly-iii/firefly-iii/issues/2214), could not view attachment.
- [Issue 2219](https://github.com/firefly-iii/firefly-iii/issues/2219), max amount was a little low.
- [Issue 2239](https://github.com/firefly-iii/firefly-iii/issues/2239), fixed ordering issue.
- [Issue 2246](https://github.com/firefly-iii/firefly-iii/issues/2246), could not disable EUR.
- [Issue 2268](https://github.com/firefly-iii/firefly-iii/issues/2268), could not import into liability accounts.
- [Issue 2293](https://github.com/firefly-iii/firefly-iii/issues/2293), could not trigger rule on deposits in some circumstances
- [Issue 2314](https://github.com/firefly-iii/firefly-iii/issues/2314), could not trigger rule on transfers in some circumstances
- [Issue 2325](https://github.com/firefly-iii/firefly-iii/issues/2325), some balance issues on the frontpage.
- [Issue 2328](https://github.com/firefly-iii/firefly-iii/issues/2328), some date range issues in reports
- [Issue 2331](https://github.com/firefly-iii/firefly-iii/issues/2331), some broken fields in reports.
- [Issue 2333](https://github.com/firefly-iii/firefly-iii/issues/2333), API issues with piggy banks.
- [Issue 2355](https://github.com/firefly-iii/firefly-iii/issues/2355), configuration issues with LDAP
- [Issue 2361](https://github.com/firefly-iii/firefly-iii/issues/2361), some ordering issues.

### API
- Updated API to reflect the changes in the database.
- New API end-point for a summary of your data.
- Some new API charts.

## [4.7.17.6 (API 0.9.2)] - 2019-08-02

### Security
- XSS issue in liability account redirect, found by [@0x2500](https://github.com/0x2500).

## [4.7.17.5 (API 0.9.2)] - 2019-08-02

### Security
- Several XSS issues, found by [@0x2500](https://github.com/0x2500).

## [4.7.17.4 (API 0.9.2)] - 2019-08-02

### Security
- Several XSS issues, found by [@0x2500](https://github.com/0x2500).

## [4.7.17.3 (API 0.9.2)] - 2019-07-16

### Security
- XSS bug in file uploads (x2), found by [@dayn1ne](https://github.com/dayn1ne).
- XSS bug in search, found by [@dayn1ne](https://github.com/dayn1ne).

## [4.7.17.2 (API 0.9.2)] - 2019-07-15

### Security
- XSS bug in budget title, found by [@dayn1ne](https://github.com/dayn1ne).

## [4.7.17 (API 0.9.2)] - 2019-03-17

### Added
- Support for Norwegian!

### Changed
- Clear cache during install routine.
- Add Firefly III version number to install routine.

### Removed
- Initial release.

### Fixed
- [Issue 2159](https://github.com/firefly-iii/firefly-iii/issues/2159) Bad redirect due to Laravel upgrade.
- [Issue 2166](https://github.com/firefly-iii/firefly-iii/issues/2166) Importer had some issues with distinguishing double transfers.
- [Issue 2167](https://github.com/firefly-iii/firefly-iii/issues/2167) New LDAP package gave some configuration changes.
- [Issue 2173](https://github.com/firefly-iii/firefly-iii/issues/2173) Missing class when generating 2FA codes.

## [4.7.16 (API 0.9.2)] - 2019-03-08

4.7.16 was released to fix a persistent issue with broken user preferences.

### Changed

- Firefly III now uses Laravel 5.8.

## [4.7.15 (API 0.9.2)] - 2019-03-02

4.7.15 was released to fix some issues upgrading from older versions.

### Added
- [Issue 2128](https://github.com/firefly-iii/firefly-iii/issues/2128) Support for Postgres SSL

### Changed
- [Issue 2120](https://github.com/firefly-iii/firefly-iii/issues/2120) Add a missing meta tag, thanks to @lastlink
- Search is a lot faster now.

### Fixed
- [Issue 2125](https://github.com/firefly-iii/firefly-iii/issues/2125) Decryption issues during upgrade
- [Issue 2130](https://github.com/firefly-iii/firefly-iii/issues/2130) Fixed database migrations and rollbacks.
- [Issue 2135](https://github.com/firefly-iii/firefly-iii/issues/2135) Date fixes in transaction overview

## [4.7.14 (API 0.9.2)] - 2019-02-24

4.7.14 was released to fix an issue with the Composer installation script.

## [4.7.13 (API 0.9.2)] - 2019-02-23

4.7.13 was released to fix an issue that affected the Softaculous build.

### Added
- A routine has been added that warns about transactions with a 0.00 amount.

### Changed
- PHP maximum execution time is now 600 seconds in the Docker image.
- Moved several files outside of the root of Firefly III

### Fixed
- Fix issue where missing preference breaks the database upgrade.
- [Issue 2100](https://github.com/firefly-iii/firefly-iii/issues/2100) Mass edit transactions results in a reset of the date.

## [4.7.12 (API 0.9.2)] - 2019-02-16

4.7.12 was released to fix several shortcomings in v4.7.11's Docker image. Those in turn were caused by me. My apologies.

### Changed
- [Issue 2085](https://github.com/firefly-iii/firefly-iii/issues/2085) Upgraded the LDAP code. To keep using LDAP, set the `LOGIN_PROVIDER` to `ldap`.

### Fixed
- [Issue 2061](https://github.com/firefly-iii/firefly-iii/issues/2061) Some users reported empty update popups. 
- [Issue 2070](https://github.com/firefly-iii/firefly-iii/issues/2070) A cache issue prevented rules from being applied correctly.
- [Issue 2071](https://github.com/firefly-iii/firefly-iii/issues/2071) Several issues with Postgres and date values with time zone information in them.
- [Issue 2081](https://github.com/firefly-iii/firefly-iii/issues/2081) Rules were not being applied when importing using FinTS.
- [Issue 2082](https://github.com/firefly-iii/firefly-iii/issues/2082) The mass-editor changed all dates to today.

## [4.7.11 (API 0.9.2)] - 2019-02-10
### Added
- Experimental audit logging channel to track important events (separate from debug logging).

### Changed
- [Issue 2003](https://github.com/firefly-iii/firefly-iii/issues/2003), [issue 2006](https://github.com/firefly-iii/firefly-iii/issues/2006) Transactions can be stored with a timestamp. The user-interface does not support this yet. But the API does.
- Docker image tags a new manifest for arm and amd64.

### Removed
- [skuzzle](https://github.com/skuzzle) removed an annoying console.log statement.

### Fixed
- [Issue 2048](https://github.com/firefly-iii/firefly-iii/issues/2048) Fix "Are you sure?" popup, thanks to @nescafe2002!
- [Issue 2049](https://github.com/firefly-iii/firefly-iii/issues/2049) Empty preferences would crash Firefly III.
- [Issue 2052](https://github.com/firefly-iii/firefly-iii/issues/2052) Rules could not auto-covert to liabilities.
- Webbased upgrade routine will also decrypt the database.
- Last use date for categories was off.

### API
- The `date`-field in any transaction object now returns a ISO 8601 timestamp instead of a date.
 

## [4.7.10] - 2019-02-03
### Added
- [Issue 2037](https://github.com/firefly-iii/firefly-iii/issues/2037) Added some new magic keywords to reports.
- Added a new currency exchange rate service, [ratesapi.io](https://ratesapi.io/), that does not require expensive API keys. Built by [@BoGnY](https://github.com/BoGnY).
- Added Chinese Traditional translations. Thanks!

### Changed
- [Issue 1977](https://github.com/firefly-iii/firefly-iii/issues/1977) Docker image now includes memcached support
- [Issue 2031](https://github.com/firefly-iii/firefly-iii/issues/2031) A new generic debit/credit indicator for imports.
- The new Docker image no longer has the capability to run cron jobs, and will no longer generate your recurring transactions for you. This has been done to simplify the build and make sure your Docker container runs one service, as it should. To set up a cron job for your new Docker container, [check out the documentation](https://docs.firefly-iii.org/en/latest/installation/cronjob.html).
- Due to a change in the database structure, this upgrade will reset your preferences. Sorry about that.

### Deprecated
- I will no longer accept PR's that introduce new currencies.

### Removed
- Firefly III no longer encrypts the database and will [decrypt the database](https://github.com/firefly-iii/help/wiki/Database-encryption) on its first run.

### Fixed
- [Issue 1923](https://github.com/firefly-iii/firefly-iii/issues/1923) Broken window position for date picker.
- [Issue 1967](https://github.com/firefly-iii/firefly-iii/issues/1967) Attachments were hidden in bill view.
- [Issue 1927](https://github.com/firefly-iii/firefly-iii/issues/1927) It was impossible to make recurring transactions skip.
- [Issue 1929](https://github.com/firefly-iii/firefly-iii/issues/1929) Fix the recurring transactions calendar overview.
- [Issue 1933](https://github.com/firefly-iii/firefly-iii/issues/1933) Fixed a bug that made it impossible to authenticate to FreeIPA servers.
- [Issue 1938](https://github.com/firefly-iii/firefly-iii/issues/1938) The importer can now handle the insane way Postbank (DE) formats its numbers.
- [Issue 1942](https://github.com/firefly-iii/firefly-iii/issues/1942) Favicons are relative so Scriptaculous installations work better.
- [Issue 1944](https://github.com/firefly-iii/firefly-iii/issues/1944) Make sure that the search allows you to mass-select transactions.
- [Issue 1945](https://github.com/firefly-iii/firefly-iii/issues/1945) Slight UI change so the drop-down menu renders better.
- [Issue 1955](https://github.com/firefly-iii/firefly-iii/issues/1955) Fixed a bug in the category report.
- [Issue 1968](https://github.com/firefly-iii/firefly-iii/issues/1968) The yearly range would jump to 1-Jan / 1-Jan instead of 1-Jan / 31-Dec
- [Issue 1975](https://github.com/firefly-iii/firefly-iii/issues/1975) Fixed explanation for missing credit card liabilities.
- [Issue 1979](https://github.com/firefly-iii/firefly-iii/issues/1979) Make sure tags are trimmed.
- [Issue 1983](https://github.com/firefly-iii/firefly-iii/issues/1983) Could not use your favorite decimal separator.
- [Issue 1989](https://github.com/firefly-iii/firefly-iii/issues/1989) Bug in YNAB importer forced you to select all accounts.
- [Issue 1990](https://github.com/firefly-iii/firefly-iii/issues/1990) Rule description was invisible in edit screen.
- [Issue 1996](https://github.com/firefly-iii/firefly-iii/issues/1996) Deleted budget would inadvertently also hide transactions.
- [Issue 2001](https://github.com/firefly-iii/firefly-iii/issues/2001) Various issues with tag chart view.
- [Issue 2009](https://github.com/firefly-iii/firefly-iii/issues/2009) Could not change recurrence back to "forever".
- [Issue 2033](https://github.com/firefly-iii/firefly-iii/issues/2033) Longitude can go from -180 to 180.
- [Issue 2034](https://github.com/firefly-iii/firefly-iii/issues/2034) Rules were not being triggered in mass-edit.
- [Issue 2043](https://github.com/firefly-iii/firefly-iii/issues/2043) In rare instances the repetition of a recurring transaction was displayed incorrectly.
- Fixed broken translations in the recurring transactions overview.
- When you create a recurring transfer you make make it fill (or empty) a piggy bank. This was not working, despite a fix in 4.7.8.
- Fixed a bug where the importer would not be capable of creating new currencies.
- Rule trigger tester would skip the amount.

### Security
- OAuth2 form can now submit back to original requester.

### API (0.9.1)
- Submitting transactions with a disabled currency will auto-enable the currency.
- The documentation now states that "Deposit" is a possible return when you get a transaction.
- "savingAsset" was incorrectly documented as "savingsAsset".
- Account endpoint can now return type "reconciliation" and "initial-balance" correctly.
- New API endpoint under `/summary/basic` that gives you a basic overview of the user's finances.
- New API endpoints under `/chart/*` to allow you to render charts.
- `/accounts/x/transactions` now supports the limit query parameter.
- `/budgets/x/transactions` now supports the limit query parameter.
- `/available_budgets` now supports custom start and end date parameters.
- New endpoint `/preferences/prefName` to retrieve a single preference.
- Added field `account_name` to all piggy banks.
- New tag cloud in API.


See the [API docs](https://api-docs.firefly-iii.org/) for more information.

## [4.7.9] - 2018-12-25
### Added
- [Issue 1622](https://github.com/firefly-iii/firefly-iii/issues/1622) Can now unlink a transaction from a bill.
- [Issue 1848](https://github.com/firefly-iii/firefly-iii/issues/1848) Added support for the Swiss Franc.

### Changed
- [Issue 1828](https://github.com/firefly-iii/firefly-iii/issues/1828) Focus on fields for easy access.
- [Issue 1859](https://github.com/firefly-iii/firefly-iii/issues/1859) Warning when seeding database.
- Completely rewritten API. Check out the documentation [here](https://api-docs.firefly-iii.org/).
- Currencies can now be enabled and disabled, making for cleaner views.
- You can disable the `X-Frame-Options` header if this is necessary.
- New fancy favicons.
- Updated and improved docker build.
- Firefly III has been translated into Chinese (Traditional).


### Removed
- Docker build no longer builds its own cURL.

### Fixed
- [Issue 1607](https://github.com/firefly-iii/firefly-iii/issues/1607) [issue 1857](https://github.com/firefly-iii/firefly-iii/issues/1857) [issue 1895](https://github.com/firefly-iii/firefly-iii/issues/1895) Improved bunq import and added support for auto-savings.
- [Issue 1766](https://github.com/firefly-iii/firefly-iii/issues/1766) Extra commands so cache dir is owned by www user.
- [Issue 1811](https://github.com/firefly-iii/firefly-iii/issues/1811) 404 when generating report without options.
- [Issue 1835](https://github.com/firefly-iii/firefly-iii/issues/1835) Strange debug popup removed.
- [Issue 1840](https://github.com/firefly-iii/firefly-iii/issues/1840) Error when exporting data.
- [Issue 1857](https://github.com/firefly-iii/firefly-iii/issues/1857) Bunq import words again (see above).
- [Issue 1858](https://github.com/firefly-iii/firefly-iii/issues/1858) SQL errors when importing CSV.
- [Issue 1861](https://github.com/firefly-iii/firefly-iii/issues/1861) Period navigator was broken.
- [Issue 1864](https://github.com/firefly-iii/firefly-iii/issues/1864) First description was empty on split transactions.
- [Issue 1865](https://github.com/firefly-iii/firefly-iii/issues/1865) Bad math when showing categories.
- [Issue 1868](https://github.com/firefly-iii/firefly-iii/issues/1868) Fixes to FinTS import.
- [Issue 1872](https://github.com/firefly-iii/firefly-iii/issues/1872) Some images had 404's.
- [Issue 1877](https://github.com/firefly-iii/firefly-iii/issues/1877) Several encryption / decryption issues.
- [Issue 1878](https://github.com/firefly-iii/firefly-iii/issues/1878) Wrong nav links
- [Issue 1884](https://github.com/firefly-iii/firefly-iii/issues/1884) Budget API improvements (see above)
- [Issue 1888](https://github.com/firefly-iii/firefly-iii/issues/1888) Transaction API improvements (see above)
- [Issue 1890](https://github.com/firefly-iii/firefly-iii/issues/1890) Fixes in Bills API
- [Issue 1891](https://github.com/firefly-iii/firefly-iii/issues/1891) Typo fixed.
- [Issue 1893](https://github.com/firefly-iii/firefly-iii/issues/1893) Update piggies from recurring transactions.
- [Issue 1898](https://github.com/firefly-iii/firefly-iii/issues/1898) Bug in tag report.
- [Issue 1901](https://github.com/firefly-iii/firefly-iii/issues/1901) Redirect when cloning transactions.
- [Issue 1909](https://github.com/firefly-iii/firefly-iii/issues/1909) Date range fixes.
- [Issue 1916](https://github.com/firefly-iii/firefly-iii/issues/1916) Date range fixes.

## [4.7.8] - 2018-10-28
### Added
- [Issue 1005](https://github.com/firefly-iii/firefly-iii/issues/1005) You can now configure Firefly III to use LDAP. 
- [Issue 1071](https://github.com/firefly-iii/firefly-iii/issues/1071) You can execute transaction rules using the command line (so you can cronjob it)
- [Issue 1108](https://github.com/firefly-iii/firefly-iii/issues/1108) You can now reorder budgets.
- [Issue 1159](https://github.com/firefly-iii/firefly-iii/issues/1159) The ability to import transactions from FinTS-enabled banks.
- [Issue 1727](https://github.com/firefly-iii/firefly-iii/issues/1727) You can now use SFTP as storage for uploads and exports.
- [Issue 1733](https://github.com/firefly-iii/firefly-iii/issues/1733) You can configure Firefly III not to send emails with transaction information in them.



### Changed
- [Issue 1040](https://github.com/firefly-iii/firefly-iii/issues/1040) Fixed various things that would not scale properly in the past.
- [Issue 1771](https://github.com/firefly-iii/firefly-iii/issues/1771) A link to the transaction that fits the bill.
- [Issue 1800](https://github.com/firefly-iii/firefly-iii/issues/1800) Icon updated to match others.
- MySQL database connection now forces the InnoDB to be used.

### Fixed
- [Issue 1583](https://github.com/firefly-iii/firefly-iii/issues/1583) Some times recurring transactions would not fire.
- [Issue 1607](https://github.com/firefly-iii/firefly-iii/issues/1607) Problems with the bunq API, finally solved?! (I feel like a clickbait YouTube video now)
- [Issue 1698](https://github.com/firefly-iii/firefly-iii/issues/1698) Certificate problems in the Docker container
- [Issue 1751](https://github.com/firefly-iii/firefly-iii/issues/1751) Bug in autocomplete
- [Issue 1760](https://github.com/firefly-iii/firefly-iii/issues/1760) Tag report bad math
- [Issue 1765](https://github.com/firefly-iii/firefly-iii/issues/1765) API inconsistencies for piggy banks.
- [Issue 1774](https://github.com/firefly-iii/firefly-iii/issues/1774) Integer exception in SQLite databases
- [Issue 1775](https://github.com/firefly-iii/firefly-iii/issues/1775) Heroku now supports all locales
- [Issue 1778](https://github.com/firefly-iii/firefly-iii/issues/1778) More autocomplete problems fixed
- [Issue 1747](https://github.com/firefly-iii/firefly-iii/issues/1747) Rules now stop at the right moment.
- [Issue 1781](https://github.com/firefly-iii/firefly-iii/issues/1781) Problems when creating new rules.
- [Issue 1784](https://github.com/firefly-iii/firefly-iii/issues/1784) Can now create a liability with an empty balance.
- [Issue 1785](https://github.com/firefly-iii/firefly-iii/issues/1785) Redirect error
- [Issue 1790](https://github.com/firefly-iii/firefly-iii/issues/1790) Show attachments for bills.
- [Issue 1792](https://github.com/firefly-iii/firefly-iii/issues/1792) Mention excluded accounts.
- [Issue 1798](https://github.com/firefly-iii/firefly-iii/issues/1798) Could not recreate deleted piggy banks
- [Issue 1805](https://github.com/firefly-iii/firefly-iii/issues/1805) Fixes when handling foreign currencies
- [Issue 1807](https://github.com/firefly-iii/firefly-iii/issues/1807) Also decrypt deleted records.
- [Issue 1812](https://github.com/firefly-iii/firefly-iii/issues/1812) Fix in transactions API
- [Issue 1815](https://github.com/firefly-iii/firefly-iii/issues/1815) Opening balance account name can now be translated.
- [Issue 1830](https://github.com/firefly-iii/firefly-iii/issues/1830) Multi-user in a single browser could leak autocomplete data.

## [4.7.7] - 2018-10-01

This version of Firefly III requires PHP 7.2. I've already started using several features from 7.2. Please make sure you upgrade.

### Added
- [Issue 954](https://github.com/firefly-iii/firefly-iii/issues/954) Some additional view chart ranges
- [Issue 1710](https://github.com/firefly-iii/firefly-iii/issues/1710) Added a new currency ([hamuz](https://github.com/hamuz)) 
- Transactions will now store (in the database) how they were created.

### Changed
- [Issue 907](https://github.com/firefly-iii/firefly-iii/issues/907) Better and more options on the transaction list.
- [Issue 1450](https://github.com/firefly-iii/firefly-iii/issues/1450) Add a rule to change the type of a transaction automagically
- [Issue 1701](https://github.com/firefly-iii/firefly-iii/issues/1701) Fix reference to PHP executable ([hertzg](https://github.com/hertzg))
- Budget limits have currency information, for future expansion.
- Some charts and pages can handle multiple currencies better.
- New GA code for those who use it.

### Removed
- The credit card liability type has been removed.

### Fixed
- [Issue 896](https://github.com/firefly-iii/firefly-iii/issues/896) Better redirection when coming from deleted objects.
- [Issue 1519](https://github.com/firefly-iii/firefly-iii/issues/1519) Fix autocomplete tags
- [Issue 1607](https://github.com/firefly-iii/firefly-iii/issues/1607) Some fixes for the bunq api calls
- [Issue 1650](https://github.com/firefly-iii/firefly-iii/issues/1650) Add a negated amount column for CSV imports ([hamuz](https://github.com/hamuz))
- [Issue 1658](https://github.com/firefly-iii/firefly-iii/issues/1658) Make font heavy again.
- [Issue 1660](https://github.com/firefly-iii/firefly-iii/issues/1660) Add a negated amount column for CSV imports ([hamuz](https://github.com/hamuz))
- [Issue 1667](https://github.com/firefly-iii/firefly-iii/issues/1667) Fix pie charts
- [Issue 1668](https://github.com/firefly-iii/firefly-iii/issues/1668) YNAB iso_code fix
- [Issue 1670](https://github.com/firefly-iii/firefly-iii/issues/1670) Fix piggy bank API error
- [Issue 1671](https://github.com/firefly-iii/firefly-iii/issues/1671) More options for liability accounts.
- [Issue 1673](https://github.com/firefly-iii/firefly-iii/issues/1673) Fix reconciliation issues.
- [Issue 1675](https://github.com/firefly-iii/firefly-iii/issues/1675) Wrong sum in tag report.
- [Issue 1679](https://github.com/firefly-iii/firefly-iii/issues/1679) Change type of a transaction wouldn't trigger rules.
- [Issue 1682](https://github.com/firefly-iii/firefly-iii/issues/1682) Add liability accounts to transaction conversion
- [Issue 1683](https://github.com/firefly-iii/firefly-iii/issues/1683) See matching transaction showed transfers twice.
- [Issue 1685](https://github.com/firefly-iii/firefly-iii/issues/1685) fix autocomplete for rules
- [Issue 1690](https://github.com/firefly-iii/firefly-iii/issues/1690) Missing highlighted button in intro popup
- [Issue 1691](https://github.com/firefly-iii/firefly-iii/issues/1691) No mention of liabilities in demo text
- [Issue 1695](https://github.com/firefly-iii/firefly-iii/issues/1695) Small fixes in bills pages.
- [Issue 1708](https://github.com/firefly-iii/firefly-iii/issues/1708) Fix by [mathieupost](https://github.com/mathieupost) for bunq
- [Issue 1709](https://github.com/firefly-iii/firefly-iii/issues/1709) Fix oauth buttons 
- [Issue 1712](https://github.com/firefly-iii/firefly-iii/issues/1712) Double slash fix by [hamuz](https://github.com/hamuz)
- [Issue 1719](https://github.com/firefly-iii/firefly-iii/issues/1719) Add missing accounts to API
- [Issue 1720](https://github.com/firefly-iii/firefly-iii/issues/1720) Fix validation for transaction type.
- [Issue 1723](https://github.com/firefly-iii/firefly-iii/issues/1723) API broken for currency exchange rates.
- [Issue 1728](https://github.com/firefly-iii/firefly-iii/issues/1728) Fix problem with transaction factory.
- [Issue 1729](https://github.com/firefly-iii/firefly-iii/issues/1729) Fix bulk transaction editor
- [Issue 1731](https://github.com/firefly-iii/firefly-iii/issues/1731) API failure for budget limits.

### Security
- Secure headers now allow Mapbox and the 2FA QR code.

## [4.7.6.2] - 2018-09-03
### Fixed
- Docker file builds again.
- Fix CSS of OAuth2 authorization view.

## [4.7.6.1] - 2018-09-02
### Fixed
- An issue where I switched variables from the Docker `.env` file to the normal `.env` file and vice versa -- breaking both.
- [Issue 1649](https://github.com/firefly-iii/firefly-iii/issues/1649) 2FA QR code would not show up due to very strict security policy headers
- Docker build gave a cURL error whenever it runs PHP commands.

## [4.7.6] - 2018-09-02
### Added
- [Issue 145](https://github.com/firefly-iii/firefly-iii/issues/145) You can now download transactions from YNAB.
- [Issue 306](https://github.com/firefly-iii/firefly-iii/issues/306) You can now add liabilities to Firefly III.
- [Issue 740](https://github.com/firefly-iii/firefly-iii/issues/740) Various charts are now currency aware.
- [Issue 833](https://github.com/firefly-iii/firefly-iii/issues/833) Bills can use non-default currencies.
- [Issue 1578](https://github.com/firefly-iii/firefly-iii/issues/1578) Firefly III will notify you if the cron job hasn't fired.
- [Issue 1623](https://github.com/firefly-iii/firefly-iii/issues/1623) New transactions will link back from the success message.
- [Issue 1624](https://github.com/firefly-iii/firefly-iii/issues/1624) transactions will link to the object.
- You can call the cron job over the web now (see docs).
- You don't need to call the cron job every minute any more.
- Various charts are now red/green to signify income and expenses.
- Option to add or remove accounts from the net worth calculations.

### Deprecated
- This will be the last release on PHP 7.1. Future versions will require PHP 7.2.

### Fixed
- [Issue 1460](https://github.com/firefly-iii/firefly-iii/issues/1460) Downloading transactions from bunq should go more smoothly.
- [Issue 1464](https://github.com/firefly-iii/firefly-iii/issues/1464) Fixed the docker file to work on Raspberry Pi's.
- [Issue 1540](https://github.com/firefly-iii/firefly-iii/issues/1540) The Docker file now has a working cron job for recurring transactions.
- [Issue 1564](https://github.com/firefly-iii/firefly-iii/issues/1564) Fix double transfers when importing from bunq.
- [Issue 1575](https://github.com/firefly-iii/firefly-iii/issues/1575) Some views would give a XSRF token warning
- [Issue 1576](https://github.com/firefly-iii/firefly-iii/issues/1576) Fix assigning budgets
- [Issue 1580](https://github.com/firefly-iii/firefly-iii/issues/1580) Missing string for translation
- [Issue 1581](https://github.com/firefly-iii/firefly-iii/issues/1581) Expand help text
- [Issue 1584](https://github.com/firefly-iii/firefly-iii/issues/1584) Link to administration is back.
- [Issue 1586](https://github.com/firefly-iii/firefly-iii/issues/1586) Date fields in import were mislabeled.
- [Issue 1593](https://github.com/firefly-iii/firefly-iii/issues/1593) Link types are translatable.
- [Issue 1594](https://github.com/firefly-iii/firefly-iii/issues/1594) Very long breadcrumbs are weird.
- [Issue 1598](https://github.com/firefly-iii/firefly-iii/issues/1598) Fix budget calculations.
- [Issue 1597](https://github.com/firefly-iii/firefly-iii/issues/1597) Piggy banks are always inactive.
- [Issue 1605](https://github.com/firefly-iii/firefly-iii/issues/1605) System will ignore foreign currency setting if user doesn't indicate the amount.
- [Issue 1608](https://github.com/firefly-iii/firefly-iii/issues/1608) Spelling error in command line import.
- [Issue 1609](https://github.com/firefly-iii/firefly-iii/issues/1609) Link to budgets page was absolute.
- [Issue 1615](https://github.com/firefly-iii/firefly-iii/issues/1615) Fix currency bug in transactions.
- [Issue 1616](https://github.com/firefly-iii/firefly-iii/issues/1616) Fix null pointer exception in pie charts.
- [Issue 1617](https://github.com/firefly-iii/firefly-iii/issues/1617) Fix for complex tag names in URL's.
- [Issue 1620](https://github.com/firefly-iii/firefly-iii/issues/1620) Fixed index reference in API.
- [Issue 1639](https://github.com/firefly-iii/firefly-iii/issues/1639) Firefly III trusts the Heroku load balancer, fixing deployment on Heroku.
- [Issue 1642](https://github.com/firefly-iii/firefly-iii/issues/1642) Fix issue with split journals.
- [Issue 1643](https://github.com/firefly-iii/firefly-iii/issues/1643) Fix reconciliation issue.
- Users can no longer give income a budget.
- Fix bug in Spectre import.
- Heroku would not make you owner.
- The rule "tester" will now also take the "strict"-checkbox into account.
 
### Security
- Add `.htaccess` files to all public directories.
- New secure headers will make Firefly III slightly more secure.

## [4.7.5.3] - 2017-07-28
### Added
- Many updated French translations thanks to [@bubka](https://crowdin.com/profile/bubka).

### Fixed
- [Issue 1527](https://github.com/firefly-iii/firefly-iii/issues/1527), fixed views for transactions without a budget.
- [Issue 1553](https://github.com/firefly-iii/firefly-iii/issues/1553), report could not handle transactions before the first one in the system.
- [Issue 1549](https://github.com/firefly-iii/firefly-iii/issues/1549) update a budget will also update any rules that refer to that budget.
- [Issue 1530](https://github.com/firefly-iii/firefly-iii/issues/1530), fix issue with bill chart.
- [Issue 1563](https://github.com/firefly-iii/firefly-iii/issues/1563), fix piggy bank suggested amount
- [Issue 1571](https://github.com/firefly-iii/firefly-iii/issues/1571), fix OAuth in Sandstorm
- [Issue 1568](https://github.com/firefly-iii/firefly-iii/issues/1568), bug in Sandstorm user code.
- [Issue 1569](https://github.com/firefly-iii/firefly-iii/issues/1569), optimized Sandstorm build by [ocdtrekkie](https://github.com/ocdtrekkie)
- Fixed a bug where transfers would be stored inversely when using the CSV import.
- Retired the "Rabobank description"-fix, because it is no longer necessary.
- Fixed a bug where users could not delete budget limits in the API.
- Piggy bank notes are visible again.

## [4.7.5.2] - 2017-07-28
This version was superseeded by v4.7.5.3 because of a critical bug in the proxy-middleware.

## [4.7.5.1] - 2018-07-14
### Fixed
- [Issue 1531](https://github.com/firefly-iii/firefly-iii/issues/1531), the database routine incorrectly reports empty categories.
- [Issue 1532](https://github.com/firefly-iii/firefly-iii/issues/1532), broken dropdown for autosuggest things.
- [Issue 1533](https://github.com/firefly-iii/firefly-iii/issues/1533), fix where the import could not import category names.
- [Issue 1538](https://github.com/firefly-iii/firefly-iii/issues/1538), fix a bug where Spectre would not work when ignoring rules.
- [Issue 1542](https://github.com/firefly-iii/firefly-iii/issues/1542), fix a bug where the importer was incapable of generating new currencies.
- [Issue 1541](https://github.com/firefly-iii/firefly-iii/issues/1541), no longer ignore composer.lock in Docker ignore.
- Bills are stored inactive.

## [4.7.5] - 2018-07-02
### Added
- A new feature called "recurring transactions" that will make Firefly III automatically create transactions for you.
- New API end points for attachments, available budgets, budgets, budget limits, categories, configuration, currency exchange rates, journal links, link types, piggy banks, preferences, recurring transactions, rules, rule groups and tags.
- Added support for YunoHost.

### Changed
- The 2FA secret is visible so you can type it into 2FA apps.
- Bunq and Spectre imports will now ask to apply rules.
- Sandstorm users can now make API keys.

### Fixed
- Various typo's in the English translations. [issue 1493](https://github.com/firefly-iii/firefly-iii/issues/1493)
- Bug where Spectre was never called [issue 1492](https://github.com/firefly-iii/firefly-iii/issues/1492)
- Clear cache after journal is created through API [issue 1483](https://github.com/firefly-iii/firefly-iii/issues/1483)
- Make sure docker directories exist [issue 1500](https://github.com/firefly-iii/firefly-iii/issues/1500)
- Broken link to bill edit [issue 1505](https://github.com/firefly-iii/firefly-iii/issues/1505)
- Several bugs in the editing of split transactions [issue 1509](https://github.com/firefly-iii/firefly-iii/issues/1509)
- Import routine ignored formatting of several date fields [issue 1510](https://github.com/firefly-iii/firefly-iii/issues/1510)
- Piggy bank events now show the correct currency [issue 1446](https://github.com/firefly-iii/firefly-iii/issues/1446)
- Inactive accounts are no longer suggested [issue 1463](https://github.com/firefly-iii/firefly-iii/issues/1463)
- Some income / expense charts are less confusing [issue 1518](https://github.com/firefly-iii/firefly-iii/issues/1518)
- Validation bug in multi-currency create view [issue 1521](https://github.com/firefly-iii/firefly-iii/issues/1521)
- Bug where imported transfers would be stored incorrectly.

## [4.7.4] - 2015-06-03
### Added
- [Issue 1409](https://github.com/firefly-iii/firefly-iii/issues/1409), add Indian Rupee and explain that users can do this themselves [issue 1413](https://github.com/firefly-iii/firefly-iii/issues/1413)
- [Issue 1445](https://github.com/firefly-iii/firefly-iii/issues/1445), upgrade Curl in Docker image.
- [Issue 1386](https://github.com/firefly-iii/firefly-iii/issues/1386), quick links to often used pages.
- [Issue 1405](https://github.com/firefly-iii/firefly-iii/issues/1405), show proposed amount to piggy banks.
- [Issue 1416](https://github.com/firefly-iii/firefly-iii/issues/1416), ability to delete lost attachments.

### Changed
- A completely rewritten import routine that can handle bunq (thanks everybody for testing!), CSV files and Spectre. Please make sure you read about this at http://bit.ly/FF3-new-import
- [Issue 1392](https://github.com/firefly-iii/firefly-iii/issues/1392), explicitly mention rules are inactive (when they are).
- [Issue 1406](https://github.com/firefly-iii/firefly-iii/issues/1406), bill conversion to rules will be smarter about the rules they create.

### Fixed
- [Issue 1369](https://github.com/firefly-iii/firefly-iii/issues/1369), you can now properly order piggy banks again.
- [Issue 1389](https://github.com/firefly-iii/firefly-iii/issues/1389), null-pointer in the import routine.
- [Issue 1400](https://github.com/firefly-iii/firefly-iii/issues/1400), missing translation.
- [Issue 1403](https://github.com/firefly-iii/firefly-iii/issues/1403), bill would always be marked as inactive in edit screen.
- [Issue 1418](https://github.com/firefly-iii/firefly-iii/issues/1418), missing note text on bill page.
- Export routine would break when encountering un-decryptable files.
- [Issue 1425](https://github.com/firefly-iii/firefly-iii/issues/1425), empty fields when edit multiple transactions at once.
- [Issue 1449](https://github.com/firefly-iii/firefly-iii/issues/1449), bad calculations in "budget left to spend" view.
- [Issue 1451](https://github.com/firefly-iii/firefly-iii/issues/1451), same but in another view.
- [Issue 1453](https://github.com/firefly-iii/firefly-iii/issues/1453), same as [issue 1403](https://github.com/firefly-iii/firefly-iii/issues/1403).
- [Issue 1455](https://github.com/firefly-iii/firefly-iii/issues/1455), could add income to a budget.
- [Issue 1442](https://github.com/firefly-iii/firefly-iii/issues/1442), issues with editing a split deposit.
- [Issue 1452](https://github.com/firefly-iii/firefly-iii/issues/1452), date range problems with tags.
- [Issue 1458](https://github.com/firefly-iii/firefly-iii/issues/1458), same for transactions.


### Security
- [Issue 1415](https://github.com/firefly-iii/firefly-iii/issues/1415), will email you when OAuth2 keys are generated.


## [4.7.3.2] - 2018-05-16
### Fixed
- Forgot to increase the version number :(.


## [4.7.3.1] - 2018-05-14
### Fixed
- Fixed a critical bug where the rules-engine would fire inadvertently.

## [4.7.3] - 2018-04-29
### Added
- Currency added to API
- Firfely III will also generate a cash wallet for new users.
- Can now reset Spectre and bunq settings
- Docker file has a time zone
- Allow database connection to be configured in Docker file
- Can now view and edit attachments in edit-screen
- User can visit hidden `/attachments` page
- [Issue 1356](https://github.com/firefly-iii/firefly-iii/issues/1356): Budgets will show the remaining amount per day
- [Issue 1367](https://github.com/firefly-iii/firefly-iii/issues/1367): Rules now come in strict and non-strict mode.
- Added a security.txt
- More support for trusted proxies

### Changed
- Improved edit routine for split transactions.
- Upgrade routine can handle `proc_close` being disabled.
- Bills now use rules to match transactions, making it more flexible.
- [Issue 1328](https://github.com/firefly-iii/firefly-iii/issues/1328): piggy banks no have a more useful chart.
- Spectre API upgraded to v4
- Move to MariaDB ([issue 1366](https://github.com/firefly-iii/firefly-iii/issues/1366))
- Piggy banks take currency from parent account ([issue 1334](https://github.com/firefly-iii/firefly-iii/issues/1334))

### Deprecated
- [Issue 1341](https://github.com/firefly-iii/firefly-iii/issues/1341): Removed depricated command from dockerfile

### Fixed
- Several issues with docker image ([issue 1320](https://github.com/firefly-iii/firefly-iii/issues/1320), [issue 1382](https://github.com/firefly-iii/firefly-iii/issues/1382)).
- Fix giant tags and division by zero ([issue 1325](https://github.com/firefly-iii/firefly-iii/issues/1325) and others)
- Several issues with bunq import ([issue 1352](https://github.com/firefly-iii/firefly-iii/issues/1352), [issue 1330](https://github.com/firefly-iii/firefly-iii/issues/1330), [issue 1378](https://github.com/firefly-iii/firefly-iii/issues/1378), [issue 1380](https://github.com/firefly-iii/firefly-iii/issues/1380))
- [Issue 1246](https://github.com/firefly-iii/firefly-iii/issues/1246): date picker is internationalised
- [Issue 1327](https://github.com/firefly-iii/firefly-iii/issues/1327): fix formattting issues in piggy banks
- [Issue 1348](https://github.com/firefly-iii/firefly-iii/issues/1348): 500 error in API
- [Issue 1349](https://github.com/firefly-iii/firefly-iii/issues/1349): Errors in import routine
- Several fixes for (multi-currency) reconciliation ([issue 1336](https://github.com/firefly-iii/firefly-iii/issues/1336), [issue 1363](https://github.com/firefly-iii/firefly-iii/issues/1363))
- [Issue 1353](https://github.com/firefly-iii/firefly-iii/issues/1353): return NULL values in range-indicator

## [4.7.2.2] - 2018-04-04
### Fixed
- Bug in split transaction edit routine
- Piggy bank percentage was very specific.
- Logging in Slack is easier to config.
- [Issue 1312](https://github.com/firefly-iii/firefly-iii/issues/1312) Import broken for ING accounts
- [Issue 1313](https://github.com/firefly-iii/firefly-iii/issues/1313) Error when creating new asset account
- [Issue 1317](https://github.com/firefly-iii/firefly-iii/issues/1317) Forgot an include :(

## [4.7.2.1] - 2018-04-02
### Fixed
- Null pointer exception in transaction overview.
- Installations running in subdirs were incapable of creating OAuth tokens.
- OAuth keys were not created in all cases.

## [4.7.2] - 2018-04-01
### Added
- [Issue 1123](https://github.com/firefly-iii/firefly-iii/issues/1123) First browser based update routine.
- Add support for Italian.
- [Issue 1232](https://github.com/firefly-iii/firefly-iii/issues/1232) Allow user to specify Docker database port.
- [Issue 1197](https://github.com/firefly-iii/firefly-iii/issues/1197) Beter account list overview 
- [Issue 1202](https://github.com/firefly-iii/firefly-iii/issues/1202) Some budgetary warnings 
- [Issue 1284](https://github.com/firefly-iii/firefly-iii/issues/1284) Experimental support for bunq import
- [Issue 1248](https://github.com/firefly-iii/firefly-iii/issues/1248) Ability to import BIC, ability to import SEPA fields. 
- [Issue 1102](https://github.com/firefly-iii/firefly-iii/issues/1102) Summary line for bills 
- More info to debug page.
- [Issue 1186](https://github.com/firefly-iii/firefly-iii/issues/1186) You can see the latest account balance in CRUD forms 
- Add Kubernetes YAML files, kindly created by a FF3 user.

### Changed
- [Issue 1244](https://github.com/firefly-iii/firefly-iii/issues/1244) Better line for "today" marker and add it to other chart as well ([issue 1214](https://github.com/firefly-iii/firefly-iii/issues/1214))
- [Issue 1219](https://github.com/firefly-iii/firefly-iii/issues/1219) Languages in dropdown 
- [Issue 1189](https://github.com/firefly-iii/firefly-iii/issues/1189) Inactive accounts get removed from net worth 
- [Issue 1220](https://github.com/firefly-iii/firefly-iii/issues/1220) Attachment description and notes migrated to just "notes". 
- [Issue 1236](https://github.com/firefly-iii/firefly-iii/issues/1236) Multi currency balance box 
- [Issue 1240](https://github.com/firefly-iii/firefly-iii/issues/1240) Better overview for accounts. 
- [Issue 1292](https://github.com/firefly-iii/firefly-iii/issues/1292) Removed some charts from the "all"-overview of budgets and categories 
- [Issue 1245](https://github.com/firefly-iii/firefly-iii/issues/1245) Improved recognition of IBANs 
- Improved import routine.
- Update notifier will wait three days before notifying users.
- [Issue 1300](https://github.com/firefly-iii/firefly-iii/issues/1300) Virtual balance of credit cards does not count for net worth 
- [Issue 1247](https://github.com/firefly-iii/firefly-iii/issues/1247) Can now see overspent amount 
- [Issue 1221](https://github.com/firefly-iii/firefly-iii/issues/1221) Upgrade to Laravel 5.6 
- [Issue 1187](https://github.com/firefly-iii/firefly-iii/issues/1187) Updated the password verifier to use Troy Hunt's new API 
- Revenue chart is now on frontpage permanently
- [Issue 1153](https://github.com/firefly-iii/firefly-iii/issues/1153) 2FA settings are in your profile now 
- [Issue 1227](https://github.com/firefly-iii/firefly-iii/issues/1227) Can set the timezone in config or in Docker 

### Fixed
- [Issue 1294](https://github.com/firefly-iii/firefly-iii/issues/1294) Ability to link a transaction to itself 
- Correct reference to journal description in split form.
- [Issue 1234](https://github.com/firefly-iii/firefly-iii/issues/1234) Fix budget page issues in SQLite 
- [Issue 1262](https://github.com/firefly-iii/firefly-iii/issues/1262) Can now use double and epty headers in CSV files 
- [Issue 1258](https://github.com/firefly-iii/firefly-iii/issues/1258) Fixed a possible date mismatch in piggy banks
- [Issue 1283](https://github.com/firefly-iii/firefly-iii/issues/1283) Bulk delete was broken 
- [Issue 1293](https://github.com/firefly-iii/firefly-iii/issues/1293) Layout problem with notes 
- [Issue 1257](https://github.com/firefly-iii/firefly-iii/issues/1257) Improve transaction lists query count 
- [Issue 1291](https://github.com/firefly-iii/firefly-iii/issues/1291) Fixer IO problems 
- [Issue 1239](https://github.com/firefly-iii/firefly-iii/issues/1239) Could not edit expense or revenue accounts ([issue 1298](https://github.com/firefly-iii/firefly-iii/issues/1298)) 
- [Issue 1297](https://github.com/firefly-iii/firefly-iii/issues/1297) Could not convert to withdrawal 
- [Issue 1226](https://github.com/firefly-iii/firefly-iii/issues/1226) Category overview in default report shows no income. 
- Various other bugs and problems ([issue 1198](https://github.com/firefly-iii/firefly-iii/issues/1198), [issue 1213](https://github.com/firefly-iii/firefly-iii/issues/1213), [issue 1237](https://github.com/firefly-iii/firefly-iii/issues/1237), [issue 1238](https://github.com/firefly-iii/firefly-iii/issues/1238), [issue 1199](https://github.com/firefly-iii/firefly-iii/issues/1199), [issue 1200](https://github.com/firefly-iii/firefly-iii/issues/1200))

### Security
- Fixed an issue with token validation on the command line.

## [4.7.1] - 2018-03-04
### Added
- A brand new API. Read about it in the [documentation](http://firefly-iii.readthedocs.io/en/latest/).
- Add support for Spanish. [issue 1194](https://github.com/firefly-iii/firefly-iii/issues/1194)
- Some custom preferences are selected by default for a better user experience.
- Some new currencies [issue 1211](https://github.com/firefly-iii/firefly-iii/issues/1211)

### Fixed
- Fixed [issue 1155](https://github.com/firefly-iii/firefly-iii/issues/1155) (reported by [ndandanov](https://github.com/ndandanov))
- [Issue 1156](https://github.com/firefly-iii/firefly-iii/issues/1156) [issue 1182](https://github.com/firefly-iii/firefly-iii/issues/1182) and other issues related to SQLite databases.
- Multi-page budget overview was broken (reported by [jinformatique](https://github.com/jinformatique))
- Importing CSV files with semi-colons in them did not work [issue 1172](https://github.com/firefly-iii/firefly-iii/issues/1172) [issue 1183](https://github.com/firefly-iii/firefly-iii/issues/1183) [issue 1210](https://github.com/firefly-iii/firefly-iii/issues/1210)
- Could not use account number that was in use by a deleted account [issue 1174](https://github.com/firefly-iii/firefly-iii/issues/1174) 
- Fixed spelling error that lead to 404's [issue 1175](https://github.com/firefly-iii/firefly-iii/issues/1175) [issue 1190](https://github.com/firefly-iii/firefly-iii/issues/1190)
- Fixed tag autocomplete [issue 1178](https://github.com/firefly-iii/firefly-iii/issues/1178)
- Better links for "new transaction" buttons [issue 1185](https://github.com/firefly-iii/firefly-iii/issues/1185)
- Cache errors in budget charts [issue 1192](https://github.com/firefly-iii/firefly-iii/issues/1192)
- Deleting transactions that are linked to other other transactions would lead to errors [issue 1209](https://github.com/firefly-iii/firefly-iii/issues/1209)

## [4.7.0] - 2018-01-31
### Added
- Support for Russian and Portuguese (Brazil)
- Support for the Spectre API (Salt Edge)
- Many strings now translatable thanks to [Nik-vr](https://github.com/Nik-vr) ([issue 1118](https://github.com/firefly-iii/firefly-iii/issues/1118), [issue 1116](https://github.com/firefly-iii/firefly-iii/issues/1116), [issue 1109](https://github.com/firefly-iii/firefly-iii/issues/1109), )
- Many buttons to quickly create stuff
- Sum of tables in reports, requested by [MacPaille](https://github.com/MacPaille) ([issue 1106](https://github.com/firefly-iii/firefly-iii/issues/1106))
- Future versions of Firefly III will notify you there is a new version, as suggested by [8bitgentleman](https://github.com/8bitgentleman) in [issue 1050](https://github.com/firefly-iii/firefly-iii/issues/1050)
- Improved net worth box [issue 1101](https://github.com/firefly-iii/firefly-iii/issues/1101) ([Nik-vr](https://github.com/Nik-vr))
- Nice dropdown in transaction list [issue 1082](https://github.com/firefly-iii/firefly-iii/issues/1082)
- Better support for local fonts thanks to [devlearner](https://github.com/devlearner) ([issue 1145](https://github.com/firefly-iii/firefly-iii/issues/1145))
- Improve attachment support and view capabilities (suggested by [trinhit](https://github.com/trinhit) in [issue 1146](https://github.com/firefly-iii/firefly-iii/issues/1146))

### Changed
- Whole new [read me file](https://github.com/firefly-iii/firefly-iii/blob/master/readme.md), [new end user documentation](https://firefly-iii.readthedocs.io/en/latest/) and an [updated website](https://www.firefly-iii.org/)!
- Many charts and info-blocks now scale property ([issue 989](https://github.com/firefly-iii/firefly-iii/issues/989) and [issue 1040](https://github.com/firefly-iii/firefly-iii/issues/1040))

### Fixed
- Charts work in IE thanks to [devlearner](https://github.com/devlearner) ([issue 1107](https://github.com/firefly-iii/firefly-iii/issues/1107))
- Various fixes in import routine
- Bug that left charts empty ([issue 1088](https://github.com/firefly-iii/firefly-iii/issues/1088)), reported by various users amongst which [jinformatique](https://github.com/jinformatique)
- [Issue 1124](https://github.com/firefly-iii/firefly-iii/issues/1124), as reported by [gavu](https://github.com/gavu)
- [Issue 1125](https://github.com/firefly-iii/firefly-iii/issues/1125), as reported by [gavu](https://github.com/gavu)
- [Issue 1126](https://github.com/firefly-iii/firefly-iii/issues/1126), as reported by [gavu](https://github.com/gavu)
- [Issue 1131](https://github.com/firefly-iii/firefly-iii/issues/1131), as reported by [dp87](https://github.com/dp87)
- [Issue 1129](https://github.com/firefly-iii/firefly-iii/issues/1129), as reported by [gavu](https://github.com/gavu)
- [Issue 1132](https://github.com/firefly-iii/firefly-iii/issues/1132), as reported by [gavu](https://github.com/gavu)
- Issue with cache in Sandstorm ([issue 1130](https://github.com/firefly-iii/firefly-iii/issues/1130))
- [Issue 1134](https://github.com/firefly-iii/firefly-iii/issues/1134)
- [Issue 1140](https://github.com/firefly-iii/firefly-iii/issues/1140)
- [Issue 1141](https://github.com/firefly-iii/firefly-iii/issues/1141), reported by [ErikFontanel](https://github.com/ErikFontanel)
- [Issue 1142](https://github.com/firefly-iii/firefly-iii/issues/1142)

### Security
- Removed many access rights from the demo user

## [4.6.13] - 2018-01-06
### Added
- [Issue 1074](https://github.com/firefly-iii/firefly-iii/issues/1074), suggested by [MacPaille](https://github.com/MacPaille)
- [Issue 1077](https://github.com/firefly-iii/firefly-iii/issues/1077), suggested by [wtercato](https://github.com/wtercato)
- Bulk edit of transactions thanks to [vicmosin](https://github.com/vicmosin) ([issue 1078](https://github.com/firefly-iii/firefly-iii/issues/1078))
- Support for Turkish.
- [Issue 1090](https://github.com/firefly-iii/firefly-iii/issues/1090), suggested by [Findus23](https://github.com/Findus23)
- [Issue 1097](https://github.com/firefly-iii/firefly-iii/issues/1097), suggested by [kelvinhammond](https://github.com/kelvinhammond)
- [Issue 1093](https://github.com/firefly-iii/firefly-iii/issues/1093), suggested by [jinformatique](https://github.com/jinformatique)
- [Issue 1098](https://github.com/firefly-iii/firefly-iii/issues/1098), suggested by [Nik-vr](https://github.com/Nik-vr)

### Fixed
- [Issue 972](https://github.com/firefly-iii/firefly-iii/issues/972), reported by [pjotrvdh](https://github.com/pjotrvdh)
- [Issue 1079](https://github.com/firefly-iii/firefly-iii/issues/1079), reported by [gavu](https://github.com/gavu)
- [Issue 1080](https://github.com/firefly-iii/firefly-iii/issues/1080), reported by [zjean](https://github.com/zjean)
- [Issue 1083](https://github.com/firefly-iii/firefly-iii/issues/1083), reported by [skuzzle](https://github.com/skuzzle)
- [Issue 1085](https://github.com/firefly-iii/firefly-iii/issues/1085), reported by [nicoschreiner](https://github.com/nicoschreiner)
- [Issue 1087](https://github.com/firefly-iii/firefly-iii/issues/1087), reported by [4oo4](https://github.com/4oo4)
- [Issue 1089](https://github.com/firefly-iii/firefly-iii/issues/1089), reported by [robin5210](https://github.com/robin5210)
- [Issue 1092](https://github.com/firefly-iii/firefly-iii/issues/1092), reported by [kelvinhammond](https://github.com/kelvinhammond)
- [Issue 1096](https://github.com/firefly-iii/firefly-iii/issues/1096), reported by [wtercato](https://github.com/wtercato)

## [4.6.12] - 2017-12-31
### Added
- Support for Indonesian.
- New report, see [issue 384](https://github.com/firefly-iii/firefly-iii/issues/384)
- [Issue 964](https://github.com/firefly-iii/firefly-iii/issues/964) as suggested by [gavu](https://github.com/gavu)

### Changed
- Greatly improved Docker support and documentation.

### Fixed
- [Issue 1046](https://github.com/firefly-iii/firefly-iii/issues/1046), as reported by [pkoziol](https://github.com/pkoziol)
- [Issue 1047](https://github.com/firefly-iii/firefly-iii/issues/1047), as reported by [pkoziol](https://github.com/pkoziol)
- [Issue 1048](https://github.com/firefly-iii/firefly-iii/issues/1048), as reported by [webence](https://github.com/webence)
- [Issue 1049](https://github.com/firefly-iii/firefly-iii/issues/1049), as reported by [nicoschreiner](https://github.com/nicoschreiner) 
- [Issue 1015](https://github.com/firefly-iii/firefly-iii/issues/1015), as reported by a user on Tweakers.net
- [Issue 1056](https://github.com/firefly-iii/firefly-iii/issues/1056), as reported by [repercussion](https://github.com/repercussion) 
- [Issue 1061](https://github.com/firefly-iii/firefly-iii/issues/1061), as reported by [Meizikyn](https://github.com/Meizikyn)
- [Issue 1045](https://github.com/firefly-iii/firefly-iii/issues/1045), as reported by [gavu](https://github.com/gavu)
- First code for [issue 1040](https://github.com/firefly-iii/firefly-iii/issues/1040) ([simonsmiley](https://github.com/simonsmiley))
- [Issue 1059](https://github.com/firefly-iii/firefly-iii/issues/1059), as reported by [4oo4](https://github.com/4oo4)
- [Issue 1063](https://github.com/firefly-iii/firefly-iii/issues/1063), as reported by [pkoziol](https://github.com/pkoziol)
- [Issue 1064](https://github.com/firefly-iii/firefly-iii/issues/1064), as reported by [pkoziol](https://github.com/pkoziol)
- [Issue 1066](https://github.com/firefly-iii/firefly-iii/issues/1066), reported by [wtercato](https://github.com/wtercato)


## [4.6.11.1] - 2017-12-08
### Added
- Import routine can scan for matching bills, [issue 956](https://github.com/firefly-iii/firefly-iii/issues/956)

### Changed
- Import will no longer scan for rules, this has become optional. Originally suggested in [issue 956](https://github.com/firefly-iii/firefly-iii/issues/956) by [gavu](https://github.com/gavu) 
- [Issue 1033](https://github.com/firefly-iii/firefly-iii/issues/1033), as reported by [Jumanjii](https://github.com/Jumanjii)
- [Issue 1033](https://github.com/firefly-iii/firefly-iii/issues/1034), as reported by [Aquariu](https://github.com/Aquariu)
- Extra admin check for [issue 1039](https://github.com/firefly-iii/firefly-iii/issues/1039), as reported by [ocdtrekkie](https://github.com/ocdtrekkie)

### Fixed
- Missing translations ([issue 1026](https://github.com/firefly-iii/firefly-iii/issues/1026)), as reported by [gavu](https://github.com/gavu) and [zjean](https://github.com/zjean)
- [Issue 1028](https://github.com/firefly-iii/firefly-iii/issues/1028), reported by [zjean](https://github.com/zjean)
- [Issue 1029](https://github.com/firefly-iii/firefly-iii/issues/1029), reported by [zjean](https://github.com/zjean)
- [Issue 1030](https://github.com/firefly-iii/firefly-iii/issues/1030), as reported by [Traxxi](https://github.com/Traxxi)
- [Issue 1036](https://github.com/firefly-iii/firefly-iii/issues/1036), as reported by [webence](https://github.com/webence)
- [Issue 1038](https://github.com/firefly-iii/firefly-iii/issues/1038), as reported by [gavu](https://github.com/gavu)

## [4.6.11] - 2017-11-30
### Added
- A debug page at `/debug` for easier debug.
- Strings translatable (see [issue 976](https://github.com/firefly-iii/firefly-iii/issues/976)), thanks to [Findus23](https://github.com/Findus23)
- Even more strings are translatable (and translated), thanks to [pkoziol](https://github.com/pkoziol) (see [issue 979](https://github.com/firefly-iii/firefly-iii/issues/979))
- Reconciliation of accounts ([issue 736](https://github.com/firefly-iii/firefly-iii/issues/736)), as requested by [kristophr](https://github.com/kristophr) and several others

### Changed
- Extended currency list, as suggested by [emuhendis](https://github.com/emuhendis) in [issue 994](https://github.com/firefly-iii/firefly-iii/issues/994)
- [Issue 996](https://github.com/firefly-iii/firefly-iii/issues/996) as suggested by [dp87](https://github.com/dp87)

### Removed
- Disabled Heroku support until I get it working again.

### Fixed
- [Issue 980](https://github.com/firefly-iii/firefly-iii/issues/980), reported by [Tim-Frensch](https://github.com/Tim-Frensch)
- [Issue 987](https://github.com/firefly-iii/firefly-iii/issues/987), reported by [gavu](https://github.com/gavu)
- [Issue 988](https://github.com/firefly-iii/firefly-iii/issues/988), reported by [gavu](https://github.com/gavu)
- [Issue 992](https://github.com/firefly-iii/firefly-iii/issues/992), reported by [ncicovic](https://github.com/ncicovic)
- [Issue 993](https://github.com/firefly-iii/firefly-iii/issues/993), reported by [gavu](https://github.com/gavu)
- [Issue 997](https://github.com/firefly-iii/firefly-iii/issues/997), reported by [gavu](https://github.com/gavu)
- [Issue 1000](https://github.com/firefly-iii/firefly-iii/issues/1000), reported by [xpfgsyb](https://github.com/xpfgsyb)
- [Issue 1001](https://github.com/firefly-iii/firefly-iii/issues/1001), reported by [gavu](https://github.com/gavu)
- [Issue 1002](https://github.com/firefly-iii/firefly-iii/issues/1002), reported by [ursweiss](https://github.com/ursweiss)
- [Issue 1003](https://github.com/firefly-iii/firefly-iii/issues/1003), reported by [ursweiss](https://github.com/ursweiss)
- [Issue 1004](https://github.com/firefly-iii/firefly-iii/issues/1004), reported by [Aquariu](https://github.com/Aquariu)
- [Issue 1010](https://github.com/firefly-iii/firefly-iii/issues/1010)
- [Issue 1014](https://github.com/firefly-iii/firefly-iii/issues/1014), reported by [ursweiss](https://github.com/ursweiss)
- [Issue 1016](https://github.com/firefly-iii/firefly-iii/issues/1016)
- [Issue 1024](https://github.com/firefly-iii/firefly-iii/issues/1024), reported by [gavu](https://github.com/gavu)
- [Issue 1025](https://github.com/firefly-iii/firefly-iii/issues/1025), reported by [gavu](https://github.com/gavu)


## [4.6.10] - 2017-11-03
### Added
- Greatly expanded Docker support thanks to [alazare619](https://github.com/alazare619)
- [Issue 967](https://github.com/firefly-iii/firefly-iii/issues/967), thanks to [Aquariu](https://github.com/Aquariu)

### Changed
- Improved Sandstorm support.

### Fixed
- [Issue 963](https://github.com/firefly-iii/firefly-iii/issues/963), as reported by [gavu](https://github.com/gavu)
- [Issue 970](https://github.com/firefly-iii/firefly-iii/issues/970), as reported by [gavu](https://github.com/gavu)
- [Issue 971](https://github.com/firefly-iii/firefly-iii/issues/971), as reported by [gavu](https://github.com/gavu)
- Various Sandstorm.io related issues.

## [4.6.9] - 2017-10-22
### Added
- Firefly III is now available on the [Sandstorm.io](https://apps.sandstorm.io/app/uws252ya9mep4t77tevn85333xzsgrpgth8q4y1rhknn1hammw70) market.
- Issue template
- Pull request template
- Clean up routine to remove double budget limits (see [issue 932](https://github.com/firefly-iii/firefly-iii/issues/932))

### Changed
- Changed license to GPLv3.

### Fixed
- [Issue 895](https://github.com/firefly-iii/firefly-iii/issues/895), as reported by [gavu](https://github.com/gavu)
- [Issue 902](https://github.com/firefly-iii/firefly-iii/issues/902), as reported by [gavu](https://github.com/gavu)
- [Issue 916](https://github.com/firefly-iii/firefly-iii/issues/916), as reported by [gavu](https://github.com/gavu)
- [Issue 942](https://github.com/firefly-iii/firefly-iii/issues/942), as reported by [pkoziol](https://github.com/pkoziol)
- [Issue 943](https://github.com/firefly-iii/firefly-iii/issues/943), as reported by [aclex](https://github.com/aclex)
- [Issue 944](https://github.com/firefly-iii/firefly-iii/issues/944), as reported by [gavu](https://github.com/gavu)
- [Issue 932](https://github.com/firefly-iii/firefly-iii/issues/932), as reported by [nicoschreiner](https://github.com/nicoschreiner)
- [Issue 933](https://github.com/firefly-iii/firefly-iii/issues/933), as reported by [nicoschreiner](https://github.com/nicoschreiner)

## [4.6.8] - 2017-10-15
### Added
- Verify routine will check if deposits have a budget (they shouldn't).
- New translations!

### Changed
- Changed docker files for [issue 919](https://github.com/firefly-iii/firefly-iii/issues/919) and [issue 915](https://github.com/firefly-iii/firefly-iii/issues/915)

### Fixed
- [Issue 917](https://github.com/firefly-iii/firefly-iii/issues/917), as reported by [Wr0ngName](https://github.com/Wr0ngName)
- Rules can no longer set a budget for a transfer or a deposit ([issue 916](https://github.com/firefly-iii/firefly-iii/issues/916))
- Fixed [issue 925](https://github.com/firefly-iii/firefly-iii/issues/925), [issue 928](https://github.com/firefly-iii/firefly-iii/issues/928) as reported by [dzaikos](https://github.com/dzaikos) and [DeltaKiloz](https://github.com/DeltaKiloz)
- A fix for [issue 926](https://github.com/firefly-iii/firefly-iii/issues/926), as reported by [Aquariu](https://github.com/Aquariu)

## [4.6.7] - 2017-10-09
### Added
- [Issue 872](https://github.com/firefly-iii/firefly-iii/issues/872), reported [gavu](https://github.com/gavu)

### Fixed
- [Issue 878](https://github.com/firefly-iii/firefly-iii/issues/878), fixed by [Findus23](https://github.com/Findus23)
- [Issue 881](https://github.com/firefly-iii/firefly-iii/issues/881), reported by [nicoschreiner](https://github.com/nicoschreiner)
- [Issue 884](https://github.com/firefly-iii/firefly-iii/issues/884), by [gavu](https://github.com/gavu)
- [Issue 840](https://github.com/firefly-iii/firefly-iii/issues/840), reported by [MacPaille](https://github.com/MacPaille)
- [Issue 882](https://github.com/firefly-iii/firefly-iii/issues/882), reported by [nicoschreiner](https://github.com/nicoschreiner)
- [Issue 891](https://github.com/firefly-iii/firefly-iii/issues/891), [issue 892](https://github.com/firefly-iii/firefly-iii/issues/892), reported by [nicoschreiner](https://github.com/nicoschreiner)
- [Issue 891](https://github.com/firefly-iii/firefly-iii/issues/891), reported by [gavu](https://github.com/gavu)
- [Issue 827](https://github.com/firefly-iii/firefly-iii/issues/827), fixed by [pkoziol](https://github.com/pkoziol)
- [Issue 903](https://github.com/firefly-iii/firefly-iii/issues/903), fixed by [hduijn](https://github.com/hduijn)
- [Issue 904](https://github.com/firefly-iii/firefly-iii/issues/904), reported by [gavu](https://github.com/gavu)
- [Issue 910](https://github.com/firefly-iii/firefly-iii/issues/910), reported by [gavu](https://github.com/gavu)
- [Issue 911](https://github.com/firefly-iii/firefly-iii/issues/911), reported by [gavu](https://github.com/gavu)
- [Issue 915](https://github.com/firefly-iii/firefly-iii/issues/915), reported by [TomWis97](https://github.com/TomWis97)
- [Issue 917](https://github.com/firefly-iii/firefly-iii/issues/917), reported by [Wr0ngName](https://github.com/Wr0ngName)

## [4.6.6] - 2017-09-30
### Added
- [Issue 826](https://github.com/firefly-iii/firefly-iii/issues/826), reported by [pkoziol](https://github.com/pkoziol).
- [Issue 855](https://github.com/firefly-iii/firefly-iii/issues/855), by [ms32035](https://github.com/ms32035)
- [Issue 786](https://github.com/firefly-iii/firefly-iii/issues/786), by [SmilingWorlock](https://github.com/SmilingWorlock)
- [Issue 875](https://github.com/firefly-iii/firefly-iii/issues/875), by [gavu](https://github.com/gavu)
- [Issue 834](https://github.com/firefly-iii/firefly-iii/issues/834), by [gavu](https://github.com/gavu) (and others)


### Changed
- Upgraded to Laravel 5.5
- Add version parameter to CSS and JS files
- [Issue 823](https://github.com/firefly-iii/firefly-iii/issues/823), [issue 824](https://github.com/firefly-iii/firefly-iii/issues/824) fixed Docker config by [DieBauer](https://github.com/DieBauer)

### Fixed
- [Issue 830](https://github.com/firefly-iii/firefly-iii/issues/830)
- [Issue 822](https://github.com/firefly-iii/firefly-iii/issues/822), reported by [gazben](https://github.com/gazben)
- [Issue 827](https://github.com/firefly-iii/firefly-iii/issues/827), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 835](https://github.com/firefly-iii/firefly-iii/issues/835), reported by [gavu](https://github.com/gavu)
- [Issue 836](https://github.com/firefly-iii/firefly-iii/issues/836), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 838](https://github.com/firefly-iii/firefly-iii/issues/838), reported by [gavu](https://github.com/gavu)
- [Issue 839](https://github.com/firefly-iii/firefly-iii/issues/839), reported by [gavu](https://github.com/gavu)
- [Issue 843](https://github.com/firefly-iii/firefly-iii/issues/843), reported by [gavu](https://github.com/gavu)
- [Issue 837](https://github.com/firefly-iii/firefly-iii/issues/837), reported by [gavu](https://github.com/gavu)
- [Issue 845](https://github.com/firefly-iii/firefly-iii/issues/845), reported by [gavu](https://github.com/gavu)
- [Issue 846](https://github.com/firefly-iii/firefly-iii/issues/846), reported by [gavu](https://github.com/gavu)
- [Issue 848](https://github.com/firefly-iii/firefly-iii/issues/848), reported by [gavu](https://github.com/gavu)
- [Issue 854](https://github.com/firefly-iii/firefly-iii/issues/854), reported by [gavu](https://github.com/gavu)
- [Issue 866](https://github.com/firefly-iii/firefly-iii/issues/866), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 847](https://github.com/firefly-iii/firefly-iii/issues/847), reported by [gavu](https://github.com/gavu)
- [Issue 853](https://github.com/firefly-iii/firefly-iii/issues/853), reported by [gavu](https://github.com/gavu)
- [Issue 857](https://github.com/firefly-iii/firefly-iii/issues/857), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 865](https://github.com/firefly-iii/firefly-iii/issues/865), reported by [simonsmiley](https://github.com/simonsmiley)
- [Issue 826](https://github.com/firefly-iii/firefly-iii/issues/826), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 856](https://github.com/firefly-iii/firefly-iii/issues/856), reported by [ms32035](https://github.com/ms32035)
- [Issue 860](https://github.com/firefly-iii/firefly-iii/issues/860), reported by [gavu](https://github.com/gavu)
- [Issue 861](https://github.com/firefly-iii/firefly-iii/issues/861), reported by [gavu](https://github.com/gavu)
- [Issue 870](https://github.com/firefly-iii/firefly-iii/issues/870), reported by [gavu](https://github.com/gavu)

## [4.6.5] - 2017-09-09

### Added
- [Issue 616](https://github.com/firefly-iii/firefly-iii/issues/616), The ability to link transactions
- [Issue 763](https://github.com/firefly-iii/firefly-iii/issues/763), as suggested by [tannie](https://github.com/tannie)
- [Issue 770](https://github.com/firefly-iii/firefly-iii/issues/770), as suggested by [skibbipl](https://github.com/skibbipl)
- [Issue 780](https://github.com/firefly-iii/firefly-iii/issues/780), as suggested by [skibbipl](https://github.com/skibbipl)
- [Issue 784](https://github.com/firefly-iii/firefly-iii/issues/784), as suggested by [SmilingWorlock](https://github.com/SmilingWorlock)
- Lots of code for future support of automated Bunq imports

### Changed
- Rewrote the export routine
- [Issue 782](https://github.com/firefly-iii/firefly-iii/issues/782), as suggested by [NiceGuyIT](https://github.com/NiceGuyIT)
- [Issue 800](https://github.com/firefly-iii/firefly-iii/issues/800), as suggested by [jleeong](https://github.com/jleeong)

### Fixed
- [Issue 724](https://github.com/firefly-iii/firefly-iii/issues/724), reported by [skibbipl](https://github.com/skibbipl)
- [Issue 738](https://github.com/firefly-iii/firefly-iii/issues/738), reported by [skibbipl](https://github.com/skibbipl)
- [Issue 760](https://github.com/firefly-iii/firefly-iii/issues/760), reported by [leander091](https://github.com/leander091)
- [Issue 764](https://github.com/firefly-iii/firefly-iii/issues/764), reported by [tannie](https://github.com/tannie)
- [Issue 792](https://github.com/firefly-iii/firefly-iii/issues/792), reported by [jleeong](https://github.com/jleeong)
- [Issue 793](https://github.com/firefly-iii/firefly-iii/issues/793), reported by [nicoschreiner](https://github.com/nicoschreiner)
- [Issue 797](https://github.com/firefly-iii/firefly-iii/issues/797), reported by [leander091](https://github.com/leander091)
- [Issue 801](https://github.com/firefly-iii/firefly-iii/issues/801), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 803](https://github.com/firefly-iii/firefly-iii/issues/803), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 805](https://github.com/firefly-iii/firefly-iii/issues/805), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 806](https://github.com/firefly-iii/firefly-iii/issues/806), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 807](https://github.com/firefly-iii/firefly-iii/issues/807), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 808](https://github.com/firefly-iii/firefly-iii/issues/808), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 809](https://github.com/firefly-iii/firefly-iii/issues/809), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 814](https://github.com/firefly-iii/firefly-iii/issues/814), reported by [nicoschreiner](https://github.com/nicoschreiner)
- [Issue 818](https://github.com/firefly-iii/firefly-iii/issues/818), reported by [gavu](https://github.com/gavu)
- [Issue 819](https://github.com/firefly-iii/firefly-iii/issues/819), reported by [DieBauer](https://github.com/DieBauer)
- [Issue 820](https://github.com/firefly-iii/firefly-iii/issues/820), reported by [simonsmiley](https://github.com/simonsmiley) 
- Various other fixes


## [4.6.4] - 2017-08-13
### Added
- PHP7.1 support
- Routine to decrypt attachments from the command line, for [issue 671](https://github.com/firefly-iii/firefly-iii/issues/671)
- A routine that can check if your password has been stolen in the past.
- Split transaction shows amount left to be split


### Changed
- Importer can (potentially) handle new import routines such as banks.
- Importer can fall back from JSON errors 

### Removed
- PHP7.0 support
- Support for extended tag modes
- Remove "time jumps" to non-empty periods


### Fixed
- [Issue 717](https://github.com/firefly-iii/firefly-iii/issues/717), reported by [NiceGuyIT](https://github.com/NiceGuyIT)
- [Issue 718](https://github.com/firefly-iii/firefly-iii/issues/718), reported by [wtercato](https://github.com/wtercato)
- [Issue 722](https://github.com/firefly-iii/firefly-iii/issues/722), reported by [simonsmiley](https://github.com/simonsmiley)
- [Issue 648](https://github.com/firefly-iii/firefly-iii/issues/648), reported by [skibbipl](https://github.com/skibbipl)
- [Issue 730](https://github.com/firefly-iii/firefly-iii/issues/730), reported by [ragnarkarlsson](https://github.com/ragnarkarlsson)
- [Issue 733](https://github.com/firefly-iii/firefly-iii/issues/733), reported by [xpfgsyb](https://github.com/xpfgsyb)
- [Issue 735](https://github.com/firefly-iii/firefly-iii/issues/735), reported by [kristophr](https://github.com/kristophr)
- [Issue 739](https://github.com/firefly-iii/firefly-iii/issues/739), reported by [skibbipl](https://github.com/skibbipl)
- [Issue 515](https://github.com/firefly-iii/firefly-iii/issues/515), reported by [schwalberich](https://github.com/schwalberich)
- [Issue 743](https://github.com/firefly-iii/firefly-iii/issues/743), reported by [simonsmiley](https://github.com/simonsmiley)
- [Issue 746](https://github.com/firefly-iii/firefly-iii/issues/746), reported by [tannie](https://github.com/tannie)
- [Issue 747](https://github.com/firefly-iii/firefly-iii/issues/747), reported by [tannie](https://github.com/tannie)


## [4.6.3.1] - 2017-07-23
### Fixed
- Hotfix to close [issue 715](https://github.com/firefly-iii/firefly-iii/issues/715)

## [4.6.3] - 2017-07-23

This will be the last release to support PHP 7.0.

### Added
- New guidelines and new introduction tour to aid new users.
- Rules can now be applied at will to transactions, not just rule groups.

### Changed
- Improved category overview.
- Improved budget overview.
- Improved budget report.
- Improved command line import responsiveness and speed.
- All code comparisons are now strict.
- Improve search page.
- Charts are easier to read thanks to [simonsmiley](https://github.com/simonsmiley)
- Fixed [issue 708](https://github.com/firefly-iii/firefly-iii/issues/708).

### Fixed
- Fixed bug where import would not respect default account. [issue 694](https://github.com/firefly-iii/firefly-iii/issues/694)
- Fixed various broken paths
- Fixed several import inconsistencies.
- Various bug fixes.

## [4.6.2] - 2017-07-08
### Added
- Links added to boxes, idea by [simonsmiley](https://github.com/simonsmiley)

### Fixed
- Various bugs in import routine

## [4.6.1] - 2017-07-02
### Fixed
- Fixed several small issues all around.

## [4.6.0] - 2017-06-28

### Changed
- Revamped import routine. Will be buggy.

### Fixed
- [Issue 667](https://github.com/firefly-iii/firefly-iii/issues/667), postgresql reported by [skibbipl](https://github.com/skibbipl).
- [Issue 680](https://github.com/firefly-iii/firefly-iii/issues/680), fixed by [Xeli](https://github.com/Xeli)
- Fixed [issue 660](https://github.com/firefly-iii/firefly-iii/issues/660)
- Fixes [issue 672](https://github.com/firefly-iii/firefly-iii/issues/672), reported by [dzaikos](https://github.com/dzaikos)
- Fix a bug where the balance routine forgot to account for accounts without a currency preference.
- Various other bugfixes.

## [4.5.0] - 2017-06-07

### Added
- Better support for multi-currency transactions and display of transactions, accounts and everything. This requires a database overhaul (moving the currency information to specific transactions) so be careful when upgrading.
- Translations for Spanish and Slovenian.
- New interface for budget page, ~~stolen from~~ inspired by YNAB.
- Expanded Docker to work with postgresql as well, thanks to [kressh](https://github.com/kressh)

### Fixed
- PostgreSQL support in database upgrade routine ([issue 644](https://github.com/firefly-iii/firefly-iii/issues/644), reported by [skibbipl](https://github.com/skibbipl))
- Frontpage budget chart was off, fix by [nhaarman](https://github.com/nhaarman)
- Was not possible to remove opening balance.

## [4.4.3] - 2017-05-03
### Added
- Added support for Slovenian
- Removed support for Spanish. No translations whatsoever by the guy who requested it.
- Removed support for Russian. Same thing.
- Removed support for Croatian. Same thing.
- Removed support for Chinese Traditional, Hong Kong. Same thing.

### Changed
- The journal collector, an internal piece of code to collect transactions, now uses a slightly different method of collecting journals. This may cause problems.

### Fixed
- [Issue 638](https://github.com/firefly-iii/firefly-iii/issues/638) as reported by [worldworm](https://github.com/worldworm).
- Possible fix for [issue 624](https://github.com/firefly-iii/firefly-iii/issues/624)

## [4.4.2] - 2017-04-27
### Fixed
- Fixed a bug where the opening balance could not be stored.

## [4.4.1] - 2017-04-27

### Added
- Support for deployment on Heroku

### Fixed
- Bug in new-user routine.

## [4.4.0] - 2017-04-23
### Added
- Firefly III can now handle foreign currencies better, including some code to get the exchange rate live from the web.
- Can now make rules for attachments, see [issue 608](https://github.com/firefly-iii/firefly-iii/issues/608), as suggested by [dzaikos](https://github.com/dzaikos).

### Fixed
- Fixed [issue 629](https://github.com/firefly-iii/firefly-iii/issues/629), reported by [forcaeluz](https://github.com/forcaeluz)
- Fixed [issue 630](https://github.com/firefly-iii/firefly-iii/issues/630), reported by [welbert](https://github.com/welbert)
- And more various bug fixes.

## [4.3.8] - 2017-04-08

### Added
- Better overview / show pages.
- [Issue 628](https://github.com/firefly-iii/firefly-iii/issues/628), as reported by [xzaz](https://github.com/xzaz).
- Greatly expanded test coverage

### Fixed
- [Issue 619](https://github.com/firefly-iii/firefly-iii/issues/619), as reported by [dfiel](https://github.com/dfiel).
- [Issue 620](https://github.com/firefly-iii/firefly-iii/issues/620), as reported by [forcaeluz](https://github.com/forcaeluz).
- Attempt to fix [issue 624](https://github.com/firefly-iii/firefly-iii/issues/624), as reported by [TheSerenin](https://github.com/TheSerenin).
- Favicon link is relative now, fixed by [welbert](https://github.com/welbert).
- Some search bugs

## [4.3.7] - 2017-03-06
### Added
- Nice user friendly views for empty lists.
- Extended contribution guidelines.
- First version of financial report filtered on tags.
- Suggested monthly savings for piggy banks, by [Zsub](https://github.com/Zsub)
- Better test coverage.

### Changed
- Slightly changed tag overview.
- Consistent icon for bill in list.
- Slightly changed account overview.

### Removed
- Removed IDE specific views from .gitignore, [issue 598](https://github.com/firefly-iii/firefly-iii/issues/598)

### Fixed
- Force key generation during installation.
- The `date` function takes the fieldname where a date is stored, not the literal date by [Zsub](https://github.com/Zsub)
- Improved budget frontpage chart, as suggested by [skibbipl](https://github.com/skibbipl)
- [Issue 602](https://github.com/firefly-iii/firefly-iii/issues/602) and [issue 607](https://github.com/firefly-iii/firefly-iii/issues/607), as reported by [skibbipl](https://github.com/skibbipl) and [dzaikos](https://github.com/dzaikos).
- [Issue 605](https://github.com/firefly-iii/firefly-iii/issues/605), as reported by [Zsub](https://github.com/Zsub).
- [Issue 599](https://github.com/firefly-iii/firefly-iii/issues/599), as reported by [leander091](https://github.com/leander091).
- [Issue 610](https://github.com/firefly-iii/firefly-iii/issues/610), as reported by [skibbipl](https://github.com/skibbipl).
- [Issue 611](https://github.com/firefly-iii/firefly-iii/issues/611), as reported by [ragnarkarlsson](https://github.com/ragnarkarlsson).
- [Issue 612](https://github.com/firefly-iii/firefly-iii/issues/612), as reported by [ragnarkarlsson](https://github.com/ragnarkarlsson).
- [Issue 614](https://github.com/firefly-iii/firefly-iii/issues/614), as reported by [worldworm](https://github.com/worldworm).
- Various other bug fixes.

## [4.3.6] - 2017-02-20
### Fixed
- [Issue 578](https://github.com/firefly-iii/firefly-iii/issues/578), reported by [xpfgsyb](https://github.com/xpfgsyb).

## [4.3.5] - 2017-02-19
### Added
- Beta support for Sandstorm.IO
- Docker support by [schoentoon](https://github.com/schoentoon), [elohmeier](https://github.com/elohmeier), [patrickkostjens](https://github.com/patrickkostjens) and [crash7](https://github.com/crash7)!
- Can now use special keywords in the search to search for specic dates, categories, etc.

### Changed
- Updated to laravel 5.4!
- User friendly error message
- Updated locales to support more operating systems, first reported in [issue 536](https://github.com/firefly-iii/firefly-iii/issues/536) by [dabenzel](https://github.com/dabenzel)
- Updated budget report
- Improved 404 page
- Smooth curves, improved by [elamperti](https://github.com/elamperti).

### Fixed
- [Issue 549](https://github.com/firefly-iii/firefly-iii/issues/549)
- [Issue 553](https://github.com/firefly-iii/firefly-iii/issues/553)
- Fixed [issue 559](https://github.com/firefly-iii/firefly-iii/issues/559) reported by [elamperti](https://github.com/elamperti).
- [Issue 565](https://github.com/firefly-iii/firefly-iii/issues/565), as reported by a user over the mail
- [Issue 566](https://github.com/firefly-iii/firefly-iii/issues/566), as reported by [dspeckmann](https://github.com/dspeckmann)
- [Issue 567](https://github.com/firefly-iii/firefly-iii/issues/567), as reported by [winsomniak](https://github.com/winsomniak)
- [Issue 569](https://github.com/firefly-iii/firefly-iii/issues/569), as reported by [winsomniak](https://github.com/winsomniak)
- [Issue 572](https://github.com/firefly-iii/firefly-iii/issues/572), as reported by [zjean](https://github.com/zjean)
- Many issues with the transaction filters which will fix reports (they tended to display the wrong amount).

## [4.3.4] - 2017-02-02
### Fixed
- Fixed bug [issue 550](https://github.com/firefly-iii/firefly-iii/issues/550), reported by [worldworm](https://github.com/worldworm)!
- Fixed bug [issue 551](https://github.com/firefly-iii/firefly-iii/issues/551), reported by [t-me](https://github.com/t-me)!

## [4.3.3] - 2017-01-30

_The 100th release of Firefly!_

### Added
- Add locales to Docker ([issue 534](https://github.com/firefly-iii/firefly-iii/issues/534)) by [elohmeier](https://github.com/elohmeier).
- Optional database encryption. On by default.
- Datepicker for Firefox and other browsers.
- New instruction block for updating and installing.
- Ability to clone transactions.
- Use multi-select Bootstrap thing instead of massive lists of checkboxes.

### Removed
- Lots of old Javascript

### Fixed
- Missing sort broke various charts
- Bug in reports that made amounts behave weird
- Various bug fixes

### Security
- Tested FF against the naughty string list.

## [4.3.2] - 2017-01-09

An intermediate release because something in the Twig and Twigbridge libraries is broken and I have to make sure it doesn't affect you guys. But some cool features were on their way so there's that oo.

### Added
- Some code for [issue 475](https://github.com/firefly-iii/firefly-iii/issues/475), consistent overviews.
- Better currency display. Make sure you have locale packages installed.

### Changed
- Uses a new version of Laravel.

### Fixed
- The password reset routine was broken.
- [Issue 522](https://github.com/firefly-iii/firefly-iii/issues/522), thanks to [xpfgsyb](https://github.com/xpfgsyb)
- [Issue 524](https://github.com/firefly-iii/firefly-iii/issues/524), thanks to [worldworm](https://github.com/worldworm)
- [Issue 526](https://github.com/firefly-iii/firefly-iii/issues/526), thanks to [worldworm](https://github.com/worldworm)
- [Issue 528](https://github.com/firefly-iii/firefly-iii/issues/528), thanks to [skibbipl](https://github.com/skibbipl)
- Various other fixes.

## [4.3.1] - 2017-01-04
### Added
- Support for Russian and Polish. 
- Support for a proper demo website.
- Support for custom decimal places in currencies ([issue 506](https://github.com/firefly-iii/firefly-iii/issues/506), suggested by [xpfgsyb](https://github.com/xpfgsyb)).
- Most amounts are now right-aligned ([issue 511](https://github.com/firefly-iii/firefly-iii/issues/511), suggested by [xpfgsyb](https://github.com/xpfgsyb)).
- German is now a "complete" language, more than 75% translated!

### Changed
- **[New Github repository!](github.com/firefly-iii/firefly-iii)**
- Better category overview.
- [Issue 502](https://github.com/firefly-iii/firefly-iii/issues/502), thanks to [zjean](https://github.com/zjean)

### Removed
- Removed a lot of administration functions.
- Removed ability to activate users.

### Fixed
- [Issue 501](https://github.com/firefly-iii/firefly-iii/issues/501), thanks to [zjean](https://github.com/zjean)
- [Issue 513](https://github.com/firefly-iii/firefly-iii/issues/513), thanks to [skibbipl](https://github.com/skibbipl) 

### Security
- [Issue 519](https://github.com/firefly-iii/firefly-iii/issues/519), thanks to [xpfgsyb](https://github.com/xpfgsyb)

## [4.3.0] - 2016-12-26
### Added
- New method of keeping track of available budget, see [issue 489](https://github.com/firefly-iii/firefly-iii/issues/489)
- Support for Spanish
- Firefly III now has an extended demo mode. Will expand further in the future.
 

### Changed
- New favicon
- Import routine no longer gives transactions a description [issue 483](https://github.com/firefly-iii/firefly-iii/issues/483)


### Removed
- All test data generation code.

### Fixed
- Removed import accounts from search results [issue 478](https://github.com/firefly-iii/firefly-iii/issues/478)
- Redirect after delete will no longer go back to deleted item [issue 477](https://github.com/firefly-iii/firefly-iii/issues/477)
- Cannot math [issue 482](https://github.com/firefly-iii/firefly-iii/issues/482)
- Fixed bug in virtual balance field [issue 479](https://github.com/firefly-iii/firefly-iii/issues/479)

## [4.2.2] - 2016-12-18
### Added
- New budget report (still a bit of a beta)
- Can now edit user

### Changed
- New config for specific events. Still need to build Notifications.

### Fixed
- Various bugs
- [Issue 472](https://github.com/firefly-iii/firefly-iii/issues/472) thanks to [zjean](https://github.com/zjean)

## [4.2.1] - 2016-12-09
### Added
- BIC support (see [issue 430](https://github.com/firefly-iii/firefly-iii/issues/430))
- New category report section and chart (see the general financial report)


### Changed
- Date range picker now also available on mobile devices (see [issue 435](https://github.com/firefly-iii/firefly-iii/issues/435))
- Extended range of amounts for [issue 439](https://github.com/firefly-iii/firefly-iii/issues/439)
- Rewrote all routes. Old bookmarks may break.

## [4.2.0] - 2016-11-27
### Added
- Lots of (empty) tests
- Expanded transaction lists ([issue 377](https://github.com/firefly-iii/firefly-iii/issues/377))
- New charts at account view
- First code for [issue 305](https://github.com/firefly-iii/firefly-iii/issues/305)


### Changed
- Updated all email messages.
- Made some fonts local

### Fixed
- [Issue 408](https://github.com/firefly-iii/firefly-iii/issues/408)
- Various issues with split journals
- [Issue 414](https://github.com/firefly-iii/firefly-iii/issues/414), thx [zjean](https://github.com/zjean)
- [Issue 419](https://github.com/firefly-iii/firefly-iii/issues/419), thx [schwalberich](https://github.com/schwalberich) 
- [Issue 422](https://github.com/firefly-iii/firefly-iii/issues/422), thx [xzaz](https://github.com/xzaz)
- Various import bugs, such as [issue 416](https://github.com/firefly-iii/firefly-iii/issues/416) ([zjean](https://github.com/zjean))

## [4.1.7] - 2016-11-19
### Added
- Check for database table presence in console commands.
- Category report
- Reinstated old test routines.


### Changed
- Confirm account setting is no longer in `.env` file.
- Titles are now in reverse (current page > parent > firefly iii)
- Easier update of language files thanks to Github implementation.
- Uniform colours for charts.

### Fixed
- Made all pages more mobile friendly.
- Fixed [issue 395](https://github.com/firefly-iii/firefly-iii/issues/395) found by [marcoveeneman](https://github.com/marcoveeneman).
- Fixed [issue 398](https://github.com/firefly-iii/firefly-iii/issues/398) found by [marcoveeneman](https://github.com/marcoveeneman).
- Fixed [issue 401](https://github.com/firefly-iii/firefly-iii/issues/401) found by [marcoveeneman](https://github.com/marcoveeneman).
- Many optimizations.
- Updated many libraries.
- Various bugs found by myself.


## [4.1.6] - 2016-11-06
### Added
- New budget table for multi year report.

### Changed
- Greatly expanded help pages and their function.
- Built a new transaction collector, which I think was the idea of [roberthorlings](https://github.com/roberthorlings) originally.
- Rebuilt seach engine.

### Fixed
- [Issue 375](https://github.com/firefly-iii/firefly-iii/issues/375), thanks to [schoentoon](https://github.com/schoentoon) which made it impossible to resurrect currencies.
- [Issue 370](https://github.com/firefly-iii/firefly-iii/issues/370) thanks to [ksmolder](https://github.com/ksmolder)
- [Issue 378](https://github.com/firefly-iii/firefly-iii/issues/378), thanks to [HomelessAvatar](https://github.com/HomelessAvatar)

## [4.1.5] - 2016-11-01
### Changed
- Report parts are loaded using AJAX, making a lot of code more simple.
- Help content will fall back to English.
- Help content is translated through Crowdin.

### Fixed
- [Issue 370](https://github.com/firefly-iii/firefly-iii/issues/370)

## [4.1.4] - 2016-10-30
### Added
- New Dockerfile thanks to [schoentoon](https://github.com/schoentoon)
- Added changing the destination account as rule action.
- Added changing the source account as rule action.
- Can convert transactions into different types.

### Changed
- Changed the export routine to be more future-proof.
- Improved help routine.
- Integrated CrowdIn translations.
- Simplified reports
- Change error message to refer to solution.

### Fixed
- [Issue 367](https://github.com/firefly-iii/firefly-iii/issues/367) thanks to [HungryFeline](https://github.com/HungryFeline)
- [Issue 366](https://github.com/firefly-iii/firefly-iii/issues/366) thanks to [3mz3t](https://github.com/3mz3t)
- [Issue 362](https://github.com/firefly-iii/firefly-iii/issues/362) and [issue 341](https://github.com/firefly-iii/firefly-iii/issues/341) thanks to [bnw](https://github.com/bnw)
- [Issue 355](https://github.com/firefly-iii/firefly-iii/issues/355) thanks to [roberthorlings](https://github.com/roberthorlings)

## [4.1.3] - 2016-10-22
### Fixed
- Some event handlers called the wrong method.

## [4.1.2] - 2016-10-22

### Fixed
- A bug is fixed in the journal event handler that prevented Firefly III from actually storing journals.

## [4.1.1] - 2016-10-22

### Added
- Option to show deposit accounts on the front page.
- Script to upgrade split transactions
- Can now save notes on piggy banks.
- Extend user admin options.
- Run import jobs from the command line


### Changed
- New preferences screen layout.

### Deprecated
- ``firefly:import`` is now ``firefly:start-import``

### Removed
- Lots of old code

### Fixed
- [Issue 357](https://github.com/firefly-iii/firefly-iii/issues/357), where non utf-8 files would break Firefly.
- Tab delimiter is not properly loaded from import configuration ([roberthorlings](https://github.com/roberthorlings))
- System response to yearly bills

## [4.0.2] - 2016-10-14
### Added
- Added ``intl`` dependency to composer file to ease installation (thanks [telyn](https://github.com/telyn))
- Added support for Croatian.

### Changed
- Updated all copyright notices to refer to the [Creative Commons Attribution-ShareAlike 4.0 International License](https://creativecommons.org/licenses/by-sa/4.0/)
- Fixed [issue 344](https://github.com/firefly-iii/firefly-iii/issues/344)
- Fixed [issue 346](https://github.com/firefly-iii/firefly-iii/issues/346), thanks to [SanderKleykens](https://github.com/SanderKleykens)
- [Issue 351](https://github.com/firefly-iii/firefly-iii/issues/351)
- Did some internal remodelling.

### Fixed
- PostgreSQL compatibility thanks to [SanderKleykens](https://github.com/SanderKleykens)
- [roberthorlings](https://github.com/roberthorlings) fixed a bug in the ABN Amro import specific.


## [4.0.1] - 2016-10-04
### Added
- New ING import specific by [tomwerf](https://github.com/tomwerf)
- New Presidents Choice specific to fix [issue 307](https://github.com/firefly-iii/firefly-iii/issues/307)
- Added some trimming ([issue 335](https://github.com/firefly-iii/firefly-iii/issues/335))

### Fixed
- Fixed a bug where incoming transactions would not be properly filtered in several reports.
- [Issue 334](https://github.com/firefly-iii/firefly-iii/issues/334) by [cyberkov](https://github.com/cyberkov)
- [Issue 337](https://github.com/firefly-iii/firefly-iii/issues/337)
- [Issue 336](https://github.com/firefly-iii/firefly-iii/issues/336)
- [Issue 338](https://github.com/firefly-iii/firefly-iii/issues/338) found by [roberthorlings](https://github.com/roberthorlings)

## [4.0.0] - 2016-09-26
### Added
- Upgraded to Laravel 5.3, most other libraries upgraded as well.
- Added GBP as currency, thanks to [Mortalife](https://github.com/Mortalife)

### Changed
- Jump to version 4.0.0.
- Firefly III is now subject to a [Creative Commons Attribution-ShareAlike 4.0 International License](https://creativecommons.org/licenses/by-sa/4.0/) license. Previous versions of this software are still MIT licensed.

### Fixed
- Support for specific decimal places, thanks to [Mortalife](https://github.com/Mortalife)
- Various CSS fixes
- Various bugs, thanks to [fuf](https://github.com/fuf), [sandermulders](https://github.com/sandermulders) and [vissert](https://github.com/vissert)
- Various queries optimized for MySQL 5.7

## [3.10.4] - 2016-09-14
### Fixed
- Migration fix by [sandermulders](https://github.com/sandermulders)
- Tricky import bug fix thanks to [vissert](https://github.com/vissert)
- Currency preference will be correctly pulled from user settings, thanks to [fuf](https://github.com/fuf)
- Simplified code for upgrade instructions.


## [3.10.3] - 2016-08-29
### Added
- More fields for mass-edit, thanks to [vissert](https://github.com/vissert) ([issue 282](https://github.com/firefly-iii/firefly-iii/issues/282))
- First start of German translation

### Changed
- More optional fields for transactions and the ability to filter them.

### Removed
- Preference for budget maximum.

### Fixed
- A bug in the translation routine broke the import.
- It was possible to destroy your Firefly installation by removing all currencies. Thanks [mondjef](https://github.com/mondjef)
- Translation bugs.
- Import bug.

### Security
- Firefly will not accept registrations beyond the first one, by default.


## [3.10.2] - 2016-08-29
### Added
- New Chinese translations. Set Firefly III to show incomplete translations to follow the progress. Want to translate Firefly III in Chinese, or in any other language? Then check out [the Crowdin project](https://crowdin.com/project/firefly-iii).
- Added more admin pages. They do nothing yet.

### Changed
- Import routine will now also apply user rules.
- Various code cleanup.
- Some small HTML changes.

### Fixed
- Bug in the mass edit routines.
- Firefly III over a proxy will now work (see [issue 290](https://github.com/firefly-iii/firefly-iii/issues/290), thanks [dfiel](https://github.com/dfiel) for reporting.
- Sneaky bug in the import routine, fixed by [Bonno](https://github.com/Bonno) 

## [3.10.1] - 2016-08-25
### Added
- More feedback in the import procedure.
- Extended model for import job.
- Web bases import procedure.


### Changed
- Scrutinizer configuration
- Various code clean up.

### Removed
- Code climate YAML file.

### Fixed
- Fixed a bug where a migration would check an empty table name.
- Fixed various bugs in the import routine.
- Fixed various bugs in the piggy banks pages.
- Fixed a bug in the `firefly:verify` routine

## [3.10] - 2016-08-12
### Added
- New charts in year report
- Can add / remove money from piggy bank on mobile device.
- Bill overview shows some useful things.
- Firefly will track registration / activation IP addresses.


### Changed
- Rewrote the import routine.
- The date picker now supports more ranges and periods.
- Rewrote all migrations. [issue 272](https://github.com/firefly-iii/firefly-iii/issues/272)

### Fixed
- [Issue 264](https://github.com/firefly-iii/firefly-iii/issues/264)
- [Issue 265](https://github.com/firefly-iii/firefly-iii/issues/265)
- Fixed amount calculation problems, [issue 266](https://github.com/firefly-iii/firefly-iii/issues/266), thanks [xzaz](https://github.com/xzaz)
- [Issue 271](https://github.com/firefly-iii/firefly-iii/issues/271)
- [Issue 278](https://github.com/firefly-iii/firefly-iii/issues/278), [issue 273](https://github.com/firefly-iii/firefly-iii/issues/273), thanks [StevenReitsma](https://github.com/StevenReitsma) and [rubella](https://github.com/rubella)
- Bug in attachment download routine would report the wrong size to the user's browser.
- Various NULL errors fixed.
- Various strict typing errors fixed.
- Fixed pagination problems, [issue 276](https://github.com/firefly-iii/firefly-iii/issues/276), thanks [xzaz](https://github.com/xzaz)
- Fixed a bug where an expense would be assigned to a piggy bank if you created a transfer first.
- Bulk update problems, [issue 280](https://github.com/firefly-iii/firefly-iii/issues/280), thanks [stickgrinder](https://github.com/stickgrinder)
- Fixed various problems with amount reporting of split transactions.

## [3.9.1] - 2016-06-06
### Fixed
- Fixed a bug where removing money from a piggy bank would not work. See [issue 265](https://github.com/firefly-iii/firefly-iii/issues/265) and [issue 269](https://github.com/firefly-iii/firefly-iii/issues/269)

## [3.9.0] - 2016-05-22
### Added
- [zjean](https://github.com/zjean) has added code that allows you to force "https://"-URL's.
- [tonicospinelli](https://github.com/tonicospinelli) has added Portuguese (Brazil) translations.
- Firefly III supports the *splitting* of transactions:
  - A withdrawal (expense) can be split into multiple sub-transactions (with multiple destinations)
  - Likewise for deposits (incomes). You can set multiple sources.
  - Likewise for transfers.

### Changed
- Update a lot of libraries.
- Big improvement to test data generation.
- Cleaned up many repositories.

### Removed
- Front page boxes will no longer respond to credit card bills.

### Fixed
- Many bugs

## [3.8.4] - 2016-04-24
### Added
- Lots of new translations.
- Can now set page size.
- Can now mass edit transactions.
- Can now mass delete transactions.
- Firefly will now attempt to verify the integrity of your database when updating.

### Changed
- New version of Charts library.

### Fixed
- Several CSV related bugs.
- Several other bugs.
- Bugs fixed by [Bonno](https://github.com/Bonno).

## [3.8.3] - 2016-04-17
### Added
- New audit report to see what happened.

### Changed
- New Chart JS release used.
- Help function is more reliable.

### Fixed
- Expected bill amount is now correct.
- Upgrade will now invalidate cache.
- Search was broken.
- Queries run better

## [3.8.2] - 2016-04-03
### Added
- Small user administration at /admin.
- Informational popups are working in reports.

### Changed
- User activation emails are better

### Fixed
- Some bugs related to accounts and rules.


## [3.8.1] - 2016-03-29
### Added
- More translations
- Extended cookie control.
- User accounts can now be activated (disabled by default).
- Bills can now take the source and destination account name into account.

### Changed
- The pages related to rules have new URL's.

### Fixed
- Spelling errors.
- Problems related to the "account repository".
- Some views showed empty (0.0) amounts.

## [3.8.0] - 2016-03-20
### Added
- Two factor authentication, thanks to the excellent work of [zjean](https://github.com/zjean).
- A new chart showing your net worth in year and multi-year reports.
- You can now see if your current or future rules actually match any transactions, thanks to the excellent work of [roberthorlings](https://github.com/roberthorlings).
- New date fields for transactions. They are not used yet in reports or anything, but they can be filled in.
- New routine to export your data.
- Firefly III will mail the site owner when blocked users try to login, or when blocked domains are used in registrations.


### Changed
- Firefly III now requires PHP 7.0 minimum.


### Fixed
- HTML fixes, thanks to [roberthorlings](https://github.com/roberthorlings) and [zjean](https://github.com/zjean)..
- A bug fix in the ABN Amro importer, thanks to [roberthorlings](https://github.com/roberthorlings)
- It was not possible to change the opening balance, once it had been set. Thanks to [xnyhps](https://github.com/xnyhps) and [marcoveeneman](https://github.com/marcoveeneman) for spotting this.
- Various other bug fixes.



## [3.4.2] - 2015-05-25
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
- Initial release