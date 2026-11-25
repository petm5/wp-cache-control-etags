<?php
/*
 * Plugin Name:  HTTP Cache Integration
 * Description:  Speed up webpages with HTTP entity tags and cache signals. Zero setup, self-contained, and won't touch your database.
 * Version:      0.5.3
 * Author:       Peter Marshall
 * Author URI:   https://petermarshall.ca
 * License:      GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */

add_action('template_redirect', function() {
    if ( is_admin() ) {
        return;
    }

    header('Vary: Cookie');

    if ( is_user_logged_in() || post_password_required() ) {
        header('Cache-Control: private');
        return;
    }

    if ( ! ( is_singular() || is_archive() || is_home() ) ) {
        header('Cache-Control: public, max-age=30');
        return;
    }

    header('Cache-Control: public, max-age=180');

    if ( cheesecake_has_known_secondary_loop() ) {
        return;
    }

    $semantic_entity_hash = cheesecake_get_semantic_entity_hash();

    $current_etag   = 'W/"' . $semantic_entity_hash . '"';
    $requested_etag = trim($_SERVER['HTTP_IF_NONE_MATCH']??'');

    if ($requested_etag === $current_etag) {
        status_header(304);
        exit;
    }

    header('ETag: ' . $current_etag);
}, 999);

function cheesecake_get_semantic_entity_hash() {
    $state = [
        'loop'    => cheesecake_get_global_loop_state(),
        'content' => cheesecake_get_current_post_state(),
        'site'    => cheesecake_get_global_site_state(),
    ];

    return md5( serialize( $state ) );
}

function cheesecake_get_global_site_state() {
    return [
        'theme_state'  => cheesecake_get_active_theme_state(),
        'plugin_state' => cheesecake_get_cached_plugin_hash(),
        'menu_state'   => cheesecake_get_menu_state(),
        'identity'     => cheesecake_get_site_identity_state(),
        'core_version' => $GLOBALS['wp_version'],
    ];
}

function cheesecake_get_current_post_state() {
    return [
        'updated'      => (int)get_post_modified_time(),
        'last_comment' => cheesecake_get_date_of_last_comment(),
    ];
}

function cheesecake_get_global_loop_state() {
    global $wp_query;

    if ( ! ( $wp_query instanceof WP_Query ) || empty( $wp_query->posts ) ) {
        return null;
    }

    $items = array();

    foreach ( $wp_query->posts as $post ) {
        if ( ! ( $post instanceof WP_Post ) ) {
            continue;
        }
        $items[] = $post->ID . ':' . $post->post_modified_gmt;
    }

    return $items;
}

$cheesecake_found_secondary_loop = false;

function cheesecake_has_known_secondary_loop() {
    if ( ! is_singular() ) {
        return false;
    };

    $post_id = get_queried_object_id();
    return $post_id > 0 && (bool) get_post_meta( $post_id, '_has_secondary_loop', true );
}

add_action( 'loop_start', 'cheesecake_on_loop_start', 10, 1 );

function cheesecake_on_loop_start( $query ) {
    global $cheesecake_found_secondary_loop;
    if ( ! $query->is_main_query() ) {
        $cheesecake_found_secondary_loop = true;
    }
}

add_action( 'wp_footer', 'cheesecake_persist_secondary_loop_marker', 999 );

function cheesecake_persist_secondary_loop_marker() {
    global $cheesecake_found_secondary_loop;

    if ( ! is_singular() ) {
        return;
    }

    if ( $cheesecake_found_secondary_loop == cheesecake_has_known_secondary_loop() ) {
        return;
    }

    $post_id = (int) get_queried_object_id();
    if ( $post_id > 0 ) {
        update_post_meta( $post_id, '_has_secondary_loop', $cheesecake_found_secondary_loop );
    }
}

function cheesecake_get_normalized_request_uri() {
    return isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : '/';
}

function cheesecake_get_site_identity_state() {
    return [
        'title'       => get_option( 'blogname' ),
        'tagline'     => get_option( 'blogdescription' ),
        'site_icon'   => get_option( 'site_icon' ),
        'site_logo'   => get_option( 'site_logo' ),
        'custom_logo' => get_theme_mod( 'custom_logo' ),
    ];
}

