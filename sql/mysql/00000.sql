-- ---------------------------------
-- Author: Michael Mifsud <info@tropotek.com>
-- ---------------------------------
--
-- For now we will be assuming all historical data will be
--  compiled off-site in another app or DB, this should help
--  keep the CPU load down, as historical reports are
--  rarely used.
--
-- If we do this this way, then once a collection is created by a user
--   then we cannot allow delete/create function for the staff
--   I would have to be done by the system admin taking into account
--   entries that are not added to existing collections.???
-- Also for historic reporting we need a solution to merge the data
--   alternatively this data could be merged externally
--
--


-- ----------------------------
--  Skill and Entry Tables
-- ----------------------------

CREATE TABLE IF NOT EXISTS skill_collection (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  profile_id INT UNSIGNED DEFAULT 0 NOT NULL,

  name VARCHAR(255) DEFAULT '' NOT NULL,
  instructions TEXT,
  confirm TEXT,
  enable_self_assessment TINYINT(1) DEFAULT 0 NOT NULL,
  enable_view_results TINYINT(1) DEFAULT 0 NOT NULL,
  notes TEXT,

  del TINYINT(1) NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (profile_id),
  KEY del (del)
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS skill_domain (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  collection_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  name VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT,
  label VARCHAR(10) NOT NULL,                                 -- abbreviated label, parent will be used if none
  weight FLOAT DEFAULT '1' NOT NULL,                          -- grade weight as a ratio, parent will be used if none
  order_by INT(11) UNSIGNED NOT NULL DEFAULT 0,
  del TINYINT(1) NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (collection_id),
  KEY del (del)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS skill_scale (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  collection_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  name VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT,
  value DECIMAL(6,3) NOT NULL DEFAULT 0.0,              -- TODO: may not be needed. just divide total scale items-1
  order_by INT(11) UNSIGNED NOT NULL DEFAULT 0,
  del TINYINT(1) NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (collection_id),
  KEY del (del)
) ENGINE=InnoDB;



-- ---------------------------------------
-- Skill question setup tables
-- ---------------------------------------

-- skill_category as a nested tree, generally has 2 levels
--   items should only be allowed to be added to sub-categories only
CREATE TABLE IF NOT EXISTS skill_category (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid VARCHAR(128) NOT NULL DEFAULT '',
  collection_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  parent_id INT(10) UNSIGNED NOT NULL DEFAULT 0,              -- parent skill_group.id
  name VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT,
  publish TINYINT(1) NOT NULL DEFAULT 1,
  order_by INT(11) UNSIGNED NOT NULL DEFAULT 0,
  del TINYINT(1) NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (collection_id),
  KEY (parent_id),
  KEY (del)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS skill_item (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid VARCHAR(128) NOT NULL DEFAULT '',                       -- TODO: Use to create unique/updateable data
  collection_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  category_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  domain_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  name VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT,
  publish TINYINT(1) NOT NULL DEFAULT 1,
  order_by INT(11) UNSIGNED NOT NULL DEFAULT 0,
  del TINYINT(1) NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  -- KEY (category_id),
  KEY (collection_id),
  KEY (del)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS skill_tag (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid VARCHAR(128) NOT NULL DEFAULT '',
  item_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  name VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT,
  order_by INT(11) UNSIGNED NOT NULL DEFAULT 0,
  del TINYINT(1) NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (item_id),
  KEY (del)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS skill_type (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid VARCHAR(128) NOT NULL DEFAULT '',
  collection_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  placement_type_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  type_group VARCHAR(190) NOT NULL DEFAULT '',
  name VARCHAR(190) NOT NULL DEFAULT '',
  order_by INT(11) UNSIGNED NOT NULL DEFAULT 0,
  del TINYINT(1) NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (collection_id),
  KEY (placement_type_id),
  UNIQUE KEY (`collection_id`, `type_group`, `name`),
  KEY (del)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `skill_item_has_type` (
  `item_id` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `type_id` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY `course_has_user_key` (`item_id`, `type_id`)
) ENGINE=InnoDB;






-- These are the instance tables.
-- TODO: OOHH!! lets create a conversation/discussion per entry instance, then the staff and student can converse on it....
-- TODO:
--
CREATE TABLE IF NOT EXISTS skill_entry (
  id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  collection_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  placement_id INT(10) UNSIGNED NOT NULL DEFAULT 0,         -- The placement this entry is linked to if 0 then assume self-assessment
  user_id INT(10) UNSIGNED NOT NULL DEFAULT 0,              -- The student user id the bundle belongs to
  title VARCHAR(255) NOT NULL DEFAULT '',                   -- A title for the assessment instance
  assessor VARCHAR(128) DEFAULT '' NOT NULL,                -- Name of person assessing the student if not supervisor.
  absent INT(4) DEFAULT '0' NOT NULL,                       -- Number of days absent from placement.
  status VARCHAR(64) NOT NULL DEFAULT '',                   -- pending, approved, not-approved

  notes TEXT,                                               -- Staff only notes
  del TINYINT(1) NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (collection_id),
  KEY (placement_id),
  KEY (user_id),
  KEY (del)
) ENGINE=InnoDB;


-- ------------------------------------------------------
-- TODO: Lets change the value table to have no entry for a zero values
-- TODO:  this will save around 1/4 of required disc space .....
-- TODO:
-- ------------------------------------------------------
CREATE TABLE IF NOT EXISTS skill_value (
  entry_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  item_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  value VARCHAR(32) NOT NULL DEFAULT '',
  PRIMARY KEY (entry_id, item_id),
  KEY (entry_id),
  KEY (item_id)
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS skill_selected (
  entry_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  item_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (entry_id, item_id),
  KEY (entry_id),
  KEY (item_id)
) ENGINE=InnoDB;






