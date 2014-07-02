firefly-iii
===========

Firefly Mark III is a new version of Firefly built upon best practices and lessons learned 
from building Firefly. It's Mark III since the original Firefly never made it outside of my
laptop and Firefly II is live.

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
