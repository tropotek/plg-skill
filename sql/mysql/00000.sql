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
  uid VARCHAR(128) NOT NULL DEFAULT '',
  profile_id INT UNSIGNED DEFAULT 0 NOT NULL,
  name VARCHAR(255) DEFAULT '' NOT NULL,
  role VARCHAR(128) DEFAULT '' NOT NULL,
  icon VARCHAR(255) DEFAULT '' NOT NULL,          -- a bootstrap CSS icon for the collection EG: 'fa fa-pen', 'glyphicon glyphicon-home'
  color VARCHAR(8) DEFAULT '' NOT NULL,           -- the representative color for this collection
  available VARCHAR(255) DEFAULT '' NOT NULL,     -- A list of placement statuses that the collection is available for submission/editing by user
  active BOOL NOT NULL DEFAULT 1,                 -- enable/disable user submission/editing
  gradable BOOL DEFAULT 0 NOT NULL,               -- Is this collection gradable
  view_grade BOOL DEFAULT 0 NOT NULL,             -- Can the student view their grade results for this collection
  include_zero BOOL DEFAULT 1 NOT NULL,           -- Should the zero values be included in the weighted average calculation (Default: true)
  confirm TEXT,
  instructions TEXT,
  notes TEXT,
  del BOOL NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (profile_id),
  KEY del (del)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS skill_domain (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid VARCHAR(128) NOT NULL DEFAULT '',
  collection_id INT UNSIGNED NOT NULL DEFAULT 0,
  name VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT,
  label VARCHAR(10) NOT NULL,                                 -- abbreviated label, parent will be used if none
  weight FLOAT DEFAULT '1' NOT NULL,                          -- grade weight as a ratio, parent will be used if none
  order_by INT UNSIGNED NOT NULL DEFAULT 0,
  del BOOL NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (collection_id),
  KEY del (del)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS skill_scale (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid VARCHAR(128) NOT NULL DEFAULT '',
  collection_id INT UNSIGNED NOT NULL DEFAULT 0,
  name VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT,
  value DECIMAL(6,3) NOT NULL DEFAULT 0.0,              -- TODO: may not be needed. just divide total scale items-1
  order_by INT UNSIGNED NOT NULL DEFAULT 0,
  del BOOL NOT NULL DEFAULT 0,
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
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid VARCHAR(128) NOT NULL DEFAULT '',
  collection_id INT UNSIGNED NOT NULL DEFAULT 0,
  name VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT,
  publish BOOL NOT NULL DEFAULT 1,
  order_by INT UNSIGNED NOT NULL DEFAULT 0,
  del BOOL NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (collection_id),
  KEY (del)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS skill_item (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid VARCHAR(128) NOT NULL DEFAULT '',                       -- TODO: Use to create unique/updateable data
  collection_id INT UNSIGNED NOT NULL DEFAULT 0,
  category_id INT UNSIGNED NOT NULL DEFAULT 0,
  domain_id INT UNSIGNED NOT NULL DEFAULT 0,
  question VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT,
  publish BOOL NOT NULL DEFAULT 1,
  order_by INT UNSIGNED NOT NULL DEFAULT 0,
  del BOOL NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  -- KEY (category_id),
  KEY (collection_id),
  KEY (del)
) ENGINE=InnoDB;







-- These are the instance tables.
-- TODO: OOHH!! lets create a conversation/discussion per entry instance, then the staff and student can converse on it....
-- TODO:
--
CREATE TABLE IF NOT EXISTS skill_entry (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  collection_id INT UNSIGNED NOT NULL DEFAULT 0,            --
  course_id INT UNSIGNED NOT NULL DEFAULT 0,                --
  user_id INT UNSIGNED NOT NULL DEFAULT 0,                  -- The student user id the bundle belongs to
  placement_id INT UNSIGNED NOT NULL DEFAULT 0,             -- (optional) The placement this entry is linked to if 0 then assume self-assessment
  title VARCHAR(255) NOT NULL DEFAULT '',                   -- A title for the assessment instance
  assessor VARCHAR(128) DEFAULT '' NOT NULL,                -- Name of person assessing the student if not supervisor.
  absent INT(4) DEFAULT '0' NOT NULL,                       -- Number of days absent from placement.
  average DECIMAL(6,2) NOT NULL DEFAULT 0.0,                -- Average calculated from all item values
  weighted_average DECIMAL(6,2) NOT NULL DEFAULT 0.0,       -- Average calculated from all item values with their domain weight, including/not zero values
  confirm BOOL NOT NULL DEFAULT 0,                          -- The value of the confirmation question
  status VARCHAR(64) NOT NULL DEFAULT '',                   -- pending, approved, not-approved
  notes TEXT,                                               -- Staff only notes
  del BOOL NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (collection_id),
  KEY (course_id),
  KEY (user_id),
  KEY (placement_id),
  KEY (del)
) ENGINE=InnoDB;


-- ------------------------------------------------------
-- TODO: Lets change the value table to have no entry for a zero values
-- TODO:  this will save around 1/4 of required disc space .....
-- TODO:
-- ------------------------------------------------------
CREATE TABLE IF NOT EXISTS skill_value (
  entry_id INT UNSIGNED NOT NULL DEFAULT 0,
  item_id INT UNSIGNED NOT NULL DEFAULT 0,
  value TEXT,
  PRIMARY KEY (entry_id, item_id),
  KEY (entry_id),
  KEY (item_id)
) ENGINE=InnoDB;






