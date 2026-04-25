<h1>JSM Show Term Metadata</h1>

<table>
<tr><th align="right" valign="top" nowrap>Plugin Name</th><td>JSM Show Term Metadata</td></tr>
<tr><th align="right" valign="top" nowrap>Summary</th><td>Show term metadata in a metabox when editing terms - a great tool for debugging issues with term metadata.</td></tr>
<tr><th align="right" valign="top" nowrap>Stable Version</th><td>4.8.0</td></tr>
<tr><th align="right" valign="top" nowrap>Requires PHP</th><td>7.4.33 or newer</td></tr>
<tr><th align="right" valign="top" nowrap>Requires WordPress</th><td>6.0 or newer</td></tr>
<tr><th align="right" valign="top" nowrap>Tested Up To WordPress</th><td>6.9.4</td></tr>
<tr><th align="right" valign="top" nowrap>Contributors</th><td>jsmoriss</td></tr>
<tr><th align="right" valign="top" nowrap>License</th><td><a href="https://www.gnu.org/licenses/gpl.txt">GPLv3</a></td></tr>
<tr><th align="right" valign="top" nowrap>Tags / Keywords</th><td>taxonomy, terms, metadata, categories, tags</td></tr>
</table>

<h2>Description</h2>

<p>The JSM Show Term Metadata plugin displays term (ie. categories, tags, and custom taxonomies) meta keys and unserialized values in a metabox at the bottom of the term editing page.</p>

<p>There are no plugin settings - simply install and activate the plugin.</p>

<h4>Shows Yoast SEO Term Meta</h4>

<p>Yoast SEO stores its term (ie. categories, tags, and custom taxonomies) metadata in the WordPress options table, not the term meta table.</p>

<p>The JSM Show Term Metadata plugin can read and display Yoast SEO's term metadata, but it cannot be deleted (as it does not reside in the WordPress term meta table).</p>

<h4>Available Filters for Developers</h4>

<p>Filter the term meta shown in the metabox:</p>

<pre><code>'jsmstm_metabox_table_metadata' ( array $metadata, $term_obj )</code></pre>

<p>Array of regular expressions to exclude meta keys:</p>

<pre><code>'jsmstm_metabox_table_exclude_keys' ( array $exclude_keys, $term_obj )</code></pre>

<p>Capability required to show term meta:</p>

<pre><code>'jsmstm_show_metabox_capability' ( 'manage_options', $term_obj )</code></pre>

<p>Show term meta for a taxonomy (defaults to true):</p>

<pre><code>'jsmstm_show_metabox_taxonomy' ( true, $taxonomy )</code></pre>

<p>Capability required to delete term meta:</p>

<pre><code>'jsmstm_delete_meta_capability' ( 'manage_options', $term_obj )</code></pre>

<p>Icon for the delete term meta button:</p>

<pre><code>'jsmstm_delete_meta_icon_class' ( 'dashicons dashicons-table-row-delete' )</code></pre>

<h4>Related Plugins</h4>

<ul>
<li><a href="https://wordpress.org/plugins/jsm-show-comment-meta/">JSM Show Comment Metadata</a></li>
<li><a href="https://wordpress.org/plugins/jsm-show-order-meta/">JSM Show Order Metadata for WooCommerce HPOS</a></li>
<li><a href="https://wordpress.org/plugins/jsm-show-post-meta/">JSM Show Post Metadata</a></li>
<li><a href="https://wordpress.org/plugins/jsm-show-term-meta/">JSM Show Term Metadata</a></li>
<li><a href="https://wordpress.org/plugins/jsm-show-user-meta/">JSM Show User Metadata</a></li>
<li><a href="https://wordpress.org/plugins/jsm-show-registered-shortcodes/">JSM Show Registered Shortcodes</a></li>
</ul>

