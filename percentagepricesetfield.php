<?php


/** FIXME: TODO:
 * Admin UI
 * Does it work with taxes? Yes.
 * Does it work with cividiscount? Yes, if you install this extension after cividiscount.
 */



require_once 'percentagepricesetfield.civix.php';

function percentagepricesetfield_civicrm_buildAmount($pageType, &$form, &$amount) {
  dsm(__FUNCTION__);
//dsm(func_get_args(), __FUNCTION__);
  $field_id = _percentagepricesetfield_get_pergentage_field_id($form);
  if ($field_id) {
    if (!_percentagepricesetfield_is_displayForm($form)) {
      // If this is the confirmation page, adjust the line item label.
      if (!empty($form->_submitValues) && array_key_exists("price_{$field_id}", $form->_submitValues)) {
        // "Percentage" checkbox will have only one option, but ID is unknow to us,
        // so use a foreach loop. If the one option for the percentage checkbox is
        // checked, adjust the total and label for the checkbox.
        foreach ($amount[$field_id]['options'] as $option_id => &$option) {
          // Determine whether "percentage" checkbox was checked.
          if ($form->_submitValues["price_{$field_id}"][$option_id]) {
            $option['amount'] = _percentagepricesetfield_calculate_additional_amount($form);
            $option['label'] = _percentagepricesetfield_calculate_label($form);
          }
        }
      }
    }
  }
}

/**
 * Determine whether or not the given form is the actual form (not, say, a
 * confirmation page).
 *
 * @return Boolean TRUE if this is the actual form.
 */
function _percentagepricesetfield_is_displayForm($form) {
  $action = $form->controller->_actionName;
  return ($action[1] == 'display');

}

function _percentagepricesetfield_get_pergentage_field_id($form) {
  // FIXME: get actual id
  return 17;
}

function _percentagepricesetfield_calculate_additional_amount($form) {
  static $run_once = FALSE;
  if (!$run_once) {
    $run_once = TRUE;

    $field_id = _percentagepricesetfield_get_pergentage_field_id($form);
    $base_total = 0;

    $line_items = array();
    $params = $form->_submitValues;
    $fields = $form->_values['fee'];
    unset($fields[$field_id]);

    CRM_Price_BAO_PriceSet::processAmount($fields, $params, $line_items);

    if (_percentagepricesetfield_apply_to_taxes($form)) {
      // $params['amount'] holds the total with taxes, so this is easy.
      $base_total = $params['amount'];
    }
    else {
      // If we're not configured to apply the percentage to taxes, then apply it
      // to each line item individually.
      foreach ($line_items as $line_item) {
        if ($line_item['price_field_id'] != $field_id) {
          $base_total += $line_item['line_total'];
        }
      }
    }
    
    $percentage = _percentagepricesetfield_get_percentage($form);
    $additional_amount = round(($base_total * $percentage / 100), 2);

    return $additional_amount;
  }
}

function _percentagepricesetfield_apply_to_taxes($form) {
  // FIXME: Actually check configuration for this field.
  return FALSE;
  return TRUE;
}

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

function _percentagepricesetfield_calculate_label($form) {
  // FIXME: dynamically build string.
  $label = ts('FIXME: %1 %', array(1 => _percentagepricesetfield_calculate_additional_amount($form)));
  return $label;
}

function _percentagepricesetfield_get_percentage($form) {
  // FIXME: get the actual percentage from the field.
  return 2.3;
}

