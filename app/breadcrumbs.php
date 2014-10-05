<?php
/*
 * Back home.
 */
Breadcrumbs::register('home', function($breadcrumbs) {
    $breadcrumbs->push('Home', route('index'));
});