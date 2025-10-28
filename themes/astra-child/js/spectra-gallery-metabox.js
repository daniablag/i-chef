jQuery(document).ready(function ($) {
    var frame;
    $('.spectra-upload-button').on('click', function (e) {
        e.preventDefault();
        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: 'Выберите изображения для галереи',
            button: { text: 'Добавить в галерею' },
            multiple: true
        });

        frame.on('select', function () {
            var attachments = frame.state().get('selection').toJSON();
            var attachment_ids = [];
            var galleryList = $('.spectra-gallery-images');
            galleryList.html('');

            attachments.forEach(function (attachment) {
                attachment_ids.push(attachment.id);
                galleryList.append('<li data-id="' + attachment.id + '"><img src="' + attachment.sizes.thumbnail.url + '" /><span class="remove">Удалить</span></li>');
            });

            $('#spectra-gallery-input').val(attachment_ids.join(','));
        });

        frame.open();
    });

    $(document).on('click', '.spectra-gallery-images .remove', function () {
        var attachment_id = $(this).parent().data('id');
        var ids = $('#spectra-gallery-input').val().split(',');
        ids = ids.filter(function (id) {
            return id != attachment_id;
        });

        $('#spectra-gallery-input').val(ids.join(','));
        $(this).parent().remove();
    });
});
