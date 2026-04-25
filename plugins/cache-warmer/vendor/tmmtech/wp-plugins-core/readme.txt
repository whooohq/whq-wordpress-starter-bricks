== Changelog ==

= 0.1.52 2024-09-13 =

#### Enhancements

* Do not check for missing extensions when running from WP CLI (as can use a different config with different extensions than on the web server).

= 0.1.51 2024-09-13 =

#### Enhancements

* Improve action scheduler methods: add support for args and group.

= 0.1.50 2024-06-19 =

#### Enhancements

* Improved logic for notifications fetch.

= 0.1.49 2024-06-05 =

#### Bugfixes

* Fix interval bug.

= 0.1.48 2024-05-07 =

#### Enhancements

* Minor bug fix.

= 0.1.47 2024-05-02 =

#### Bugfixes

* Minor bug fix.

= 0.1.46 2024-05-02 =

#### Bugfixes

* Minor bug fix.

= 0.1.45 2024-04-07 =

#### Bugfixes

* Minor bug fix.

= 0.1.44 2024-03-07 =

- Add transients at different places to schedule_an_interval() to minimize the chance of duplicates.

= 0.1.43 2024-02-23 =

#### Enhancements

* schedule_single_action() function added for action scheduler logic.

= 0.1.42 2024-01-28 =

#### Bugfixes

* as_unschedule_all_actions() now supports args and group.

= 0.1.41 2024-01-28 =

#### Bugfixes

* as_next_scheduled_action() now supports args and group.

= 0.1.40 2023-09-12 =

#### Enhancements

* Added JS for "number" input fields (when only digits are allowed).

= 0.1.37 2023-09-10 =

#### Enhancements

* Add support for "pure HTML" setting field type (which is 2 columns wide setting).

= 0.1.34 2023-08-25 =

#### Enhancements

* Add support for priorities for Action Scheduler interval.

= 0.1.33 2023-08-10 =

#### Bugfixes

* Fix tabs style.

= 0.1.32 2023-08-09 =

#### Enhancements

* Use the new, WC-like tabs view.

#### Bugfixes

* Fix early Action Scheduler call.

= 0.1.31 2023-08-07 =

#### Enhancements

* Delete inclusion of "autoload_packages" file, as the plugin should do this.

= 0.1.30 2023-08-07 =

#### Enhancements

* Delete bundled action scheduler.

#### Bugfixes

* Fix notifications unscheduling on plugin deactivation.

= 0.1.29 2023-07-18 =

#### Enhancements

* Composer update.

= 0.1.26 2023-07-18 =

#### Enhancements

* Set min PHP version to 7.4.

= 0.1.26 2023-06-27 =

#### Bugfixes

* Fix action scheduler scheduling (improve initialization check).

= 0.1.25 2023-06-07 =

#### Bugfixes

* Missing libraries fix.

= 0.1.24 2023-06-06 =

#### Enhancements

* Decrease the size by removing irrelevant packages from the bundle.

= 0.1.23 2023-06-01 =

#### Bugfixes

* Fix intervals scheduling (accept more arguments).

= 0.1.6 2023-02-26 =

#### Enhancements

* Disable the plugin and show a notification when the required PHP extension is missing.

= 0.1.4 2023-02-15 =

#### Enhancements

* Added min PHP version to composer.
