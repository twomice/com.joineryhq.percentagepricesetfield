cj(function() {
  var totalElementSelector = '#pricevalue';
  var totalWithPercentageElementSelector = '#percentagepricesetfield_pricevalue';
  var monetarySymbol = '';
  var updateTotal = function() {
    cj(totalWithPercentageElementSelector).html(monetarySymbol + ' ' + calculateTotal());
  };
  var calculateTotal = function() {
    var finalTotal;
    var regex = new RegExp('[^0-9.]', 'g')
    var baseTotal = cj(totalElementSelector).text().replace(regex, '').trim() * 1;
    if (cj('#' + CRM.vars.percentagepricesetfield.percentage_checkbox_id).prop('checked')) {
      var percentage = CRM.vars.percentagepricesetfield.percentage
      var extra = (baseTotal*percentage/100)
      var total = extra + baseTotal;
      finalTotal = Math.round(total*100)/100;
    }
    else {
      var finalTotal = baseTotal
    }
    return formatMoney(finalTotal, 2, seperator, thousandMarker);
  }

  // Clone and hide the original 'pricesetTotal' div. We'll use the new one to
  // display the total-plus-percentage amount.
  var originalTotal = cj('div#pricesetTotal');
  var myTotal = originalTotal.clone();
  // Modfiy IDs of cloned elements, recursively.
  myTotal.attr('id', 'totalWithPercentage');
  myTotal.find('*').each(function(idx, el) {
    if (el.id) {
      el.id = 'percentagepricesetfield_' + el.id
    }
  });
  // Add the cloned div.
  myTotal.insertAfter(originalTotal);
  originalTotal.hide();

  // Note the monetary symbol for later use.
  monetarySymbol = cj(totalElementSelector + ' b').html();


  // Add our custom event listener to update total including the percentage.
  cj("input,#priceset select,#priceset").each(function () {
    if (cj(this).attr('price')) {
      var eleType =  cj(this).attr('type');
      if ( this.tagName == 'SELECT' ) {
        eleType = 'select-one';
      }
      switch( eleType ) {
        case 'checkbox':
        case 'radio':
          cj(this).click(updateTotal);
          break;

        case 'text':
          cj(this)
            .bind('keyup', updateTotal)
            .bind('blur', updateTotal);
          break;

        case 'select-one':
          cj(this).change(updateTotal);
          break;
      }
    }
  });
  updateTotal();
})