document.addEventListener('DOMContentLoaded', function () {
    if (document.body.classList.contains('single-product')) {
        initProductPageURLSync();
    } else {
        initCatalogVariationURLSync();
    }

    const urlParams = new URLSearchParams(window.location.search);
    const hasAttributeParams = [...urlParams.keys()].some(k => k.startsWith('attribute_pa_'));

    window.addEventListener('pageshow', function (event) {
        if (event.persisted || performance.getEntriesByType('navigation')[0]?.type === 'back_forward') {
            setTimeout(() => {
                if (hasAttributeParams) {
                    selectVariationsFromURL();
                } else if (!document.querySelector('.cfvsw-selected-swatch')) {
                    selectFirstAvailableVariations();
                }
            }, 100);
        }
    });

    setTimeout(() => {
        if (hasAttributeParams) {
            selectVariationsFromURL();
        } else if (!document.querySelector('.cfvsw-selected-swatch')) {
            selectFirstAvailableVariations();
        }
    }, 100);

    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === 1) {
                    if (jQuery(node).find('.variations_form').length > 0 || node.classList?.contains('variations_form')) {
                        const urlParams = new URLSearchParams(window.location.search);
                        const hasAttributeParams = [...urlParams.keys()].some(k => k.startsWith('attribute_pa_'));
                        setTimeout(() => {
                            if (hasAttributeParams) {
                                selectVariationsFromURL(node);
                            } else if (!node.querySelector('.cfvsw-selected-swatch')) {
                                selectFirstAvailableVariations(node);
                            }
                            initCatalogVariationURLSync(node);
                        }, 300);
                    }
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});

function initProductPageURLSync() {
    const form = document.querySelector('.variations_form');
    if (!form) return;

    const selects = form.querySelectorAll('select');

    function updateURL() {
        const params = new URLSearchParams(window.location.search);
        selects.forEach(select => {
            const name = select.name;
            const value = select.value;
            if (value) {
                params.set(name, value);
            } else {
                params.delete(name);
            }
        });
        const newUrl = window.location.pathname + '?' + params.toString();
        history.replaceState({}, '', newUrl);
    }

    selects.forEach(select => {
        select.addEventListener('change', updateURL);
    });

    document.querySelectorAll('.cfvsw-swatches-option').forEach(option => {
        option.addEventListener('click', () => setTimeout(updateURL, 100));
    });
}

function initCatalogVariationURLSync(context = document) {
    context.querySelectorAll('.variations_form').forEach(form => {
        form.querySelectorAll('.cfvsw-swatches-container').forEach(container => {
            container.querySelectorAll('.cfvsw-swatches-option').forEach(swatch => {
                swatch.addEventListener('click', function () {
                    const variationData = {};

                    form.querySelectorAll('select').forEach(select => {
                        if (select.value) {
                            variationData[select.name] = select.value;
                        }
                    });

                    const slug = swatch.dataset.slug;
                    const attrName = getAttributeNameFromContainer(container);
                    if (attrName && slug) {
                        variationData[attrName] = slug;
                    }

                    updateProductLinkFromVariation(form, variationData);
                });
            });
        });

        const variationData = {};
        form.querySelectorAll('select').forEach(select => {
            if (select.value) {
                variationData[select.name] = select.value;
            }
        });
        form.querySelectorAll('.cfvsw-swatches-container').forEach(container => {
            const attrName = getAttributeNameFromContainer(container);
            if (!attrName || variationData[attrName]) return;

            const selectedSwatch = container.querySelector('.cfvsw-selected-swatch');
            if (selectedSwatch) {
                variationData[attrName] = selectedSwatch.dataset.slug;
            }
        });

        updateProductLinkFromVariation(form, variationData);
    });
}

function updateProductLinkFromVariation(form, variationData) {
    const productCard = form.closest('.product');
    if (!productCard) return;

    const imageLink = productCard.querySelector('a.woocommerce-LoopProduct-link, a.woocommerce-loop-product__link');
    if (!imageLink) return;

    const baseUrl = imageLink.href.split('?')[0];
    const params = new URLSearchParams();

    Object.entries(variationData).forEach(([key, value]) => {
        if (value) {
            params.set(key, value);
        }
    });

    const newHref = `${baseUrl}?${params.toString()}`;

    productCard.querySelectorAll('a.woocommerce-LoopProduct-link, a.woocommerce-loop-product__link, a.ast-loop-product__link').forEach(link => {
        link.href = newHref;
    });
}

