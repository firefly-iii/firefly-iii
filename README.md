Firefly III (v3.3.3)
===========

[![Build Status](https://travis-ci.org/JC5/firefly-iii.svg?branch=develop)](https://travis-ci.org/JC5/firefly-iii)
[![Project Status](http://stillmaintained.com/JC5/firefly-iii.png?a=b)](http://stillmaintained.com/JC5/firefly-iii)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/d44c7012-5f50-41ad-add8-8445330e4102/mini.png)](https://insight.sensiolabs.com/projects/d44c7012-5f50-41ad-add8-8445330e4102)
[![Code Climate](https://codeclimate.com/github/JC5/firefly-iii/badges/gpa.svg)](https://codeclimate.com/github/JC5/firefly-iii)
[![Test Coverage](https://codeclimate.com/github/JC5/firefly-iii/badges/coverage.svg)](https://codeclimate.com/github/JC5/firefly-iii)

[![Latest Stable Version](https://poser.pugx.org/grumpydictator/firefly-iii/v/stable.svg)](https://packagist.org/packages/grumpydictator/firefly-iii)
[![Total Downloads](https://poser.pugx.org/grumpydictator/firefly-iii/downloads.svg)](https://packagist.org/packages/grumpydictator/firefly-iii)
[![Latest Unstable Version](https://poser.pugx.org/grumpydictator/firefly-iii/v/unstable.svg)](https://packagist.org/packages/grumpydictator/firefly-iii)
[![License](https://poser.pugx.org/grumpydictator/firefly-iii/license.svg)](https://packagist.org/packages/grumpydictator/firefly-iii)

Firefly III is a tool to help you manage your finances. Please read the full description [in the wiki](https://github.com/JC5/firefly-iii/wiki/full-description).

Firefly Mark III is a new version of Firefly built upon best practices and lessons learned
from building [Firefly](https://github.com/JC5/Firefly). It's Mark III since the original Firefly never made it outside of my
laptop and [Firefly II](https://github.com/JC5/Firefly) is live.

If you're not sure if this tool is for you, please read the [full description](https://github.com/JC5/firefly-iii/wiki/full-description).

To install and use Firefly III, please read [the installation guide](https://github.com/JC5/firefly-iii/wiki/Installation),
 [the upgrade guide](https://github.com/JC5/firefly-iii/wiki/Upgrade-instructions) (if applicable) and the **[first use guide](https://github.com/JC5/firefly-iii/wiki/First-use)**

## Current features

- [A double-entry bookkeeping system](http://en.wikipedia.org/wiki/Double-entry_bookkeeping_system);
- You can store, edit and remove withdrawals, deposits and transfers. This allows you full financial management;
- It's possible to create, change and manage money using _budgets_;
- Organize transactions using categories;
- Save towards a goal using piggy banks;
- Predict and anticipate large expenses using "repeated expenses" (ie. yearly taxes);
- Predict and anticipate bills using "recurring transactions" (rent for example);
- View basic income / expense reports.
- Lots of help text in case you don't get it;

Everything is organised:

- Clear views that should show you how you're doing;
- Easy navigation through your records;
- Browse back and forth to see previous months or even years;
- Lots of charts because we all love them.
- Financial reporting showing you how well you are doing;

## Changes

Firefly III will feature, but does not feature yet:


- More control over other resources outside of personal finance
  - Accounts shared with a partner (household accounts)
  - Debts
  - Credit cards
- More test-coverage;
- Firefly will be able to split transactions; a single purchase can be split in multiple entries, for more fine-grained control.
- Firefly will be able to join transactions.
- Any other features I might not have thought of.

Some stuff has been removed:

- The nesting of budgets, categories and beneficiaries is removed because it was pretty pointless.
- Firefly will not encrypt the content of the (MySQL) tables. Old versions of Firefly had this capability but it sucks when searching, sorting and organizing entries.

## Screenshots

![Index](http://i.imgur.com/TkZNIer.png)

![Accounts](http://i.imgur.com/YE8WavP.png)

![Budgets](http://i.imgur.com/Go0M6Nd.png)

![Reports](http://i.imgur.com/EnEIyQI.png)

## Current state
I have the basics up and running. Test coverage is currently coming, slowly.

Although I have not checked extensively, some forms and views have CSRF vulnerabilities. This is because not all
views escape all characters by default. Will be fixed.

Questions, ideas or other things to contribute? [Let me know](https://github.com/JC5/firefly-iii/issues/new)!
