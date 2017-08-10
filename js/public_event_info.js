/**
 * Custom JavaScript functions for event info page.
 */

cj(function() {
  // Removed the price field row containing the percentage price set field, if any.
  CRM.$('td:contains('+ CRM.vars.percentagepricesetfield.PERCENTAGEPRICESETFIELD_PLACEHOLDER_LABEL +')').closest('tr').remove();
});