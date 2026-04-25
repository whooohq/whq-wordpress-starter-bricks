=== Cache Warmer ===
Contributors: tmmtechnology
Tags: cache, warming, cloudflare, redis, object cache
Tested up to: 6.7.0
Stable tag: 1.3.8
Requires PHP: 7.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Visits website pages to warm (create) the cache if you have any caching solutions configured.

== Settings ==

- Configure cache warming entry points and the depth of the warming;
- Add all public site posts as entry points;
- Add entry point sites sitemaps as entry points;
- Select which types of assets to load, with 4 checkboxes: scripts, styles, images, fonts;
- Set custom URL params (?foo=bar);
- Set custom user agent;
- Set custom request headers;
- Set custom cookies;
- Set connection timeout;
- Set speed limit (pages to visit per minute);
- Autorun Interval: to run the warming each N minutes;
- Exclude pages (by substring or regex);
- Rewrite URLs to HTTPS;
- Visit pages second time without custom URL params (if they are set);
- Warm-up posts (on their publication and edit (Can be seen in Logs -> Triggered)):
 - Set custom posts warming interval.
- Use object cache for tree storage, when it's available.

== Additional ==

- Adds a graph to your dashboard with page load time before and after the warming (2 lines) per day.
- Cleans up Action Scheduler log after itself (older than 30 days for failed actions, and older than 2 days for completed actions).

== External services ==

- Connect to paid service for warming from external global servers.

== Run from code ==

* Start: `\Cache_Warmer\AJAX::start_warm_up( false );`
* Stop: `\Cache_Warmer\AJAX::stop_warm_up( false );`

== Run from WP CLI ==

* Start: `wp cache-warmer start`
* Stop: `wp cache-warmer stop`

== Required PHP extensions ==

- json
- simplexml (optional)

== Bug reports / Questions / Suggestions ==

[wp@tmm.ventures](mailto:wp@tmm.ventures)

== Changelog ==

= 1.3.8 2024-11-18 =

#### Bugfixes

* Fix UI rendering bugs.

= 1.3.7 2024-11-02 =

#### Bugfixes

* Fix interval scheduling issue.

= 1.3.6 2024-10-21 =

#### Enhancements

* Add WP-CLI support.

= 1.3.5 2024-10-20 =

#### Bugfixes

* Fix fatal error on plugin update.

= 1.3.4 2024-10-19 =

#### Enhancements

* Add support for WP CLI.

#### Bugfixes

* Fix scheduled intervals.

= 1.3.3 2024-10-05 =

#### Enhancements

* Instead for checking for intervals in constructors on every page load, check for them only once on plugin activation. Add a button and other interval (which is scheduled in constructor) to fix any missing intervals.

= 1.3.2 2024-09-29 =

#### Bugfixes

* Warming start bug fix.

= 1.3.1 2024-09-29 =

#### Enhancements

* Add support for external warmer - simply for each page visit, and with the intervals.
* Added functionality to start / stop the warming from code (or with WP CLI with "wp eval").

#### Bugfixes

* Fix the warming interval stuck bug.

= 1.3.0 2024-06-24 =

#### Enhancements

* Speed up warmings (optimization).

#### Bugfixes

* Fix stuck object cache bug.

= 1.2.5 2024-06-23 =

#### Bugfixes

* Fix stuck object cache bug.

= 1.2.4 2024-06-23 =

#### Changes

* Do not schedule notifications fetch action.

= 1.2.3 2024-06-05 =

#### Bugfixes

* Fix Action Scheduler bug.

= 1.2.2 2024-05-26 =

#### Bugfixes

* Fix Fatal Error with explode on Windows by using DIRECTORY_SEPARATOR constant.

= 1.2.1 2024-05-02 =

#### Enhancements

* Use standard Action Scheduler interval.
* Update WP-Plugins-Core.

= 1.2.0 2024-04-23 =

#### Bugfixes

* Fix the unscheduled warming with empty data (which results in PHP Warnings).

= 1.1.9 2024-03-12 =

#### Bugfixes

* Fix the "depth" setting.

= 1.1.8 2024-03-11 =

#### Bugfixes

