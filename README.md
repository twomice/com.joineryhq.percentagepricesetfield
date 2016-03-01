percentagepricesetfield
=======================

Provides options to add a "Percentage" price set field, which can add an
additional amount to a transaction, as a configurable percentage of other
selected price set options.

As a price set field, this option will appear as a line item in contribution
records and receipts.

Tested with contribution pages and events, with sales tax and with the CiviDiscount
extension. (For proper CiviDiscount support, this extension must be installed
after CiviDiscount.)

Configuration
=============

After enabling the extension, you'll find a new option named "Field calculates
'Automatic Additional Percentage'" available for price set fields of the type
"CheckBox". Selecting this option reveals additional options:

- Percentage: The actual percentage to be used in calculations, e.g, 3.5.
- Apply percentage to tax amounts: If checked (the default setting), this will
  cause percentage calculations to be performed on the total including any
  added sales tax. If un-checked, percentage calculations will be made on the
  total exclusive of sales tax.  (Most sites don't use sales tax, in which case
  this setting will be inconsequential.)

The usual CiviCRM price field options are still available as well.
