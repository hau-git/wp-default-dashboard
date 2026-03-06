/* WP Default - Admin Scripts */
(function ($) {
    'use strict';

    /* ── Media Upload ───────────────────────────────────────────── */
    $(document).ready(function () {
        $('.wpd-media-upload').on('click', function (e) {
            e.preventDefault();

            var button = $(this);
            var targetInput = $(button.data('target'));

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

        /* ── Section master toggles ─────────────────────────────── */

        /**
         * Apply the visible/collapsed state to a section body and update
         * the badge label inside the section header.
         *
         * @param {HTMLInputElement} checkbox
         * @param {boolean}          animate
         */
        function applyToggle(checkbox, animate) {
            var $checkbox = $(checkbox);
            var $section  = $checkbox.closest('.wpd-section');
            var $body     = $('[data-controlled-by="' + checkbox.id + '"]');
            var $badge    = $section.find('.wpd-section__badge');
            var active    = checkbox.checked;

            if (active) {
                $section.addClass('is-active');
                $badge.text($badge.data('label-active'));
                if (animate) {
                    $body.slideDown(160);
                } else {
                    $body.show();
                }
            } else {
                $section.removeClass('is-active');
                $badge.text($badge.data('label-inactive'));
                if (animate) {
                    $body.slideUp(160);
                } else {
                    $body.hide();
                }
            }
        }

        // Initialise labels from server-rendered text, then collapse if needed.
        $('[data-controlled-by]').each(function () {
            var id       = $(this).data('controlled-by');
            var checkbox = document.getElementById(id);
            if (!checkbox) { return; }

            var $section = $(checkbox).closest('.wpd-section');
            var $badge   = $section.find('.wpd-section__badge');

            // Store both label variants from the server-rendered badge text.
            if (checkbox.checked) {
                $badge.data('label-active',   $badge.text());
                $badge.data('label-inactive',
                    $badge.data('label-inactive') || window.wpdL10n && window.wpdL10n.inactive || 'inaktiv'
                );
            } else {
                $badge.data('label-inactive', $badge.text());
                $badge.data('label-active',
                    $badge.data('label-active') || window.wpdL10n && window.wpdL10n.active || 'aktiv'
                );
                // Collapse without animation on page load.
                $(this).hide();
            }
        });

        // React to toggle changes.
        $(document).on('change', '.wpd-section .wpd-toggle input[type="checkbox"]', function () {
            applyToggle(this, true);
        });
    });
})(jQuery);
