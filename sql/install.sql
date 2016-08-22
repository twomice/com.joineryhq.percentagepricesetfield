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
  PRIMARY KEY (`id`),
  UNIQUE KEY `field_id` (`field_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Price set fields marked as "additional percentage" type.';

--
-- Constraints for table `civicrm_percentagepricesetfield`
--

ALTER TABLE `civicrm_percentagepricesetfield`
  ADD CONSTRAINT `civicrm_percentagepricesetfield_ibfk_1` FOREIGN KEY (`field_id`) REFERENCES `civicrm_price_field` (`id`) ON DELETE CASCADE;

