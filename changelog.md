# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

Alpha 2


- Data import: when you submit a transaction but give the ID of an account of the wrong type, Firefly III will try to create an account of the right type. For example: you submit a deposit but the source account is an expense account: Firefly III will try to create a revenue account instead. 



## 5.8.0-alpha.1 - 2023-01-08

This is the first release of the new 5.8.0 series of Firefly III. It should upgrade the database automatically BUT 
make a backup of your database first! I guarantee nothing.

### Warnings

- ⚠️ Make a backup of your database first!
- ⚠️ This version requires **PHP 8.2**.

You can access the new V3 layout under `/v3/`. If you decide to use or test it:

- ⚠️ Read the instructions under the ☠️ icon FIRST.
- ⚠️ The new layout is not yet finished. Use it to change your data at your own risk. 

### Added

Lots of new stuff that I invite you to test and break.

- [Issue 4975](https://github.com/firefly-iii/firefly-iii/issues/4975) Rules can copy/move description to notes and vice versa
- [Issue 5031](https://github.com/firefly-iii/firefly-iii/issues/5031) You can invite users to your installation when registration is off
- [Issue 5213](https://github.com/firefly-iii/firefly-iii/issues/5213) You can trigger recurring transactions beforehand
- [Issue 5592](https://github.com/firefly-iii/firefly-iii/issues/5592) Transactions have a little history box to show how rules changed them
- [Issue 5752](https://github.com/firefly-iii/firefly-iii/issues/5752) Firefly III can send Slack notifications instead of emails
- [Issue 5862](https://github.com/firefly-iii/firefly-iii/issues/5862) Search can filter on reconciled transactions
- [Issue 6086](https://github.com/firefly-iii/firefly-iii/issues/6086) All search filters can be negative by putting `-` in front of them
- [Issue 6441](https://github.com/firefly-iii/firefly-iii/issues/6441) Buttons to purge deleted data, which is easy for data imports
- [Issue 6457](https://github.com/firefly-iii/firefly-iii/issues/6457) Rule trigger 'transaction exists', that will always trigger
- [Issue 6526](https://github.com/firefly-iii/firefly-iii/issues/6526) Option to disable rules and/or webhooks when saving transactions
- [Issue 6605](https://github.com/firefly-iii/firefly-iii/issues/6605) You can search for external ID values
- Working beta of the new layout under `/v3/`
- New authentication screens that support dark mode.
- There is a page for webhooks.

### Changed
- Firefly III requires PHP 8.2
- Liabilities are no longer part of your net worth.
- Liabilities no longer need two transactions to be managed properly (see the documentation)

### Removed
- [Issue 4198](https://github.com/firefly-iii/firefly-iii/issues/4198) The total available budget amount bar on the `/budgets` page is no longer manageable but will be auto-calculated

### Fixed

Not many bugfixes (yet).

- [Issue 6581](https://github.com/firefly-iii/firefly-iii/issues/6581) Fields were not cleared in the transaction screen in some cases

### API

New `/v2/` endpoints are being implemented that prepare the application for (among other things) the ability
to manage multiple financial administrations. The documentation for these endpoints will be at 
https://api-docs.firefly-iii.org/.

- [Issue 6130](https://github.com/firefly-iii/firefly-iii/issues/6130) You can now create a reconciliation transaction

## 5.7.18 - 2023-01-03

### Fixed
- [Issue 6775](https://github.com/firefly-iii/firefly-iii/issues/6775) OAuth authentication was broken for Authelia and other remote user providers.
- [Issue 6787](https://github.com/firefly-iii/firefly-iii/issues/6787) SQLite value conversion broke several functions

## 5.7.17 - 2022-12-30

### Fixed
- [Issue 6742](https://github.com/firefly-iii/firefly-iii/issues/6742) Error when a rule tries to add or remove an amount from a piggy bank
- [Issue 6743](https://github.com/firefly-iii/firefly-iii/issues/6743) Error when opening piggy bank overview
- [Issue 6753](https://github.com/firefly-iii/firefly-iii/issues/6753) Rules are not finding any transactions with trigger 'Amount is greater than 0'

## 5.7.16 - 2022-12-25

### Added
- You can now search for SEPA CT, thanks @dasJ!

### Changed
- Links go to [Mastodon](https://fosstodon.org/@ff3), not Twitter.
- Most if not all remaining float values removed. None were used in financial math.
- Expand Laravel Passport settings.

### Fixed
- [Issue 6597](https://github.com/firefly-iii/firefly-iii/issues/6597) Edit existing split transaction's source did not work properly.
- [Issue 6610](https://github.com/firefly-iii/firefly-iii/issues/6610) Fix search for attachments
- [Issue 6625](https://github.com/firefly-iii/firefly-iii/issues/6625) Page of the links is not displayed due to an error
- [Issue 6701](https://github.com/firefly-iii/firefly-iii/issues/6701) Ensure remote_guard_alt_email if changed, thanks @nebulade!
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
- [Issue 6564](https://github.com/firefly-iii/firefly-iii/issues/6564) Right-Align numbers to match monetary value digits
- [Issue 6589](https://github.com/firefly-iii/firefly-iii/issues/6589) Webhook not fired after destroying transaction
- Add missing locale data

## 5.7.14 - 2022-10-19

### Fixed
- Bulk editing transactions works.
- Negative budgets no longer work.

## 5.7.13 - 2022-10-17

### Added
- [Issue 6502](https://github.com/firefly-iii/firefly-iii/issues/6502) A few students from @D7032E-Group-6 added MTD and YTD, thanks!

### Fixed
- [Issue 6461](https://github.com/firefly-iii/firefly-iii/issues/6461) Broken link in `/public` directory warning.
- [Issue 6475](https://github.com/firefly-iii/firefly-iii/issues/6475) Method name mixup.
- [Issue 6471](https://github.com/firefly-iii/firefly-iii/issues/6471) Fix float conversion
- [Issue 6510](https://github.com/firefly-iii/firefly-iii/issues/6510) Destroy transaction now also triggers liability recalculation.
- Amount check for budget amounts was too low.
- Some other small fixes

### API
- [Issue 6481](https://github.com/firefly-iii/firefly-iii/issues/6481) Mixup in API validation, fixed by @janw

## 5.7.12 - 2022-09-12

### Fixed
- [Issue 6287](https://github.com/firefly-iii/firefly-iii/issues/6287) Catch error when trying to email with invalid settings.
- [Issue 6423](https://github.com/firefly-iii/firefly-iii/issues/6423) Fix redis error, thanks @canoine!
- [Issue 6421](https://github.com/firefly-iii/firefly-iii/issues/6421) Fix issue with SQLite.
- [Issue 6379](https://github.com/firefly-iii/firefly-iii/issues/6379) Fix issue when user has lots of currencies but short list settings.
- [Issue 6333](https://github.com/firefly-iii/firefly-iii/issues/6333) Fix broken chart for reconciliation.
- [Issue 6332](https://github.com/firefly-iii/firefly-iii/issues/6332) Fix issue with uploading zipped PDF's.

## 5.7.11 - 2022-09-05

### Added
- [Issue 6254](https://github.com/firefly-iii/firefly-iii/issues/6254) Use Piggy Bank's start date in monthly suggestion by @rickdoesdev
- Add best practices badge.
- Various sanity checks on large amounts.

### Removed
- Service worker is removed.

### Fixed
- [Issue 6260](https://github.com/firefly-iii/firefly-iii/issues/6260)
- [Issue 6271](https://github.com/firefly-iii/firefly-iii/issues/6271) Improve settings for Redis, by @canoine
- [Issue 6283](https://github.com/firefly-iii/firefly-iii/issues/6283) Convert to deposit means the transaction loses its bill.
- Fix issue with foreign currencies in transaction form.
- Fix various issues with SQLite.
- [Issue 6379](https://github.com/firefly-iii/firefly-iii/issues/6379) Some foreign currencies not list for setting on new transactions
- Make 2FA code + validation more robust. Thanks to @jtmoss3991, @timaschew and @Ottega.

## 5.7.10 - 2022-07-16

### Fixed
- [Issue 6122](https://github.com/firefly-iii/firefly-iii/issues/6122) Type error on data import and display
- SQLite query issues fixed
- Fix nullpointer.
- [Issue 6168](https://github.com/firefly-iii/firefly-iii/issues/6168) Missing date overview in no-category list.
- [Issue 6165](https://github.com/firefly-iii/firefly-iii/issues/6165) Account numbers could not be shared between expense and revenue accounts.
- [Issue 6150](https://github.com/firefly-iii/firefly-iii/issues/6150) The first remote user would not get admin.
- [Issue 6118](https://github.com/firefly-iii/firefly-iii/issues/6118) Piggy bank events would not get copied when transaction was copied.  

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
- [Issue 6058](https://github.com/firefly-iii/firefly-iii/issues/6058) Bad type-casting could break Firefly III on Home Assistant.
- [Issue 6059](https://github.com/firefly-iii/firefly-iii/issues/6059) Fix issue with missing list of bills when creating a recurring transaction from a transaction.
- Added missing DB integrity checks.

### Security
- Updated various packages

## 5.7.5 - 2022-05-06

### Fixed
- Fixed an issue where missing method names would break the API.
- [Issue 6040](https://github.com/firefly-iii/firefly-iii/issues/6040) Could not add or remove money from piggy banks without a target.
- [Issue 6009](https://github.com/firefly-iii/firefly-iii/issues/6009) `has_no_attachments:true` would not return transactions with *deleted* transactions.
- [Issue 6050](https://github.com/firefly-iii/firefly-iii/issues/6050) ja_JP is part of the Docker image

## 5.7.4 - 2022-05-03

### Fixed
- Fixed issue in method names.

## 5.7.3 - 2022-05-03

### Fixed
- Searching for `updated_at_before` and `created_at_before` works again.
- [Issue 6000](https://github.com/firefly-iii/firefly-iii/issues/6000) Bad math when dealing with multi-currency reconciliation.
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

Please refer to the [documentation](https://docs.firefly-iii.org/firefly-iii/) and support channels if you run into problems:

- [Gitter.im](https://gitter.im/firefly-iii/firefly-iii)
- [Twitter](https://twitter.com/Firefly_III/)
- [GitHub Issues](https://github.com/firefly-iii/firefly-iii/issues)
- [GitHub Discussions](https://github.com/firefly-iii/firefly-iii/discussions)

### Added
- Error email message now includes HTTP headers.
- [Issue 5373](https://github.com/firefly-iii/firefly-iii/issues/5373) You can give budgets notes, although they're not visible yet.
- [Issue 5648](https://github.com/firefly-iii/firefly-iii/issues/5648) The Docker image supports custom locales, see `.env.example` for instructions.
- [Issue 3984](https://github.com/firefly-iii/firefly-iii/issues/3984) [issue 5636](https://github.com/firefly-iii/firefly-iii/issues/5636) [issue 4903](https://github.com/firefly-iii/firefly-iii/issues/4903) [issue 5326](https://github.com/firefly-iii/firefly-iii/issues/5326) Lots of new search and rule operators. For the full list, see [search.php](https://github.com/firefly-iii/firefly-iii/blob/main/config/search.php) (a bit technical).
- [Issue 5269](https://github.com/firefly-iii/firefly-iii/issues/5269) It's possible to add piggy banks that have no explicit target amount goal.
- [Issue 4893](https://github.com/firefly-iii/firefly-iii/issues/4893) Bills can be given an end date and an extension date and will warn you about those dates.

### Changed
- [Issue 5757](https://github.com/firefly-iii/firefly-iii/issues/5757) Upgrade to Laravel 9.

### Deprecated
- [Issue 5911](https://github.com/firefly-iii/firefly-iii/issues/5911) Removed support for LDAP.

### Fixed
- [Issue 5810](https://github.com/firefly-iii/firefly-iii/issues/5810) Could not search for `no_notes:true` in some cases.
- [Issue 5869](https://github.com/firefly-iii/firefly-iii/issues/5869) Converting transactions would sometimes fail.
- [Issue 5870](https://github.com/firefly-iii/firefly-iii/issues/5870) Fixed broken link to instructions.
- [Issue 5903](https://github.com/firefly-iii/firefly-iii/issues/5903) API budget limits was broken due to upgraded package.
- [Issue 5852](https://github.com/firefly-iii/firefly-iii/issues/5852) It was not possible to recreate a currency.
- [Issue 5882](https://github.com/firefly-iii/firefly-iii/issues/5882) `no_external_url:true` was broken.
- [Issue 5770](https://github.com/firefly-iii/firefly-iii/issues/5770) Liabilities spent amount would be doubled.
- [Issue 4013](https://github.com/firefly-iii/firefly-iii/issues/4013) Date in email message was not localized.
- [Issue 5949](https://github.com/firefly-iii/firefly-iii/issues/5949) Deleting a transaction would sometimes send you back to a 404.

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

Can be found here: https://docs.firefly-iii.org/firefly-iii/about-firefly-iii/changelog/


