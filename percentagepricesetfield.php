<?php

require_once 'percentagepricesetfield.civix.php';

/**
 * Implements hook_civicrm_buildAmount().
 */
function percentagepricesetfield_civicrm_buildAmount($pageType, &$form, &$amount) {
  if ($form->_priceSetId) {
    $field_ids = _percentagepricesetfield_get_percentage_field_ids($form->_priceSetId);
    $field_id = array_shift($field_ids);
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
              $option['label'] = ts('Thank you!');
            }
          }
        }
      }
    }
  }
}

/**
 * Implements hook_civicrm_().
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
 */
function percentagepricesetfield_civicrm_pageRun(&$page) {
  $page_name = $page->getVar('_name');
  if ($page_name == 'CRM_Price_Page_Field') {
    $sid = $page->getVar('_sid');

    $field_ids = _percentagepricesetfield_get_percentage_field_ids($sid, FALSE);

    $tpl = CRM_Core_Smarty::singleton();
    $tpl_vars =& $tpl->get_template_vars();
    foreach ($field_ids as $field_id) {
      if (
        array_key_exists('priceField', $tpl_vars)
        && array_key_exists($field_id, $tpl_vars['priceField'])
      ) {
        $tpl_vars['priceField'][$field_id]['html_type'] = 'Text';
        $tpl_vars['priceField'][$field_id]['html_type_display'] = 'Percentage';
      }
    }
  }
}

/**
 * Implements hook_civicrm_validateForm().
 */
function percentagepricesetfield_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if ($formName == 'CRM_Price_Form_Field') {
    if (!$fields['fid']) {
      // If there's no fid, then this is a new field. If it's set as is_percentagepricesetfield,
      // make sure there are no others already (enabled) in this fieldset.
      if (array_key_exists('is_percentagepricesetfield', $fields) && $fields['is_percentagepricesetfield']) {
        $field_ids = _percentagepricesetfield_get_percentage_field_ids($fields['sid']);
        $field_id = array_shift($field_ids);
        if ($field_id) {
          $errors['is_percentagepricesetfield'] = ts('This price set already has a percentage field. Please disable or delete that field before creating a new one.');
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

function _percentagepricesetfield_get_percentage_field_ids($price_set_id, $limit_enabled = TRUE) {
  $ret = array();
  $key = serialize(func_get_args());

  if (!array_key_exists($price_set_id, $ret)) {
    $field_ids = array();

    $dao = new CRM_Price_DAO_PriceField();
    $dao->price_set_id = $price_set_id;
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

function _percentagepricesetfield_calculate_additional_amount($form) {
  static $run_once = FALSE;
  if (!$run_once) {
    $run_once = TRUE;
    if ($form->_priceSetId) {
      $field_ids = _percentagepricesetfield_get_percentage_field_ids($form->_priceSetId);
      $field_id = array_shift($field_ids);
      $base_total = 0;

      $line_items = array();
      $params = $form->_submitValues;
      $fields = $form->_values['fee'];
      unset($fields[$field_id]);

      CRM_Price_BAO_PriceSet::processAmount($fields, $params, $line_items);

      if (_percentagepricesetfield_apply_to_taxes($field_id)) {
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

      $percentage = _percentagepricesetfield_get_percentage($form->_priceSetId);
      $additional_amount = round(($base_total * $percentage / 100), 2);
    }
  }
  return $additional_amount;
}

function _percentagepricesetfield_apply_to_taxes($field_id) {
  $values = _percentagepricesetfield_get_values($field_id);
  return (bool) $values['apply_to_taxes'];
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

function _percentagepricesetfield_get_percentage($price_set_id) {
  $field_ids = _percentagepricesetfield_get_percentage_field_ids($price_set_id);
  $field_id = array_shift($field_ids);
  $values = _percentagepricesetfield_get_values($field_id);
  return $values['percentage'];
}
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
          'percentage' => _percentagepricesetfield_get_percentage($form->_priceSetId),
          'percentage_checkbox_id' => $element_id,
        );
        $resource->addVars('percentagepricesetfield', $vars);
      }
    }
  }
}

function _percentagepricesetfield_buildForm_AdminPriceField(&$form) {

  if (
    $form->_flagSubmitted
    && !$form->_submitValues['fid']
    && $form->_submitValues['html_type'] == 'CheckBox'
    && $form->_submitValues['is_percentagepricesetfield']
  ) {
    // Auto-create the list of options to have a single option.
    $form->_submitValues['option_label'] = array(1 => '&nbsp;');
    $form->_submitValues['option_amount'] = array(1 => 1);
    $form->_submitValues['option_financial_type_id'] = array(1 => $form->_submitValues['percentagepricesetfield_financial_type_id']);
    $form->_submitValues['option_status'] = array(1 => 1);
    for ($i = 2; $i <= 15; $i++) {
      $form->_submitValues['option_label'][$i] = '';
      $form->_submitValues['option_amount'][$i] = '';
      $form->_submitValues['option_financial_type_id'][$i] = '';
      $form->_submitValues['option_status'][$i] = '';
    }
    $form->_submitValues['is_display_amounts'] = 0;
  }

  $field_id = $form->getVar('_fid');
  $price_set_id = $form->getVar('_sid');
  $percentage_field_ids = _percentagepricesetfield_get_percentage_field_ids($price_set_id, FALSE);
  if (!$field_id || in_array($field_id, $percentage_field_ids)) {
    // Add custom JavaScript to override option_html_type() function
    $resource = CRM_Core_Resources::singleton();
    $resource->addScriptFile('com.joineryhq.percentagepricesetfield', 'js/admin_price_field.js', 100, 'page-footer');

    // Add our own fields to this form.
    $form->addElement('checkbox', 'is_percentagepricesetfield', ts('Field calculates "Automatic Additional Percentage"'));
    $form->addElement('text', 'percentagepricesetfield_', ts('Short label for line item'));
    $form->addElement('text', 'percentagepricesetfield_percentage', ts('Percentage'));
    $form->addElement('checkbox', 'percentagepricesetfield_apply_to_taxes', ts('Apply percentage to tax amounts'));
    if ($form->getVar('_action') & CRM_Core_Action::UPDATE) {
      $form->freeze('is_percentagepricesetfield');
    }

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

    $resource = CRM_Core_Resources::singleton();
    $resource->addVars('percentagepricesetfield', $vars);
  }
}

function _percentagepricesetfield_setDefaults_adminPriceField(&$form) {
  $defaults = array();

  $field_id = $form->getVar('_fid');
  if (!$field_id) {
    return;
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
function _percentagepricesetfield_rectify_price_options($field_values) {

  $field_id = $field_values['field_id'];
  // Delete any existing price options, and add only the one that should be there.
  $dao = new CRM_Price_DAO_PriceFieldValue();
  $dao->price_field_id = $field_id;
  $dao->find(TRUE);
  $dao->financial_type_id = $field_values['financial_type_id'];
  $dao->save();

}

function _percentagepricesetfield_postProcess_AdminPriceField($form) {
  $values = $form->_submitValues;
  $sid = $values['sid'];
  $fid = $values['fid'];

  if (array_key_exists('is_percentagepricesetfield', $values) && $values['is_percentagepricesetfield']) {
    $field_values = array(
      'percentage' => (float) $values['percentagepricesetfield_percentage'],
      'financial_type_id' => (int) $values['percentagepricesetfield_financial_type_id'],
      'apply_to_taxes' => (int) $values['percentagepricesetfield_apply_to_taxes'],
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
}

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
  CRM_Core_DAO::executeQuery($query, $params);
}
