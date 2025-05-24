// assets/js/script.js
(function($) {
    $(document).ready(function() {
        $('#bb-add-category-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = {
                action: 'bb_add_category',
                category_name: $('#category_name').val(),
                percentage: $('#percentage').val(),
                bb_category_nonce: $('#bb_category_nonce').val()
            };

            $.ajax({
                url: bb_data.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    $('#bb-category-message').html('<div class="notice notice-' + (response.success ? 'success' : 'error') + '"><p>' + response.data.message + '</p></div>');
                    if (response.success) {
                        $('#bb-add-category-form')[0].reset();
                        $.get(bb_data.ajax_url, { action: 'bb_get_categories' }, function(data) {
                            $('#bb-categories-table').replaceWith(data);
                        });
                    }
                },
                error: function() {
                    $('#bb-category-message').html('<div class="notice notice-error"><p>An error occurred. Please try again.</p></div>');
                }
            });
        });
    });
})(jQuery);