-- --------------------------------------------------------

--
-- Table structure for table `civicrm_percentagepricesetfield`
--

CREATE TABLE IF NOT EXISTS `civicrm_percentagepricesetfield` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `field_id` int(10) unsigned NOT NULL COMMENT 'FK: civicrm_price_field.id',
  PRIMARY KEY (`id`),
  KEY `field_id` (`field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Price set fields marked as "additional percentage" type.' AUTO_INCREMENT=1 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `civicrm_percentagepricesetfield`
--
ALTER TABLE `civicrm_percentagepricesetfield`
  ADD CONSTRAINT `civicrm_percentagepricesetfield_ibfk_1` FOREIGN KEY (`field_id`) REFERENCES `civicrm_price_field` (`id`) ON DELETE CASCADE;
