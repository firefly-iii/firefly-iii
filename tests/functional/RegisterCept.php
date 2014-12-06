<?php
$I = new FunctionalTester($scenario);
$I->wantTo('register a new account');
$I->amOnPage('/register');
$I->submitForm('#register', ['email' => 'noreply@gmail.com']);
$I->see('Password sent!');
$I->seeInDatabase('users', ['email' => 'noreply@gmail.com']);