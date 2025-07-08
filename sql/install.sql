--
-- Drop table `civicrm_percentagepricesetfield`.
--

DROP TABLE IF EXISTS `civicrm_percentagepricesetfield`;

--
-- Table structure for table `civicrm_percentagepricesetfield`
--

CREATE TABLE IF NOT EXISTS `civicrm_percentagepricesetfield` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `field_id` int(10) unsigned NOT NULL COMMENT 'FK: civicrm_price_field.id',
  `percentage` float NOT NULL COMMENT 'Percentage to apply',
  `financial_type_id` int(11) NOT NULL COMMENT 'financial_type_id of the first option of the checkbox group',
  `apply_to_taxes` tinyint(4) DEFAULT '1' COMMENT 'Should this percentage be applied on top of taxes',
  `hide_and_force` tinyint(4) DEFAULT '0' COMMENT 'Should this percentage be applied always, and the field hidden',
  `disable_payment_methods` varchar(255) NOT NULL COMMENT 'Concatenated string of payment processor IDs',
  `is_slider` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Display as slider',
  `slider_min` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Minimum percent for slider',
  `slider_max` int(10) unsigned NOT NULL DEFAULT 100 COMMENT 'Maximum percent for slider',
  'slider_default' int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Default percent for slider',
  `slider_step` int(10) unsigned NOT NULL DEFAULT 1 COMMENT 'Step for slider',
  'slider_step_list' int(10) unsigned NOT NULL DEFAULT 1 COMMENT 'Step for slider',
  PRIMARY KEY (`id`),
  UNIQUE KEY `field_id` (`field_id`)
) ENGINE=InnoDB COMMENT='Price set fields marked as "additional percentage" type.';

--
-- Constraints for table `civicrm_percentagepricesetfield`
--

ALTER TABLE `civicrm_percentagepricesetfield`
  ADD CONSTRAINT `civicrm_percentagepricesetfield_ibfk_1` FOREIGN KEY (`field_id`) REFERENCES `civicrm_price_field` (`id`) ON DELETE CASCADE;

