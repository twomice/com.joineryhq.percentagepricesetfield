<?php

require_once 'percentagepricesetfield.civix.php';

/**
 * Implements hook_civicrm_copy().
 */
function percentagepricesetfield_civicrm_copy($objectName, &$object) {
  if (strtolower($objectName) == 'set') {
    // Get the price set that this one was copied from, basd on the convention
    // that copied price sets always have '__Copy_id_[N]_' appended
    // to their name, where [N] is an integer (Note: it's often, but not always,
    // the ID of the new price set, because N is calculated using max(id)+1, and
    // max(id) is not always equivalent to the next serial ID.)
    $original_price_set_name = preg_replace('/__Copy_id_[0-9]+_$/', '', $object->name);
    $params = array(
      'name' => $original_price_set_name,
    );
    CRM_Price_BAO_PriceSet::retrieve($params, $source_price_set);
    // Get all percentage fields in the source prices set:
    $source_percentage_field_ids = _percentagepricesetfield_get_percentage_field_ids($source_price_set['id'], FALSE);
    foreach ($source_percentage_field_ids as $field_id) {
      // Get the percentage price field values for this field.
      $source_percentage_values = _percentagepricesetfield_get_settings($field_id);
      // Get the price field values for this field; we need its 'name' value.
      $params = array(
        'id' => $field_id,
      );
      CRM_Price_BAO_PriceField::retrieve($params, $source_price_field_values);
      // Now find the like-named checkbox field in the new price set. We need its ID.
      $params = array(
        'price_set_id' => $object->id,
        'name' => $source_price_field_values['name'],
        'html_type' => 'CheckBox',
      );
      CRM_Price_BAO_PriceField::retrieve($params, $new_price_field_values);
      // Use the source percentage values to mark the new field as a percentage field.
      $source_percentage_values['field_id'] = $new_price_field_values['id'];
      _percentagepricesetfield_create_field($source_percentage_values);
    }
  }
}

/**
 * Implements hook_civicrm_buildAmount().
 */
function percentagepricesetfield_civicrm_buildAmount($pageType, &$form, &$amount) {
  if (!empty($form->_priceSetId)) {
    $field_ids = _percentagepricesetfield_get_percentage_field_ids($form->_priceSetId, TRUE);
    $field_id = array_pop($field_ids);
    // This checkbox field should have exactly one option. We need that option
    // value because the checkbox element's "id" attribute will be
    // "price_[field_id]_[field_value]".
    $field_value = _percentagepricesetfield_get_field_value($field_id);

    if (!empty($form->_submitValues)) {
      // If this form is being submitted, we'll adjust the line item label, if
      // the price set contains a percentage field.
      foreach ($amount[$field_id]['options'] as $option_id => &$option) {
        // Apply percentage if "percentage" checkbox was checked.
        if (!empty($form->_submitValues["price_{$field_id}"][$option_id])) {
          $option['amount'] = _percentagepricesetfield_calculate_additional_amount($form);
          $percent = _percentagepricesetfield_get_percentage($form->_priceSetId);
          $option['label'] = $percent . '%';
        }
      }
    }
  }
}

/**
 * Implements hook_civicrm_buildForm().
 */
function percentagepricesetfield_civicrm_buildForm($formName, &$form) {
  switch ($formName) {
    case 'CRM_Price_Form_Field':
      _percentagepricesetfield_buildForm_AdminPriceField($form);
      break;

    case 'CRM_Event_Form_ParticipantFeeSelection':
    case 'CRM_Event_Form_Registration_Register':
    case 'CRM_Contribute_Form_Contribution_Main':
    case 'CRM_Contribute_Form_Contribution':
    case 'CRM_Event_Form_Participant':
      _percentagepricesetfield_buildForm_public_price_set_form($form);
      break;

    case 'CRM_Price_Form_Preview':
      // TODO: Would be nice to get this working.
      break;
  }
}

/**
 * Implements hook_civicrm_postProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postProcess
 */
