/**
 * Custom JavaScript functions for the form at /civicrm/admin/price/field.
 */
/*global CRM, ts */
cj(function($) {
  // Override option_html_type() with our own version of it.
  // This is necessary because div#html_type has this function as an onChange
  // event handler, and thus it may be called after any onChange event handler
  // we add for that element. By replacing the function with our own definition
  // of it, we can control the order of execution.
  percentagepricesetfield_option_html_type_original = option_html_type;
  option_html_type = function(form) {
    var html_type_name = cj('#html_type').val();
    // Call the original event listener.
    percentagepricesetfield_option_html_type_original(form);
    cj("#percentagepricesetfield-block").show();
    is_percentagepricesetfield_change();
  };

  /**
   * Change handler for hide-and-force field.
   */
  var hide_and_force_change = function() {
    if (cj('#percentagepricesetfield_hide_and_force').is(':checked')) {
      // If hide-and-force is true, then hide the is_default checkbox and show
      // an [x] to indicate the value is forced.
      cj('#percentagepricesetfield_is_default').hide().after('<span id="percentagepricesetfield_is_default_x">[x]</span>');
    }
    else {
      // If hide-and-force is false, then show the is_default checkbox and hide
      // the [x] indicator.
      cj('#percentagepricesetfield_is_default').show();
      cj('#percentagepricesetfield_is_default_x').remove();
    }
  };

  /**
   * OnChange handler for is_percentagepricesetfield checkbox.
   * Show and hide some relevant sections, depending on whether the
   * is_percentagepricesetfield checkbox is checked.
   */
  var is_percentagepricesetfield_change = function() {
    var el_is_percentagepricesetfield = cj('input#is_percentagepricesetfield');
    if (
      (el_is_percentagepricesetfield.attr('type') == 'checkbox' && el_is_percentagepricesetfield.prop('checked')) ||
      (el_is_percentagepricesetfield.attr('type') == 'hidden' && el_is_percentagepricesetfield.val() == 1)
    ) {
      cj("#showoption").hide();
      cj("#optionsPerLine").hide();
      cj(".crm-price-field-form-block-is_display_amounts").hide();
      cj("#percentagepricesetfield_financial_type").show();
      cj("div#percentagepricesetfield-block table tbody.percentagepricesetfield_details").show();
      cj("div#percentagepricesetfield-block").addClass('percentagepricesetfield-highlight');
    }
    else {
      cj("#showoption").show();
      cj("#optionsPerLine").show();
      cj(".crm-price-field-form-block-is_display_amounts").show();
      cj("#percentagepricesetfield_financial_type").hide();
      cj("div#percentagepricesetfield-block table tbody.percentagepricesetfield_details").hide();
      cj("div#percentagepricesetfield-block").removeClass('percentagepricesetfield-highlight');
    }
  };

  var percentagepricesetfield_disable_payment_methods_change = function() {
    var msg_id = 'disable_payment_methods_message';
    var msg = ts('"Required" setting not available while "Disable for payment methods" setting is enabled.');
    CRM.$('em#'+ msg_id).remove();
    if (CRM.$('input[id^="percentagepricesetfield_disable_payment_methods_"]:checked').length) {
      CRM.$('input#is_required').hide().after('<em id="' + msg_id + '">' +  msg + '</em>');
    }
    else {
      CRM.$('input#is_required').show();
    }
  };

  // Move bhfe fields to before price-block. ("bhfe" or "BeforeHookFormElements"
  // fields are added in this extension's buildForm hook.)
  // First create a container to hold these fields, including two separate
  // tbody elements (so two groups of fields can be hidden/displayed independently).
  cj('div#price-block').before(
    '<div id="percentagepricesetfield-block" class="hiddenElement" style="display: none;">' +
    '  <table class="form-layout">' +
    '    <tbody class="percentagepricesetfield_main"></tbody>' +
    '    <tbody class="percentagepricesetfield_details"></tbody>' +
    '  </table>' +
    '</div>'
  );
  // Add a unique ID to the table holding bhfe fields, so we can access it
  // directly later.
  cj('input#is_percentagepricesetfield').closest('table').attr('id', 'bhfe-table');
  // Move the is_percentagepricesetfield bhfe field to its own tbody (there's
  // a second tbody, for other options, which will be hidden, but we don't want
  // to hide this master field).
  cj('div#percentagepricesetfield-block table tbody.percentagepricesetfield_main').append(cj('table#bhfe-table input#is_percentagepricesetfield').closest('tr'));
  // Move remaining bhfe fields into the second tbody.
  cj('table#bhfe-table tr').each(function(idx, el) {
    el = cj(el);
    cj('div#percentagepricesetfield-block table tbody.percentagepricesetfield_details').append(el);
    var input_name = el.find('input').attr('name').split('[')[0];
    el.attr('id', 'tr-' + input_name);
    var cells = el.find('td');
    cj(cells[0]).addClass('label');
    cj(cells[1]).addClass('input');
  });
  cj('div#percentagepricesetfield-block td').removeClass('nowrap');

  // Freeze hide-and-force checkbox if so instructed. See "NOTE ON FREEZING
  // HIDE-AND-FORCE" in percentagepricesetfield.php.
  if (CRM.vars.percentagepricesetfield.hide_and_force_element_freeze) {
    CRM.$('#percentagepricesetfield_hide_and_force').hide().after('[x]');
    // Also freeze the is_default checkbox, since it will be forced to true
    // whenever hide-and-force is true.
    CRM.$('#percentagepricesetfield_is_default').hide().after('[x]');
  }
  else {
    // If the global hide-and-force-all is false, then hide-and-force might be
    // anything, so run its change handler for appropriate display adjustements.
    hide_and_force_change();
  }

  //
  // Append any descriptions for bhfe fields.
  for (var i in CRM.vars.percentagepricesetfield.descriptions) {
    cj('tr#tr-'+ i +' td.input').append('<div class="description">'+ CRM.vars.percentagepricesetfield.descriptions[i] +'</div>');
  }

  // Remove the bhfe table. It should be empty at this point, but clean up anyway.
  cj('table#bhfe-table').remove();

  // Clone financial_type_id field into percentagepricesetfield-block
  myFinancialTypeId = cj('select#financial_type_id').closest('tr').clone();
  // Modfiy identifying attributes of cloned elements, recursively.
  myFinancialTypeId.attr('id', 'percentagepricesetfield_financial_type');
  myFinancialTypeId.find('*').each(function(idx, el) {
    var attributes = ['id', 'name', 'for'];
    for (var i in attributes) {
      var attribute = attributes[i];
      var originalValue = el.getAttribute(attribute);
      if (originalValue) {
        el.setAttribute(attribute, 'percentagepricesetfield_' + originalValue);
      }
    }
  });

  // Add the cloned div.
  myFinancialTypeId.appendTo('div#percentagepricesetfield-block tbody.percentagepricesetfield_details');

  // Set the value for the financial_type_id (this is of course recorded with the
  // first checkbox option of the price field, but it won't be set in the field
  // we've cloned).
  if (CRM.vars.percentagepricesetfield && CRM.vars.percentagepricesetfield.hasOwnProperty('values')) {
    cj('#percentagepricesetfield_financial_type_id').val(CRM.vars.percentagepricesetfield.values.financial_type_id);
  }

  // Add change handler for "is percentage" checkbox
  cj('input#is_percentagepricesetfield').change(is_percentagepricesetfield_change);

  // Add change handler for "hide and force" checkbox
  cj('#percentagepricesetfield_hide_and_force').change(hide_and_force_change);

  // Add change handler for "disable for payment method" checkbox
  CRM.$('input[id^="percentagepricesetfield_disable_payment_methods_"]').change(percentagepricesetfield_disable_payment_methods_change);

  // Fire the onChange event handler for the html_type field. This adjusts form layout
  // to properly support existing percentage priceset fields.
  // Note: on "new price field" forms, we could call this as cj('#html_type').change();
  // but on "edit price field" forms, #html_type has no onChange event handler.
  // So we call the function directly in both cases.
  option_html_type();

  // Fire the onChange event handler for "disable for payment method" checkbox.
  percentagepricesetfield_disable_payment_methods_change();
});

