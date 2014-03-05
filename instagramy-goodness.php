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

function instagramy_goodness_options(){
    $current_user = wp_get_current_user();
    $t = $current_user->ID . "ig_redirect";
    $redirecturl = (isset($_SERVER["HTTPS"]) ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $options = get_option('instagramy_goodness');
    if(isset($_POST['submit'])){
        $oldoptions = $options;
        $options = array();
        if(isset($_POST['client_id'])) $options['client_id'] = $_POST['client_id'];
        if(isset($_POST['client_secret'])) $options['client_secret'] = $_POST['client_secret'];
        update_option('instagramy_goodness',$options);
    }
    if(isset($_GET['code'])){
        $code = $_GET['code'];
        $args = array("body" => array(
            "client_id"   =>    $options['client_id'],
            "client_secret" =>  $options['client_secret'],
            "grant_type"    =>  "authorization_code",
            "redirect_uri"  =>  get_transient( $t ),
            "code"  =>  $code
        ));
        $tokenrawdata = wp_safe_remote_post("https://api.instagram.com/oauth/access_token",$args);
        $tokendata = json_decode($tokenrawdata["body"]);
        if(!isset($tokendata->error) && isset($tokendata->access_token)){
            $options['access_token'] = (string)$tokendata->access_token;
            update_option('instagramy_goodness',$options);
        }
        delete_transient( $t );
    }
?>
<div class="wrap">
    <h2>Instagramy Goodness</h2>
    <?php if(isset($tokendata) && (isset($tokendata->error))) {
        ?>
        <div id="message" class="error fade">
            Instagram error: <?php echo $tokendata->error; ?>
        </div>
    <?php
    }
if(!$options) { ?>
<p>
    To get this to work, you need to register a new Application over at
    <a href="http://instagram.com/developer/clients/manage/">instagram.com/developer/clients/manage</a>.
</p>
<p>
    Use
    <code><?php echo $redirecturl; ?></code>
    as <em>Redirect Url</em>.
</p>
<p>When you're done with that, copy and paste the client id and client secret here:</p>
<?php
}
?>
    <form method="post">
        <h3>Client ID and Secret</h3>
        <table class='form-table'>
            <tr>
                <th scope='row'>client id:</th>
                <td><input type="text" name="client_id" required value="<?php echo $options['client_id'] ?>"></td>
            </tr>
            <tr>
                <th scope='row'>client secret:</th>
                <td><input type="text" name="client_secret" required value="<?php echo $options['client_secret'] ?>"></td>
            </tr>
        </table>
        <?php submit_button('Update'); ?>
    </form>
</div>
<?php
if((trim($options['client_id']) != "") && (trim($options['client_secret']) != "")){
    $instaurl = sprintf(
        "https://api.instagram.com/oauth/authorize/?client_id=%s&response_type=code&redirect_uri=%s",
        $options['client_id'],
        urlencode($redirecturl)
    );
    set_transient( $t, $redirecturl, HOUR_IN_SECONDS );
    ?>
<h3>Connect with Instagram</h3>
<p>
    <a href="<?php echo $instaurl ?>">Connect this with Instagram.</a>
</p>
<?php
}
    if(trim($options['access_token']) != ""){
        ?>
<h3>And we're set!</h3>
<p>
	Now we're all set.
</p>
		<?php
    }
}

function instagramy_goodness_menu(){
    add_options_page(
        'Instagramy Goodness',
        'Instagramy Goodness',
        'manage_options',
        'instagramy_goodness',
        'instagramy_goodness_options'
    );
}


function instagramy_setup_schedule(){
    if ( ! wp_next_scheduled('instagramy_hourly_event')) {
        wp_schedule_event( time(), 'hourly', 'instagramy_hourly_event');
    }
}
function instagramy_hourly_event(){
    wp_mail( 'dschwind@lostfocus.de', 'The subject', 'The message' );
}


/*
 * Hooks
 */

add_action( 'admin_menu', 'instagramy_goodness_menu' );
add_action( 'wp', 'instagramy_setup_schedule' );