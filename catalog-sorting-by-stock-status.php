<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @since             1.0.0
 * @package           aaglobe_catalog_sorting_by_stock_status
 *
 * @wordpress-plugin
 * Plugin Name:       Catalog Sorting by Stock Status
 * Plugin URI:        https://wordpress.org/plugins/catalog-sorting-by-stock-status/
 * Donate link:       https://www.buymeacoffee.com/aaglobe
 * Description:       This plugin changes the sorting of a catalog (or product category) so that out-of-stock items are always at the end of the list.
 * Version:           1.0.0
 * Author:            AA-Globe
 * Author URI:        https://aa-globe.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       aa-catalog-sorting-by-stock-status
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class aaglobe_catalog_sorting_by_stock_status
{
    public function __construct()
    {
        if ( $this->is_wc_activated() ) {
            add_filter( 'posts_clauses', array( $this, 'aaglobe_catalog_sorting_by_stock_status' ), 999, 2 );
        } else {
            add_action( 'admin_notices', array( $this, 'no_woocommerce' ) );
        }

        add_filter( 'plugin_row_meta', array( $this, 'filter_plugin_row_meta' ), 10, 2 );
    }

    /**
     * Query WooCommerce activation.
     *
     * @return bool
     */
    private function is_wc_activated() {
        return in_array('woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option('active_plugins') ) );
    }

    public function no_woocommerce() {
        echo '
        <div class="error">
            <p>' . esc_html( 'Plugin Catalog Sorting by Stock Status: Activated WooCommerce plugin is required', 'aa-catalog-sorting-by-stock-status' ) . '</p>
        </div>';
    }


    public function aaglobe_catalog_sorting_by_stock_status( $args, $query ) {

        global $wpdb;

        if( is_admin() || is_search() ) {
            return $args;
        }

        if ( $query->is_main_query() && get_queried_object() && is_woocommerce()
            && ( is_shop() || is_product_category() || is_product_tag() ) ) {

            if( is_object( $wpdb ) ) {
                $db_prefix = $wpdb->prefix;
                if ( $args['groupby'] === $db_prefix . 'posts.ID' ) {

                    $paged = is_object( $query ) && isset( $query->query['paged'] ) ? $query->query['paged'] : null;
                    $products_per_page = is_object( $query ) && isset( $query->query_vars['posts_per_page'] )
                        ? $query->query_vars['posts_per_page']
                        : '';

                    if ( $this->is_woo_catalog_query( $args['limits'], $products_per_page, $paged ) ) {
                        $args['join'] .= " INNER JOIN $wpdb->postmeta istockstatus ON ($wpdb->posts.ID = istockstatus.post_id) ";
                        $args['orderby'] = " istockstatus.meta_value ASC, " . $args['orderby'];
                        $args['where'] = " AND istockstatus.meta_key = '_stock_status' AND istockstatus.meta_value <> '' " . $args['where'];
                    }
                }
            }
        }

        return $args;
    }


    private function is_woo_catalog_query( $args_limits, $products_per_page, $paged = null ) {

        if( ! empty( $products_per_page ) ) {
            if (isset( $paged ) ) {
                $limit = ( (int)$paged - 1 ) * (int) $products_per_page;
            } else {
                $limit = 0;
            }

            if ( $args_limits === 'LIMIT ' . $limit . ', ' . $products_per_page ) {
                return true;
            }
        }

        return false;
    }


    /**
     * Filters the array of row meta for each plugin in the Plugins list table.
     *
     * @param array<int, string> $plugin_meta An array of the plugin's metadata.
     * @param string             $plugin_file Path to the plugin file relative to the plugins directory.
     * @return array<int, string> Updated array of the plugin's metadata.
     */
    public function filter_plugin_row_meta( $plugin_meta, $plugin_file ) {
        if ( 'catalog-sorting-by-stock-status/catalog-sorting-by-stock-status.php' !== $plugin_file ) {
            return $plugin_meta;
        }

        $plugin_meta[] = sprintf(
            '<a href="%1$s" target="_blank"><span class="dashicons dashicons-coffee" aria-hidden="true" style="font-size:14px;line-height:1.3"></span>%2$s</a>',
            'https://www.buymeacoffee.com/aaglobe',
            esc_html( 'Buy me a coffee' )
        );

        return $plugin_meta;
    }
}

new aaglobe_catalog_sorting_by_stock_status;
