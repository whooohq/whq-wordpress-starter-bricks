<?php
/**
 * Object Cache usage helper.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

use Exception;

/**
 * Class Object_Cache.
 */
final class Object_Cache {

    /**
     * Check if to use object cache.
     *
     * @return bool
     * @throws Exception Exception.
     */
    public static function use_object_cache() {
        return wp_using_ext_object_cache() && '1' === Cache_Warmer::$options->get( 'setting-use-object-cache' );
    }
}
