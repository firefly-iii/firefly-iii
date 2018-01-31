<p align="center"><img src="https://firefly-iii.org/static/img/logo-small-new.png"></p>
<h1 align="center">Firefly III</h1>

<p align="center">
	<!-- alll icons are flatsquare -->
	<!-- version -->
	<a href="https://packagist.org/packages/grumpydictator/firefly-iii"><img src="https://img.shields.io/packagist/v/grumpydictator/firefly-iii.svg?style=flat-square" alt="Packagist"></a>
	<!-- license -->
	<a href="https://www.gnu.org/licenses/gpl-3.0.en.html"><img src="https://img.shields.io/github/license/firefly-iii/firefly-iii.svg?style=flat-square" alt="License"></a>
</p>

<!-- MarkdownTOC autolink="true" depth="4" bracket="round" list_bullets="*" -->

* [Introduction](#introduction)
	* [Purpose](#purpose)
	* [Features](#features)
	* [Who is it for?](#who-is-it-for)
* [Get started](#get-started)
	* [Update your instance](#update-your-instance)
* [Contribute](#contribute)
* [The goal](#the-goal)
* [Contact](#contact)
* [Other stuff](#other-stuff)
	* [Versioning](#versioning)
	* [License](#license)
	* [Donate](#donate)
	* [Alternatives](#alternatives)
	* [Badges](#badges)

<!-- /MarkdownTOC -->

## Introduction
"Firefly III" is a (self-hosted) manager for your personal finances. It can help you keep track of your expenses and income, so you can spend less and save more. Firefly III supports the use of budgets, categories and tags. It can import data from external sources and it has many neat financial reports available. Here are some screenshots:

[![The index of Firefly III](https://firefly-iii.org/static/screenshots/4.7.0/tiny/index.png)](https://firefly-iii.org/static/screenshots/4.7.0/index.png) [![The account overview of Firefly III](https://firefly-iii.org/static/screenshots/4.7.0/tiny/account.png)](https://firefly-iii.org/static/screenshots/4.7.0/account.png)

[![Overview of all budgets](https://firefly-iii.org/static/screenshots/4.7.0/tiny/budget.png)](https://firefly-iii.org/static/screenshots/4.7.0/budget.png) [![Overview of a category](https://firefly-iii.org/static/screenshots/4.7.0/tiny/category.png)](https://firefly-iii.org/static/screenshots/4.7.0/category.png)

### Purpose
Personal financial management is pretty difficult, and everybody has their own approach to it. Some people make budgets, other people limit their cashflow by throwing away their credit cards, 
others try to increase their current cashflow. There are tons of ways to save and earn money. Firefly III works on the principle that if you know where you're money is going, you can stop it from going there.

By keeping track of your expenses and your income you can budget accordingly and save money. Stop living from paycheck to paycheck but give yourself the financial wiggle room you need.

You can read more about this in the [official documentation](http://firefly-iii.readthedocs.io/en/latest/index.html).

### Features
Most importantly...

* Firefly III runs on your own server, so you are fully in control of your data. It will not contact other sites or servers
* You can import from over 2500 financial providers, in 55 countries when you enable the [Spectre API](http://firefly-iii.readthedocs.io/en/latest/import/spectre.html)
* You can import CSV files from practically any bank
* If you feel you’re missing something you can just ask me and I’ll add it!

But actually, it features:

* [A double-entry bookkeeping system](https://en.wikipedia.org/wiki/Double-entry_bookkeeping_system)
* You can store, edit and remove [withdrawals, deposits and transfers](http://firefly-iii.readthedocs.io/en/latest/concepts/transactions.html). This allows you full financial management
* You can manage different types of accounts
  * [Asset](http://firefly-iii.readthedocs.io/en/latest/concepts/accounts.html) accounts
  * Shared [asset accounts](http://firefly-iii.readthedocs.io/en/latest/concepts/accounts.html) ([household accounts](http://firefly-iii.readthedocs.io/en/latest/concepts/accounts.html))
  * Saving accounts
  * Credit cards
* It's possible to create, change and manage money using [budgets](http://firefly-iii.readthedocs.io/en/latest/concepts/budgets.html)
* Organize transactions using [categories](http://firefly-iii.readthedocs.io/en/latest/concepts/categories.html)
* Save towards a goal using [piggy banks](http://firefly-iii.readthedocs.io/en/latest/advanced/piggies.html)
* Predict and anticipate [bills](http://firefly-iii.readthedocs.io/en/latest/advanced/bills.html)
* View income / expense [reports](http://firefly-iii.readthedocs.io/en/latest/advanced/reports.html)
* [Rule based](http://firefly-iii.readthedocs.io/en/latest/advanced/rules.html) transaction handling with the ability to create your own rules.
* The ability to [export data](http://firefly-iii.readthedocs.io/en/latest/import/export.html) so you can move to another system.
* The ability to [import data](http://firefly-iii.readthedocs.io/en/latest/import/csv.html) so you can move _from_ another system.
* Organize expenses using [tags](http://firefly-iii.readthedocs.io/en/latest/concepts/tags.html)
* 2 factor authentication for extra security 🔒
* Supports any currency you want, including [crypto currencies](http://firefly-iii.readthedocs.io/en/latest/concepts/currencies.html) such as ₿itcoin  and Ξthereum
* Lots of help text in case you don’t get it

Everything is organised:

* Clear views that should show you how you're doing
* Easy navigation through your records
* Browse back and forth to see previous months or even years
* Lots of charts because we all love them
* Financial reporting showing you how well you are doing
* Lots of math because we all like math!

### Who is it for?
This application is for people who want to track their finances, keep an eye on their money **without having to upload their financial records to the cloud**. You're a bit tech-savvy, you like open source software and you don't mind tinkering with (self-hosted) servers.

## Get started
There are many ways to run Firefly III
1. There is a [demo site](https://demo.firefly-iii.org) with an example financial administration already present.
2. You can [install it on your server](http://firefly-iii.readthedocs.io/en/latest/installation/server.html).
3. You can [run it using Docker](http://firefly-iii.readthedocs.io/en/latest/installation/docker.html).
4. You can [deploy to Heroku](https://heroku.com/deploy?template=https://github.com/firefly-iii/firefly-iii/tree/master)
5. You can [deploy to Sandstorm.io](https://apps.sandstorm.io/app/uws252ya9mep4t77tevn85333xzsgrpgth8q4y1rhknn1hammw70)
6. You can [install it using Softaculous](https://softaculous.com/). These guys even have made [another demo site](http://www.softaculous.com/softaculous/apps/others/Firefly_III)!
7. You can [install it using AMPPS](https://www.ampps.com/)
5. *Even more options are on the way!*

### Update your instance
Make sure you check for updates regularly. Your Firefly III instance will ask you to do this. Upgrade instructions can be found with the installation instructions.

## Contribute
Your help is always welcome! Feel free to open issues, ask questions, talk about it and discuss this tool. I've create several social media accounts and I invite you to follow them, tweet at them and post to them. There's [reddit](https://www.reddit.com/r/FireflyIII/), [Twitter](https://twitter.com/Firefly_III) and [Facebook](https://www.facebook.com/FireflyIII/) just to start. It's not very active but it's a start!

Of course there are some [contributing guidelines](https://github.com/firefly-iii/firefly-iii/blob/master/.github/contributing.md) and a [code of conduct](https://github.com/firefly-iii/firefly-iii/blob/master/.github/code_of_conduct.md), which I invite you to check out.

I can always use your help [squashing bugs](http://firefly-iii.readthedocs.io/en/latest/support/contribute.html#bugs), thinking about [new features](http://firefly-iii.readthedocs.io/en/latest/support/contribute.html#feature-requests) or [translating Firefly III](http://firefly-iii.readthedocs.io/en/latest/support/contribute.html#translations) into other languages.

## The goal
Firefly III should give you **insight** into and **control** over your finances. Money should be useful, not scary. You should be able to *see* where it is going, to *feel* your expenses and to... wow, I'm going overboard with this aren't I?

But you get the idea: this is your money. These are your expenses. Stop them from controlling you. I built this tool because I started to dislike money. Having it, not having, paying bills with it, etc. But no more. I want to feel "safe", whatever my balance is. And I hoop this tool can help. I know it helps me.

## Contact
You can contact me at [thegrumpydictator@gmail.com](mailto:thegrumpydictator@gmail.com), you may open an issue or contact me through the various social media pages there are: [reddit](https://www.reddit.com/r/FireflyIII/), [Twitter](https://twitter.com/Firefly_III) and [Facebook](https://www.facebook.com/FireflyIII/).

Over time, [many people have contributed to Firefly III](https://github.com/firefly-iii/firefly-iii/graphs/contributors).

## Other stuff
### Versioning
We use [SemVer](http://semver.org/) for versioning. For the versions available, see [the tags](https://github.com/firefly-iii/firefly-iii/tags) on this repository.

### License
This work [is licensed](https://github.com/firefly-iii/firefly-iii/blob/master/LICENSE) under the [GPL v3](https://www.gnu.org/licenses/gpl.html).

### Donate
If you like Firefly III and if it helps you save lots of money, why not send me [a dime for every dollar saved](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=44UKUT455HUFA) (this is a joke, although the Paypal form works just fine, try it!)

### Alternatives
If you are looking for alternatives, check out [Kickball's Awesome-Selfhosted list](https://github.com/Kickball/awesome-selfhosted) which features not only Firefly III but also noteworthy alternatives such as [Silverstrike](https://github.com/agstrike/silverstrike).

### Badges
I like badges!

[![Travis branch](https://img.shields.io/travis/firefly-iii/firefly-iii/master.svg?style=flat-square)](https://travis-ci.org/firefly-iii/firefly-iii/branches) [![Scrutinizer](https://img.shields.io/scrutinizer/g/firefly-iii/firefly-iii.svg?style=flat-square)](https://scrutinizer-ci.com/g/firefly-iii/firefly-iii/) [![Coveralls github branch](https://img.shields.io/coveralls/github/firefly-iii/firefly-iii/master.svg?style=flat-square)](https://coveralls.io/github/firefly-iii/firefly-iii) [![Requires PHP7.1](https://img.shields.io/badge/php-7.1-red.svg?style=flat-square)](https://secure.php.net/downloads.php) [![license](https://img.shields.io/github/license/firefly-iii/firefly-iii.svg?style=flat-square)](https://www.gnu.org/licenses/gpl-3.0.en.html) [![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg?style=flat-square)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=44UKUT455HUFA) 
