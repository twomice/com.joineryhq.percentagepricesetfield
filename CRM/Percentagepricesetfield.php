<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


class CRM_Percentagepricesetfield {
  function convertField() {
    $fid = CRM_Utils_Request::retrieve('fid', 'Int');
    $to = CRM_Utils_Request::retrieve('to', 'String');

    // Now find the like-named checkbox field in the new price set. We need its ID.
    $params = array(
      'id' => $fid,
    );
    CRM_Price_BAO_PriceField::retrieve($params, $price_field_values);

    drupal_set_message("FIXME: this action should have changed the field $fid to a $to.", 'error');

    CRM_Utils_System::redirect('/civicrm/admin/price/field?reset=1&action=browse&sid='. $price_field_values['price_set_id']);
  }
}