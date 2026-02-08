/* WP Default - Admin Scripts */
(function ($) {
    'use strict';

    $(document).ready(function () {
        $('.wpd-media-upload').on('click', function (e) {
            e.preventDefault();

            var button = $(this);
            var targetSelector = button.data('target');
            var targetInput = $(targetSelector);

            var frame = wp.media({
                title: 'Select Image',
                button: { text: 'Use This Image' },
                multiple: false,
                library: { type: 'image' }
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                targetInput.val(attachment.url);
            });

            frame.open();
        });
    });
})(jQuery);
