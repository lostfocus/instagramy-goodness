<?php
function instagramy_goodness_admin(){
    $options = get_option('instagramy_goodness');
    if(isset($_POST['submit'])){
        $oldoptions = $options;
        $options = array();
        if(isset($_POST['client_id'])) $options['client_id'] = sanitize_key($_POST['client_id']);
        if(isset($_POST['client_secret'])) $options['client_secret'] = sanitize_key($_POST['client_secret']);
        update_option('instagramy_goodness',$options);
    }
?>
<div class="wrap">
    <h2>Instagramy Goodness</h2>
    <p>
        <?php _e("To get this to work, you need to register a new application over at","instagramy_goodness"); ?>
        <a href="http://instagram.com/developer/clients/manage/">instagram.com/developer/clients/manage</a>.
    </p>
    <p>
        <?php printf(__("Use <code>%s</code> as <em>Redirect Url</em>.","instagramy_goodness"),instagramy_goodness_redirecturl());?>
    </p>
    <p>
        <?php _e("When you're done with that, copy and paste the client id and client secret here:","instagramy_goodness"); ?>
    </p>
    <form method="post">
        <h3><?php _e("Client ID and Secret","instagramy_goodness"); ?></h3>
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
    <?php if(isset($options['client_id']) && isset($options['client_secret'])) { ?>
    <p><?php _e("Great! Now it should be possible for each user to set their Instagramy Goodness settings!","instagramy_goodness");?></p>
    <?php } ?>
</div>
<?php
}