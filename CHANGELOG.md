# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).



## [4.0.2] - 2016-10-x
### Added
- Initial release.

### Changed
- Initial release.

### Deprecated
- Initial release.

### Removed
- Initial release.

### Fixed
- PostgreSQL compatibility thanks to @SanderKleykens

### Security
- Initial release.


## [4.0.1] - 2016-10-04
### Added
- New ING import specific by @tomwerf
- New Presidents Choice specific to fix #307
- Added some trimming (#335)

### Changed
- Initial release.

### Deprecated
- Initial release.

### Removed
- Initial release.

### Fixed
- Fixed a bug where incoming transactions would not be properly filtered in several reports.
- #334 by @cyberkov
- #337
- #336
- #338 found by @roberthorlings

### Security
- Initial release.




## [4.0.0] - 2015-09-26
### Added
- Upgraded to Laravel 5.3, most other libraries upgraded as well.
- Added GBP as currency, thanks to @Mortalife

### Changed
- Jump to version 4.0.0.
- Firefly III is now subject to a [Creative Commons Attribution-ShareAlike 4.0 International License](https://creativecommons.org/licenses/by-sa/4.0/) license. Previous versions of this software are still MIT licensed.

### Fixed
- Support for specific decimal places, thanks to @Mortalife
- Various CSS fixes
- Various bugs, thanks to @fuf, @sandermulders and @vissert
- Various queries optimized for MySQL 5.7

## [3.10.4] - 2015-09-14
### Fixed
- Migration fix by @sandermulders
- Tricky import bug fix thanks to @vissert
- Currency preference will be correctly pulled from user settings, thanks to @fuf
- Simplified code for upgrade instructions.


## [3.10.3] - 2016-08-29
### Added
- More fields for mass-edit, thanks to @Vissert (#282)
- First start of German translation

### Changed
- More optional fields for transactions and the ability to filter them.

### Removed
- Preference for budget maximum.

### Fixed
- A bug in the translation routine broke the import.
- It was possible to destroy your Firefly installation by removing all currencies. Thanks @mondjef
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
- Firefly III over a proxy will now work (see [issue #290](https://github.com/JC5/firefly-iii/issues/290)), thanks @dfiel for reporting.
- Sneaky bug in the import routine, fixed by @Bonno 

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
- Fixed a bug in the ``firefly:verify`` routine

## [3.10] - 2015-05-25
### Added
- New charts in year report
- Can add / remove money from piggy bank on mobile device.
- Bill overview shows some useful things.
- Firefly will track registration / activation IP addresses.


### Changed
- Rewrote the import routine.
- The date picker now supports more ranges and periods.
- Rewrote all migrations. #272

### Fixed
- Issue #264
- Issue #265
- Fixed amount calculation problems, #266, thanks @xzaz
- Issue #271
- Issue #278, #273, thanks @StevenReitsma and @rubella
- Bug in attachment download routine would report the wrong size to the user's browser.
- Various NULL errors fixed.
- Various strict typing errors fixed.
- Fixed pagination problems, #276, thanks @xzaz
- Fixed a bug where an expense would be assigned to a piggy bank if you created a transfer first.
- Bulk update problems, #280, thanks @stickgrinder
- Fixed various problems with amount reporting of split transactions.

[3.9.1]
### Fixed
- Fixed a bug where removing money from a piggy bank would not work. See issue #265 and #269

[3.9.0]
### Added
- @zjean has added code that allows you to force "https://"-URL's.
- @tonicospinelli has added Portuguese (Brazil) translations.
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
- Bugs fixed by @Bonno.

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
- You can now see if your current or future rules actually match any transactions, thanks to the excellent work of @roberthorlings.
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
