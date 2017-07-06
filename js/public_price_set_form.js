/**
 * Custom JavaScript functions for user-facing forms that display price set
 * fields (e.g., event registration forms, contribution forms).
 */

CRM.percentagepricesetfield = {
  // Monetary symbol (e.g. '$') in use on the page.
  monetarySymbol: '',
  // Storage for most recent value of percentage checkbox, for use in cases 
  // where we have to automatically disable it (e.g., when disabling the 
  // percentage option based on the selected payment method).
  is_percentage: false,


  /**
   * Hide/show and un-check/restore the percentage option, based on seleted
   * payment method.
   */
  changePaymentProcessor: function changePaymentProcessor() {
    var selected_payment_method = cj('input[name="payment_processor_id"]:checked').val();
    if (CRM.vars.percentagepricesetfield.disable_payment_methods[selected_payment_method]) {
      // Hide the option.
      cj('#' + CRM.vars.percentagepricesetfield.percentage_checkbox_id).closest('.crm-section').hide();
      // Store the state of the checkbox, so we can restore it later.
      CRM.percentagepricesetfield.is_percentage = cj('#' + CRM.vars.percentagepricesetfield.percentage_checkbox_id).prop('checked');
      // Un-check the checkbox; we have to actually uncheck it, because it's
      // a Price Set Field and will be treated as a line item if checked.
      cj('#' + CRM.vars.percentagepricesetfield.percentage_checkbox_id).prop('checked', false);
    }
    else {
      // Restore the previous state of the percentage checkbox.
      cj('#' + CRM.vars.percentagepricesetfield.percentage_checkbox_id).prop('checked', CRM.percentagepricesetfield.isPercentage());
      // Dispaly the option again.
      cj('#' + CRM.vars.percentagepricesetfield.percentage_checkbox_id).closest('.crm-section').show();
    }
    // Re-calculate the total-with-percentage; in the lines above, we manipulated
    // the state of the checkbox but did not update the total, so we need to
    // do it now to be sure all is right.
    CRM.percentagepricesetfield.updateTotal();
  },
  
  /**
   * Get the appropriate state of the percentage checkbox.
   * @returns boolean TRUE or FALSE
   */
  isPercentage: function isPercentage() {
    // Double-check to ensure we're honoring hide_and_force as well as latest
    // value.
    return (CRM.vars.percentagepricesetfield.hide_and_force || CRM.percentagepricesetfield.is_percentage)
  },
  
  /**
   * Update the total-plus-percentage display with the correct amount.
   */
  updateTotal: function updateTotal() {
    if (!cj('#percentagepricesetfield_pricevalue').length) {
      // If our total-plus-percentage display element doesn't exist (as it won't
      // on the confirmation page), don't try to update it.
      return;
    }
    cj('#percentagepricesetfield_pricevalue').html(CRM.percentagepricesetfield.monetarySymbol + ' ' + CRM.percentagepricesetfield.calculateTotal());
  },

  /**
   * Calculate the correct total-plus-percentage amount.
   */
  calculateTotal: function calculateTotal() {
    var finalTotal;
    // Clean up formatted total number by removing non-numerical characters.
    // FIXME: Move this to a new function that anticipates different decimal
    // and thousands separators (e.g., uses 'separator' variable here instead
    // of literal '.') Consider: http://stackoverflow.com/a/20716046
    var regex = new RegExp('[^0-9.]', 'g');
    var baseTotal = cj('#pricevalue').text().replace(regex, '').trim() * 1;
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
  }
}

cj(function() {  
  if (CRM.vars.percentagepricesetfield.hide_and_force) {
    // Hide and force if so configured.
    cj('#' + CRM.vars.percentagepricesetfield.percentage_checkbox_id).prop('checked', true);
    cj('#' + CRM.vars.percentagepricesetfield.percentage_checkbox_id).closest('.price-set-row').hide();
  }
    
  // Add an onChange handler for all of the payment method options.
  cj('input[name="payment_processor_id"]').change(CRM.percentagepricesetfield.changePaymentProcessor);
  

  // Clone and hide the original 'pricesetTotal' div. We'll use the new one to
  // display the total-plus-percentage amount. This allows us to use the original
  // one to store the without-percentage amount, and our new one to display the
  // total-plus-percentage.
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
  CRM.percentagepricesetfield.monetarySymbol = cj('#pricevalue b').html();

  // Add our function update-plus-percentage, as an event handler for all
  // price fields.
  cj("input,#priceset select,#priceset").each(function () {
    if (cj(this).attr('price')) {
      var eleType =  cj(this).attr('type');
      if ( this.tagName == 'SELECT' ) {
        eleType = 'select-one';
      }
      switch( eleType ) {
        case 'checkbox':
        case 'radio':
          cj(this).click(CRM.percentagepricesetfield.updateTotal);
          break;

        case 'text':
          cj(this)
            .bind('keyup', CRM.percentagepricesetfield.updateTotal)
            .bind('blur', CRM.percentagepricesetfield.updateTotal);
          break;

        case 'select-one':
          cj(this).change(CRM.percentagepricesetfield.updateTotal);
          break;
      }
    }
  });  
  
  // Update the form now, based on default form field values.
  CRM.percentagepricesetfield.updateTotal();
  CRM.percentagepricesetfield.changePaymentProcessor();
});