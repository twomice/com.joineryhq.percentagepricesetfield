/**
 * Custom JavaScript functions for the form at /civicrm/admin/price/field.
 */
cj(function($) {
  // Additional onChange event handler for the html_type field.
  cj('#html_type').change(function(form) {
    // Call the original event listener.
    if (cj('#html_type').val() == 'CheckBox') {
      cj("#percentagepricesetfield-block").show();
    }
    else {
      cj("#percentagepricesetfield-block").hide();
    }
    is_percentagepricesetfield_change();
  });

  /**
   * OnChange handler for is_percentagepricesetfield checkbox.
   * If html_type field is "CheckBox", show and hide some relevant sections,
   * depending on whether the is_percentagepricesetfield checkbox is checked.
   */
  var is_percentagepricesetfield_change = function() {
    if (cj('#html_type').val() != 'CheckBox') {
      return;
    }

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
    }
    else {
      cj("#showoption").show();
      cj("#optionsPerLine").show();
      cj(".crm-price-field-form-block-is_display_amounts").show();
      cj("#percentagepricesetfield_financial_type").hide();
      cj("div#percentagepricesetfield-block table tbody.percentagepricesetfield_details").hide();
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
  cj('input#is_percentagepricesetfield').closest('table').attr('id', 'bfhe-table');
  // For each bhfe field, create a tr in the correct tbody, and move each field/label
  // into the correct td element.
  if (CRM.vars.percentagepricesetfield && CRM.vars.percentagepricesetfield.bhfe_fields) {
    for (var i in CRM.vars.percentagepricesetfield.bhfe_fields) {
      var field_id = CRM.vars.percentagepricesetfield.bhfe_fields[i];
      if (field_id == 'is_percentagepricesetfield') {
        tbodyClassName = 'percentagepricesetfield_main';
      }
      else {
        tbodyClassName = 'percentagepricesetfield_details';
      }
      cj('div#percentagepricesetfield-block table tbody.' + tbodyClassName).append(
        '<tr class="field_' + field_id +'">' +
        '    <td class="label"></td>' +
        '    <td class="input"></td>' +
        '  </tr>'
      );
      cj('div#percentagepricesetfield-block tr.field_' + field_id +' td.label').append(cj('label[for="' + field_id +'"]').closest('td').html());
      cj('div#percentagepricesetfield-block tr.field_' + field_id +' td.input').append(cj('input#' + field_id).closest('td').html());
    }
  }
  // Remove the bhfe table. Because we used the append() method above, the fields
  // were copied rather than moved, so we remove the entire table in order to
  // remove the original fields.
  cj('table#bfhe-table').remove();

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

  // Fire the change handler for the html_type field. This adjusts form layout
  // to properly support existing percentage priceset fields.
  cj('#html_type').change();
});

