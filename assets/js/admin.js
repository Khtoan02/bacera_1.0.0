jQuery(document).ready(function($){
    // ---------- Media Library ----------
    $('#wks-select-thumbnail').on('click', function(e){
        e.preventDefault();
        var frame = wp.media({
            title: 'Chọn ảnh thumbnail',
            button: { text: 'Chọn' },
            library : { type : 'image' },
            multiple: false
        });
        frame.on('select', function(){
            var attachment = frame.state().get('selection').first().toJSON();
            $('#wks-thumbnail-url').val(attachment.url);
            $('#wks-thumbnail-preview').attr('src', attachment.url).show();
        });
        frame.open();
    });

    // ---------- AJAX actions (for Roster) ----------
    $(document).on('click', '.wks-checkin-btn', function(){
        var btn = $(this), id = btn.data('booking');
        $.post(ajaxurl, {action:'wks_checkin', booking_id:id, _ajax_nonce: btn.data('nonce')}, function(res){
            if(res.success){ btn.replaceWith('<span class="wks-badge wks-badge-green">✓ Đã check‑in</span>'); }
        });
    });

    $(document).on('click', '.wks-cancel-btn', function(){
        if(!confirm('Bạn chắc muốn hủy booking này?')) return;
        var btn = $(this), id = btn.data('booking');
        $.post(ajaxurl, {action:'wks_cancel_booking', booking_id:id, _ajax_nonce: btn.data('nonce')}, function(res){
            if(res.success){ btn.closest('tr').fadeOut(); }
        });
    });

    $(document).on('change', '.wks-payment-btn', function(){
        var sel = $(this), id = sel.data('booking'), status = sel.val();
        $.post(ajaxurl, {action:'wks_update_payment', booking_id:id, status:status, _ajax_nonce: sel.data('nonce')}, function(res){
            if(res.success){ /* optional UI feedback */ }
        });
    });
});
