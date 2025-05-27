/**
 * Custom JavaScript functions for user-facing forms that display price set
 * fields (e.g., event registration forms, contribution forms).
 */

CRM.percentagepricesetfield = {
  // Storage for most recent value of percentage checkbox, for use in cases
  // where we have to automatically disable it (e.g., when disabling the
  // percentage option based on the selected payment method).
  is_percentage: CRM.vars.percentagepricesetfield.is_default,

  // Function storage for CiviCRM's original calculateTotalFee() function
  originalCalculateTotalFee: window.calculateTotalFee,

  storePercentageState: function storePercentageState() {
    var field = cj('#' + CRM.vars.percentagepricesetfield.percentage_field_id);
    if (field.attr('type') === 'checkbox') {
      CRM.percentagepricesetfield.is_percentage = field.prop('checked');
    } else if (field.attr('type') === 'text') {
      CRM.percentagepricesetfield.is_percentage = Boolean(field.val());
    }
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
      cj('#' + CRM.vars.percentagepricesetfield.percentage_field_id).closest('.crm-section').hide();
      // Store the state of the checkbox, so we can restore it later.
      CRM.percentagepricesetfield.storePercentageState();
      // Un-check the checkbox; we have to actually uncheck it, because it's
      // a Price Set Field and will be treated as a line item if checked.
      cj('#' + CRM.vars.percentagepricesetfield.percentage_field_id).prop('checked', false);
    }
    else {

      // Restore the previous state of the percentage checkbox.
      cj('#' + CRM.vars.percentagepricesetfield.percentage_field_id).prop('checked', CRM.percentagepricesetfield.isPercentage());
      // Dispaly the option again.
      if (!CRM.vars.percentagepricesetfield.hide_and_force) {
        cj('#' + CRM.vars.percentagepricesetfield.percentage_field_id).closest('.crm-section').show();
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
    var total = CRM.percentagepricesetfield.calculateTotalFee();
    cj('#percentagepricesetfield_pricevalue').html(CRM.formatMoney(total, false, moneyFormat));
  },

  /**
   * Function to override CiviCRM's window.calculateTotalFee()
   *
   * @returns float
   */
  calculateTotalFee: function calculateTotalFee() {
    CRM.percentagepricesetfield.storePercentageState();
    // Calculate total per original calculateTotalFee function:
    var baseTotal;
    var taxTotal = 0;
    // If we're not applying a percentage, just use Core's calculation.
    if (!CRM.percentagepricesetfield.is_percentage) {
      return CRM.percentagepricesetfield.originalCalculateTotalFee();
    }

    if (CRM.vars.percentagepricesetfield.apply_to_taxes == 1) {
      // If we apply the percentage to taxes, we can just use Core's calculation of baseTotal
      baseTotal = CRM.percentagepricesetfield.originalCalculateTotalFee();
    }
    else {
      // If we're NOT applying the percentage to taxes, we must calculate baseTotal
      // *without* taxes.
      baseTotal = 0;
      var lineTax = 0;
      var lineRawTotal;
      cj("#priceset [price]").each(function () {
        lineRawTotal = cj(this).data('line_raw_total');
        if (lineRawTotal) {
          lineTax = lineRawTotal - (lineRawTotal / (1 + (CRM.vars.percentagepricesetfield.tax_rate/100)));
          baseTotal += lineRawTotal - lineTax;
          taxTotal += lineTax;
        }
      });
    }

    var finalTotal;
    // Calculate the appropriate percentage.
    var percentage = CRM.vars.percentagepricesetfield.percentage;
    var extra = (baseTotal*percentage/100);
    // Consider any taxes to be applied to the extra percentage amount.
    var extra_tax = extra * (CRM.vars.percentagepricesetfield.tax_rate / 100);
    var total = extra + baseTotal + taxTotal + extra_tax;
    finalTotal = Math.round( (total + Number.EPSILON) *100)/100;
    return finalTotal;
  },

};

cj(function() {
  // Replace CiviCRM's window.calculateTotalFee with our own. This function is
  // called by various core and extension JS code (e.g., Stripe) to determine
  // the actual total to be charged. In the case of Stripe, this is required so
  // that the payment_intent matches the amount that is eventually charged.
  window.calculateTotalFee = CRM.percentagepricesetfield.calculateTotalFee;

  // Store the state of the checkbox, so we can restore it later.
  CRM.percentagepricesetfield.storePercentageState();

  if (CRM.vars.percentagepricesetfield.hide_and_force) {
    // Hide and force if so configured.
    cj('#' + CRM.vars.percentagepricesetfield.percentage_field_id).prop('checked', true);
    cj('#' + CRM.vars.percentagepricesetfield.percentage_field_id).closest('.crm-section').hide();
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
  cj('#' + CRM.vars.percentagepricesetfield.percentage_field_id).change(function(){
    CRM.percentagepricesetfield.storePercentageState();
  });
});