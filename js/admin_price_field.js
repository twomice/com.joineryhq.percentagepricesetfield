/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

cj(function($) {
  // Override option_html_type() with our own version of it.
  percentagepricesetfield_option_html_type_original = option_html_type
  option_html_type = function(form) {
    var html_type_name = cj('#html_type').val();
    // Call the original event listener.
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
    console.log('html_type_name', html_type_name)
    if (html_type_name != 'CheckBox') {
      return;
    }

    var el_is_percentagepricesetfield = cj('input#is_percentagepricesetfield')
    if (
      (el_is_percentagepricesetfield.attr('type') == 'checkbox' && el_is_percentagepricesetfield.prop('checked'))
      || (el_is_percentagepricesetfield.attr('type') == 'hidden' && el_is_percentagepricesetfield.val() == 1)
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
  }

  // move bhfe fields to before price-block
  cj('div#price-block').before('\
    <div id="percentagepricesetfield-block" class="hiddenElement" style="display: none;">\
      <table class="form-layout">\
        <tbody class="percentagepricesetfield_main"></tbody>\n\
        <tbody class="percentagepricesetfield_details"></tbody>\n\
      </table>\
    </div>  \
    \
  ')
  cj('input#is_percentagepricesetfield').closest('table').attr('id', 'bfhe-table');
  for (i in CRM.vars.percentagepricesetfield.bhfe_fields) {
    var field_id = CRM.vars.percentagepricesetfield.bhfe_fields[i]
    console.log('field_id', field_id)
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
    cj('div#percentagepricesetfield-block tr.field_' + field_id +' td.label').append(cj('label[for="' + field_id +'"]').closest('td').html());
    cj('div#percentagepricesetfield-block tr.field_' + field_id +' td.input').append(cj('input#' + field_id).closest('td').html());
  }
  cj('table#bfhe-table').remove();

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

