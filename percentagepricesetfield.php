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
      $source_percentage_values = _percentagepricesetfield_get_values($field_id);
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
  if ($form->_priceSetId) {
    if (!_percentagepricesetfield_is_displayForm($form)) {
      // If this is the confirmation page, we'll adjust the line item label, if
      // the price set contains a percentage field.
      $field_ids = _percentagepricesetfield_get_percentage_field_ids($form->_priceSetId);
      $field_id = array_shift($field_ids);
      if ($field_id) {
        if (!empty($form->_submitValues) && array_key_exists("price_{$field_id}", $form->_submitValues)) {
          // "Percentage" checkbox will have only one option, but ID is unknow to us,
          // so use a foreach loop. If the one option for the percentage checkbox is
          // checked, adjust the total and label for the checkbox.
          foreach ($amount[$field_id]['options'] as $option_id => &$option) {
            // Determine whether "percentage" checkbox was checked.
            if ($form->_submitValues["price_{$field_id}"][$option_id]) {
              $option['amount'] = _percentagepricesetfield_calculate_additional_amount($form);
              $option['label'] = ts('Thank you!');
            }
          }
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

    case 'CRM_Event_Form_Registration_Register':
    case 'CRM_Contribute_Form_Contribution_Main':
      _percentagepricesetfield_buildForm_public_price_set_form($form);
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
    $tpl_vars =& $tpl->get_template_vars();
    foreach ($field_ids as $field_id) {
      if (
        array_key_exists('priceField', $tpl_vars)
        && array_key_exists($field_id, $tpl_vars['priceField'])
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
      while(($fid_key = array_search($fid, $field_ids)) !== false) {
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
 * Determine whether or not the given form is the actual form (not, say, a
 * confirmation page).
 *
 * @return Bool TRUE if this is the actual form.
 */
function _percentagepricesetfield_is_displayForm($form) {
  $action = $form->controller->_actionName;
  return ($action[1] == 'display');
}

/**
 * Get the field_ids of all percentage fields in the given price set.
 *
 * @param Integer $price_set_id The ID of the price set to check; if string 'ALL',
 *   return all percentage price fields regardlesss of price_set.
 * @param Bool $limit_enabled If TRUE, return IDs of only enabled percentage
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
 * @param Object $form The form being processed.
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
      $fields = $form->_values['fee'];
      unset($fields[$field_id]);

      CRM_Price_BAO_PriceSet::processAmount($fields, $params, $line_items);

      // Calculate differently depending on "apply to taxes" setting.
      if (_percentagepricesetfield_apply_to_taxes($field_id)) {
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
 * @param Integer $field_id The ID of the percentage field.
 * @return Bool
 */
function _percentagepricesetfield_apply_to_taxes($field_id) {
  $values = _percentagepricesetfield_get_values($field_id);
  return (bool) $values['apply_to_taxes'];
}

/**
 * Get the stored configuration settings for a given percentage field.
 *
 * @param Integer $field_id The ID of the percentage field.
 * @return Array of setting values.
 */
function _percentagepricesetfield_get_values($field_id) {
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
        $values[$field_name] = $dao->$field_name;
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
 * @param Integer $price_set_id
 * @return Float
 */
function _percentagepricesetfield_get_percentage($price_set_id) {
  $field_ids = _percentagepricesetfield_get_percentage_field_ids($price_set_id);
  $field_id = array_shift($field_ids);
  $values = _percentagepricesetfield_get_values($field_id);
  return $values['percentage'];
}

/**
 * buildForm hook handler for public-facing forms containing price set fields
 * (e.g., event registration forms, contribution pages)
 *
 * @param Object $form
 */
function _percentagepricesetfield_buildForm_public_price_set_form($form) {
  if ($form->_priceSetId) {
    $field_ids = _percentagepricesetfield_get_percentage_field_ids($form->_priceSetId);
    $field_id = array_shift($field_ids);
    if ($field_id) {
      if (array_key_exists("price_{$field_id}", $form->_elementIndex)) {
        $field =& $form->_elements[$form->_elementIndex["price_{$field_id}"]];
        foreach ($field->_elements as &$element) {
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
        }
        // Remove this field's label (we copied it to the checkbox itself a few lines
        // above.
        $field->_label = '';

        // Set up our JavaScript file and variables.
        $resource = CRM_Core_Resources::singleton();
        $resource->addScriptFile('com.joineryhq.percentagepricesetfield', 'js/public_price_set_form.js', 100, 'page-footer');
        $vars = array(
          'percentage' => _percentagepricesetfield_get_percentage($form->_priceSetId),
          'percentage_checkbox_id' => $element_id,
        );
        $resource->addVars('percentagepricesetfield', $vars);
      }
    }
  }
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
  if (
    $form->_flagSubmitted
    && !$form->_submitValues['fid']
    && $form->_submitValues['html_type'] == 'CheckBox'
    && $form->_submitValues['is_percentagepricesetfield']
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
  
  // Add our own fields to this form, to handle percentage fields
  $form->addElement('checkbox', 'is_percentagepricesetfield', ts('Field calculates "Automatic Additional Percentage"'));
  $form->addElement('text', 'percentagepricesetfield_', ts('Short label for line item'));
  $form->addElement('text', 'percentagepricesetfield_percentage', ts('Percentage'));
  $form->addElement('checkbox', 'percentagepricesetfield_apply_to_taxes', ts('Apply percentage to tax amounts'));

  $tpl = CRM_Core_Smarty::singleton();
  $bhfe = $tpl->get_template_vars('beginHookFormElements');
  if (!$bhfe) {
    $bhfe = array();
  }
  $bhfe[] = 'is_percentagepricesetfield';
  $bhfe[] = 'percentagepricesetfield_percentage';
  $bhfe[] = 'percentagepricesetfield_apply_to_taxes';
  $form->assign('beginHookFormElements', $bhfe);

  // Set default values for our fields.
  _percentagepricesetfield_setDefaults_adminPriceField($form);

  // Pass some of these values to JavaScript.
  $vars = array();
  $vars['bhfe_fields'] = array(
    'is_percentagepricesetfield',
    'percentagepricesetfield_percentage',
    'percentagepricesetfield_apply_to_taxes',
  );
  $field_id = $form->getVar('_fid');
  if ($field_id) {
    $values = _percentagepricesetfield_get_values($field_id);
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

    $values = _percentagepricesetfield_get_values($field_id);
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
  try{
    $price_options = civicrm_api3('price_field_value', 'get', array(
      'price_field_id' => $field_id,
      'sequential' => 1,
    ));
  }
  catch (CiviCRM_API3_Exception $e) {
   $error = $e->getMessage();
   CRM_Core_Error::fatal(ts('Percentage Price Set Field: fatal error while rectifying price options: %1', array(1 => $error)));
  }

  // Remove each price field value for this field.
  foreach ($price_options['values'] as $value) {
    try{
      civicrm_api3('price_field_value', 'delete', array(
        'id' => $value['id'],
      ));
    }
    catch (CiviCRM_API3_Exception $e) {
     $error = $e->getMessage();
     CRM_Core_Error::fatal(ts('Percentage Price Set Field: fatal error while rectifying price options: %1', array(1 => $error)));
    }
  }

  // Create a single correct price_field_value entity for this price field.
  try{
    civicrm_api3('price_field_value', 'create', array(
      'price_field_id' => $field_id,
      'name' => '_',
      'label' => 'one',
      'amount' => '1',
      'financial_type_id' => $field_values['financial_type_id'],
    ));
  }
  catch (CiviCRM_API3_Exception $e) {
   $error = $e->getMessage();
   CRM_Core_Error::fatal(ts('Percentage Price Set Field: fatal error while rectifying price options: %1', array(1 => $error)));
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
      'apply_to_taxes' => (int) $values['percentagepricesetfield_apply_to_taxes'],
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
      $bao->sid = $price_set_id;
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
 * @param integer $field_id The field_id of the given priceset field.
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
    1 => array($field_id, 'Integer')
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
