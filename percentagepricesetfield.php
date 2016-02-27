<?php


/** FIXME: NOTES:
 * To get "additional percentage" into the "Input Field Type", use the buildForm hook.
 * To display "additional percentage"-type fields in the price set form, use the buildForm hook.
 */



require_once 'percentagepricesetfield.civix.php';

function percentagepricesetfield_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Price_Form_Field') {
    _percentagepricesetfield_buildForm_PriceFormField($form);
  }
}

function _percentagepricesetfield_buildForm_PriceFormField(&$form) {
  dfb(__FUNCTION__);
  // Add custom JavaScript to override option_html_type() function
  $resource = CRM_Core_Resources::singleton();
  $resource->addScriptFile('com.joineryhq.percentagepricesetfield', 'js/percentagepricesetfield.js', 100, 'html-header');

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
