<?php
/*
Plugin Name: Instagramy Goodness
Plugin URI: http://lostfocus.de
Description: Rewrites the image urls to use TimThumb to resize and add whitespace instead of cropping
Version: 0.1
Author: Dominik Schwind
Author URI: http://lostfocus.de/
License: GPL2
*/
/*  Copyright 2014  Dominik Schwind  (email : dschwind@lostfocus.de)

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


define('IG_PATH', plugin_dir_path(__FILE__));
define('IG_URL', plugin_dir_url(__FILE__));

require_once(IG_PATH."helpers.php");
require_once(IG_PATH."instagramy_goodness.class.php");
require_once(IG_PATH."menu/admin.php");
require_once(IG_PATH."menu/user.php");

function instagramy_goodness_menues(){
    add_options_page(
        'Instagramy Goodness',
        'Instagramy Goodness Admin',
        'manage_options',
        'instagramy_goodness_admin',
        'instagramy_goodness_admin'
    );
    $options = get_option('instagramy_goodness');
    if(isset($options['client_id']) && isset($options['client_secret'])) {
        add_users_page(
            'Instagramy Goodness',
            'Instagramy Goodness',
            'edit_posts',
            'instagramy_goodness',
            'instagramy_goodness_user'
        );
    }
}

/*
 * Hooks
 */

add_action( 'admin_menu', 'instagramy_goodness_menues' );
