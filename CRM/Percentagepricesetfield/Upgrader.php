<?php

/**
 * Collection of upgrade steps
 */
class CRM_Percentagepricesetfield_Upgrader extends CRM_Extension_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Run an external SQL script when the module is installed.
   */
  public function install() {
    $this->executeSqlFile('sql/install.sql');
  }

  /**
   * Remove custom table, and alert user if any fields remain abandoned.
   */
  public function uninstall() {
    $field_ids = _percentagepricesetfield_get_percentage_field_ids('ALL', FALSE);
    if (!empty($field_ids)) {
      $message = ts('There were existing percentage price fields; all percentage-related data for these fields has been permanently lost, and the fields will now behave as normal checkbox fields.');
      CRM_Core_Session::setStatus($message, ts('Abandonded fields remaining'), 'alert', array('expires' => 0));
    }

    $this->executeSqlFile('sql/uninstall.sql');
  }

  /**
   * Example: Run a simple query when a module is enabled
   */
  // public function enable() {
  //   CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  // }

  /**
   * Alert user if any fields remain abandoned.
   */
  public function disable() {
    $field_ids = _percentagepricesetfield_get_percentage_field_ids('ALL', FALSE);
    if (!empty($field_ids)) {
      $message = ts('There are existing percentage price fields; all percentage-related data for these fields will be permanently lost if the Percentage Price Set Field extension is uninstalled.');
      CRM_Core_Session::setStatus($message, ts('Abandonded fields remaining'), 'alert', array('expires' => 0));
    }
  }

  /**
   * Clean up duplicates; add unique index.
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1100() {
    $this->ctx->log->info('Applying update 1100 - cleaning up possible duplicates and adding unique key');

    CRM_Core_Transaction::create()->run(function($tx) {
      // Remove any duplicate rows per field, being sure to retain the one
      // with the greatest ID (which should be the newest one).
      $query = "
        DELETE del.* FROM civicrm_percentagepricesetfield del
        LEFT JOIN
          (
            SELECT MAX(id) AS id, field_id
            FROM civicrm_percentagepricesetfield
            GROUP BY field_id
          ) AS keep
          ON keep.id = del.id
        WHERE keep.id IS NULL
      ";
      CRM_Core_DAO::executeQuery($query);

      // Modify the index on field_id to be unique.
      $query = "
        ALTER TABLE civicrm_percentagepricesetfield DROP INDEX  `field_id`,
        ADD UNIQUE field_id (field_id)
      ";
      CRM_Core_DAO::executeQuery($query);
    });

    return TRUE;
  }

  /**
   * Add columns to civicrm_percentagepricesetfield table; rebuild menu.
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1101() {
    $query = "ALTER TABLE  civicrm_percentagepricesetfield
      ADD hide_and_force TINYINT NULL DEFAULT  '0' COMMENT 'Should this percentage be applied always, and the field hidden',
      ADD disable_payment_methods varchar(255) NOT NULL COMMENT 'Concatenated string of payment processor IDs'
    ";
    CRM_Core_DAO::executeQuery($query);

    // Also clear caches, because in version 1.2 we added a new menu path.
    civicrm_api3('system', 'flush', array());

    return TRUE;
  }

  /**
   * Update percentage price set field option label to use PERCENTAGEPRICESETFIELD_PLACEHOLDER_LABEL
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1102() {

    $query = "
      UPDATE civicrm_percentagepricesetfield ppf
        INNER JOIN civicrm_price_field pf ON pf.id = ppf.field_id
        INNER JOIN civicrm_price_field_value pfv ON pfv.price_field_id = pf.id
      SET
        pfv.label = '" . PERCENTAGEPRICESETFIELD_PLACEHOLDER_LABEL . "'
    ";

    CRM_Core_DAO::executeQuery($query, $query);

    return TRUE;
  }

  /**
   * We've changed the value of PERCENTAGEPRICESETFIELD_PLACEHOLDER_LABEL to something
   * more human-readable in case the extension is disabled (reference:
   * https://github.com/twomice/com.joineryhq.percentagepricesetfield/issues/34)
   * So, update percentage price set field option label to use the new value of
   * PERCENTAGEPRICESETFIELD_PLACEHOLDER_LABEL.
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1103() {

    $query = "
      UPDATE civicrm_percentagepricesetfield ppf
        INNER JOIN civicrm_price_field pf ON pf.id = ppf.field_id
        INNER JOIN civicrm_price_field_value pfv ON pfv.price_field_id = pf.id
      SET
        pfv.label = '" . PERCENTAGEPRICESETFIELD_PLACEHOLDER_LABEL . "'
    ";

    CRM_Core_DAO::executeQuery($query, $query);

    return TRUE;
  }

  /**
   * Added a new show_fees column to the database to allow for fees to be displayed
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1104() {

    $query = "
      ALTER TABLE civicrm_percentagepricesetfield ppf
        ADD COLUMN show_fees tinyint(4) DEFAULT '0' COMMENT 'Should this display the fees and label'
    ";

    CRM_Core_DAO::executeQuery($query, $query);

    return TRUE;
  }
  

}
