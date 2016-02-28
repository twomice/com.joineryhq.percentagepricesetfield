/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

cj(function($) {
  // FIXME: probably don't need to override option_html_type(), rather just
  // add our own click/change handler to #html_type.
  percentagepricesetfield_option_html_type_original = option_html_type

  option_html_type = function(form) {
    var html_type_name = cj('#html_type').val();
    // Call the original even listener.
    percentagepricesetfield_option_html_type_original(form)
    if (html_type_name == 'CheckBox') {
      cj("#percentagepricesetfield-block").show();
    }
    else {
      cj("#percentagepricesetfield-block").hide();
    }
    is_percentagepricesetfield_change()
  }

  var is_percentagepricesetfield_change = function() {
    var html_type_name = cj('#html_type').val();
    if (html_type_name != 'CheckBox') {
      return;
    }
    if (cj('input#is_percentagepricesetfield').prop('checked')) {
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
  }

  // move bhfe fields to before price-block
  cj('input#is_percentagepricesetfield').closest('table').hide();
  cj('div#price-block').before('\
    <div id="percentagepricesetfield-block" class="hiddenElement" style="display: none;">\
      <table class="form-layout">\
        <tbody class="percentagepricesetfield_main"></tbody>\n\
        <tbody class="percentagepricesetfield_details"></tbody>\n\
      </table>\
    </div>  \
    \
  ')
  for (i in CRM.vars.percentagepricesetfield.bhfe_fields) {
    var field_id = CRM.vars.percentagepricesetfield.bhfe_fields[i]
    if (field_id == 'is_percentagepricesetfield') {
      tbodyClassName = 'percentagepricesetfield_main'
    }
    else {
      tbodyClassName = 'percentagepricesetfield_details'
    }
    cj('div#percentagepricesetfield-block table tbody.' + tbodyClassName).append('\
      <tr class="field_' + field_id +'">\
          <td class="label"></td>\
          <td class="input"></td>\
        </tr>\
      '
    )
    cj('label[for="' + field_id +'"]').appendTo('div#percentagepricesetfield-block tr.field_' + field_id +' td.label');
    cj('input#' + field_id +'').appendTo('div#percentagepricesetfield-block tr.field_' + field_id +' td.input');
  }
  // Clone financial_type_id field into percentagepricesetfield-block
  my_financial_type_id = cj('select#financial_type_id').closest('tr').clone()
  // Modfiy identifying attributes of cloned elements, recursively.
  my_financial_type_id.attr('id', 'percentagepricesetfield_financial_type');
  my_financial_type_id.find('*').each(function(idx, el) {
    var attributes = ['id', 'name', 'for'];
    for (i in attributes) {
      var attribute = attributes[i]
      var originalValue = el.getAttribute(attribute)
      if (originalValue) {
        el.setAttribute(attribute, 'percentagepricesetfield_' + originalValue)
      }
    }
  });

  // Add the cloned div.
  my_financial_type_id.appendTo('div#percentagepricesetfield-block tbody.percentagepricesetfield_details');

  // Set the value for the financial_type_id (this is of course recorded with the
  // first checkbox option of the price field, but it won't be set in the field
  // we've cloned.
  if (CRM.vars.percentagepricesetfield.hasOwnProperty('values')) {
    cj('#percentagepricesetfield_financial_type_id').val(CRM.vars.percentagepricesetfield.values.financial_type_id)
  }

  // Add change handler for "is percentage" checkbox
  cj('input#is_percentagepricesetfield').change(is_percentagepricesetfield_change)

  option_html_type();
})

