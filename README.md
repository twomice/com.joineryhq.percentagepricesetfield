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

## Support
![screenshot](/images/joinery-logo.png)

Joinery provides services for CiviCRM including custom extension development, training, data migrations, and more. We aim to keep this extension in good working order, and will do our best to respond appropriately to issues reported on its [github issue queue](https://github.com/twomice/com.joineryhq.percentagepricesetfield/issues). In addition, if you require urgent or highly customized improvements to this extension, we may suggest conducting a fee-based project under our standard commercial terms.  In any case, the place to start is the [github issue queue](https://github.com/twomice/com.joineryhq.percentagepricesetfield/issues) -- let us hear what you need and we'll be glad to help however we can.

And, if you need help with any other aspect of CiviCRM -- from hosting to custom development to strategic consultation and more -- please contact us directly via https://joineryhq.com