function percentagepricesetfield_civicrm_buildForm($formName, &$form) {
//  dsm($formName);
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

function _percentagepricesetfield_buildForm_public_price_set_form($form) {
//  dsm($form);
  $field_id = _percentagepricesetfield_get_pergentage_field_id($form);
  if ($field_id) {
    if (array_key_exists("price_{$field_id}", $form->_elementIndex)) {
      $field =& $form->_elements[$form->_elementIndex["price_{$field_id}"]];
      foreach ($field->_elements as &$element) {
        // Use the field label as the label for this checkbox element.
        $element->_text = $field->_label;
        // Set this checkbox's "price" to 0. CiviCRM uses a custom format for this
        // attribute, parsing it later in JavaScript to auto-calculate the total
        // (see CRM/Price/Form/Calculate.tpl). Setting the price to 0 allows us
        // to avoid having this checkbox affect that calculation, and we'll use our
        // own JavaScript to adjust the total based on the percentage.
        $element->_attributes['price'] = preg_replace('/(\["[0-9]+",")[0-9]+(\|.+)$/', '${1}0${2}', $element->_attributes['price']); // e.g., ["30","20||"]: change "20" to "0".
        $element_id = $field->_name . '_' . $element->_attributes['id'];
      }
      // Remove this field's label (we copied it to the checkbox itself a few lines
      // above.
      $field->_label = '';

      $resource = CRM_Core_Resources::singleton();
      $resource->addScriptFile('com.joineryhq.percentagepricesetfield', 'js/public_price_set_form.js', 100, 'page-footer');
      $vars = array(
        'percentage' => _percentagepricesetfield_get_percentage($form),
        'percentage_checkbox_id' => $element_id,
      );
      $resource->addVars('percentagepricesetfield', $vars);
    }
  }
}

function _percentagepricesetfield_buildForm_AdminPriceField(&$form) {
  dsm($form, 'form before modification in '. __FUNCTION__);
  if (
    $form->_flagSubmitted
    && !$form->_submitValues['fid']
    && $form->_submitValues['html_type'] == 'CheckBox'
    && $form->_submitValues['is_percentagepricesetfield']
  ) {
    // Auto-create the list of options to have a single option.
    $form->_submitValues['option_label'] = array(1 => 'THIS STRING IS A MEANINGLESS PLACEHOLDER');
    $form->_submitValues['option_amount'] = array(1 => 1);
    $form->_submitValues['option_financial_type_id'] = array(1 => $form->_submitValues['percentagepricesetfield_financial_type_id']);
    $form->_submitValues['option_status'] = array(1 => 1);
    for ($i = 2; $i <= 15; $i++) {
      $form->_submitValues['option_label'][$i] = '';
      $form->_submitValues['option_amount'][$i] = '';
      $form->_submitValues['option_financial_type_id'][$i] = '';
      $form->_submitValues['option_status'][$i] = '';
    }
    dsm($form, 'form after modification in '. __FUNCTION__);
  }



  // FIXME: TODO:
  // - configurable values for a percentage field:
  //  - Provided: Checkbox label (e.g., Please add 4% to my donation amount to cover credit-card processing fees.)
  //  - Provided: checkbox help text (e.g., "This helps us a lot, thank you!")
  //  - Custom: line item label (e.g., "4% extra for credit card fees")
  //  - Custom: percentage (e.g., 4.0)
  //

//  dsm('FIXME: '. __FUNCTION__ . ' not running.'); return;


  // Add custom JavaScript to override option_html_type() function
  $resource = CRM_Core_Resources::singleton();
  $resource->addScriptFile('com.joineryhq.percentagepricesetfield', 'js/admin_price_field.js', 100, 'page-footer');

  // FIXME: this is no longer needed:
  /*
  // Remove the CRM_Price_Form_Field::formRule form rule. We'll call it ourselves
  // in our own form rule.
  foreach ($form->_formRules as $key => &$rule) {
    $class_name = $rule[0][0];
    $method_name = $rule[0][1];
    if ($class_name == 'CRM_Price_Form_Field' && $method_name == 'formRule') {
      unset($form->_formRules[$key]);
    }
  }
  // Add our own form rule processor.
  $form->addFormRule('_percentagepricesetfield_validate_field', $form);
   *
   */

  // Add our own fields to this form.
  $form->addElement('checkbox', 'is_percentagepricesetfield', ts('Field calculates "Automatic Additional Percentage"'));
  $form->addElement('text', 'percentagepricesetfield_line_item_label', ts('Short label for line item'));
  $form->addElement('text', 'percentagepricesetfield_percentage', ts('Percentage'));
  $tpl = CRM_Core_Smarty::singleton();
  $bhfe = $tpl->get_template_vars('beginHookFormElements');
  if (!$bhfe) {
    $bhfe = array();
  }
  $bhfe[] = 'is_percentagepricesetfield';
  $bhfe[] = 'percentagepricesetfield_line_item_label';
  $bhfe[] = 'percentagepricesetfield_percentage';
  $form->assign('beginHookFormElements', $bhfe);

  // Set default values for our fields.
  _percentagepricesetfield_setDefaults_adminPriceField($form);

  $vars = array();
  $vars['bhfe_fields'] = array(
    'is_percentagepricesetfield',
    'percentagepricesetfield_line_item_label',
    'percentagepricesetfield_percentage',
  );

  $field_id = $form->getVar('_fid');
  if ($field_id) {
    $values = _percentagepricesetfield_get_values($field_id);
    $vars['values'] = $values;
  }

  $resource = CRM_Core_Resources::singleton();
  $resource->addVars('percentagepricesetfield', $vars);
}



function _percentagepricesetfield_setDefaults_adminPriceField(&$form) {
  $defaults = array();
  
  // FIXME: get actual values for this field.
  $field_id = $form->getVar('_fid');
  if (!$field_id) {
    return;
  }

  $values = _percentagepricesetfield_get_values($field_id);
  dsm($values, 'values');
  if(!empty($values)) {
    $defaults['is_percentagepricesetfield'] = 1;
    foreach ($values as $name => $value) {
      $defaults['percentagepricesetfield_' . $name] = $value;
    }
  }
  dsm($defaults, 'defaults');
  
  $form->setDefaults($defaults);
}

function percentagepricesetfield_civicrm_preProcess($formName, &$form) {
  if ($formName == 'CRM_Price_Form_Field') {
    _percentagepricesetfield_preProcess_AdminPriceField($form);
  }

}

function _percentagepricesetfield_preProcess_AdminPriceField(&$form) {
  dsm('FIXME: '. __FUNCTION__ . ' not running.'); return;
  dsm($form, __FUNCTION__);
}

function percentagepricesetfield_civicrm_postProcess($formName, &$form) {
  if ($formName == 'CRM_Price_Form_Field') {
    _percentagepricesetfield_postProcess_AdminPriceField($form);
  }
}

function _percentagepricesetfield_rectify_price_options($field_values) {
  
  $field_id = $field_values['field_id'];
  // Delete any existing price options, and add only the one that should be there.
  $dao = new CRM_Price_DAO_PriceFieldValue();
  $dao->price_field_id = $field_id;
  $dao->find(TRUE);
  $dao->financial_type_id = $field_values['financial_type_id'];
  $dao->save();

}

function _percentagepricesetfield_postProcess_AdminPriceField($form){
  $values = $form->_submitValues;
  $sid = $values['sid'];
  $fid = $values['fid'];

  if ($values['is_percentagepricesetfield']) {
    $field_values = array(
      'line_item_label' => $values['percentagepricesetfield_line_item_label'],
      'percentage' => $values['percentagepricesetfield_percentage'],
      'financial_type_id' => $values['percentagepricesetfield_financial_type_id'],
      'field_id' => $fid,
    );

    if ($fid) {
      _percentagepricesetfield_update_field($field_values);
    }
    else {
      $bao = new CRM_Price_BAO_PriceField();
      $bao->sid = $sid;
      $bao->label = $values['label'];
      $bao->find();
      $bao->fetch();
      $fid = $bao->id;
      $field_values['field_id'] = $fid;
      _percentagepricesetfield_create_field($field_values);
    }
    _percentagepricesetfield_rectify_price_options($field_values);
  }
  else {
    if ($fid) {
      _percentagepricesetfield_delete_field($fid);
    }
  }
}

function _percentagepricesetfield_get_valid_fields() {
  // Define fields with valid data types (as in CRM_Utils_Type::validate()).
  $valid_fields = array(
    'field_id' => 'Integer',
    'line_item_label' => 'String',
    'percentage' => 'Float',
    'financial_type_id' => 'Integer',
  );
  return $valid_fields;
}

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

function _percentagepricesetfield_update_field($field_values) {
  $field_id = $field_values['field_id'];
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
    UPdATE `civicrm_percentagepricesetfield` SET " . implode(',', $updates) . "
    WHERE field_id = %{$param_key}
  ";
  dsm($query, 'query');
  dsm($params, 'params');
  CRM_Core_DAO::executeQuery($query, $params);
}

function _percentagepricesetfield_delete_field($fid) {
  dsm('FIXME: '. __FUNCTION__ . ' not running.'); return;
}

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function percentagepricesetfield_civicrm_config(&$config) {
  _percentagepricesetfield_civix_civicrm_config($config);
//  $html_types =& CRM_Price_BAO_PriceField::htmlTypes();
//  $html_types['percentage'] = ts('Additional Percentage');
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function percentagepricesetfield_civicrm_xmlMenu(&$files) {
  _percentagepricesetfield_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function percentagepricesetfield_civicrm_install() {
  return _percentagepricesetfield_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function percentagepricesetfield_civicrm_uninstall() {
  return _percentagepricesetfield_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function percentagepricesetfield_civicrm_enable() {
  return _percentagepricesetfield_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function percentagepricesetfield_civicrm_disable() {
  return _percentagepricesetfield_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function percentagepricesetfield_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _percentagepricesetfield_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function percentagepricesetfield_civicrm_managed(&$entities) {
  return _percentagepricesetfield_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function percentagepricesetfield_civicrm_caseTypes(&$caseTypes) {
  _percentagepricesetfield_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function percentagepricesetfield_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _percentagepricesetfield_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
