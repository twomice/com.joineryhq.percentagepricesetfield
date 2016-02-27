<?php


/** FIXME: NOTES:
 * To get "additional percentage" into the "Input Field Type", use the buildForm hook.
 * To display "additional percentage"-type fields in the price set form, use the buildForm hook.
 */



require_once 'percentagepricesetfield.civix.php';

function percentagepricesetfield_civicrm_buildAmount($pageType, &$form, &$amount) {
//  return;
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
            $option['amount'] = _percentagepricesetfield_calculate_total($form);
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

function _percentagepricesetfield_calculate_total($form) {
  // FIXME: do actual math.
  return 2222.23;
}

function _percentagepricesetfield_get_properties($form) {
  // FIXME: IS this funtion ever called?
  // FIXME: get actual properties, and use static caching

  // - configurable values for a percentage field:
  //  - Checkbox label (e.g., Please add 4% to my donation amount to cover credit-card processing fees.)
  //  - checkbox help text (e.g., "This helps us a lot, thank you!")
  //  - line item label (e.g., "4% extra for credit card fees")
  //  - percentage (e.g., 4.0)
  return array(
    'checkbox_label',
  );
}

function _percentagepricesetfield_calculate_label($form) {
  // FIXME: dynamically build string.
  $label = ts('FIXME: %1 %', array(1 => _percentagepricesetfield_calculate_total($form)));
  return $label;
}

function _percentagepricesetfield_get_percentage($form) {
  // FIXME: get the actual percentage from the field.
  return 2.3;
}

function percentagepricesetfield_civicrm_buildForm($formName, &$form) {
  switch ($formName) {
    case 'CRM_Price_Form_Field':
      _percentagepricesetfield_buildForm_PriceFormField($form);
      break;

    case 'CRM_Event_Form_Registration_Register':
    case 'CRM_Contribute_Form_Contribution_Main':
      _percentagepricesetfield_buildForm_public_price_set_form($form);
      break;

  }
}

function _percentagepricesetfield_buildForm_public_price_set_form($form) {
  dsm($form);
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

function _percentagepricesetfield_buildForm_PriceFormField(&$form) {
  // FIXME: TODO:
  // - configurable values for a percentage field:
  //  - Checkbox label (e.g., Please add 4% to my donation amount to cover credit-card processing fees.)
  //  - checkbox help text (e.g., "This helps us a lot, thank you!")
  //  - line item label (e.g., "4% extra for credit card fees")
  //  - percentage (e.g., 4.0)
  //

  dsm('FIXME: '. __FUNCTION__ . ' not running.'); return;


  // Add custom JavaScript to override option_html_type() function
  $resource = CRM_Core_Resources::singleton();
  $resource->addScriptFile('com.joineryhq.percentagepricesetfield', 'js/percentagepricesetfield.js', 100, 'page-footer');

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

  $settings = array(
    'price_label' => $form->_elements[$form->_elementIndex['price']]->_label,
  );
  $resource->addVars('percentagepricesetfield', $settings);
}

function percentagepricesetfield_civicrm_postProcess($formName, &$form) {
  if ($formName == 'CRM_Price_Form_Field') {
    _percentagepricesetfield_postProcess_PriceFormField($form);
  }
}

function _percentagepricesetfield_postProcess_PriceFormField($form){
  dsm($form);
}

function _percentagepricesetfield_validate_field($fields, $files, $form) {
  $fields_backup = $fields;
  $fields['html_type'] = 'Text';
  $errors = $form->formRule($fields, $files, $form);
  $fields = $fields_backup;
  
  return empty($errors) ? TRUE : $errors;
}

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function percentagepricesetfield_civicrm_config(&$config) {
  _percentagepricesetfield_civix_civicrm_config($config);
  $html_types =& CRM_Price_BAO_PriceField::htmlTypes();
  $html_types['percentage'] = ts('Additional Percentage');
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
