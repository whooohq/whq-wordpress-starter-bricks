<h1>JSM Show User Metadata</h1>

<table>
<tr><th align="right" valign="top" nowrap>Plugin Name</th><td>JSM Show User Metadata</td></tr>
<tr><th align="right" valign="top" nowrap>Summary</th><td>Show user metadata in a metabox when editing users - a great tool for debugging issues with user metadata.</td></tr>
<tr><th align="right" valign="top" nowrap>Stable Version</th><td>4.8.0</td></tr>
<tr><th align="right" valign="top" nowrap>Requires PHP</th><td>7.4.33 or newer</td></tr>
<tr><th align="right" valign="top" nowrap>Requires WordPress</th><td>6.0 or newer</td></tr>
<tr><th align="right" valign="top" nowrap>Tested Up To WordPress</th><td>6.9.4</td></tr>
<tr><th align="right" valign="top" nowrap>Contributors</th><td>jsmoriss</td></tr>
<tr><th align="right" valign="top" nowrap>License</th><td><a href="https://www.gnu.org/licenses/gpl.txt">GPLv3</a></td></tr>
<tr><th align="right" valign="top" nowrap>Tags / Keywords</th><td>users, custom fields, metadata, profile, inspector</td></tr>
</table>

<h2>Description</h2>

<p>The JSM Show User Metadata plugin displays user profile meta keys and unserialized values in a metabox at the bottom of the user profile editing page.</p>

<p>There are no plugin settings - simply install and activate the plugin.</p>

<h4>Available Filters for Developers</h4>

<p>Filter the user meta shown in the metabox:</p>

<pre><code>'jsmsum_metabox_table_metadata' ( array $metadata, $user_obj )</code></pre>

<p>Array of regular expressions to exclude meta keys:</p>

<pre><code>'jsmsum_metabox_table_exclude_keys' ( array $exclude_keys, $user_obj )</code></pre>

<p>Capability required to show user meta:</p>

<pre><code>'jsmsum_show_metabox_capability' ( 'manage_options', $user_obj )</code></pre>

<p>Show user meta for a screen base (defaults to true):</p>

<pre><code>'jsmsum_show_metabox_screen_base' ( true, $screen_base )</code></pre>

<p>Capability required to delete user meta:</p>

<pre><code>'jsmsum_delete_meta_capability' ( 'manage_options', $user_obj )</code></pre>

<p>Icon for the delete user meta button:</p>

<pre><code>'jsmsum_delete_meta_icon_class' ( 'dashicons dashicons-table-row-delete' )</code></pre>

<h4>Related Plugins</h4>

<ul>
<li><a href="https://wordpress.org/plugins/jsm-show-comment-meta/">JSM Show Comment Metadata</a></li>
<li><a href="https://wordpress.org/plugins/jsm-show-order-meta/">JSM Show Order Metadata for WooCommerce HPOS</a></li>
<li><a href="https://wordpress.org/plugins/jsm-show-post-meta/">JSM Show Post Metadata</a></li>
<li><a href="https://wordpress.org/plugins/jsm-show-term-meta/">JSM Show Term Metadata</a></li>
<li><a href="https://wordpress.org/plugins/jsm-show-user-meta/">JSM Show User Metadata</a></li>
<li><a href="https://wordpress.org/plugins/jsm-show-registered-shortcodes/">JSM Show Registered Shortcodes</a></li>
</ul>

