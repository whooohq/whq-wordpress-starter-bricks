=== JSM Show Term Metadata ===
Plugin Name: JSM Show Term Metadata
Plugin Slug: jsm-show-term-meta
Text Domain: jsm-show-term-meta
Domain Path: /languages
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl.txt
Assets URI: https://jsmoriss.github.io/jsm-show-term-meta/assets/
Tags: taxonomy, terms, metadata, categories, tags
Contributors: jsmoriss
Requires PHP: 7.4.33
Requires At Least: 6.0
Tested Up To: 6.9.4
Stable Tag: 4.8.0

Show term metadata in a metabox when editing terms - a great tool for debugging issues with term metadata.

== Description ==

The JSM Show Term Metadata plugin displays term (ie. categories, tags, and custom taxonomies) meta keys and unserialized values in a metabox at the bottom of the term editing page.

There are no plugin settings - simply install and activate the plugin.

= Shows Yoast SEO Term Meta =

Yoast SEO stores its term (ie. categories, tags, and custom taxonomies) metadata in the WordPress options table, not the term meta table.

The JSM Show Term Metadata plugin can read and display Yoast SEO's term metadata, but it cannot be deleted (as it does not reside in the WordPress term meta table).

= Available Filters for Developers =

Filter the term meta shown in the metabox:

<pre><code>'jsmstm_metabox_table_metadata' ( array $metadata, $term_obj )</code></pre>

Array of regular expressions to exclude meta keys:

<pre><code>'jsmstm_metabox_table_exclude_keys' ( array $exclude_keys, $term_obj )</code></pre>

Capability required to show term meta:

<pre><code>'jsmstm_show_metabox_capability' ( 'manage_options', $term_obj )</code></pre>

Show term meta for a taxonomy (defaults to true):

<pre><code>'jsmstm_show_metabox_taxonomy' ( true, $taxonomy )</code></pre>

Capability required to delete term meta:

<pre><code>'jsmstm_delete_meta_capability' ( 'manage_options', $term_obj )</code></pre>

Icon for the delete term meta button:

<pre><code>'jsmstm_delete_meta_icon_class' ( 'dashicons dashicons-table-row-delete' )</code></pre>

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

01. The "Term Metadata" metabox added to admin term editing pages.

== Changelog ==

<h3 class="top">Version Numbering</h3>

Version components: `{major}.{minor}.{bugfix}[-{stage}.{level}]`

* {major} = Major structural code changes and/or incompatible API changes (ie. breaking changes).
* {minor} = New functionality was added or improved in a backwards-compatible manner.
* {bugfix} = Backwards-compatible bug fixes or small improvements.
* {stage}.{level} = Pre-production release: dev < a (alpha) < b (beta) < rc (release candidate).

<h3>Repositories</h3>

* [GitHub](https://jsmoriss.github.io/jsm-show-term-meta/)
* [WordPress.org](https://plugins.trac.wordpress.org/browser/jsm-show-term-meta/)

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

