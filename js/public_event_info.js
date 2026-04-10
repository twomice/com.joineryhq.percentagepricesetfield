/**
 * Custom JavaScript functions for event info page.
 */

CRM.$(function($){
  // Remove the price field row containing the percentage price set field, if any.
  CRM.$('td:contains('+ CRM.vars.percentagepricesetfield.PERCENTAGEPRICESETFIELD_PLACEHOLDER_LABEL +')').closest('tr').remove();
});