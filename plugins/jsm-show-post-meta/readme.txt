=== JSM Show Post Metadata ===
Plugin Name: JSM Show Post Metadata
Plugin Slug: jsm-show-post-meta
Text Domain: jsm-show-post-meta
Domain Path: /languages
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl.txt
Assets URI: https://jsmoriss.github.io/jsm-show-post-meta/assets/
Tags: posts, custom fields, metadata, post types, inspector
Contributors: jsmoriss
Requires PHP: 7.4.33
Requires At Least: 6.0
Tested Up To: 6.9.4
Stable Tag: 4.8.0

Show post metadata (aka custom fields) in a metabox when editing posts / pages - a great tool for debugging issues with post metadata.

== Description ==

The JSM Show Post Metadata plugin displays post (ie. posts, pages, and custom post types) meta keys (aka custom field names) and unserialized values in a metabox at the bottom of the post editing page.

Note that if you're using WooCommerce HPOS (High-Performance Order Storage), available since WooCommerce v8.2, then your WooCommerce orders are NOT post objects and you should use the [JSM Show Order Metadata](https://wordpress.org/plugins/jsm-show-order-meta/) plugin instead.

There are no plugin settings - simply install and activate the plugin.

= Available Filters for Developers =

Filter the post meta shown in the metabox:

<pre><code>'jsmspm_metabox_table_metadata' ( array $metadata, $post_obj )</code></pre>

Array of regular expressions to exclude meta keys:

<pre><code>'jsmspm_metabox_table_exclude_keys' ( array $exclude_keys, $post_obj )</code></pre>

Capability required to show post meta:

<pre><code>'jsmspm_show_metabox_capability' ( 'manage_options', $post_obj )</code></pre>

Show post meta for a post type (defaults to true):

<pre><code>'jsmspm_show_metabox_post_type' ( true, $post_type )</code></pre>

Capability required to delete post meta:

<pre><code>'jsmspm_delete_meta_capability' ( 'manage_options', $post_obj )</code></pre>

Icon for the delete post meta button:

<pre><code>'jsmspm_delete_meta_icon_class' ( 'dashicons dashicons-table-row-delete' )</code></pre>

= Related Plugins =

* [JSM Show Comment Metadata](https://wordpress.org/plugins/jsm-show-comment-meta/)
* [JSM Show Order Metadata for WooCommerce HPOS](https://wordpress.org/plugins/jsm-show-order-meta/)
* [JSM Show Post Metadata](https://wordpress.org/plugins/jsm-show-post-meta/)
* [JSM Show Term Metadata](https://wordpress.org/plugins/jsm-show-term-meta/)
* [JSM Show User Metadata](https://wordpress.org/plugins/jsm-show-user-meta/)
* [JSM Show Registered Shortcodes](https://wordpress.org/plugins/jsm-show-registered-shortcodes/)

== Installation ==

== Frequently Asked Questions ==

== Screenshots ==

01. The "Post Metadata" metabox added to admin post editing pages.

== Changelog ==

<h3 class="top">Version Numbering</h3>

Version components: `{major}.{minor}.{bugfix}[-{stage}.{level}]`

* {major} = Major structural code changes and/or incompatible API changes (ie. breaking changes).
* {minor} = New functionality was added or improved in a backwards-compatible manner.
* {bugfix} = Backwards-compatible bug fixes or small improvements.
* {stage}.{level} = Pre-production release: dev < a (alpha) < b (beta) < rc (release candidate).

<h3>Repositories</h3>

* [GitHub](https://jsmoriss.github.io/jsm-show-post-meta/)
* [WordPress.org](https://plugins.trac.wordpress.org/browser/jsm-show-post-meta/)

<h3>Changelog / Release Notes</h3>

**Version 4.8.0 (2025/07/14)**

* **New Features**
	* None.
* **Improvements**
	* None.
* **Bugfixes**
	* None.
* **Developer Notes**
	* Updated the `SucomUtil` class.
* **Requires At Least**
	* PHP v7.4.33.
	* WordPress v6.0.

== Upgrade Notice ==

= 4.8.0 =

(2025/07/14) Updated the `SucomUtil` class.

