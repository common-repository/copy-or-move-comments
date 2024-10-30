jQuery(document).ready(function($) {
    jQuery('#source_post').select2();
    jQuery('#target_post').select2();

    jQuery("#all_post_types").change(function() {
        var post_type = jQuery(this).val();
        jQuery('#hidden_post_id').val('');
        jQuery("#bc_loader").show();
        var data = {
            'action': 'get_all_posts',
            'post_type': post_type
        };
        jQuery.post(ajaxurl, data, function(response) {
            jQuery("#source_post").html(response);
            jQuery('#source_post').select2();

            jQuery("#bc_loader").hide();
        });
    });

    jQuery("#target_all_post_types").change(function() {
        var post_type = jQuery(this).val();
        jQuery("#target_bc_loader").show();
        var data = {
            'action': 'get_all_posts',
            'post_type': post_type
        };
        jQuery.post(ajaxurl, data, function(response) {
            jQuery("#target_post").html(response);
            jQuery('#target_post').select2();
            jQuery("#target_bc_loader").hide();

            var source_post_value = jQuery("#source_post").val();
            jQuery("#target_post option[value='" + source_post_value + "']").remove();
        });
    });

    jQuery("#comment_type").on("change", function() {
        jQuery(".tablenav.bottom").show();
        var post_type_value = jQuery("#target_all_post_types").val();
        if (post_type_value != 0) {
            jQuery("#target_all_post_types").trigger("change");
        }
    });

    jQuery('#source_post').on('change', function() {
        var post_id = this.value;
        jQuery('#hidden_post_id').val(post_id);
        jQuery("#bc_loader1").show();

        var data = {
            'action': 'get_comment_type',
            'post_id': post_id
        };

        $.post(ajaxurl, data, function(response) {
            jQuery("#comment_type").html(response);
            jQuery("#bc_loader1").hide();
        });
    });

    jQuery('#comment_type').on('change', function() {
        var comment_type = this.value;
        var post_id = jQuery('#hidden_post_id').val();

        var data = {
            'action': 'get_post_comments',
            'post_id': post_id,
            'comment_type': comment_type
        };

        $.post(ajaxurl, data, function(response) {
            jQuery("#get_comments").html(response);
            jQuery("input[type='checkbox']").on("click", function() {
                if (jQuery(this).hasClass('chkbox_val')) {
                    if(jQuery(this).is(":checked")) {
                        chechUncheckAll();       
                    } else {
                        chechUncheckAll();
                    }
                }
            });
        });
       
        function chechUncheckAll() {
            var numberOfChecked = $('input:checkbox:checked').length;
                var checkboxes = document.querySelectorAll('.chkbox_val');
                var totalCheckboxes = checkboxes.length;
                if (totalCheckboxes == numberOfChecked) {
                    $('.move_comment_id1').prop('checked',true);
                }else{
                    $('.move_comment_id1').prop('checked',false);
                }
        }
    });
                
})
    