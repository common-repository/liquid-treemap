<?php
/*
Plugin Name: LIQUID TREEMAP
Plugin URI: https://lqd.jp/wp/plugin.html
Description: LIQUID PRESS Plugin.
Author: LIQUID DESIGN Ltd.
Author URI: https://lqd.jp/wp/
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: liquid-treemap
Version: 1.0.2
*/
/*  Copyright LIQUID DESIGN Ltd.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
     published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// ------------------------------------
// Admin
// ------------------------------------

// json
if ( is_admin() ) {
    $json_liquid_treemap_error = "";
    $json_liquid_treemap_url = "https://lqd.jp/wp/data/p/liquid-treemap.json";
    $json_liquid_treemap = wp_remote_get($json_liquid_treemap_url);
    if ( is_wp_error( $json_liquid_treemap ) ) {
        $json_liquid_treemap_error = $json_liquid_treemap->get_error_message().$json_liquid_treemap_url;
    }else{
        $json_liquid_treemap = json_decode($json_liquid_treemap['body']);
    }
}

// notices
function liquid_treemap_admin_notices() {
    global $json_liquid_treemap, $hook_suffix, $json_liquid_treemap_error;
    if($hook_suffix == 'tools_page_liquid_treemap') {
        if( !empty($json_liquid_treemap->notices) && !empty($json_liquid_treemap->flag) ){
            echo '<div class="notice notice-info"><p>'.$json_liquid_treemap->notices.'</p></div>';
        }
    }
    if(!empty($json_liquid_treemap_error)) {
        echo '<script>console.log("'.$json_liquid_treemap_error.'");</script>';
    }
}
add_action( 'admin_notices', 'liquid_treemap_admin_notices' );

// admin_enqueue
function liquid_treemap_enqueue($hook) {
    if($hook == 'tools_page_liquid_treemap') {
        wp_enqueue_style( 'liquid-treemap', plugins_url('style.css', __FILE__) );
    }
}
add_action( 'admin_enqueue_scripts', 'liquid_treemap_enqueue' );

// admin_menu
function liquid_treemap_menu() {
    global $json_liquid_treemap;
    include 'liquid-treemap-menu.php';
}
function liquid_treemap() {
    add_management_page('LIQUID TREEMAP', 'LIQUID TREEMAP', 'edit_posts',
               'liquid_treemap', 'liquid_treemap_menu');
}
add_action ( 'admin_menu', 'liquid_treemap' );

?>