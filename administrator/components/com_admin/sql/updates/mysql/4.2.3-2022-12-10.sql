--
-- Table structure for table `#__schemaorg`
--

CREATE TABLE IF NOT EXISTS `#__schemaorg` (
	`id` int(10) NOT NULL AUTO_INCREMENT,
	`itemId` int,
	`context` varchar(100),
	`schemaType` varchar(100),
	`schemaForm` text,
	`schema` text,
	PRIMARY KEY (`id`)
<<<<<<< HEAD
) ENGINE=INNODB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
=======
) ENGINE=INNODB DEFAULT CHARSET=utf8;
>>>>>>> b873bf0eb654712ff3874f560058e341ddada91e

-- Add plugins to `#__extensions`
INSERT INTO `#__extensions` (`package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `locked`, `manifest_cache`, `params`, `custom_data`, `ordering`, `state`) VALUES
(0, 'plg_schemaorg_book', 'plugin', 'book', 'schemaorg', 0, 1, 1, 0, 0, '', '{}', '', 0, 0),
(0, 'plg_schemaorg_blogposting', 'plugin', 'blogposting', 'schemaorg', 0, 1, 1, 0, 0, '', '{}', '', 0, 0),
<<<<<<< HEAD
(0, 'plg_schemaorg_organization', 'plugin', 'organization', 'schemaorg', 0, 1, 1, 0, 0, '', '{}', '', 0, 0);
=======
(0, 'plg_schemaorg_organization', 'plugin', 'organization', 'schemaorg', 0, 1, 1, 0, 0, '', '{}', '', 0, 0),
(0, 'plg_schemaorg_event', 'plugin', 'event', 'schemaorg', 0, 1, 1, 0, 0, '', '{}', '', 0, 0);
>>>>>>> b873bf0eb654712ff3874f560058e341ddada91e
