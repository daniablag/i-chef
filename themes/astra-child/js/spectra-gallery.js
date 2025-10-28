jQuery(document).ready(function($) {
    var currentIndex = 0;
    var $thumbnails = $('.spectra-gallery-thumbnails img');
    if (!$thumbnails.length) { return; }
    
    function changeImage(index) {
        var fullImg = $thumbnails.eq(index).data('full');
        if (fullImg) {
            $('#spectra-main-image').attr('src', fullImg);
        }
        $thumbnails.removeClass('active');
        $thumbnails.eq(index).addClass('active');
    }
    
    var $arrowPrev = $('.spectra-gallery-arrow-prev');
    var $arrowNext = $('.spectra-gallery-arrow-next');
    
    if ($arrowPrev.length) {
        $arrowPrev.on('click', function() {
            currentIndex = (currentIndex > 0) ? currentIndex - 1 : $thumbnails.length - 1;
            changeImage(currentIndex);
        });
    }
    if ($arrowNext.length) {
        $arrowNext.on('click', function() {
            currentIndex = (currentIndex < $thumbnails.length - 1) ? currentIndex + 1 : 0;
            changeImage(currentIndex);
        });
    }
    
    $thumbnails.on('click', function() {
        currentIndex = $(this).index();
        changeImage(currentIndex);
    });
    
    $thumbnails.eq(0).addClass('active');
});
