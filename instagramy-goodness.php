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
        add_management_page(
            'Instagramy Goodness',
            'Instagramy Goodness',
            'edit_posts',
            'instagramy_goodness',
            'instagramy_goodness_user'
        );
    }
}

function instagramy_goodness_create_simple_post($userid){
    global $wp_version;
    $token = get_user_option("instagramy_goodness_token",$userid);
    $ig_userid = get_user_option("instagramy_goodness_id",$userid);
    $lastpost = get_user_option("instagramy_goodness_lastpost",$userid);
    $user = get_userdata($userid);
    if(!$lastpost){
        $lastpost = time() - WEEK_IN_SECONDS;
    } elseif($lastpost > (time() - WEEK_IN_SECONDS + 60)){
        return false;
    }
    $lastpost += 1;
    $lastpicturetime = 0;
    if(!$token || !$ig_userid){
        return false; // goto fail;
    }
    $ig = new instagramy_goodness();
    $ig->setToken($token);
    $ig->setUserId($ig_userid);
    $pictures = $ig->getOwnMedia(10, $lastpost);
    $images = array();
    foreach($pictures->data as $picture){
        if($picture->created_time > $lastpicturetime){
            $lastpicturetime = $picture->created_time;
        }
        $shortcode = explode("/",$picture->link);
        $shortcode = $shortcode[4];
        // $images[] = '<iframe src="//instagram.com/p/'.$shortcode.'/embed/" width="612" height="710" frameborder="0" scrolling="no" allowtransparency="true"></iframe>';
        $images[] = $picture->link;
        ?>
    <?php
    }
    if(count($images) > 0){
        $post = array(
            "post_content" => implode("\n\n",$images),
            "post_title" =>  "Instagramy Goodness",
            "post_status"   =>  "draft",
            "post_author"   =>  $userid
        );
        $postid = wp_insert_post( $post);
        if($postid > 0){
            update_user_option($userid,"instagramy_goodness_lastpost",$lastpicturetime,true);
            $text = "";
            foreach($pictures->data as $picture){
                $imgtag = media_sideload_image($picture->images->standard_resolution->url, $postid, $picture->caption->text);
                if(!is_a($imgtag,"WP_Error")){
                    if(version_compare($wp_version,"3.9") >= 0){
                        $text .= sprintf("<figure><a href='%s'>%s</a><figcaption>%s</figcaption></figure>",$picture->link,$imgtag,$picture->caption->text) . "\n\n";
                    } else {
                        $text .= sprintf("<p><a href='%s'>%s</a></p>",$picture->link,$imgtag) . "\n\n";
                    }
                }
            }
            $post = array(
                "post_content" => $text,
                "post_title" =>  "Instagramy Goodness",
                "post_status"   =>  "draft",
                "post_author"   =>  $userid,
                "ID"    => $postid
            );
            wp_insert_post( $post);
            wp_mail($user->get("user_email"),__("New instagramy goodness post"),$post["post_content"]);
        }
    }
}

function instagramy_goodness_create_all_the_posts(){
    $users = get_users();
    foreach($users as $user){
        instagramy_goodness_create_simple_post($user->ID);
    }
}

function instagramy_goodness_setup_schedule() {
    if ( ! wp_next_scheduled( 'instagramy_goodness_hourly_event' ) ) {
        wp_schedule_event( time(), 'hourly', 'instagramy_goodness_hourly_event');
    }
}

/*
 * Hooks
 */

add_action( 'admin_menu', 'instagramy_goodness_menues' );
add_action( 'wp', 'instagramy_goodness_setup_schedule' );
add_action( 'instagramy_goodness_hourly_event', 'instagramy_goodness_create_all_the_posts' );
