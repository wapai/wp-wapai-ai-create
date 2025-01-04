jQuery(document).ready(function ($) {
    $.post(wapai_obj.ajax_url, {
        action: 'wapai_publish',
        _ajax_nonce: wapai_obj.nonce
    }, function (data) {
    });
});