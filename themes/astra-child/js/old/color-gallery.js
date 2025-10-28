jQuery(document).ready(function ($) {
    $('.select-gallery').click(function (e) {
        e.preventDefault();
        var button = $(this);
        var color = button.data('color');
        var frame = wp.media({
            title: 'Выберите изображения для ' + color,
            multiple: true,
            library: { type: 'image' },
            button: { text: 'Выбрать' }
        });

        frame.on('select', function () {
            var attachments = frame.state().get('selection').map(function (attachment) {
                return attachment.id;
            });

            var attachment_ids = attachments.join(',');

            $('input[name="color_gallery[' + color + ']"]').val(attachment_ids);

            var galleryContainer = $('#gallery-' + color);
            galleryContainer.empty();

            attachments.forEach(function (attachment) {
                var image = wp.media.attachment(attachment).get('url');
                galleryContainer.append('<img src="' + image + '" style="max-width: 50px; margin-right: 5px;">');
            });
        });

        frame.open();
    });
});
