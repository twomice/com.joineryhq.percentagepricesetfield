/**
 * Custom JavaScript functions for user-facing forms that display price set
 * fields (e.g., event registration forms, contribution forms).
 */
cj(function() {
  var totalElementSelector = '#pricevalue';
  var totalWithPercentageElementSelector = '#percentagepricesetfield_pricevalue';
  var monetarySymbol = '';

  /**
   * Update the total-plus-percentage display with the correct amount.
   */
  var updateTotal = function() {
    cj(totalWithPercentageElementSelector).html(monetarySymbol + ' ' + calculateTotal());
  };

  /**
   * Calculate the correct total-with-percentage amount.
   */
  var calculateTotal = function() {
    var finalTotal;
    // Clean up formatted total number by removing non-numerical characters.
    // FIXME: Move this to a new function that anticipates different decimal
    // and thousands separators (e.g., uses 'separator' variable here instead
    // of literal '.') Consider: http://stackoverflow.com/a/20716046
    var regex = new RegExp('[^0-9.]', 'g');
    var baseTotal = cj(totalElementSelector).text().replace(regex, '').trim() * 1;
    if (cj('#' + CRM.vars.percentagepricesetfield.percentage_checkbox_id).prop('checked')) {
      var percentage = CRM.vars.percentagepricesetfield.percentage;
      var extra = (baseTotal*percentage/100);
      var total = extra + baseTotal;
      finalTotal = Math.round(total*100)/100;
    }
    else {
      finalTotal = baseTotal;
    }

    // Older CiviCRM versions used 'seperator' instead of 'separator'
    var currency_separator
    if (typeof separator === 'undefined') {
      currency_separator = seperator
    }
    else {
      currency_separator = separator
    }

    return formatMoney(finalTotal, 2, currency_separator, thousandMarker);
  };

  // Clone and hide the original 'pricesetTotal' div. We'll use the new one to
  // display the total-plus-percentage amount.
  var originalTotal = cj('div#pricesetTotal');
  var myTotal = originalTotal.clone();
  // Modfiy IDs of cloned elements, recursively.
  myTotal.attr('id', 'totalWithPercentage');
  myTotal.find('*').each(function(idx, el) {
    if (el.id) {
      el.id = 'percentagepricesetfield_' + el.id;
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
});