* Fix the sitemaps parsing (didn't visit them at all previously).
* Fix ID for "external warmer" setting fields.
* Fix "speed limit" bug.

= 1.1.7 2024-02-28 =

#### Bugfixes

* Fix settings import button color.
* Fix a bug when the scheduling time is drifting.
* Fix the wrong warming "Duration".
* Fix "speed limit" bug.

#### Enhancements

* Warm sitemaps and posts first (with prio), and only then entry points.

= 1.1.6 2024-01-14 =

#### Bugfixes

* Fix "Request: DOMDocument::loadHTML(): Argument #1 ($source) must not be empty".

= 1.1.5 2024-01-13 =

#### Enhancements

* Update default User-Agent to the newer version of Chrome.
* Increase URL params limit from 5 to 50.
* Add a setting to specify custom request headers.
* Add a setting to visit pages second time without custom cookies (when they are set).
* Improve canonicals logic, to add a log icon with the canonical to the warm-up log; and if 'skip pages with warmed canonical' setting is off, then also warm the canonical page immediately.

#### Bugfixes

* Fix table cells style (overflow: auto for all).
* Logs in the site's timezone.
* Fix a bug when resetting settings does not change posts warming enqueue.
* Fix user-agent table width when no values are set.

= 1.1.4 2023-09-23 =

#### Bugfixes

* Fix canonicals logic.
* Do not untrailingslash all URLs, but keep them as they are.
* Allow to specify "localhost" as entry points.
* Fix URL column style (overflow: auto).

= 1.1.2 2023-09-16 =

#### Enhancements

* Add an option to specify several User-Agents.
* Dynamically escape regex characters when enabling regex. And de-escape on uncheck.
* For headers-based requests classification, do not show 0, but instead show nothing; so that people could not misinterpret it as another request.
* Add a setting to skip the pages with the warmed canonical.
* Add optional "Comment" column to the warm log.
* Change number of pages in batch size from 10 to 1 to increase the robustness.
* Add a setting for logs retention time.
* New menu layout.

#### Bugfixes

* Make "Rewrite to HTTPS" to work for the "URL to warm" on page edit block, and for entry points pages exclusion.
* Fix update_failed_to_retrieve_links() and update_retrieved_links() added links check, to not add duplicates.
* Improve RegExes robustness by using "lazy" quantifiers, instead of the greedy ones.

= 1.0.54 2023-07-18 =

#### Enhancements

* Use wp_options table instead of cache for the lock of migrations.
* Set min PHP version to 7.4.

= 1.0.51 2023-07-16 =

#### Enhancements

* Cleans up Action Scheduler log after itself (older than 30 days for failed actions, and older than 2 days for completed actions).
* Added index to the table to make the post edit page open faster.
* Entry points limit lifted.
* Can specify homepage relative paths as entry points.
* Add support for "html" files warming.
* Add a notice when the plugin is being updated instead of blocking the whole logic.

#### Bugfixes

* Fix table creation error.
* Fix action scheduler scheduling (improve initialization check).
* Fix a bug to not warm "mailto:" URLs, but only the ones that start from "http://" or "https://".
* Add checks in case wp_parse_url() is false.
* Add Author metadata to the plugin main file.
* Do not add URL params to files with 'XML' extensions.
* Fix a bug when URL params to sitemap pages were not added.
* Fix a bug when "sitemaps" setting was not reset.

= 1.0.44 2023-06-11 =

#### Bugfixes

* Add pagination to blog posts.
* Support pagination for plaintext URLs structure.

= 1.0.43 2023-06-08 =

#### Bugfixes

* Fix URLs overlap over the post edit content box.

= 1.0.41 2023-06-07 =

#### Enhancements

* Add pages support for taxonomies.
* Better migration routine.

= 1.0.40 2023-06-07 =

#### Bugfixes

* Add missing libraries.

= 1.0.38 2023-06-06 =

#### Bugfixes

* Fix the plugin (didn't start previously).

= 1.0.37 2023-06-06 =

#### Enhancements

* Decrease plugin size from 8 MiB to 2.8 MiB by removing irrelevant files.

= 1.0.36 2023-06-03 =

#### Enhancements

* Rename "Interval" tab to "Autorun Interval".
* Capitalize first letters of all tab words.
* Add post URL, and with URL params (if presents) to the post edit block.
* Add option to exclude pages by regex.

#### Bugfixes

* Fix interval-based warming.

= 1.0.35 2023-05-15 =

#### Enhancements

* Add an option to skip pages (that match URL a substring).
* Add links (tag <a>) to the warm-up URL column.

= 1.0.34 2023-04-30 =

#### Bugfixes

* Fixed error when the host was undefined.

= 1.0.25 2023-02-23 =

#### Enhancements

* Check page load time pre warmer.
* Add Varnish and Cloudfront support.

= 1.0.20 2023-02-15 =

#### Bugfixes

* Redundant dashboard query deleted.

= 1.0.19 2023-02-14 =

#### Bugfixes

* Min PHP version required downgraded to 5.6.20.

= 1.0.17 2023-02-08 =

#### Optimizations

* Dashboard query to consider only the previous 30 days.

#### Bugfixes

* Default sitemap priority changed from 0 to 0.5.
* Add terms to the tree query fixed.

= 1.0.16 2023-02-08 =

#### Enhancements

* Improved accuracy for server IP address detection.

= 1.0.15 2023-02-08 =

#### Bugfixes

* Regexes fixed and improved (now work more accurately and catch more links).

= 1.0.11 2023-02-08 =

#### Enhancements

* Consider priority for sitemaps.

= 1.0.10 2023-02-08 =

#### Enhancements

* Optimized dashboard widget by using a faster query for the dashboard widget.

#### Bugfixes

* Do not get empty afterload time for the dashboard widget.

= 1.0.9 2023-02-08 =

#### Enhancements

* Add term links  and archives to the posts also.

= 1.0.8 2023-02-05 =

#### Enhancements

* Option to sitemaps of entry points as entry points

= 1.0.7 2023-02-04 =

#### Enhancements

* Option to add all public site posts (of any type) as entry points.

#### Bugfixes

* Batch size reduced from 10 to 1.

= 1.0.6 2023-02-02 =

#### Enhancements

* Post warm details.

= 1.0.5 2023-02-02 =

#### Enhancements

* Slow down when hit 429 or 500 error: first 2 times slower for 15 minutes from the previous avg speed, then 8 times slower, then pause for an hour.

#### Bugfixes

* Speed limit is now working properly.

= 1.0.4 2023-01-30 =

#### Enhancements

* Show a notification when a page is blocked by Cloudflare or other firewall (403, 502, 504 codes).

#### Bugfixes

* Infinite loop during fake tree creation when the initial passed depth is 0.

= 1.0.3 2023-01-29 =

#### Enhancements

* Changelog added.