function cheesecake_get_date_of_last_comment() {
    $args = array(
        'number'  => 1,
        'post_id' => get_the_ID() ?: 0,
        'orderby' => 'comment_date_gmt',
        'status'  => 'approve',
    );

    $comment_query = new WP_Comment_Query( $args );

    $last_comment = $comment_query->comments;

    return $last_comment->comment_date_gmt??0;
}

function cheesecake_get_active_plugins_version_hash() {
    if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $all_plugins    = get_plugins();
    $active_plugins = get_option( 'active_plugins' );
    $versions = [];

    foreach ( $active_plugins as $plugin_path ) {
        if ( isset( $all_plugins[ $plugin_path ] ) ) {
            $versions[$plugin_path] = $all_plugins[ $plugin_path ]['Version'];
        }
    }

    return md5( serialize ( $versions ) );
}

function cheesecake_get_cached_plugin_hash() {
    $hash = get_transient( 'cheesecake_plugin_hash' );

    if ( false === $hash ) {
        $hash = cheesecake_get_active_plugins_version_hash();

        set_transient( 'cheesecake_plugin_hash' , $hash, 12 * HOUR_IN_SECONDS );
    }

    return $hash;
}

add_action( 'update_option_active_plugins', 'cheesecake_clear_plugin_hash_cache' );

add_action( 'upgrader_process_complete', function( $upgrader_object, $options ) {
    if ( $options['action'] == 'update' ) {
        if ( $options['type'] == 'plugin' ) {
            cheesecake_clear_plugin_hash_cache();
        }
    }
}, 10, 2 );

function cheesecake_clear_plugin_hash_cache() {
    delete_transient( 'cheesecake_plugin_hash' );
}

function cheesecake_get_cpts_fingerprint_data($cpt_types) {
    $data = [];

    foreach ( $cpt_types as $type) {
        $posts = get_posts( [
            'post_type'      => $type,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'suppress_filters' => true,
        ] );

        foreach ( $posts as $post ) {
            $data[ $post->ID ] = [
                'type' => $type,
                'modified' => $post->post_modified_gmt,
                'id'  => $post->ID,
            ];
        }
    }

    return $data;
}

function cheesecake_get_files_mtime($files) {
    $files_mtime = [];

    foreach ( $files as $file ) {
        $files_mtime[$file] = file_exists( $file ) ? filemtime( $file ) : 0;
    }

    return $files_mtime;
}

function cheesecake_get_active_theme_state() {
    $settings = wp_get_global_settings();
    $styles = wp_get_global_styles();
    $custom_css = wp_get_custom_css();

    $theme = wp_get_theme();

    $theme_files = [
        $theme->get_stylesheet_directory() . '/theme.json',
        $theme->get_stylesheet_directory() . '/style.css',
    ];

    if ( $theme->parent() ) {
        $theme_files[] = $theme->get_template_directory() . '/theme.json';
        $theme_files[] = $theme->get_template_directory() . '/style.css';
    }

    $theme_files_mtime = cheesecake_get_files_mtime( $theme_files );

    $user_style_post = WP_Theme_JSON_Resolver::get_user_data_from_wp_global_styles( $theme );
    $user_customizations = ! empty( $user_style_post ) ? $user_style_post['post_content'] : '';

    $theme_cpts = cheesecake_get_cpts_fingerprint_data( [
        'wp_block',
        'wp_template_part',
        'wp_template',
        'wp_font_face',
        'wp_font_family',
    ] );

    $state = [
        'settings'          => $settings,
        'styles'            => $styles,
        'custom_css'        => $custom_css,
        'user_customizer'   => $user_customizations,
        'active_stylesheet' => get_stylesheet(),
        'theme_cpts'        => $theme_cpts,
        'theme_files'       => $theme_files_mtime
    ];

    return $state;
}

function cheesecake_get_menu_state() {
    $classic_menus = wp_get_nav_menus();

    $block_menus   = get_posts( array( 'post_type' => 'wp_navigation', 'post_status' => 'publish' ) );

    $block_menu_items = [];

    foreach ( (array) $block_menus as $nav ) {
        $block_menu_items[ $nav->ID ] = $nav->post_modified_gmt;
    }

    return [
        'classic' => $classic_menus,
        'block' => $block_menu_items,
    ];
}
