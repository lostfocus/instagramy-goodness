<?php
function instagramy_goodness_user(){
    $options = get_option('instagramy_goodness');
    $user = wp_get_current_user();
    if(isset($_GET['code'])){
        $code = sanitize_key($_GET['code']);
        $args = array("body" => array(
            "client_id"   =>    $options['client_id'],
            "client_secret" =>  $options['client_secret'],
            "grant_type"    =>  "authorization_code",
            "redirect_uri"  =>  instagramy_goodness_redirecturl(),
            "code"  =>  $code
        ));
        $tokenrawdata = wp_safe_remote_post("https://api.instagram.com/oauth/access_token",$args);
        $tokendata = json_decode($tokenrawdata["body"]);
        update_user_option($user->ID,"instagramy_goodness_token",$tokendata->access_token,true);
        update_user_option($user->ID,"instagramy_goodness_id",$tokendata->user->id,true);
        update_user_option($user->ID,"instagramy_goodness_username",$tokendata->user->username,true);
    }
    if(isset($_POST['submit'])){
        check_admin_referer( 'ig_settings_'.$user->ID );
        $ig_user_day_post = sanitize_key((int)$_POST['day']);
        $ig_user_time_post = sanitize_key((int)$_POST['time']);
        $ig_user_format_post = sanitize_key($_POST['format']);
        $ig_user_title_post = sanitize_text_field($_POST['title']);
        update_user_option($user->ID,"instagramy_goodness_day",$ig_user_day_post, true);
        update_user_option($user->ID,"instagramy_goodness_time",$ig_user_time_post, true);
        update_user_option($user->ID,"instagramy_goodness_format",$ig_user_format_post, true);
        update_user_option($user->ID,"instagramy_goodness_title",$ig_user_title_post, true);
    }
    $token = get_user_option("instagramy_goodness_token");
    ?>
<div class="wrap">
    <h2>Instagramy Goodness</h2>
    <?php
        if(!isset($options['client_id']) || !isset($options['client_secret'])) {
    ?>
    <p><?php _e("You should probably not be here.","instagramy_goodness"); ?></p>
    <?php
        echo "</div>\n"; // Because.
        return;
        }
    $instaurl = sprintf(
        "https://api.instagram.com/oauth/authorize/?client_id=%s&response_type=code&redirect_uri=%s",
        $options['client_id'],
        urlencode(instagramy_goodness_redirecturl())
    );
    ?>
    <p>
        <a href="<?php echo $instaurl ?>"><?php _e("Connect to Instagram.","instagramy_goodness"); ?></a>
    </p>
    <?php if(trim($token) != ""){
        $ig_username = get_user_option("instagramy_goodness_username");
        $ig_user_day = get_user_option("instagramy_goodness_day");
        $ig_user_time = get_user_option("instagramy_goodness_time");
        $ig_user_format = get_user_option("instagramy_goodness_format");
        $ig_user_title = get_user_option("instagramy_goodness_title");
        ?>
    <p><?php printf(__("Good job! Instagram said your name is <em>%s</em>.","instagramy_goodness"),$ig_username);?></p>
    <h2><?php _e("Settings");?></h2>
    <form method="post">
        <h3><?php _e("Title");?></h3>
        <input type="text" name="title" value="<?php echo ($ig_user_title) ? $ig_user_title : "Instagramy Goodness"; ?>">
        <h3><?php _e("Format");?></h3>
        <select name="format">
            <option value="gallery"<?php if($ig_user_format == "gallery") echo "selected"; ?>><? _e("Gallery","instagramy_goodness"); ?></option>
            <option value="images"<?php if($ig_user_format == "images") echo "selected"; ?>><? _e("Image list","instagramy_goodness"); ?></option>
            <!-- <option value="embed"<?php if($ig_user_format == "embed") echo "selected"; ?>><? _e("Embeds","instagramy_goodness"); ?></option> -->
        </select>
        <h3><?php _e("Post times","instagramy_goodness");?></h3>
        <table class='form-table'>
            <tr>
                <th scope='row'><?php _e("Post day","instagramy_goodness"); ?>:</th>
                <td>
                    <select name="day">
                        <option value="1"<?php if((int)$ig_user_day == 1) echo "selected"; ?>><? _e("Monday"); ?></option>
                        <option value="2"<?php if((int)$ig_user_day == 2) echo "selected"; ?>><? _e("Tuesday"); ?></option>
                        <option value="3"<?php if((int)$ig_user_day == 3) echo "selected"; ?>><? _e("Wednesday"); ?></option>
                        <option value="4"<?php if((int)$ig_user_day == 4) echo "selected"; ?>><? _e("Thursday"); ?></option>
                        <option value="5"<?php if((int)$ig_user_day == 5) echo "selected"; ?>><? _e("Friday"); ?></option>
                        <option value="6"<?php if((int)$ig_user_day == 6) echo "selected"; ?>><? _e("Saturday"); ?></option>
                        <option value="0"<?php if((int)$ig_user_day == 0) echo "selected"; ?>><? _e("Sunday"); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope='row'><?php _e("Post time","instagramy_goodness"); ?>:</th>
                <td>
                    <select name="time">
                        <option value="0"<?php if((int)$ig_user_time == 0) echo "selected"; ?>><? _e("Early morning","instagramy_goodness"); ?></option>
                        <option value="1"<?php if((int)$ig_user_time == 1) echo "selected"; ?>><? _e("During the day","instagramy_goodness"); ?></option>
                        <option value="2"<?php if((int)$ig_user_time == 2) echo "selected"; ?>><? _e("In the afternoon","instagramy_goodness"); ?></option>
                        <option value="3"<?php if((int)$ig_user_time == 3) echo "selected"; ?>><? _e("During the evening","instagramy_goodness"); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php wp_nonce_field( 'ig_settings_'.$user->ID ); ?>
        <?php submit_button('Update'); ?>
    </form>
    <?php
    } ?>
</div>
<?php
}