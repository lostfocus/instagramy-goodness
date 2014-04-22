<?php

if(!function_exists("download_url")){
    require_once(ABSPATH."wp-admin/admin-functions.php");
}

function instagramy_goodness_redirecturl(){
    $redirecturl = (isset($_SERVER["HTTPS"]) ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . "/wp-admin/tools.php?page=instagramy_goodness";
    return $redirecturl;
}

/*
 * Yes, this is basically the media_sideload_image function.
 * But I need the id, not the HTML code.
 */
function instagramy_goodness_sideload_image($file, $post_id, $desc = null){
    if ( ! empty($file) ) {
        // Download file to temp location
        $tmp = download_url( $file );

        // Set variables for storage
        // fix file filename for query strings
        preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
        $file_array['name'] = basename($matches[0]);
        $file_array['tmp_name'] = $tmp;

        // If error storing temporarily, unlink
        if ( is_wp_error( $tmp ) ) {
            @unlink($file_array['tmp_name']);
            $file_array['tmp_name'] = '';
        }

        // do the validation and storage stuff
        $id = media_handle_sideload( $file_array, $post_id, $desc );
        // If error storing permanently, unlink
        if ( is_wp_error($id) ) {
            @unlink($file_array['tmp_name']);
        }
        return $id;
    }
}