function selectVariationsFromURL(context = document) {
    const urlParams = new URLSearchParams(window.location.search);
    const trySelect = () => {
        jQuery(context).find('.variations_form').each(function () {
            const $form = jQuery(this);
            $form.find('.cfvsw-swatches-container').each(function () {
                const $container = jQuery(this);
                let attrName = getAttributeNameFromContainer($container[0]);
                if (!attrName) return;

                const selectedSlug = urlParams.get(attrName);
                if (!selectedSlug) return;

                const $swatch = $container.find(`.cfvsw-swatches-option[data-slug="${selectedSlug.toLowerCase()}"]:not(.disabled)`).first();
                if ($swatch.length && !$swatch.hasClass('cfvsw-selected-swatch')) {
                    $swatch.trigger('click');
                }
            });
        });
    };

    let attempts = 0;
    const maxAttempts = 10;

    const interval = setInterval(() => {
        attempts++;
        trySelect();

        const allSelected = [...urlParams.keys()]
            .filter(k => k.startsWith('attribute_pa_'))
            .every(key => {
                const val = urlParams.get(key);
                const swatch = document.querySelector(`.cfvsw-swatches-option[data-slug="${val}"]`);
                return swatch && swatch.classList.contains('cfvsw-selected-swatch');
            });

        if (attempts >= maxAttempts || allSelected) {
            clearInterval(interval);
        }
    }, 300);
}

function selectFirstAvailableVariations(context = document) {
    const isProductPage = document.body.classList.contains('single-product');
    const urlParams = new URLSearchParams(window.location.search);
    const rawColorFilter = urlParams.get('wpf_filter_kolir');
    const rawSizeFilter = urlParams.get('wpf_filter_rozmir');

    const filterColors = rawColorFilter ? rawColorFilter.split('|').map(c => c.toLowerCase().trim()) : [];
    const filterSizes = rawSizeFilter ? rawSizeFilter.split('|').map(s => s.toLowerCase().trim()) : [];

    jQuery(context).find('.variations_form').each(function () {
        const $form = jQuery(this);
        const variationData = {};

        $form.find('.cfvsw-swatches-container').each(function () {
            const $container = jQuery(this);
            let attributeName = getAttributeNameFromContainer($container[0]);
            if (!attributeName) return;

            const isColorAttr = attributeName === 'attribute_pa_kolir';
            const isSizeAttr = attributeName === 'attribute_pa_rozmir';

            const selectedFromURL = urlParams.get(attributeName);
            if (isProductPage && selectedFromURL) return;

            const hasSelection = $container.find('.cfvsw-selected-swatch').length > 0;
            if (hasSelection) return;

            let $targetSwatch = null;

            if (filterColors.length > 0 && isColorAttr) {
                for (const color of filterColors) {
                    $targetSwatch = $container.find(`.cfvsw-swatches-option[data-slug="${color}"]:not(.disabled)`).first();
                    if ($targetSwatch.length) break;
                }
            } else if (filterSizes.length > 0 && isSizeAttr) {
                for (const size of filterSizes) {
                    $targetSwatch = $container.find(`.cfvsw-swatches-option[data-slug="${size}"]:not(.disabled)`).first();
                    if ($targetSwatch.length) break;
                }
            } else {
                $targetSwatch = $container.find('.cfvsw-swatches-option:not(.disabled)').first();
            }

            if ($targetSwatch && !$targetSwatch.hasClass('cfvsw-selected-swatch')) {
                const attr = getAttributeNameFromContainer($container[0]);
                variationData[attr] = $targetSwatch.data('slug');
                setTimeout(() => $targetSwatch.trigger('click'), 200);
            }
        });

        setTimeout(() => {
            updateProductLinkFromVariation($form[0], variationData);
        }, 300);
    });
}

function getAttributeNameFromContainer(container) {
    const attr = container.getAttribute('swatches-attr');
    if (attr) return attr;
    const match = container.className.match(/name-(attribute_\w+)/);
    return match ? match[1] : null;
}
