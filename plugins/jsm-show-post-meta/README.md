<h1>JSM Show Post Metadata</h1>

<table>
<tr><th align="right" valign="top" nowrap>Plugin Name</th><td>JSM Show Post Metadata</td></tr>
<tr><th align="right" valign="top" nowrap>Summary</th><td>Show post metadata (aka custom fields) in a metabox when editing posts / pages - a great tool for debugging issues with post metadata.</td></tr>
<tr><th align="right" valign="top" nowrap>Stable Version</th><td>4.8.0</td></tr>
<tr><th align="right" valign="top" nowrap>Requires PHP</th><td>7.4.33 or newer</td></tr>
<tr><th align="right" valign="top" nowrap>Requires WordPress</th><td>6.0 or newer</td></tr>
<tr><th align="right" valign="top" nowrap>Tested Up To WordPress</th><td>6.9.4</td></tr>
<tr><th align="right" valign="top" nowrap>Contributors</th><td>jsmoriss</td></tr>
<tr><th align="right" valign="top" nowrap>License</th><td><a href="https://www.gnu.org/licenses/gpl.txt">GPLv3</a></td></tr>
<tr><th align="right" valign="top" nowrap>Tags / Keywords</th><td>posts, custom fields, metadata, post types, inspector</td></tr>
</table>

<h2>Description</h2>

<p>The JSM Show Post Metadata plugin displays post (ie. posts, pages, and custom post types) meta keys (aka custom field names) and unserialized values in a metabox at the bottom of the post editing page.</p>

<p>Note that if you're using WooCommerce HPOS (High-Performance Order Storage), available since WooCommerce v8.2, then your WooCommerce orders are NOT post objects and you should use the <a href="https://wordpress.org/plugins/jsm-show-order-meta/">JSM Show Order Metadata</a> plugin instead.</p>

<p>There are no plugin settings - simply install and activate the plugin.</p>

<h4>Available Filters for Developers</h4>

<p>Filter the post meta shown in the metabox:</p>

<pre><code>'jsmspm_metabox_table_metadata' ( array $metadata, $post_obj )</code></pre>

<p>Array of regular expressions to exclude meta keys:</p>

<pre><code>'jsmspm_metabox_table_exclude_keys' ( array $exclude_keys, $post_obj )</code></pre>

<p>Capability required to show post meta:</p>

<pre><code>'jsmspm_show_metabox_capability' ( 'manage_options', $post_obj )</code></pre>

<p>Show post meta for a post type (defaults to true):</p>

<pre><code>'jsmspm_show_metabox_post_type' ( true, $post_type )</code></pre>

<p>Capability required to delete post meta:</p>

<pre><code>'jsmspm_delete_meta_capability' ( 'manage_options', $post_obj )</code></pre>

<p>Icon for the delete post meta button:</p>

<pre><code>'jsmspm_delete_meta_icon_class' ( 'dashicons dashicons-table-row-delete' )</code></pre>

<h4>Related Plugins</h4>

<ul>
<li><a href="https://wordpress.org/plugins/jsm-show-comment-meta/">JSM Show Comment Metadata</a></li>
<li><a href="https://wordpress.org/plugins/jsm-show-order-meta/">JSM Show Order Metadata for WooCommerce HPOS</a></li>
<li><a href="https://wordpress.org/plugins/jsm-show-post-meta/">JSM Show Post Metadata</a></li>
<li><a href="https://wordpress.org/plugins/jsm-show-term-meta/">JSM Show Term Metadata</a></li>
<li><a href="https://wordpress.org/plugins/jsm-show-user-meta/">JSM Show User Metadata</a></li>
<li><a href="https://wordpress.org/plugins/jsm-show-registered-shortcodes/">JSM Show Registered Shortcodes</a></li>
</ul>

