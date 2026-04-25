<?php

/*
|--------------------------------------------------------------------------
| Pest Configuration
|--------------------------------------------------------------------------
*/

use Pest\Plugin as Pest;

Pest::boot();

// Setup Brain Monkey for WordPress mocking
beforeEach(function () {
    Brain\Monkey::setUp();

    // Reset global state before each test
    global $wp_tests_options, $wp_tests_transients, $wp_tests_hooks;
    $wp_tests_options = [];
    $wp_tests_transients = [];
    $wp_tests_hooks = [];
});

// Cleanup Brain Monkey after each test
afterEach(function () {
    Brain\Monkey::tearDown();

    // Clean up after each test
    global $wp_tests_options, $wp_tests_transients, $wp_tests_hooks;
    $wp_tests_options = [];
    $wp_tests_transients = [];
    $wp_tests_hooks = [];
});
