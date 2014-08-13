firefly-iii
===========

[![Build Status](https://travis-ci.org/JC5/firefly-iii.svg?branch=master)](https://travis-ci.org/JC5/firefly-iii)
[![Coverage Status](https://coveralls.io/repos/JC5/firefly-iii/badge.png?branch=master)](https://coveralls.io/r/JC5/firefly-iii?branch=master)
![Still maintained?](http://stillmaintained.com/JC5/firefly-iii.png)

[![Latest Stable Version](https://poser.pugx.org/grumpydictator/firefly-iii/v/stable.svg)](https://packagist.org/packages/grumpydictator/firefly-iii)
[![Total Downloads](https://poser.pugx.org/grumpydictator/firefly-iii/downloads.svg)](https://packagist.org/packages/grumpydictator/firefly-iii)
[![Latest Unstable Version](https://poser.pugx.org/grumpydictator/firefly-iii/v/unstable.svg)](https://packagist.org/packages/grumpydictator/firefly-iii)
[![License](https://poser.pugx.org/grumpydictator/firefly-iii/license.svg)](https://packagist.org/packages/grumpydictator/firefly-iii)

Firefly Mark III is a new version of Firefly built upon best practices and lessons learned
from building [Firefly](https://github.com/JC5/Firefly). It's Mark III since the original Firefly never made it outside of my
laptop and [Firefly II](https://github.com/JC5/Firefly) is live.

## Changes

Firefly III will feature:

- Double-entry bookkeeping system;
- Better budgeting tools;
- Better financial reporting;
- More control over other resources outside of personal finance
  - Accounts shared with a partner (household accounts)
  - Debts
  - Credit cards
- More robust code base (mainly for my own peace of mind);
- More test-coverage (aka: actual test coverage);

## More features
 
- Firefly will be able to split transactions; a single purchase can be split in multiple entries, for more fine-grained control.
- Firefly will be able to join transactions.
- Transfers and transactions will be combined into one internal datatype which is more consistent with what you're actually doing: moving money from A to B. The fact that A or B or both are yours should not matter. And it will not, in the future.
- The nesting of budgets, categories and beneficiaries will be removed.
- Firefly will be able to automatically login a specified account. Although this is pretty unsafe, it removes the need for you to login to your own tool. 

## Not changed

- Firefly will not encrypt the content of the (MySQL) tables. Old versions of Firefly had this capability but it sucks when searching, sorting and organizing entries.

## Current state
I have the basics up and running and test coverage is doing very well.

Current issues are the consistent look-and-feel of forms and likewise, the consistent inner workings of most of Firefly.
Example: every "create"-action tends to be slightly different from the rest. Also is the fact that not all lists
and forms are equally well thought of; some are not looking very well or miss feedback.

Most forms will not allow you to enter invalid data because the database cracks, not because it's actually checked.

A lot of views have CSRF vulnerabilities.

Questions, ideas or other things to contribute? [Let me know](https://github.com/JC5/firefly-iii/issues/new)!
