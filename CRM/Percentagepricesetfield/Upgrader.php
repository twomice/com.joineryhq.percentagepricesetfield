<?php

/**
 * Collection of upgrade steps
 */
class CRM_Percentagepricesetfield_Upgrader extends CRM_Percentagepricesetfield_Upgrader_Base {

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
   * Example: Run an external SQL script
   *
   * @return TRUE on success
   * @throws Exception
   */
  // public function upgrade_4201() {
  //   $this->ctx->log->info('Applying update 4201');
  //   // this path is relative to the extension base dir
  //   $this->executeSqlFile('sql/upgrade_4201.sql');
  //   return TRUE;
  // } // */


  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk
   *
   * @return TRUE on success
   * @throws Exception
   */
  // public function upgrade_4202() {
  //   $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

  //   $this->addTask(ts('Process first step'), 'processPart1', $arg1, $arg2);
  //   $this->addTask(ts('Process second step'), 'processPart2', $arg3, $arg4);
  //   $this->addTask(ts('Process second step'), 'processPart3', $arg5);
  //   return TRUE;
  // }
  // public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  // public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  // public function processPart3($arg5) { sleep(10); return TRUE; }
  // // */


  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
   */
  // public function upgrade_4203() {
  //   $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

  //   $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
  //   $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
  //   for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
  //     $endId = $startId + self::BATCH_SIZE - 1;
  //     $title = ts('Upgrade Batch (%1 => %2)', array(
  //       1 => $startId,
  //       2 => $endId,
  //     ));
  //     $sql = '
  //       UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
  //       WHERE id BETWEEN %1 and %2
  //     ';
  //     $params = array(
  //       1 => array($startId, 'Integer'),
  //       2 => array($endId, 'Integer'),
  //     );
  //     $this->addTask($title, 'executeSql', $sql, $params);
  //   }
  //   return TRUE;
  // } // */

}
