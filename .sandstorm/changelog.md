# 4.8.1.3

- [Issue 2680](https://github.com/firefly-iii/firefly-iii/issues/2680) Upgrade routine would delete all transaction groups.

# 4.8.1.2

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

# 4.8.1.1

- Add some sensible maximum amounts to form inputs.
- [Issue 2561](https://github.com/firefly-iii/firefly-iii/issues/2561) Fixes a query error on the /tags page that affected some MySQL users.
- [Issue 2563](https://github.com/firefly-iii/firefly-iii/issues/2563) Two destination fields when editing a recurring transaction.
- [Issue 2564](https://github.com/firefly-iii/firefly-iii/issues/2564) Ability to browse pages in the search results.
- [Issue 2573](https://github.com/firefly-iii/firefly-iii/issues/2573) Could not submit an transaction update after an error was corrected.
- [Issue 2577](https://github.com/firefly-iii/firefly-iii/issues/2577) Upgrade routine would wrongly store the categories of split transactions.
- [Issue 2590](https://github.com/firefly-iii/firefly-iii/issues/2590) Fix an issue in the audit report.
- [Issue 2592](https://github.com/firefly-iii/firefly-iii/issues/2592) Fix an issue with YNAB import.
- [Issue 2597](https://github.com/firefly-iii/firefly-iii/issues/2597) Fix an issue where users could not delete currencies.

# 4.8.1 (API 0.10.2)

- Firefly III 4.8.1 requires PHP 7.3.
- Support for Greek
- [Issue 2383](https://github.com/firefly-iii/firefly-iii/issues/2383) Some tables in reports now also report percentages.
- [Issue 2389](https://github.com/firefly-iii/firefly-iii/issues/2389) Add category / budget information to transaction lists.
- [Issue 2464](https://github.com/firefly-iii/firefly-iii/issues/2464) Can now search for tag.
- [Issue 2466](https://github.com/firefly-iii/firefly-iii/issues/2466) Can order recurring transactions in a more useful manner.
- [Issue 2497](https://github.com/firefly-iii/firefly-iii/issues/2497) Transaction creation moment in hover of tag title.
- [Issue 2471](https://github.com/firefly-iii/firefly-iii/issues/2471) Added date tag to table cells.
- [Issue 2291](https://github.com/firefly-iii/firefly-iii/issues/2291) All reports are now properly multi-currency.
- [Issue 2481](https://github.com/firefly-iii/firefly-iii/issues/2481) As part of the removal of local encryption, uploads and imports are no longer encrypted.
- [Issue 2495](https://github.com/firefly-iii/firefly-iii/issues/2495) A better message of transaction submission.
- [Issue 2506](https://github.com/firefly-iii/firefly-iii/issues/2506) Some bugs in tag report fixed.
- [Issue 2510](https://github.com/firefly-iii/firefly-iii/issues/2510) All transaction descriptions cut off at 255 chars.
- Better sum in bill view.
- Clean up docker files for flawless operation.
- The bunq API has changed, and support for bunq has been disabled.
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
- [Issue 2475](https://github.com/firefly-iii/firefly-iii/issues/2475) Tags are now the same for all views.
- [Issue 2476](https://github.com/firefly-iii/firefly-iii/issues/2476) Amount is now represented equally in all views.
- [Issue 2477](https://github.com/firefly-iii/firefly-iii/issues/2477) Rules are easier to update.
- [Issue 2483](https://github.com/firefly-iii/firefly-iii/issues/2483) Several consistencies fixed.
- [Issue 2484](https://github.com/firefly-iii/firefly-iii/issues/2484) Transaction link view fixed.
- [Issue 2557](https://github.com/firefly-iii/firefly-iii/issues/2557) Fix for issue in summary API
- No longer have to submit mandatory fields to account end point. Just submit the field you wish to update, the rest will be untouched.
- Rules will no longer list the "user-action" trigger Rules will have a "moment" field that says either "update-journal" or "store-journal".

# 4.8.0.3 (API 0.10.1)

- Autocomplete for transaction description.
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
- Improvements to various API end-points. Docs are updated.

# 4.8.0.2 (API 0.10.0)

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

# 4.8.0.1 (API 0.10.0)

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

# 4.8.0 (API 0.10.0)

- Hungarian translation!
- New database model that changes the concept of "split transactions";
- New installation routine with rewritten database integrity tests and upgrade code;
- Rewritten screen to create transactions which will now completely rely on the API;
- Most terminal commands now have the prefix `firefly-iii`.
- New MFA code that will generate backup codes for you and is more robust. MFA will have to be re-enabled for ALL users.
- This will probably be the last Firefly III version to have import routines for files, Bunq and others. These will be moved to separate applications that use the Firefly III API.
- The export function has been removed.
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
- Updated API to reflect the changes in the database.
- New API end-point for a summary of your data.
- Some new API charts.

# 4.7.17.6 (API 0.9.2)

- XSS issue in liability account redirect, found by [@0x2500](https://github.com/0x2500).

# 4.7.17.5 (API 0.9.2)

- Several XSS issues, found by [@0x2500](https://github.com/0x2500).

# 4.7.17.4 (API 0.9.2)

- Several XSS issues, found by [@0x2500](https://github.com/0x2500).

# 4.7.17.3 (API 0.9.2)

- XSS bug in file uploads (x2), found by [@dayn1ne](https://github.com/dayn1ne).
- XSS bug in search, found by [@dayn1ne](https://github.com/dayn1ne).

# 4.7.17.2 (API 0.9.2)
- XSS bug in budget title, found by [@dayn1ne](https://github.com/dayn1ne).

# 4.7.17 (API 0.9.2)
- Support for Norwegian!
- Clear cache during install routine.
- Add Firefly III version number to install routine.
- Initial release.
- [Issue 2159](https://github.com/firefly-iii/firefly-iii/issues/2159) Bad redirect due to Laravel upgrade.
- [Issue 2166](https://github.com/firefly-iii/firefly-iii/issues/2166) Importer had some issues with distinguishing double transfers.
- [Issue 2167](https://github.com/firefly-iii/firefly-iii/issues/2167) New LDAP package gave some configuration changes.
- [Issue 2173](https://github.com/firefly-iii/firefly-iii/issues/2173) Missing class when generating 2FA codes.

# 4.7.16 (API 0.9.2)
- 4.7.16 was released to fix a persistent issue with broken user preferences.
- Firefly III now uses Laravel 5.8

# 4.7.15 (API 0.9.2)
- 4.7.15 was released to fix some issues upgrading from older versions.
- [Issue 2128](https://github.com/firefly-iii/firefly-iii/issues/2128) Support for Postgres SSL
- [Issue 2120](https://github.com/firefly-iii/firefly-iii/issues/2120) Add a missing meta tag, thanks to @lastlink
- Search is a lot faster now.
- [Issue 2125](https://github.com/firefly-iii/firefly-iii/issues/2125) Decryption issues during upgrade
- [Issue 2130](https://github.com/firefly-iii/firefly-iii/issues/2130) Fixed database migrations and rollbacks.
- [Issue 2135](https://github.com/firefly-iii/firefly-iii/issues/2135) Date fixes in transaction overview

# 4.7.14 (API 0.9.2)
- 4.7.14 was released to fix an issue with the Composer installation script.

# 4.7.13 (API 0.9.2)
- 4.7.13 was released to fix an issue that affected the Softaculous build.
- A routine has been added that warns about transactions with a 0.00 amount.
- PHP maximum execution time is now 600 seconds in the Docker image.
- Moved several files outside of the root of Firefly III
- Fix issue where missing preference breaks the database upgrade.
- [Issue 2100](https://github.com/firefly-iii/firefly-iii/issues/2100) Mass edit transactions results in a reset of the date.

# 4.7.12
- 4.7.12 was released to fix several shortcomings in v4.7.11's Docker image. Those in turn were caused by me. My apologies.
- [Issue 2085](https://github.com/firefly-iii/firefly-iii/issues/2085) Upgraded the LDAP code. To keep using LDAP, set the `LOGIN_PROVIDER` to `ldap`.
- [Issue 2061](https://github.com/firefly-iii/firefly-iii/issues/2061) Some users reported empty update popups. 
- [Issue 2070](https://github.com/firefly-iii/firefly-iii/issues/2070) A cache issue prevented rules from being applied correctly.
- [Issue 2071](https://github.com/firefly-iii/firefly-iii/issues/2071) Several issues with Postgres and date values with time zone information in them.
- [Issue 2081](https://github.com/firefly-iii/firefly-iii/issues/2081) Rules were not being applied when importing using FinTS.
- [Issue 2082](https://github.com/firefly-iii/firefly-iii/issues/2082) The mass-editor changed all dates to today.

# 4.7.11
- Experimental audit logging channel to track important events (separate from debug logging).
- [Issue 2003](https://github.com/firefly-iii/firefly-iii/issues/2003), [issue 2006](https://github.com/firefly-iii/firefly-iii/issues/2006) Transactions can be stored with a timestamp. The user-interface does not support this yet. But the API does.
- Docker image tags a new manifest for arm and amd64.
- [skuzzle](https://github.com/skuzzle) removed an annoying console.log statement.
- [Issue 2048](https://github.com/firefly-iii/firefly-iii/issues/2048) Fix "Are you sure?" popup, thanks to @nescafe2002!
- [Issue 2049](https://github.com/firefly-iii/firefly-iii/issues/2049) Empty preferences would crash Firefly III.
- [Issue 2052](https://github.com/firefly-iii/firefly-iii/issues/2052) Rules could not auto-covert to liabilities.
- Webbased upgrade routine will also decrypt the database.
- Last use date for categories was off.
- The `date`-field in any transaction object now returns a ISO 8601 timestamp instead of a date.

# 4.7.10
- [Issue 2037](https://github.com/firefly-iii/firefly-iii/issues/2037) Added some new magic keywords to reports.
- Added a new currency exchange rate service, [ratesapi.io](https://ratesapi.io/), that does not require expensive API keys. Built by [@BoGnY](https://github.com/BoGnY).
- Added Chinese Traditional translations. Thanks!
- [Issue 1977](https://github.com/firefly-iii/firefly-iii/issues/1977) Docker image now includes memcached support
- [Issue 2031](https://github.com/firefly-iii/firefly-iii/issues/2031) A new generic debit/credit indicator for imports.
- The new Docker image no longer has the capability to run cron jobs, and will no longer generate your recurring transactions for you. This has been done to simplify the build and make sure your Docker container runs one service, as it should. To set up a cron job for your new Docker container, [check out the documentation](https://docs.firefly-iii.org/en/latest/installation/cronjob.html).
- Due to a change in the database structure, this upgrade will reset your preferences. Sorry about that.
- I will no longer accept PR's that introduce new currencies.
- Firefly III no longer encrypts the database and will [decrypt the database]() on its first run.
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
- OAuth2 form can now submit back to original requester.
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

# 4.7.9
- [Issue 1622](https://github.com/firefly-iii/firefly-iii/issues/1622) Can now unlink a transaction from a bill.
- [Issue 1848](https://github.com/firefly-iii/firefly-iii/issues/1848) Added support for the Swiss Franc.
- [Issue 1828](https://github.com/firefly-iii/firefly-iii/issues/1828) Focus on fields for easy access.
- [Issue 1859](https://github.com/firefly-iii/firefly-iii/issues/1859) Warning when seeding database.
- Completely rewritten API. Check out the documentation [here](https://api-docs.firefly-iii.org/).
- Currencies can now be enabled and disabled, making for cleaner views.
- You can disable the `X-Frame-Options` header if this is necessary.
- New fancy favicons.
- Updated and improved docker build.
- Docker build no longer builds its own cURL.
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

# 4.7.8

- [Issue 1005](https://github.com/firefly-iii/firefly-iii/issues/1005) You can now configure Firefly III to use LDAP. 
- [Issue 1071](https://github.com/firefly-iii/firefly-iii/issues/1071) You can execute transaction rules using the command line (so you can cronjob it)
- [Issue 1108](https://github.com/firefly-iii/firefly-iii/issues/1108) You can now reorder budgets.
- [Issue 1159](https://github.com/firefly-iii/firefly-iii/issues/1159) The ability to import transactions from FinTS-enabled banks.
- [Issue 1727](https://github.com/firefly-iii/firefly-iii/issues/1727) You can now use SFTP as storage for uploads and exports.
- [Issue 1733](https://github.com/firefly-iii/firefly-iii/issues/1733) You can configure Firefly III not to send emails with transaction information in them.
- [Issue 1040](https://github.com/firefly-iii/firefly-iii/issues/1040) Fixed various things that would not scale properly in the past.
- [Issue 1771](https://github.com/firefly-iii/firefly-iii/issues/1771) A link to the transaction that fits the bill.
- [Issue 1800](https://github.com/firefly-iii/firefly-iii/issues/1800) Icon updated to match others.
- MySQL database connection now forces the InnoDB to be used.
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

# 4.7.7
- [Issue 954](https://github.com/firefly-iii/firefly-iii/issues/954) Some additional view chart ranges
- [Issue 1710](https://github.com/firefly-iii/firefly-iii/issues/1710) Added a new currency ([hamuz](https://github.com/hamuz)) 
- Transactions will now store (in the database) how they were created.
- [Issue 907](https://github.com/firefly-iii/firefly-iii/issues/907) Better and more options on the transaction list.
- [Issue 1450](https://github.com/firefly-iii/firefly-iii/issues/1450) Add a rule to change the type of a transaction automagically
- [Issue 1701](https://github.com/firefly-iii/firefly-iii/issues/1701) Fix reference to PHP executable ([hertzg](https://github.com/hertzg))
- Budget limits have currency information, for future expansion.
- Some charts and pages can handle multiple currencies better.
- New GA code for those who use it.
- The credit card liability type has been removed.
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
- Secure headers now allow Mapbox and the 2FA QR code.

# 4.7.6.2
- Docker file builds again.
- Fix CSS of OAuth2 authorization view.

# 4.7.6.1
- An issue where I switched variables from the Docker `.env` file to the normal `.env` file and vice versa -- breaking both.
- [Issue 1649](https://github.com/firefly-iii/firefly-iii/issues/1649) 2FA QR code would not show up due to very strict security policy headers
- Docker build gave a cURL error whenever it runs PHP commands.

# 4.7.6
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
- This will be the last release on PHP 7.1. Future versions will require PHP 7.2.
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
- Add `.htaccess` files to all public directories.
- New secure headers will make Firefly III slightly more secure.
- The rule "tester" will now also take the "strict"-checkbox into account.

# 4.7.5.3
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

# 4.7.5.1
- [Issue 1531](https://github.com/firefly-iii/firefly-iii/issues/1531), the database routine incorrectly reports empty categories.
- [Issue 1532](https://github.com/firefly-iii/firefly-iii/issues/1532), broken dropdown for autosuggest things.
- [Issue 1533](https://github.com/firefly-iii/firefly-iii/issues/1533), fix where the import could not import category names.
- [Issue 1538](https://github.com/firefly-iii/firefly-iii/issues/1538), fix a bug where Spectre would not work when ignoring rules.
- [Issue 1542](https://github.com/firefly-iii/firefly-iii/issues/1542), fix a bug where the importer was incapable of generating new currencies.
- [Issue 1541](https://github.com/firefly-iii/firefly-iii/issues/1541), no longer ignore composer.lock in Docker ignore.
- Bills are stored inactive.

# 4.7.5
- A new feature called "recurring transactions" that will make Firefly III automatically create transactions for you.
- New API end points for attachments, available budgets, budgets, budget limits, categories, configuration, currency exchange rates, journal links, link types, piggy banks, preferences, recurring transactions, rules, rule groups and tags.
- Added support for YunoHost.
- The 2FA secret is visible so you can type it into 2FA apps.
- Bunq and Spectre imports will now ask to apply rules.
- Sandstorm users can now make API keys.
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

# 4.7.4
- [Issue 1409](https://github.com/firefly-iii/firefly-iii/issues/1409), add Indian Rupee and explain that users can do this themselves [issue 1413](https://github.com/firefly-iii/firefly-iii/issues/1413)
- [Issue 1445](https://github.com/firefly-iii/firefly-iii/issues/1445), upgrade Curl in Docker image.
- [Issue 1386](https://github.com/firefly-iii/firefly-iii/issues/1386), quick links to often used pages.
- [Issue 1405](https://github.com/firefly-iii/firefly-iii/issues/1405), show proposed amount to piggy banks.
- [Issue 1416](https://github.com/firefly-iii/firefly-iii/issues/1416), ability to delete lost attachments.
- A completely rewritten import routine that can handle bunq (thanks everybody for testing!), CSV files and Spectre. Please make sure you read about this at http://bit.ly/FF3-new-import
- [Issue 1392](https://github.com/firefly-iii/firefly-iii/issues/1392), explicitly mention rules are inactive (when they are).
- [Issue 1406](https://github.com/firefly-iii/firefly-iii/issues/1406), bill conversion to rules will be smarter about the rules they create.
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
- [Issue 1415](https://github.com/firefly-iii/firefly-iii/issues/1415), will email you when OAuth2 keys are generated.

# 4.7.3.2
- Forgot to increase the version number :(.

# 4.7.3.1
- Fixed a critical bug where the rules-engine would fire inadvertently.

# 4.7.3
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
- Improved edit routine for split transactions.
- Upgrade routine can handle `proc_close` being disabled.
- Bills now use rules to match transactions, making it more flexible.
- [Issue 1328](https://github.com/firefly-iii/firefly-iii/issues/1328): piggy banks no have a more useful chart.
- Spectre API upgraded to v4
- Move to MariaDB ([issue 1366](https://github.com/firefly-iii/firefly-iii/issues/1366))
- Piggy banks take currency from parent account ([issue 1334](https://github.com/firefly-iii/firefly-iii/issues/1334))
- [Issue 1341](https://github.com/firefly-iii/firefly-iii/issues/1341): Removed depricated command from dockerfile
- Several issues with docker image ([issue 1320](https://github.com/firefly-iii/firefly-iii/issues/1320), [issue 1382](https://github.com/firefly-iii/firefly-iii/issues/1382)).
- Fix giant tags and division by zero ([issue 1325](https://github.com/firefly-iii/firefly-iii/issues/1325) and others)
- Several issues with bunq import ([issue 1352](https://github.com/firefly-iii/firefly-iii/issues/1352), [issue 1330](https://github.com/firefly-iii/firefly-iii/issues/1330), [issue 1378](https://github.com/firefly-iii/firefly-iii/issues/1378), [issue 1380](https://github.com/firefly-iii/firefly-iii/issues/1380))
- [Issue 1246](https://github.com/firefly-iii/firefly-iii/issues/1246): date picker is internationalised
- [Issue 1327](https://github.com/firefly-iii/firefly-iii/issues/1327): fix formattting issues in piggy banks
- [Issue 1348](https://github.com/firefly-iii/firefly-iii/issues/1348): 500 error in API
- [Issue 1349](https://github.com/firefly-iii/firefly-iii/issues/1349): Errors in import routine
- Several fixes for (multi-currency) reconciliation ([issue 1336](https://github.com/firefly-iii/firefly-iii/issues/1336), [issue 1363](https://github.com/firefly-iii/firefly-iii/issues/1363))
- [Issue 1353](https://github.com/firefly-iii/firefly-iii/issues/1353): return NULL values in range-indicator
- Bug in split transaction edit routine
- Piggy bank percentage was very specific.
- Logging in Slack is easier to config.
- [Issue 1312](https://github.com/firefly-iii/firefly-iii/issues/1312) Import broken for ING accounts
- [Issue 1313](https://github.com/firefly-iii/firefly-iii/issues/1313) Error when creating new asset account
- [Issue 1317](https://github.com/firefly-iii/firefly-iii/issues/1317) Forgot an include :(
- Null pointer exception in transaction overview.
- Installations running in subdirs were incapable of creating OAuth tokens.
- OAuth keys were not created in all cases.

# 4.7.2
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
- Fixed an issue with token validation on the command line.

# 4.7.1
- A brand new API. Read about it in the [documentation](http://firefly-iii.readthedocs.io/en/latest/).
- Add support for Spanish. [issue 1194](https://github.com/firefly-iii/firefly-iii/issues/1194)
- Some custom preferences are selected by default for a better user experience.
- Some new currencies [issue 1211](https://github.com/firefly-iii/firefly-iii/issues/1211)
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

# 4.7.0
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
- Whole new [read me file](https://github.com/firefly-iii/firefly-iii/blob/master/readme.md), [new end user documentation](https://firefly-iii.readthedocs.io/en/latest/) and an [updated website](https://www.firefly-iii.org/)!
- Many charts and info-blocks now scale property ([issue 989](https://github.com/firefly-iii/firefly-iii/issues/989) and [issue 1040](https://github.com/firefly-iii/firefly-iii/issues/1040))
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
- Removed many access rights from the demo user

# 4.6.13
- [Issue 1074](https://github.com/firefly-iii/firefly-iii/issues/1074), suggested by [MacPaille](https://github.com/MacPaille)
- [Issue 1077](https://github.com/firefly-iii/firefly-iii/issues/1077), suggested by [wtercato](https://github.com/wtercato)
- Bulk edit of transactions thanks to [vicmosin](https://github.com/vicmosin) ([issue 1078](https://github.com/firefly-iii/firefly-iii/issues/1078))
- Support for Turkish.
- [Issue 1090](https://github.com/firefly-iii/firefly-iii/issues/1090), suggested by [Findus23](https://github.com/Findus23)
- [Issue 1097](https://github.com/firefly-iii/firefly-iii/issues/1097), suggested by [kelvinhammond](https://github.com/kelvinhammond)
- [Issue 1093](https://github.com/firefly-iii/firefly-iii/issues/1093), suggested by [jinformatique](https://github.com/jinformatique)
- [Issue 1098](https://github.com/firefly-iii/firefly-iii/issues/1098), suggested by [Nik-vr](https://github.com/Nik-vr)
- [Issue 972](https://github.com/firefly-iii/firefly-iii/issues/972), reported by [pjotrvdh](https://github.com/pjotrvdh)
- [Issue 1079](https://github.com/firefly-iii/firefly-iii/issues/1079), reported by [gavu](https://github.com/gavu)
- [Issue 1080](https://github.com/firefly-iii/firefly-iii/issues/1080), reported by [zjean](https://github.com/zjean)
- [Issue 1083](https://github.com/firefly-iii/firefly-iii/issues/1083), reported by [skuzzle](https://github.com/skuzzle)
- [Issue 1085](https://github.com/firefly-iii/firefly-iii/issues/1085), reported by [nicoschreiner](https://github.com/nicoschreiner)
- [Issue 1087](https://github.com/firefly-iii/firefly-iii/issues/1087), reported by [4oo4](https://github.com/4oo4)
- [Issue 1089](https://github.com/firefly-iii/firefly-iii/issues/1089), reported by [robin5210](https://github.com/robin5210)
- [Issue 1092](https://github.com/firefly-iii/firefly-iii/issues/1092), reported by [kelvinhammond](https://github.com/kelvinhammond)
- [Issue 1096](https://github.com/firefly-iii/firefly-iii/issues/1096), reported by [wtercato](https://github.com/wtercato)

# 4.6.12
- Support for Indonesian.
- New report, see [issue 384](https://github.com/firefly-iii/firefly-iii/issues/384)
- [Issue 964](https://github.com/firefly-iii/firefly-iii/issues/964) as suggested by [gavu](https://github.com/gavu)
- Greatly improved Docker support and documentation.
- [Issue 1046](https://github.com/firefly-iii/firefly-iii/issues/1046), as reported by [pkoziol](https://github.com/pkoziol)
- [Issue 1047](https://github.com/firefly-iii/firefly-iii/issues/1047), as reported by [pkoziol](https://github.com/pkoziol)
- [Issue 1048](https://github.com/firefly-iii/firefly-iii/issues/1048), as reported by [webence](https://github.com/webence)
- [Issue 1049](https://github.com/firefly-iii/firefly-iii/issues/1049), as reported by [nicoschreiner](https://github.com/nicoschreiner) 
- [Issue 1015](https://github.com/firefly-iii/firefly-iii/issues/1015), as reporterd by a user on Tweakers.net
- [Issue 1056](https://github.com/firefly-iii/firefly-iii/issues/1056), as reported by [repercussion](https://github.com/repercussion) 
- [Issue 1061](https://github.com/firefly-iii/firefly-iii/issues/1061), as reported by [Meizikyn](https://github.com/Meizikyn)
- [Issue 1045](https://github.com/firefly-iii/firefly-iii/issues/1045), as reported by [gavu](https://github.com/gavu)
- First code for [issue 1040](https://github.com/firefly-iii/firefly-iii/issues/1040) ([simonsmiley](https://github.com/simonsmiley))
- [Issue 1059](https://github.com/firefly-iii/firefly-iii/issues/1059), as reported by [4oo4](https://github.com/4oo4)
- [Issue 1063](https://github.com/firefly-iii/firefly-iii/issues/1063), as reported by [pkoziol](https://github.com/pkoziol)
- [Issue 1064](https://github.com/firefly-iii/firefly-iii/issues/1064), as reported by [pkoziol](https://github.com/pkoziol)
- [Issue 1066](https://github.com/firefly-iii/firefly-iii/issues/1066), reported by [wtercato](https://github.com/wtercato)

# 4.6.1.1
- Import routine can scan for matching bills, [issue 956](https://github.com/firefly-iii/firefly-iii/issues/956)
- Import will no longer scan for rules, this has become optional. Originally suggested in [issue 956](https://github.com/firefly-iii/firefly-iii/issues/956) by [gavu](https://github.com/gavu) 
- [Issue 1033](https://github.com/firefly-iii/firefly-iii/issues/1033), as reported by [Jumanjii](https://github.com/Jumanjii)
- [Issue 1033](https://github.com/firefly-iii/firefly-iii/issues/1034), as reported by [Aquariu](https://github.com/Aquariu)
- Extra admin check for [issue 1039](https://github.com/firefly-iii/firefly-iii/issues/1039), as reported by [ocdtrekkie](https://github.com/ocdtrekkie)
- Missing translations ([issue 1026](https://github.com/firefly-iii/firefly-iii/issues/1026)), as reported by [gavu](https://github.com/gavu) and [zjean](https://github.com/zjean)
- [Issue 1028](https://github.com/firefly-iii/firefly-iii/issues/1028), reported by [zjean](https://github.com/zjean)
- [Issue 1029](https://github.com/firefly-iii/firefly-iii/issues/1029), reported by [zjean](https://github.com/zjean)
- [Issue 1030](https://github.com/firefly-iii/firefly-iii/issues/1030), as reported by [Traxxi](https://github.com/Traxxi)
- [Issue 1036](https://github.com/firefly-iii/firefly-iii/issues/1036), as reported by [webence](https://github.com/webence)
- [Issue 1038](https://github.com/firefly-iii/firefly-iii/issues/1038), as reported by [gavu](https://github.com/gavu)

# 4.6.11
- A debug page at `/debug` for easier debug.
- Strings translatable (see [issue 976](https://github.com/firefly-iii/firefly-iii/issues/976)), thanks to [Findus23](https://github.com/Findus23)
- Even more strings are translatable (and translated), thanks to [pkoziol](https://github.com/pkoziol) (see [issue 979](https://github.com/firefly-iii/firefly-iii/issues/979))
- Reconciliation of accounts ([issue 736](https://github.com/firefly-iii/firefly-iii/issues/736)), as requested by [kristophr](https://github.com/kristophr) and several others
- Extended currency list, as suggested by @emuhendis in [issue 994](https://github.com/firefly-iii/firefly-iii/issues/994)
- [Issue 996](https://github.com/firefly-iii/firefly-iii/issues/996) as suggested by [dp87](https://github.com/dp87)
- Disabled Heroku support until I get it working again.
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

# 4.6.10
- Greatly expanded Docker support thanks to [alazare619](https://github.com/alazare619)
- [Issue 967](https://github.com/firefly-iii/firefly-iii/issues/967), thanks to [Aquariu](https://github.com/Aquariu)
- Improved Sandstorm support.
- [Issue 963](https://github.com/firefly-iii/firefly-iii/issues/963), as reported by [gavu](https://github.com/gavu)
- [Issue 970](https://github.com/firefly-iii/firefly-iii/issues/970), as reported by [gavu](https://github.com/gavu)
- [Issue 971](https://github.com/firefly-iii/firefly-iii/issues/971), as reported by [gavu](https://github.com/gavu)
- Various Sandstorm.io related issues.

# 4.6.9.1
- Updated license
- Updated file list

# 4.6.9
- First version that works!

# 3.4.3
- Initial release on Sandstorm.io