function percentagepricesetfield_civicrm_postProcess($formName, &$form) {
  if ($formName == 'CRM_Price_Form_Field') {
    _percentagepricesetfield_postProcess_AdminPriceField($form);
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function percentagepricesetfield_civicrm_config(&$config) {
  _percentagepricesetfield_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function percentagepricesetfield_civicrm_xmlMenu(&$files) {
  _percentagepricesetfield_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function percentagepricesetfield_civicrm_install() {
  return _percentagepricesetfield_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function percentagepricesetfield_civicrm_uninstall() {
  return _percentagepricesetfield_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function percentagepricesetfield_civicrm_enable() {
  return _percentagepricesetfield_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function percentagepricesetfield_civicrm_disable() {
  return _percentagepricesetfield_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function percentagepricesetfield_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _percentagepricesetfield_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function percentagepricesetfield_civicrm_managed(&$entities) {
  return _percentagepricesetfield_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function percentagepricesetfield_civicrm_caseTypes(&$caseTypes) {
  _percentagepricesetfield_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function percentagepricesetfield_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _percentagepricesetfield_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_alterContent().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterContent
 */
function percentagepricesetfield_civicrm_alterContent(&$content, $context, $tplName, &$object) {
  $args = func_get_args();
  $args['_GET'] = $_GET;
  if ($func = call_user_func_array('_percentagepricesetfield_get_content_pricesetid_function', $args)) {
    if (function_exists($func)) {
      $price_set_id = call_user_func_array($func, $args);
    }
  }
  if (!empty($price_set_id)) {
    $allow_hide_and_force = _percentagepricesetfield_allow_hide_and_force($content, $context, $tplName, $object, $_GET);
    $field_ids = _percentagepricesetfield_get_percentage_field_ids($price_set_id, TRUE);
    if (empty($field_ids)) {
      // No enabled percentage fields were found for this form. Nothing to do.
      return;
    }
    $field_id = array_pop($field_ids);

    // This checkbox field should have exactly one option. We need that option
    // value because the checkbox element's "id" attribute will be
    // "price_[field_id]_[field_value]".
    $field_value = _percentagepricesetfield_get_field_value($field_id);
    if (!$field_value) {
      return;
    }

    // Insert our JavaScript code and variables.
    $vars = array(
      'percentage' => _percentagepricesetfield_get_percentage($price_set_id),
      'percentage_checkbox_id' => "price_{$field_id}_{$field_value}",
      'hide_and_force' => (int) ($allow_hide_and_force && _percentagepricesetfield_get_setting_value($field_id, 'hide_and_force')),
      'disable_payment_methods' => _percentagepricesetfield_get_setting_value($field_id, 'disable_payment_methods'),
    );
    $resource = CRM_Core_Resources::singleton();
    $content .= '<script type="text/javascript">';
    $content .= 'CRM.vars.percentagepricesetfield = ' . json_encode($vars) . ';';
    $content .= file_get_contents($resource->getPath('com.joineryhq.percentagepricesetfield', 'js/public_price_set_form.js'));
    $content .= '</script>';
  }
}

/**
 * Implements hook_civicrm_pageRun().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_pageRun
 */
function percentagepricesetfield_civicrm_pageRun(&$page) {
  if ($page->getVar('_name') == 'CRM_Price_Page_Field') {
    // Get a list of all percentage fields in this prices set.
    $price_set_id = $page->getVar('_sid');
    $field_ids = _percentagepricesetfield_get_percentage_field_ids($price_set_id, FALSE);
    // Adjust the template variables for each percentage field, to hide certain
    // checkbox-related functionality which is irrelevant to percentage fields.
    $tpl = CRM_Core_Smarty::singleton();
    $tpl_vars = & $tpl->get_template_vars();
    foreach ($field_ids as $field_id) {
      if (array_key_exists('priceField', $tpl_vars) && array_key_exists($field_id, $tpl_vars['priceField'])
      ) {
        // Avoid printing 'Edit Price Options' link.
        $tpl_vars['priceField'][$field_id]['html_type'] = 'Text';
        // Display an intelligible value in the Field Type column.
        $tpl_vars['priceField'][$field_id]['html_type_display'] = 'Percentage';
      }
    }
  }
}

/**
 * Implements hook_civicrm_validateForm().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_validateForm
 */
function percentagepricesetfield_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if ($formName == 'CRM_Price_Form_Field') {
    // If the field is set as is_percentagepricesetfield,
    // make sure there are no (enabled) others already in this fieldset.
    if (CRM_Utils_Array::value('is_percentagepricesetfield', $fields)) {
      $field_ids = _percentagepricesetfield_get_percentage_field_ids($fields['sid']);
      $fid = CRM_Utils_Array::value('fid', $fields);
      while (($fid_key = array_search($fid, $field_ids)) !== FALSE) {
        unset($field_ids[$fid_key]);
      }
      if (!empty($field_ids)) {
        $errors['is_percentagepricesetfield'] = ts('This price set already has a percentage field. Please disable or delete that field before creating a new one.');
      }

      // Ensure financial_type_id.
      if (!CRM_Utils_Array::value('percentagepricesetfield_financial_type_id', $fields)) {
        $errors['financial_type_id'] = ts('Financial Type is required.');
      }
    }
  }
}

/**
 * Get the field_ids of all percentage fields in the given price set.
 *
 * @param int $price_set_id The ID of the price set to check; if string 'ALL',
 *   return all percentage price fields regardlesss of price_set.
 * @param bool $limit_enabled If TRUE, return IDs of only enabled percentage
 *  fields; otherwise return IDs of all percentage fields.
 * @return Array of field ids
 */
function _percentagepricesetfield_get_percentage_field_ids($price_set_id, $limit_enabled = TRUE) {
  // Static cache.
  static $ret = array();
  $key = serialize(func_get_args());
  if (!array_key_exists($price_set_id, $ret)) {
    $field_ids = array();

    $dao = new CRM_Price_DAO_PriceField();
    if ($price_set_id != 'ALL') {
      $dao->price_set_id = $price_set_id;
    }
    if ($limit_enabled) {
      $dao->is_active = 1;
    }
    $dao->find();
    $ids = array();
    while ($dao->fetch()) {
      $ids[] = (int) $dao->id;
    }
    unset($dao);

    if (!empty($ids)) {
      $query = "
        SELECT field_id
        FROM civicrm_percentagepricesetfield
        WHERE field_id IN (" . implode(',', $ids) . ")
      ";
      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        $field_ids[] = $dao->field_id;
      }
    }
    $ret[$key] = $field_ids;
  }
  return $ret[$key];
}

/**
 * Calculate the additional amount to add, based on selected price options and
 * the percentage field.
 *
 * @param  Object $form The form being processed.
 * @return Float The additional amount to be added.
 */
function _percentagepricesetfield_calculate_additional_amount($form) {
  // No need to run this twice, though buildAmount is sometimes called more than
  // once per request.
  static $additional_amount;
  if (!isset($additional_amount)) {
    $additional_amount = 0;
    if ($form->_priceSetId) {
      $field_ids = _percentagepricesetfield_get_percentage_field_ids($form->_priceSetId);
      $field_id = array_shift($field_ids);

      $base_total = 0;

      $line_items = array();
      $params = $form->_submitValues;

      if (!empty($form->_values['fee'])) {
        $fields = $form->_values['fee'];
      }
      else {
        $fields = $form->_priceSet['fields'];
      }
      unset($fields[$field_id]);

      CRM_Price_BAO_PriceSet::processAmount($fields, $params, $line_items);

      // Calculate differently depending on "apply to taxes" setting.
      if (_percentagepricesetfield_get_setting_value($field_id, 'apply_to_taxes')) {
        // $params['amount'] holds the total with any taxes, so we can just use it.
        $base_total = $params['amount'];
      }
      else {
        $base_total = 0;
        // If we're not configured to apply the percentage to taxes, then apply it
        // to each line item individually.
        foreach ($line_items as $line_item) {
          if ($line_item['price_field_id'] != $field_id) {
            $base_total += $line_item['line_total'];
          }
        }
      }

      $percentage = _percentagepricesetfield_get_percentage($form->_priceSetId);
      $additional_amount = round(($base_total * $percentage / 100), 2);
    }
  }
  return $additional_amount;
}

/**
 * Determine whether or not to apply the percentage to tax amounts, for a given
 * percentage field.
 *
 * @param  Integer $field_id The ID of the percentage field.
 * @return Bool
 */
function _percentagepricesetfield_get_setting_value($field_id, $setting_name) {
  $value = _percentagepricesetfield_get_setting_value_override($setting_name);
  if ($value === NULL) {
    $values = _percentagepricesetfield_get_settings($field_id);
    $value = $values[$setting_name];
  }
  return $value;
}

/**
 * Get the appropriate value from global settings for the given field setting
 * name. Return any value that should override the given field setting, or NULL
 * if there is no such value.
 *
 * @param string $setting_name
 */
function _percentagepricesetfield_get_setting_value_override($setting_name) {
  switch ($setting_name) {
    case 'hide_and_force':
      // TODO: Refactor to something more re-usable.
      $result = civicrm_api3(
        'Setting', 'get', array(
          'sequential' => 1,
          'return' => array("percentagepricesetfield_hide_and_force_all"),
        )
      );
      $value = $result['values'][0]['percentagepricesetfield_hide_and_force_all'];
      if ((bool) $value) {
        return $value;
      }
      else {
        return NULL;
      }
      break;
  }
}

/**
 * Get the stored configuration settings for a given percentage field.
 *
 * @param  Integer $field_id The ID of the percentage field.
 * @return Array of setting values.
 */
function _percentagepricesetfield_get_settings($field_id) {
  static $ret = array();
  if (!array_key_exists($field_id, $ret)) {
    $values = array();
    if (!$field_id) {
      return $values;
    }

    $valid_fields = _percentagepricesetfield_get_valid_fields();
    $field_names = array_keys($valid_fields);
    $fields = implode(',', $field_names);
    $query = "
      SELECT $fields
      FROM civicrm_percentagepricesetfield
      WHERE
        field_id = %1
    ";
    $params = array(
      1 => array($field_id, 'Integer'),
    );
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $dao->fetch();
    if ($dao->N) {
      foreach ($field_names as $field_name) {
        $values[$field_name] = _percentagepricesetfield_preprocess_saved_value($field_name, $dao->$field_name);
      }
    }

    $ret[$field_id] = $values;
  }
  return $ret[$field_id];
}

/**
 * Get the configured percentage setting for the percentage field in a
 * given price set.
 *
 * @param  Integer $price_set_id
 * @return Float
 */
function _percentagepricesetfield_get_percentage($price_set_id) {
  $field_ids = _percentagepricesetfield_get_percentage_field_ids($price_set_id);
  $field_id = array_shift($field_ids);
  $values = _percentagepricesetfield_get_settings($field_id);
  return $values['percentage'];
}

/**
 * buildForm hook handler for public-facing forms containing price set fields
 * (e.g., event registration forms, contribution pages)
 *
 * @param object $form
 */
function _percentagepricesetfield_buildForm_public_price_set_form($form) {
  $field_id = _percentagepricesetfield_get_form_percentage_field_id($form);
  if ($field_id) {
    $field = & $form->_elements[$form->_elementIndex["price_{$field_id}"]];
    // Get a reference to the last element in the $field->_elements array.
    end($field->_elements);
    $element = & $field->_elements[key($field->_elements)];
    // Use the field label as the label for this checkbox element.
    $element->_text = $field->_label;
    // Set this checkbox's "price" to 0. This allows us to avoid having
    // this checkbox affect that calculation, and we'll use our  own
    // JavaScript to adjust the total based on the percentage. CiviCRM
    // uses a custom format for this attribute, parsing it later in
    // JavaScript to auto-calculate the total (see
    // CRM/Price/Form/Calculate.tpl).
    $element->_attributes['price'] = preg_replace('/(\["[^"]+",")[^|]+(\|.+)$/', '${1}0${2}', $element->_attributes['price']); // e.g., ["30","20||"]: change "20" to "0".
    $element_id = $field->_name . '_' . $element->_attributes['id'];

    // Store $element_id in the form so we can easily access it elsewhere.
    $form->_percentage_checkbox_id = $element_id;

    // Remove this field's label (we copied it to the checkbox itself a few lines
    // above.
    $field->_label = '';
  }
}

/**
 * For a given form, get the HTML "id" attribute for the percentage price field,
 * if any.
 *
 * @param  Object $form An object extending CRM_Core_Form
 * @return String "id" attribute, if any; otherwise FALSE.
 */
function _percentagepricesetfield_get_form_percentage_field_id($form) {
  if (!empty($form->_priceSetId)) {
    $field_ids = _percentagepricesetfield_get_percentage_field_ids($form->_priceSetId, TRUE);
    $field_id = array_shift($field_ids);
    if ($field_id) {
      if (array_key_exists("price_{$field_id}", $form->_elementIndex)) {
        return $field_id;
      }
    }
  }
  return FALSE;
}

/**
 * buildForm hook handler for /civicrm/admin/price/field.
 *
 * @param <type> $form
 */
function _percentagepricesetfield_buildForm_AdminPriceField(&$form) {
  // If the form has been submitted to create a new percentage field, we'll want
  // to massage the submitted values; we don't do this in hook_civicrm_postProcess()
  // because that runs after the form is processed, whereas we need to modify
  // these values before the form is processed.
  if ($form->_flagSubmitted && !$form->_submitValues['fid'] && $form->_submitValues['html_type'] == 'CheckBox' && $form->_submitValues['is_percentagepricesetfield']
  ) {
    // Auto-create the list of options to have a single option. This is necessary
    // because the form validation for a new checkbox requires options to be
    // defined.
    $form->_submitValues['option_label'] = array(1 => '_');
    $form->_submitValues['option_amount'] = array(1 => 1);
    $form->_submitValues['option_financial_type_id'] = array(1 => $form->_submitValues['percentagepricesetfield_financial_type_id']);
    $form->_submitValues['option_status'] = array(1 => 1);
    for ($i = 2; $i <= 15; $i++) {
      $form->_submitValues['option_label'][$i] = '';
      $form->_submitValues['option_amount'][$i] = '';
      $form->_submitValues['option_financial_type_id'][$i] = '';
      $form->_submitValues['option_status'][$i] = '';
    }
    // Never display amount for a percentage field.
    $form->_submitValues['is_display_amounts'] = 0;
  }

  // Add our custom JavaScript file.
  $resource = CRM_Core_Resources::singleton();
  $resource->addScriptFile('com.joineryhq.percentagepricesetfield', 'js/admin_price_field.js', 100, 'page-footer');
  $resource->addStyleFile('com.joineryhq.percentagepricesetfield', 'css/admin_price_field.css', 100, 'page-header');

  // Define an array to hold field descriptions.
  $descriptions = array();

  // Add our own fields to this form, to handle percentage fields
  $form->addElement('checkbox', 'is_percentagepricesetfield', ts('Field calculates "Automatic Additional Percentage"'));
  $form->addElement('text', 'percentagepricesetfield_', ts('Short label for line item'));
  $form->addElement('text', 'percentagepricesetfield_percentage', ts('Percentage'));
  $form->addElement('checkbox', 'percentagepricesetfield_apply_to_taxes', ts('Apply percentage to tax amounts'));
  $hide_and_force_element = $form->addElement('checkbox', 'percentagepricesetfield_hide_and_force', ts('Hide checkbox and force to "yes"'));
  $descriptions['percentagepricesetfield_hide_and_force'] = ts('This option will force the additional percentage to be applied, and hide the check box, in front-end forms. (Additional percentage is always an option in back-office forms.)');

  // Support global "hide and force" config option; if it's TRUE, then tell JS
  // to freeze this field, and adjust its description.
  // NOTE ON FREEZING HIDE-AND-FORCE: We don't use
  // $element->freeze() because it will actually prevent the element from
  // appearing in the DOM, and that will break javascript that relies on the
  // field name (maybe it doesn't have to rely on the field name, but doing so
  // covers a variety of edge cases that can't be handled with, e.g., id or
  // label "for" attribute, as in the case of checkboxes/radios.)
  $hide_and_force_element_freeze = FALSE;
  if (_percentagepricesetfield_get_setting_value_override('hide_and_force')) {
    $hide_and_force_element_freeze = TRUE;
    $descriptions['percentagepricesetfield_hide_and_force'] = ts(
      'This setting overridden by the site-wide configuration at <a href="%1">%2</a>.', array(
        1 => CRM_Utils_System::url('civicrm/admin/percentagepricesetfield/settings', 'reset=1'),
        2 => ts('Percentage Price Set Field: Settings'),
        'domain' => 'org.joineryhq.percentagepricesetfield',
      )
    );
  }

  // Create a group of "disable for payment processors" with one checkbox per
  // payment processor, plus "pay later"
  $payment_method_checkboxes = array(
    $form->createElement('checkbox', '0', 0, ' ' . ts('Pay later (check)')),
  );
  $result = civicrm_api3(
    'PaymentProcessor', 'get', array(
      'sequential' => 1,
      'is_test' => 0,
      'return' => array("name"),
      'options' => array('sort' => "name"),
    )
  );
  foreach ($result['values'] as $value) {
    $payment_method_checkboxes[] = $form->createElement('checkbox', $value['id'], $value['id'], ' ' . $value['name']);
  }
  $form->addGroup($payment_method_checkboxes, 'percentagepricesetfield_disable_payment_methods', ts('Disable for payment methods'), '<br />');
  $descriptions['percentagepricesetfield_disable_payment_methods'] = ts('Additional percentage option will be forced to "no" in front-end forms submitted with the selected payment method(s). (Additional percentage is always an option in back-office forms.)');

  // Assign bhfe fields to the template.
  $tpl = CRM_Core_Smarty::singleton();
  $bhfe = $tpl->get_template_vars('beginHookFormElements');
  if (!$bhfe) {
    $bhfe = array();
  }
  $bhfe[] = 'is_percentagepricesetfield';
  $bhfe[] = 'percentagepricesetfield_percentage';
  $bhfe[] = 'percentagepricesetfield_apply_to_taxes';
  $bhfe[] = 'percentagepricesetfield_hide_and_force';
  $bhfe[] = 'percentagepricesetfield_disable_payment_methods';
  $form->assign('beginHookFormElements', $bhfe);

  // Set default values for our fields.
  _percentagepricesetfield_setDefaults_adminPriceField($form);

  // Pass some of these values to JavaScript.
  $vars = array();
  $vars['descriptions'] = $descriptions;
  $vars['bhfe_fields'] = $bhfe;
  $vars['hide_and_force_element_freeze'] = $hide_and_force_element_freeze;
  $field_id = $form->getVar('_fid');
  if ($field_id) {
    $values = _percentagepricesetfield_get_settings($field_id);
    $vars['values'] = $values;
  }
  $resource->addVars('percentagepricesetfield', $vars);
}

/**
 * Set default values for percentage-field-related values on the given form.
 */
function _percentagepricesetfield_setDefaults_adminPriceField(&$form) {

  $price_set_id = $form->getVar('_sid');
  $field_id = $form->getVar('_fid');
  $percentage_field_ids = _percentagepricesetfield_get_percentage_field_ids($price_set_id, FALSE);
  if (!$field_id || in_array($field_id, $percentage_field_ids)) {
    $defaults = array();
    if (!$field_id) {
      $defaults['percentagepricesetfield_apply_to_taxes'] = 1;
    }

    $values = _percentagepricesetfield_get_settings($field_id);
    if (!empty($values)) {
      $defaults['is_percentagepricesetfield'] = 1;
      foreach ($values as $name => $value) {
        $defaults['percentagepricesetfield_' . $name] = $value;
      }
    }

    $form->setDefaults($defaults);
  }
}

/**
 * Ensure that price options are correct for a given set of percentage price
 * field values.
 */
function _percentagepricesetfield_rectify_price_options($field_values) {
  $field_id = $field_values['field_id'];

  // Find all existing price field values for this field.
  try {
    $price_options = civicrm_api3(
      'price_field_value', 'get', array(
        'price_field_id' => $field_id,
        'sequential' => 1,
      )
    );
  }
  catch (CiviCRM_API3_Exception $e) {
    $error = $e->getMessage();
    CRM_Core_Error::fatal(ts('Percentage Price Set Field: fatal error (on line %1) while rectifying price options: %2', array(1 => __LINE__, 2 => $error)));
  }

  // Remove each price field value for this field.
  foreach ($price_options['values'] as $value) {
    try {
      civicrm_api3(
        'price_field_value', 'delete', array(
          'id' => $value['id'],
        )
      );
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::fatal(ts('Percentage Price Set Field: fatal error (on line %1) while rectifying price options: %2', array(1 => __LINE__ . "|{$value['id']}", 2 => $error)));
    }
  }

  // Create a single correct price_field_value entity for this price field.
  try {
    civicrm_api3(
      'price_field_value', 'create', array(
        'price_field_id' => $field_id,
        'name' => '_',
        'label' => 'one',
        'amount' => '1',
        'financial_type_id' => $field_values['financial_type_id'],
      )
    );
  }
  catch (CiviCRM_API3_Exception $e) {
    $error = $e->getMessage();
    CRM_Core_Error::fatal(ts('Percentage Price Set Field: fatal error (on line %1) while rectifying price options: %2', array(1 => __LINE__, 2 => $error)));
  }
}

/**
 * postProcess handler for /civicrm/admin/price/field.
 */
function _percentagepricesetfield_postProcess_AdminPriceField($form) {
  $values = $form->_submitValues;
  $price_set_id = $values['sid'];
  $field_id = $values['fid'];

  if (array_key_exists('is_percentagepricesetfield', $values) && $values['is_percentagepricesetfield']) {
    $field_values = array(
      'percentage' => (float) $values['percentagepricesetfield_percentage'],
      'financial_type_id' => (int) $values['percentagepricesetfield_financial_type_id'],
      'apply_to_taxes' => (int) !empty($values['percentagepricesetfield_apply_to_taxes']),
      'hide_and_force' => (int) !empty($values['percentagepricesetfield_hide_and_force']),
      'disable_payment_methods' => (
      !empty($values['percentagepricesetfield_disable_payment_methods']) ?
      CRM_Utils_Array::implodePadded(array_keys($values['percentagepricesetfield_disable_payment_methods'])) :
      ''
      ),
      'field_id' => $field_id,
    );

    if ($field_id) {
      // If the $field_id is known, then it's an existing field. Update it.
      _percentagepricesetfield_update_field($field_values);
    }
    else {
      // Otherwise, it's just been created. Find it by price_set_id and label.
      // (This works only because CiviCRM enforces a unique label-per-price-set
      // limitation.)
      $bao = new CRM_Price_BAO_PriceField();
      $bao->price_set_id = $price_set_id;
      $bao->label = $values['label'];
      $bao->find();
      $bao->fetch();
      $field_values['field_id'] = $bao->id;
      _percentagepricesetfield_create_field($field_values);
    }
    _percentagepricesetfield_rectify_price_options($field_values);
  }
  else {
    // If it's not marked as a percentage field...
    if ($field_id) {
      // ... and if it's not a newly created field,
      // then make sure it's not recorded as a percentage field.
      _percentagepricesetfield_remove_field_percentage($field_id);
    }
  }
}

/**
 * Get a list of available data fields, each with its correct data type
 *
 * @return Array of fields, each with a data type matching a string type in
 *   CRM_Utils_Type::validate(). e.g.,
 *   array(
 *     'my_field' => 'String',
 *   );
 */
function _percentagepricesetfield_get_valid_fields() {
  // Define fields with valid data types (as in CRM_Utils_Type::validate()).
  $valid_fields = array(
    'field_id' => 'Integer',
    'percentage' => 'Float',
    'financial_type_id' => 'Integer',
    'apply_to_taxes' => 'Boolean',
    'hide_and_force' => 'Boolean',
    'disable_payment_methods' => 'String',
  );
  return $valid_fields;
}

/**
 * Create a record of a given percentage field, using the provided values.
 *
 * @param array $field_values An array of values with keys matching those in
 *  _percentagepricesetfield_get_valid_fields().
 */
function _percentagepricesetfield_create_field($field_values) {
  $valid_fields = _percentagepricesetfield_get_valid_fields();

  $fields = $values = $params = array();
  $param_key = 1;
  foreach ($valid_fields as $valid_field => $data_type) {
    if (array_key_exists($valid_field, $field_values)) {
      $fields[] = $valid_field;
      $values[] = "%{$param_key}";
      $params[$param_key] = array($field_values[$valid_field], $data_type);
      $param_key++;
    }
  }
  $query = "
    INSERT INTO `civicrm_percentagepricesetfield` (" . implode(',', $fields) . ")
    VALUES (" . implode(',', $values) . ")
  ";
  CRM_Core_DAO::executeQuery($query, $params);
}

/**
 * Remove "percentage field" behavior from a given priceset field.
 *
 * @param int $field_id The field_id of the given priceset field.
 */
function _percentagepricesetfield_remove_field_percentage($field_id) {
  $query = "
    DELETE FROM `civicrm_percentagepricesetfield`
    WHERE field_id = %1
  ";
  $params = array(
    1 => array($field_id, 'Integer'),
  );
  $dao = CRM_Core_DAO::executeQuery($query, $params);
}

/**
 * Update a record of a given percentage field, using the provided values.
 *
 * @param array $field_values An array of values with keys matching those in
 *  _percentagepricesetfield_get_valid_fields().
 */
function _percentagepricesetfield_update_field($field_values) {
  $field_id = $field_values['field_id'];

  // First ensure a field exists:
  $query = "
    INSERT IGNORE INTO `civicrm_percentagepricesetfield` (field_id) values (%1)
  ";
  $params = array(
    1 => array($field_id, 'Integer'),
  );
  CRM_Core_DAO::executeQuery($query, $params);

  // Now update the record with relevant values.
  $valid_fields = _percentagepricesetfield_get_valid_fields();
  $updates = $params = array();
  $param_key = 1;
  unset($field_values['field_id']);
  foreach ($valid_fields as $valid_field => $data_type) {
    if (array_key_exists($valid_field, $field_values)) {
      $updates[] = "$valid_field = %{$param_key}";
      $params[$param_key] = array($field_values[$valid_field], $data_type);
      $param_key++;
    }
  }
  $params[$param_key] = array($field_id, 'Integer');
  $query = "
    UPDATE `civicrm_percentagepricesetfield` SET " . implode(',', $updates) . "
    WHERE field_id = %{$param_key}
  ";
  CRM_Core_DAO::executeQuery($query, $params);
}

/**
 * Determine the correct callback function to call in order to retrieve the
 * price set ID of the relevant percentage price field.
 *
 * @param string $content As in first argument to hook_civicrm_alterContent()
 * @param string $context As in second argument to hook_civicrm_alterContent()
 * @param string $tplName As in third argument to hook_civicrm_alterContent()
 * @param object $object As in fourth argument to hook_civicrm_alterContent()
 * @param array $_get Contents of $_GET.
 *
 * @return String The appropriate callback function name.
 */
function _percentagepricesetfield_get_content_pricesetid_function($content, $context, $tplName, $object, $_get) {
  $url_path = implode('/', $object->urlPath);
  if ($context == 'page' && !empty($_get['priceSetId']) && $url_path == 'civicrm/contact/view/contribution' && CRM_Utils_Array::value('snippet', $_get) == 4
  ) {
    return '_percentagepricesetfield_civicrm_alterContent_get_pricesetid_for_contribution_backoffice';
  }

  if ($context == 'page' && $url_path == 'civicrm/contact/view/participant' && CRM_Utils_Array::value('snippet', $_get) == 4
  ) {
    return '_percentagepricesetfield_civicrm_alterContent_get_pricesetid_for_event_backoffice';
  }

  if ($context == 'form' && $url_path == 'civicrm/event/participant/feeselection' && CRM_Utils_Array::value('snippet', $_get) == 'json'
  ) {
    return '_percentagepricesetfield_civicrm_alterContent_get_pricesetid_for_event_backoffice_edit';
  }

  if ($context == 'form' && !empty($object->_priceSetId) && $url_path == 'civicrm/contribute/transact'
  ) {
    return '_percentagepricesetfield_civicrm_alterContent_get_pricesetid_for_contribution_public';
  }

  if ($context == 'form' && !empty($object->_priceSetId) && $url_path == 'civicrm/event/register'
  ) {
    return '_percentagepricesetfield_civicrm_alterContent_get_pricesetid_for_event_public';
  }
}

/**
 * Callback function to retrieve price set ID for a back-office contribution
 * form.
 *
 * @param string $content As in first argument to hook_civicrm_alterContent()
 * @param string $context As in second argument to hook_civicrm_alterContent()
 * @param string $tplName As in third argument to hook_civicrm_alterContent()
 * @param object $object As in fourth argument to hook_civicrm_alterContent()
 * @param array $_get Contents of $_GET.
 *
 * @return String The price set ID, if any; otherwise NULL.
 */
function _percentagepricesetfield_civicrm_alterContent_get_pricesetid_for_contribution_backoffice($content, $context, $tplName, $object, $_get) {
  return CRM_Utils_Array::value('priceSetId', $_get);
}

/**
 * Callback function to retrieve price set ID for a public-facing contribution
 * form.
 *
 * @param string $content As in first argument to hook_civicrm_alterContent()
 * @param string $context As in second argument to hook_civicrm_alterContent()
 * @param string $tplName As in third argument to hook_civicrm_alterContent()
 * @param object $object As in fourth argument to hook_civicrm_alterContent()
 * @param array $_get Contents of $_GET.
 *
 * @return String The price set ID, if any; otherwise NULL.
 */
function _percentagepricesetfield_civicrm_alterContent_get_pricesetid_for_contribution_public($content, $context, $tplName, $object, $_get) {
  return $object->_priceSetId;
}

/**
 * Callback function to retrieve price set ID for a public-facing event registration
 * form.
 *
 * @param string $content As in first argument to hook_civicrm_alterContent()
 * @param string $context As in second argument to hook_civicrm_alterContent()
 * @param string $tplName As in third argument to hook_civicrm_alterContent()
 * @param object $object As in fourth argument to hook_civicrm_alterContent()
 * @param array $_get Contents of $_GET.
 *
 * @return String The price set ID, if any; otherwise NULL.
 */
function _percentagepricesetfield_civicrm_alterContent_get_pricesetid_for_event_public($content, $context, $tplName, $object, $_get) {
  return $object->_priceSetId;
}

/**
 * Callback function to retrieve price set ID for a back-office event registration
 * form.
 *
 * @param string $content As in first argument to hook_civicrm_alterContent()
 * @param string $context As in second argument to hook_civicrm_alterContent()
 * @param string $tplName As in third argument to hook_civicrm_alterContent()
 * @param object $object As in fourth argument to hook_civicrm_alterContent()
 * @param array $_get Contents of $_GET.
 *
 * @return String The price set ID, if any; otherwise NULL.
 */
function _percentagepricesetfield_civicrm_alterContent_get_pricesetid_for_event_backoffice($content, $context, $tplName, $object, $_get) {
  if (empty($_get['eventId'])) {
    return NULL;
  }
  // CiviCRM 4.7.22 has no Price Set Entity API, so use BAO.
  $bao = new CRM_Price_DAO_PriceSetEntity();
  $bao->entity_table = 'civicrm_event';
  $bao->entity_id = $_get['eventId'];
  $bao->find();
  $bao->fetch();
  return $bao->price_set_id;
}

/**
 * Callback function to retrieve price set ID for a back-office event registration
 * form.
 *
 * @param string $content As in first argument to hook_civicrm_alterContent()
 * @param string $context As in second argument to hook_civicrm_alterContent()
 * @param string $tplName As in third argument to hook_civicrm_alterContent()
 * @param object $object As in fourth argument to hook_civicrm_alterContent()
 * @param array $_get Contents of $_GET.
 *
 * @return String The price set ID, if any; otherwise NULL.
 */
function _percentagepricesetfield_civicrm_alterContent_get_pricesetid_for_event_backoffice_edit($content, $context, $tplName, $object, $_get) {
  if (empty($object->_eventId)) {
    return NULL;
  }
  // CiviCRM 4.7.22 has no Price Set Entity API, so use BAO.
  $bao = new CRM_Price_DAO_PriceSetEntity();
  $bao->entity_table = 'civicrm_event';
  $bao->entity_id = $object->_eventId;
  $bao->find();
  $bao->fetch();
  return $bao->price_set_id;
}

/**
 * Get the option value for the (exactly) one option that should exist for a
 * given percentage price set field.
 *
 * @param string Numeric $field_id System ID of the price set field.
 *
 * @return string Numeric value of the checkbox option.
 */
function _percentagepricesetfield_get_field_value($field_id) {
  try {
    $result = civicrm_api3(
      'PriceFieldValue', 'get', array(
        'sequential' => 1,
        'price_field_id' => $field_id,
      )
    );
  }
  catch (CiviCRM_API3_Exception $e) {
    CRM_Core_Error::debug_log_message('API Error in get PriceFieldValue: ' . $e->getMessage());
    return '';
  }
  return $result['values'][0]['id'];
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
function percentagepricesetfield_civicrm_navigationMenu(&$menu) {
  _percentagepricesetfield_get_max_navID($menu, $max_navID);
  _percentagepricesetfield_civix_insert_navigation_menu(
    $menu, 'Administer/Customize Data and Screens', array(
      'label' => ts('Percentage Price Set Field', array('domain' => 'com.joineryhq.percentagepricesetfield')),
      'name' => 'Percentage Price Set Field',
      'url' => 'civicrm/admin/percentagepricesetfield/settings',
      'permission' => 'administer CiviCRM',
      'operator' => 'AND',
      'separator' => NULL,
      'navID' => ++$max_navID,
    )
  );
  _percentagepricesetfield_civix_navigationMenu($menu);
}

/**
 * For an array of menu items, recursively get the value of the greatest navID
 * attribute.
 *
 * @param <type> $menu
 * @param <type> $max_navID
 */
function _percentagepricesetfield_get_max_navID(&$menu, &$max_navID = NULL) {
  foreach ($menu as $id => $item) {
    if (!empty($item['attributes']['navID'])) {
      $max_navID = max($max_navID, $item['attributes']['navID']);
    }
    if (!empty($item['child'])) {
      _percentagepricesetfield_get_max_navID($item['child'], $max_navID);
    }
  }
}

/**
 * Prep default values to work well with setDefaults(), if needed.
 *
 * @param string $name  The name of the field.
 * @param mixed $value The field value.
 *
 * @return mixed The altered field value.
 */
function _percentagepricesetfield_preprocess_saved_value($name, $value) {
  switch ($name) {
    case 'disable_payment_methods':
      $value = array_fill_keys((array) CRM_Utils_Array::explodePadded($value), '1');
      break;
  }
  return $value;
}

function _percentagepricesetfield_allow_hide_and_force($content, $context, $tplName, $object, $_get) {
  $url_path = implode('/', $object->urlPath);
  if ($context == 'page' && !empty($_get['priceSetId']) && $url_path == 'civicrm/contact/view/contribution' && CRM_Utils_Array::value('snippet', $_get) == 4
  ) {
    return FALSE;
  }

  if ($context == 'page' && $url_path == 'civicrm/contact/view/participant' && CRM_Utils_Array::value('snippet', $_get) == 4
  ) {
    return FALSE;
  }

  if ($context == 'form' && $url_path == 'civicrm/event/participant/feeselection' && CRM_Utils_Array::value('snippet', $_get) == 'json'
  ) {
    return FALSE;
  }

  return TRUE;
}
