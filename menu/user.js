jQuery( document ).ready(function(){
    var formatselector = jQuery('#ig_format');
    var imagelistoptions = jQuery("#imagelistoptions");
    if(formatselector.val() !== "images"){
        imagelistoptions.hide();
    }
    formatselector.change(function(){
        if(formatselector.val() !== "images"){
            imagelistoptions.slideUp();
        } else {
            imagelistoptions.slideDown();
        }
    });
});