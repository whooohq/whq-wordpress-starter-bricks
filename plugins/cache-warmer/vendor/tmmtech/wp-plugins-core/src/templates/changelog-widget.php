<?php
/**
 * Dashboard recommendations widget template.
 *
 * @package WP-Plugins-Core
 */

$plugin_slug = $this->core->plugin_slug;
$changelog   = $this->changelog;

$markdown_parser = new Parsedown();

?>

<div id="widget-<?php echo esc_attr( $plugin_slug ); ?>-changelog">
    <?php
    $last_version = array_key_last( $changelog );

    foreach ( $changelog as $version => $content ) :
        ?>
        <div class="tmm-wp-plugins-core-version-changelog">
            <h2><?php echo esc_html( $version ); ?></h2>
            <span>
                <?php echo $markdown_parser->text( $content ); ?>
            </span>
            <?php if ( $version !== $last_version ) : ?>
                <hr>
            <?php endif; ?>
        </div>
        <?php
    endforeach;
    ?>
</div>
