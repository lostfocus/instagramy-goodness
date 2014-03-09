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
        $ig_userid = get_user_option("instagramy_goodness_id");
    ?>
    <p><?php printf(__("Good job! Instagram said your name is <em>%s</em>.","instagramy_goodness"),$ig_username);?></p>
    <?php
    } ?>
</div>
<?php
}