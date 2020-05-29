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
  is_percentage: CRM.vars.percentagepricesetfield.is_default,


  storePercentageState: function storePercentageState() {
    CRM.percentagepricesetfield.is_percentage = cj('#' + CRM.vars.percentagepricesetfield.percentage_checkbox_id).prop('checked');
  },

  /**
   * Hide/show and un-check/restore the percentage option, based on seleted
   * payment method.
   */
  changePaymentProcessor: function changePaymentProcessor() {

    var selected_payment_method = cj('input[name="payment_processor_id"]:checked').val();
    if (typeof selected_payment_method == 'undefined') {
      selected_payment_method = CRM.vars.percentagepricesetfield.payment_processor_id;
    }

    if (CRM.vars.percentagepricesetfield.disable_payment_methods[selected_payment_method]) {

      // Hide the option.
      cj('#' + CRM.vars.percentagepricesetfield.percentage_checkbox_id).closest('.crm-section').hide();
      // Store the state of the checkbox, so we can restore it later.
      CRM.percentagepricesetfield.storePercentageState();
      // Un-check the checkbox; we have to actually uncheck it, because it's
      // a Price Set Field and will be treated as a line item if checked.
      cj('#' + CRM.vars.percentagepricesetfield.percentage_checkbox_id).prop('checked', false);
    }
    else {

      // Restore the previous state of the percentage checkbox.
      cj('#' + CRM.vars.percentagepricesetfield.percentage_checkbox_id).prop('checked', CRM.percentagepricesetfield.isPercentage());
      // Dispaly the option again.
      if (!CRM.vars.percentagepricesetfield.hide_and_force) {
        cj('#' + CRM.vars.percentagepricesetfield.percentage_checkbox_id).closest('.crm-section').show();
      }
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
    return (CRM.vars.percentagepricesetfield.hide_and_force || CRM.percentagepricesetfield.is_percentage);
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
   * Format amount as money.
   * 
   * This function copied from CiviCRM's templates/CRM/Price/Form/Calculate.tpl in version 5.20.0
   * (https://lab.civicrm.org/dev/core/-/blob/5.20.0/templates/CRM/Price/Form/Calculate.tpl#L192)
   * then modified with non-functional changes to meet civilint's jshint criteria. 
   * Also modified by renaming variables with more  descriptive names (original code
   * relied on variables with names like c, d, t, j, etc.)
   * 
   * CRM.percentagepricesetfield.formatMoney(finalTotal, 2, currency_separator, thousandMarker);
   */
  formatMoney: function formatMoney(amount, precision, currencySeparator, thousandsMarker){
    precision = isNaN(precision = Math.abs(precision)) ? 2 : precision;
    currencySeparator = currencySeparator == undefined ? "," : currencySeparator;
    thousandsMarker = thousandsMarker == undefined ? "." : thousandsMarker;
    var negativeMarker = amount < 0 ? "-" : "";
    // Unsure of the  meaning of 'i' here; todo: figure  this out and rename for more readable code.
    var i = parseInt(amount = Math.abs(+amount || 0).toFixed(precision)) + "";
    thousandsSplitLength = (thousandsSplitLength = i.length) > 3 ? thousandsSplitLength % 3 : 0;
        
    return negativeMarker + (thousandsSplitLength ? i.substr(0, thousandsSplitLength) + thousandsMarker : "") + i.substr(thousandsSplitLength).replace(/(\d{3})(?=\d)/g, "$1" + thousandsMarker) + (precision ? currencySeparator + Math.abs(amount - i).toFixed(precision).slice(2) : "");
  },

  /**
   * Function to override CiviCRM's window.calculateTotalFee()
   *
   * @returns float
   */
  calculateTotalFee: function calculateTotalFee() {
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
    return finalTotal;
  },

  /**
   * Calculate the correct total-plus-percentage amount.
   */
  calculateTotal: function calculateTotal() {
    var finalTotal = this.calculateTotalFee();

    // Older CiviCRM versions used 'seperator' instead of 'separator'
    var currency_separator;
    if (typeof separator === 'undefined') {
      currency_separator = seperator;
    }
    else {
      currency_separator = separator;
    }

    return CRM.percentagepricesetfield.formatMoney(finalTotal, 2, currency_separator, thousandMarker);
  }
};

cj(function() {
  // Replace CiviCRM's window.calculateTotalFee with our own. This function is
  // called by various core and extension JS code (e.g., Stripe) to determine
  // the actual total to be charged. In the case of Stripe, this is required so
  // that the payment_intent matches the amount that is eventually charged.
  originalCalculateTotalFee = window.calculateTotalFee;
  window.calculateTotalFee = CRM.percentagepricesetfield.calculateTotalFee;

  // Store the state of the checkbox, so we can restore it later.
  CRM.percentagepricesetfield.storePercentageState();

  if (CRM.vars.percentagepricesetfield.hide_and_force) {
    // Hide and force if so configured.
    cj('#' + CRM.vars.percentagepricesetfield.percentage_checkbox_id).prop('checked', true);
    cj('#' + CRM.vars.percentagepricesetfield.percentage_checkbox_id).closest('.crm-section').hide();
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
  if (undefined === CRM.percentagepricesetfield.monetarySymbol) {
    // Later civicrm versions don't wrap the monetarSymbol in a <b> element -- and really why would they?
    // So if  we still don't have a value for monetarySymbol, try getting
    // the first non-space string in the pricevalue div (this is thought to be
    // more likely to work in cases of multi-character symbols (e.g. "Lek")
    CRM.percentagepricesetfield.monetarySymbol = cj('#pricevalue').html().split(' ')[0];
  }

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

  // Add an event handler to set is_percentage any time the checkbox is manually changed.
  cj('#' + CRM.vars.percentagepricesetfield.percentage_checkbox_id).change(function(){
    CRM.percentagepricesetfield.is_percentage = cj('#' + CRM.vars.percentagepricesetfield.percentage_checkbox_id).prop('checked');
  });
});