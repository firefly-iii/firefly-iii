Firefly III
===========

[![Build Status](https://travis-ci.org/JC5/firefly-iii.svg?branch=develop)](https://travis-ci.org/JC5/firefly-iii)
[![Project Status](http://stillmaintained.com/JC5/firefly-iii.png?a=b)](http://stillmaintained.com/JC5/firefly-iii)

[![Latest Stable Version](https://poser.pugx.org/grumpydictator/firefly-iii/v/stable.svg)](https://packagist.org/packages/grumpydictator/firefly-iii)
[![Total Downloads](https://poser.pugx.org/grumpydictator/firefly-iii/downloads.svg)](https://packagist.org/packages/grumpydictator/firefly-iii)
[![Latest Unstable Version](https://poser.pugx.org/grumpydictator/firefly-iii/v/unstable.svg)](https://packagist.org/packages/grumpydictator/firefly-iii)
[![License](https://poser.pugx.org/grumpydictator/firefly-iii/license.svg)](https://packagist.org/packages/grumpydictator/firefly-iii)

Firefly II is a tool to help you manage your finances. Please read the full description [in the wiki](https://github.com/JC5/firefly-iii/wiki/full-description).

Firefly Mark III is a new version of Firefly built upon best practices and lessons learned
from building [Firefly](https://github.com/JC5/Firefly). It's Mark III since the original Firefly never made it outside of my
laptop and [Firefly II](https://github.com/JC5/Firefly) is live.

## Current features

- [A double-entry bookkeeping system](http://en.wikipedia.org/wiki/Double-entry_bookkeeping_system);
- You can store, edit and remove withdrawals, deposits and transfers. This allows you full financial management;
- It's possible to create, change and manage money using _budgets_;
- Organize transactions using categories;
- Save towards a goal using piggy banks;
- Predict and anticipate large expenses using "repeated expenses" (ie. yearly taxes);
- Predict and anticipate bills using "recurring transactions" (rent for example);
- View basic income / expense reports.
- 

Everything is organised:

- Clear views that should show you how you're doing;
- Easy navigation through your records;
- Browse back and forth to see previous months or even years;
- Lots of charts because we all love them.

## Changes

Firefly III will feature, but does not feature yet:

- Financial reporting showing you how well you are doing;
- Lots of help text in case you don't get it;
- More control over other resources outside of personal finance
  - Accounts shared with a partner (household accounts)
  - Debts
  - Credit cards
- More test-coverage (aka: actual test coverage);
- Firefly will be able to split transactions; a single purchase can be split in multiple entries, for more fine-grained control.
- Firefly will be able to join transactions.
- Transfers and transactions are combined into one internal datatype which is more consistent with what you're actually doing: moving money from A to B. The fact that A or B or both are yours should not matter.
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
I have the basics up and running. Test coverage is currently non-existent.

Although I have not checked extensively, some forms and views have CSRF vulnerabilities. This is because not all
views escape all characters by default. Will be fixed.

The current layout / look & feel is a pretty basic Bootstrap3 template. I am currently working on a more consistent,
expanded layout which will feature shiny AJAX things and data tables and all the Web 3.0 goodies you've come to expect
from social media sites.

Questions, ideas or other things to contribute? [Let me know](https://github.com/JC5/firefly-iii/issues/new)!
