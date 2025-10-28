(function($){
  $(function(){
    var $form = $('form.cart');
    if (!$form.length) return;

    var $qty = $form.find('input.qty');
    if (!$qty.length) return;

    var template = (window.ichefBackorder && ichefBackorder.template)
      || 'Додатково: {QTY} буде виготовлено під замовлення (15–25 робочих днів).';

    var $note = $('<p class="ichef-backorder-extra" style="display:none;color:#e67e22;font-weight:bold;margin-top:6px;"></p>');

    // Текущее известное число на складе; если неизвестно — держим null (И НЕ показываем строку)
    var currentStock = null;

    // Простые товары: берём реальный остаток из скрытого поля (выводится PHP)
    var $hidden = $('#ichef-stock-qty');
    if ($hidden.length) {
      var sSimple = parseInt($hidden.val(), 10);
      currentStock = isNaN(sSimple) ? null : sSimple;
    }

    // Найти место вставки: якорь → запасной путь .stock
    function findAnchor(){
      var $a = $('#ichef-backorder-anchor').first();
      if ($a.length) return $a;
      return $('.woocommerce-variation-availability .stock:visible, .summary .stock:visible, .product .stock:visible, .stock:visible').first();
    }

    function placeNote(){
      var $a = findAnchor();
      if (!$a.length) return false;
      if (!$note.parent().length || $note.prev()[0] !== $a[0]) {
        $note.remove();
        $a.after($note);
      }
      return true;
    }

    function render(){
  if (!placeNote()){ $note.hide(); return; }

  // если реальный склад неизвестен — ничего не показываем
  if (currentStock === null){ $note.hide(); return; }

  // НОВОЕ: если остаток 0 или меньше — ничего не показываем
  if (currentStock <= 0){ $note.hide(); return; }

  var q = parseInt($qty.val(), 10);
  if (isNaN(q) || q < 1) q = 1;

  var extra = q - currentStock;
  if (extra > 0){
    $note.text(template.replace('{QTY}', extra)).show();
  } else {
    $note.hide();
  }
}


    // старт + «тики», чтобы поймать ленивую отрисовку темы
    render();
    setTimeout(render, 120);
    setTimeout(render, 450);

    // изменение количества
    $qty.on('input change', render);

    // Вариативные: используем карту остатков от сервера — никакого max_qty
    var $vf = $('form.variations_form');
    if ($vf.length){
      var map = (window.ichefBackorder && window.ichefBackorder.variationStocks) || {};

      $vf.on('found_variation', function(e, v){
        var vid = v && v.variation_id;
        if (vid && typeof map[vid] !== 'undefined') {
          currentStock = parseInt(map[vid], 10);
          if (isNaN(currentStock)) currentStock = null;
        } else {
          // если карта ничего не знает — лучше скрыть строку, чем показывать неверно
          currentStock = null;
        }

        $qty = $(this).find('input.qty');
        render();
        setTimeout(render, 100);
        setTimeout(render, 350);

        $qty.off('input.ichef change.ichef').on('input.ichef change.ichef', render);
      });

      $vf.on('reset_data hide_variation', function(){
        currentStock = null; // пока вариация не выбрана — ничего не показываем
        $note.hide();
      });
    }
  });
})(jQuery);
