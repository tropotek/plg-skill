-- ---------------------------------
-- common.js
-- 
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

CREATE TABLE IF NOT EXISTS skill_domain (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  profile_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  name VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT,
  order_by INT(11) UNSIGNED NOT NULL DEFAULT 0,
  del TINYINT(1) NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (profile_id),
  KEY del (del)
) ENGINE=InnoDB;
TRUNCATE skill_domain;

INSERT INTO skill_domain (profile_id, name, description, modified, created)
  VALUES
    (2, 'Clinical Skills', '', NOW(), NOW()),
    (2, 'Scientific basis of clinical practice', '', NOW(), NOW()),
    (2, 'Biosecurity and population health', '', NOW(), NOW()),
    (2, 'Ethics and Animal welfare', '', NOW(), NOW()),
    (2, 'Personal and Professional Development', '', NOW(), NOW())
;
UPDATE skill_domain SET order_by = id;

CREATE TABLE IF NOT EXISTS skill_scale (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  profile_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  name VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT,
  value DECIMAL(6,3) NOT NULL DEFAULT 0.0,              -- TODO: may not be needed. just divide total scale items-1
  order_by INT(11) UNSIGNED NOT NULL DEFAULT 0,
  del TINYINT(1) NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (profile_id),
  KEY del (del)
) ENGINE=InnoDB;
TRUNCATE skill_scale;

INSERT INTO skill_scale (profile_id, name, description, value, modified, created)
VALUES
  (2, 'Not Assessed', 'The student may have observed the task, but not taken part in the completion of the task, or the task may not be applicable to the type of placement', 0, NOW(), NOW()),                       -- 0%
  (2, 'Unable', 'The student attempted the task, but did not successfully complete the task due to lack of knowledge or skill.', 20, NOW(), NOW()),
  (2, 'Developing', 'The student attempted the task and successfully completed most of the task.', 40, NOW(), NOW()),
  (2, 'Acceptable', 'The student attempted the task and usually succeeded in fully completing the task. “Day one” skill level.', 60, NOW(), NOW()),
  (2, 'Good', 'The student completed the task and was very able.', 80, NOW(), NOW()),
  (2, 'Exceptional', 'The student demonstrated the task with exceptionally well honed skills.', 100, NOW(), NOW())           -- 100%
;
UPDATE skill_scale SET order_by = id;



-- ---------------------------------------
-- Skill question setup tables
-- ---------------------------------------

-- skill_category as a nested tree, generally has 2 levels
--   items should only be allowed to be added to sub-categories only
CREATE TABLE IF NOT EXISTS skill_category (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid VARCHAR(128) NOT NULL DEFAULT '',
  profile_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  parent_id INT(10) UNSIGNED NOT NULL DEFAULT 0,              -- parent skill_group.id
  name VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT,
  publish TINYINT(1) NOT NULL DEFAULT 1,
  order_by INT(11) UNSIGNED NOT NULL DEFAULT 0,
  del TINYINT(1) NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (profile_id),
  KEY (parent_id),
  KEY (del)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS skill_item (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid VARCHAR(128) NOT NULL DEFAULT '',                       -- TODO: Use to create unique/updateable data
  profile_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
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
  KEY (profile_id),
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
  profile_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  type_group VARCHAR(190) NOT NULL DEFAULT '',
  name VARCHAR(190) NOT NULL DEFAULT '',
  order_by INT(11) UNSIGNED NOT NULL DEFAULT 0,
  del TINYINT(1) NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (profile_id),
  UNIQUE KEY (`profile_id`, `type_group`, `name`),
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
  course_id INT(10) UNSIGNED NOT NULL DEFAULT 0,            -- The entry is connected to a course
  user_id INT(10) UNSIGNED NOT NULL DEFAULT 0,              -- The student user id the bundle belongs to
  title VARCHAR(255) NOT NULL DEFAULT '',                   -- A title for the assessment instance
  type VARCHAR(255) NOT NULL DEFAULT '',                    -- A type tag, Basic, Case Work-up, Critical Moment, Placement Plan, Placement Review.
  status VARCHAR(64) NOT NULL DEFAULT '',                   -- pending, approved, not-approved

  -- TODO: thse fields should be in a skills_entry_data table. Dynamic fields based on the profile setup.
  location VARCHAR(255) NOT NULL DEFAULT '',                -- Where did the placement/task occur
  praise_comment TEXT,                                      -- What went well in this placement?
  highlight_comment TEXT,                                   -- What one thing stood out?
  improve_comment TEXT,                                     -- What could you have done to improve your experience?
  different_comment TEXT,                                   -- What are you planning to do differently for your next placement?

  notes TEXT,                                               -- Staff only notes
  del TINYINT(1) NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (course_id),
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






