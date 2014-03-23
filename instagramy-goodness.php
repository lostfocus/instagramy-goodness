<?php
/*
Plugin Name: Instagramy Goodness
Plugin URI: http://lostfocus.de
Description: Automates an blogpost with your last couple of instagram pictures
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

    /*
     * If there is no token or no user id, we just silently fail.
     */
    if(!$token || !$ig_userid){
        return false; // goto fail;
    }

    /*
     * Either get the last timestamp OR setting it to one week ago.
     * This way we have a timestamp somewhere in the past.
     */
    $lastpost = get_user_option("instagramy_goodness_lastpost",$userid);
    if(!$lastpost){
        $lastpost = time() - WEEK_IN_SECONDS;
    }

    /*
     * Getting the user's day/time setting and comparing it to now.
     */
    $ig_user_day = get_user_option("instagramy_goodness_day",$userid);
    $ig_user_time = get_user_option("instagramy_goodness_time",$userid);

    $day_now = date("w");
    $time_now = floor(date("G") / 6);

    if(
        ($ig_user_day != $day_now) ||
        ($ig_user_time != $time_now) ||
        ($lastpost > (time() - DAY_IN_SECONDS))) {
        return false;
    }

    $user = get_userdata($userid);

    // Set this plus one second, just to be sure.
    // It happened.
    $lastpost += 1;

    // We will set the time of the youngest picture here
    $lastpicturetime = 0;

    // And here we get the pictures
    $ig = new instagramy_goodness();
    $ig->setToken($token);
    $ig->setUserId($ig_userid);
    $pictures = $ig->getOwnMedia(0, $lastpost);

    // If there are no pictures, we can go home
    if(count($pictures->data) < 1){
        return false;
    }

    $ig_user_title = get_user_option("instagramy_goodness_title",$userid);

    // We need to create a post already for the sideload
    $post = array(
        "post_content" => "",
        "post_title" =>  $ig_user_title,
        "post_status"   =>  "auto-draft",
        "post_author"   =>  $userid
    );
    $postid = wp_insert_post( $post);

    $images = array();

    $featured = false;
    $maxlikes = 0;

    foreach($pictures->data as $picture){
        $sideload_id = instagramy_goodness_sideload_image($picture->images->standard_resolution->url, $postid, $picture->caption->text);
        if(!is_wp_error($sideload_id)){
            if($picture->created_time > $lastpicturetime){
                $lastpicturetime = $picture->created_time;
            }
            if($picture->likes->count > $maxlikes){
                $maxlikes = $picture->likes->count;
                $featured = $sideload_id;
            }
            // Let's just pray Instagram doesn't change their urls.
            $shortcode = explode("/",$picture->link);
            $shortcode = $shortcode[4];
            $image = array(
                "id"    => $sideload_id,
                "title" =>  $picture->caption->text,
                "link"  =>  $picture->link,
                "shortcode" => $shortcode,
                "likes" =>  $picture->likes->count
            );
            $post = get_post($image['id']);
            $post->post_content = sprintf('<a href="%s">Instagram</a>',$picture->link);
            wp_update_post($post);
            $images[] = $image;
        }
    }

    if(count($images) < 1) {
        return false; // this happens when there are errors with the sideloading. Uhm.
    }

    //
    $ig_user_format = get_user_option("instagramy_goodness_format",$userid);
    if(!$ig_user_format){
        $ig_user_format = "gallery";
    }

    $content = "";

    switch($ig_user_format){
        case 'gallery':
            $content = "[gallery]";
            break;
        case 'embed':
            $frames = array();
            foreach($images as $image){
                $frames[] = sprintf('<iframe src="//instagram.com/p/%s/embed/" width="612" height="710" frameborder="0" scrolling="no" allowtransparency="true"></iframe>',$image['shortcode']);
            }
            $content = implode("\n\n",$frames);
            break;
        default:
            $img = array();
            foreach($images as $image){
                $src = wp_get_attachment_url( $image['id'] );
                $alt = isset($image['title']) ? esc_attr($image['title']) : '';
                if(version_compare($wp_version,"3.9") >= 0){
                    $html = sprintf('<figure><a href="%s"><img src="%s" alt="%s"></a>',$image['link'],$src,$alt);
                    if($alt != ''){
                        $html .= sprintf("<figcaption>%s</figcaption>",$alt);
                    }
                    $html .= '</figure>';
                } else {
                    $html = sprintf('<p><a href="%s"><img src="%s" alt="%s"></a>',$image['link'],$src,$alt);
                    if($alt != ''){
                        $html .= '<br>'.$alt;
                    }
                    $html .= '</p>';
                }


                $img[] = $html;
            }
            $content = implode("\n\n",$img);
    }
    $post = array(
        "post_content" => $content,
        "post_title" =>  $ig_user_title,
        "post_status"   =>  "draft",
        "post_author"   =>  $userid,
        "ID"    => $postid
    );
    wp_insert_post( $post);
    if($featured){
        update_post_meta($postid,'_thumbnail_id',$featured);
    }
    wp_mail($user->get("user_email"),__("New instagramy goodness post"),$post["post_content"]);
    update_user_option($userid,"instagramy_goodness_lastpost",$lastpicturetime,true);
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

/* Load textdomain */

add_action('plugins_loaded', 'instagramy_goodness_textdomain');
function instagramy_goodness_textdomain() {
    load_plugin_textdomain('instagramy_goodness', false, dirname(plugin_basename( __FILE__ )).'/lang/');
}