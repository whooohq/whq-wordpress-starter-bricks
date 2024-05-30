<?php
/**
 * A class for links tree manipulation.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

use ActionScheduler_DBStore;
use ActionScheduler_Store;
use Exception;
use WP_Error;

/**
 * Contains tree modification methods.
 */
final class Tree {

    /**
     * Returns the first link in the tree.
     *
     * @param array $array Array.
     * @param int   $depth Current depth.
     *
     * @return array Array where 'link' key is the link, 'depth' key is its depth, 'meta' is the meta.
     */
    public static function get_the_first_leaf_data( $array, $depth = 0 ) : array {
        foreach ( $array as $key => $value ) { // We don't use iteration here of course but just get the first array item.
            if ( is_array( $value ) && count( $value ) ) {
                return self::get_the_first_leaf_data( $value, ++ $depth );
            } else {
                list( $url, $meta ) = explode( '|', $key );

                return [
                    'link'  => $url,
                    'depth' => $depth,
                    'meta'  => maybe_unserialize( $meta ),
                ];
            }
        }
    }

    /**
     * Updates the value of the first leaf of the tree.
     *
     * @param array $tree      Tree.
     * @param mixed $children New value.
     */
    public static function add_the_first_leaf_children( array &$tree, array $children ) {
        if ( empty( $tree ) ) {
            $tree = $children;
            return;
        }

        foreach ( $tree as &$value ) { // We don't use iteration here of course but just get the first array item.
            if ( is_array( $value ) && count( $value ) ) {
                self::add_the_first_leaf_children( $value, $children );
            } else {
                $value = $children;
            }
            return;
        }
    }

    /**
     * Adds siblings to the first leaf at a specified maximum depth.
     *
     * @param array $tree       Tree.
     * @param array $siblings   Siblings to add at the maximum depth.
     * @param bool  $to_the_end Add siblings to the end of the array, or the beginning.
     * @param int   $max_depth  Maximum depth at which siblings should be added.
     * @param int   $current_depth Current depth in the recursion (initially 0).
     */
    public static function add_the_first_leaf_siblings( array &$tree, array $siblings, bool $to_the_end = true, int $max_depth = PHP_INT_MAX, int $current_depth = 0 ) {
        if ( empty( $tree ) ) {
            $tree = $siblings;
            return;
        }

        if ( $current_depth === $max_depth ) {
            if ( $to_the_end ) {
                $tree = array_merge( $tree, $siblings );
            } else {
                $tree = array_merge( $siblings, $tree );
            }
            return;
        }

        $leaf_found = false;

        foreach ( $tree as &$value ) {
            if ( is_array( $value ) && ! empty( $value ) ) {
                self::add_the_first_leaf_siblings( $value, $siblings, $to_the_end, $max_depth, $current_depth + 1 );
            } else {
                $leaf_found = true;
                break;
            }
        }

        if ( $leaf_found && $current_depth < $max_depth - 1 ) {
            if ( $to_the_end ) {
                $tree = array_merge( $tree, $siblings );
            } else {
                $tree = array_merge( $siblings, $tree );
            }
        }
    }

    /**
     * Deletes the first leaf of the tree.
     *
     * @param array $tree Tree.
     *
     * @return void
     */
    public static function delete_the_first_leaf( array &$tree ) {
        foreach ( $tree as $key => &$value ) { // We don't use iteration here of course but just get the first array item.
            if ( is_array( $value ) && count( $value ) ) {
                self::delete_the_first_leaf( $value );
            } else {
                unset( $tree[ $key ] );
            }
            return;
        }
    }

    /**
     * Make a tree of arbitrary length.
     *
     * With placeholders added, and links pre-populated to it.
     *
     * @param array  $array                      Array.
     * @param string $placeholder                Placeholder.
     * @param int    $depths_left                How many depths left.
     * @param array  $links_to_populate_the_tree Links to populate the tree (array where key is link and value is depth of the link).
     * @param int    $current_depth              Current depth. Should not be passed.
     */
    public static function make_a_tree_of_arbitrary_depth( &$array, $placeholder, $depths_left, $links_to_populate_the_tree, $current_depth = 0 ) {
        if ( ! $depths_left ) {
            return;
        }

        $array[ $placeholder ] = [];

        ++ $current_depth;

        foreach ( $links_to_populate_the_tree as $link => $link_depth ) {
            if ( $current_depth === $link_depth ) {
                $array[ $placeholder ][ $link ] = [];
            }
        }

        if ( -- $depths_left ) {
            self::make_a_tree_of_arbitrary_depth( $array[ $placeholder ], $placeholder, $depths_left, $links_to_populate_the_tree, $current_depth );
        }
    }
}
