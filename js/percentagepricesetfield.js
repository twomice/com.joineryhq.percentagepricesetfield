/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

cj(function($) {
  console.log("I'm here!");
  var ts = CRM.ts('org.example.myextension')
  var  percentagepricesetfield_option_html_type_original = option_html_type

  option_html_type = function(form) {
    var html_type_name = cj('#html_type').val();
    if (html_type_name == 'percentage') {
      console.log('foo');
        cj("#showoption").hide();
        cj("#price-block").show();
        cj("#optionsPerLine").hide();
        cj("#optionsPerLineDef").hide();
        cj('label[for="price"]').html('Percentage');
        cj('input#price').closest('td').find('span.description').css('visibility', 'hidden')
        cj('input#price').closest('td').find('a.helpicon').css('visibility', 'hidden')
    }
    else {
      cj('label[for="price"]').html(CRM.vars.percentagepricesetfield.price_label)
      cj('input#price').closest('td').find('span.description').css('visibility', 'visible')
      cj('input#price').closest('td').find('a.helpicon').css('visibility', 'visible')
      percentagepricesetfield_option_html_type_original(form)
    }
  }
  option_html_type();
})