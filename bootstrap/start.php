<?php

include('functions.php');


$app = new Illuminate\Foundation\Application;


/*
|--------------------------------------------------------------------------
| Detect The Application Environment
|--------------------------------------------------------------------------
|
| Laravel takes a dead simple approach to your application environments
| so you can just specify a machine name for the host that matches a
| given environment, then we will automatically detect it for you.
|
*/

$env = $app->detectEnvironment(
    ['local' => ['SMJD*'], 'homestead' => ['homestead']]
);


/*
|--------------------------------------------------------------------------
| Bind Paths
|--------------------------------------------------------------------------
|
| Here we are binding the paths configured in paths.php to the app. You
| should not be changing these here. If you need to change these you
| may do so within the paths.php file and they will be bound here.
|
*/

$app->bindInstallPaths(require __DIR__ . '/paths.php');

/*
|--------------------------------------------------------------------------
| Load The Application
|--------------------------------------------------------------------------
|
| Here we will load this Illuminate application. We will keep this in a
| separate location so we can isolate the creation of an application
| from the actual running of the application with a given request.
|
*/

$framework = $app['path.base'] . '/vendor/laravel/framework/src';

/** @noinspection PhpIncludeInspection */
require $framework . '/Illuminate/Foundation/start.php';


/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

// do something with events:

Event::subscribe('FireflyIII\Event\Account');
Event::subscribe('FireflyIII\Event\Budget');
Event::subscribe('FireflyIII\Event\Event');
Event::subscribe('FireflyIII\Event\Piggybank');
Event::subscribe('FireflyIII\Event\Transaction');
Event::subscribe('FireflyIII\Event\TransactionJournal');


// event that creates a relationship between transaction journals and recurring events when created.
// event that updates the relationship between transaction journals and recurring events when edited.
// event that creates a LimitRepetition when a Limit is created.
// event for when a transfer gets created and set an associated piggy bank; save as Piggy bank event.
// when this transfer gets edited, retro-actively edit the event and THUS also the piggy bank.
// event for when a transfer gets deleted; also delete related piggy bank event.
// event to create the first repetition (for non-repeating piggy banks) when the piggy bank is created.
// event for when the non-repeating piggy bank is updated because the single repetition must also be changed.
// (also make piggy bank events "invalid" when they start falling outside of the date-scope of the piggy bank,
// although this not changes the amount in the piggy bank).
// check if recurring transactions are being updated when journals are updated (aka no longer fitting, thus removed).
// think about reminders.
// an event that triggers and creates a limit + limit repetition when a budget is created, or something?
// has many through needs to be added wherever relevant. Account > journals, etc.
// check all models for "external" methods once more.
// Auth::user() should be used very sparsely.
// direct calls to models are BAD
// cleanup everything related to reminders because it still feels a bit sloppy.
// use a Database\Reminder thing instead of self-made ORM.
// create static calls instead of all the App::make() things.
// see if the various has-many-throughs actually get used.
// set very tight rules on all models
// create custom uniquely rules.
// add "Create new X" button to any list there is: categories, accounts, piggies, etc.
// Install PHP5 and code thing and create very small methods.
return $app;
