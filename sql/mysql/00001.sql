-- ---------------------------------
-- Install SQL
-- 
-- Author: Michael Mifsud <info@tropotek.com>
-- ---------------------------------







-- ----------------------------
--  Skill and Entry Tables
-- ----------------------------

CREATE TABLE IF NOT EXISTS skill_domain (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  institution_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  name VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT,
  order_by INT(11) UNSIGNED NOT NULL DEFAULT 0,
  del TINYINT(1) NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (institution_id),
  KEY del (del)
) ENGINE=InnoDB;

TRUNCATE skill_domain;

INSERT INTO skill_domain (institution_id, name, description, modified, created)
  VALUES
    (1, 'Clinical Skills', '', NOW(), NOW()),
    (1, 'Scientific basis of clinical practice', '', NOW(), NOW()),
    (1, 'Biosecurity and population health', '', NOW(), NOW()),
    (1, 'Ethics and Animal welfare', '', NOW(), NOW()),
    (1, 'Personal and Professional Development', '', NOW(), NOW())
;

UPDATE skill_domain SET order_by = id;

CREATE TABLE IF NOT EXISTS skill_scale (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  institution_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  name VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT,
  value DECIMAL(6,3) NOT NULL DEFAULT 0.0,              -- TODO: may not be needed. just divide total scale items-1
  order_by INT(11) UNSIGNED NOT NULL DEFAULT 0,
  del TINYINT(1) NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (institution_id),
  KEY del (del)
) ENGINE=InnoDB;

TRUNCATE skill_scale;

INSERT INTO skill_scale (institution_id, name, description, value, modified, created)
VALUES
  (1, 'Not Assessed', 'The student may have observed the task, but not taken part in the completion of the task, or the task may not be applicable to the type of placement', 0, NOW(), NOW()),                       -- 0%
  (1, 'Unable', 'The student attempted the task, but did not successfully complete the task due to lack of knowledge or skill.', 20, NOW(), NOW()),
  (1, 'Developing', 'The student attempted the task and successfully completed most of the task.', 40, NOW(), NOW()),
  (1, 'Acceptable', 'The student attempted the task and usually succeeded in fully completing the task. “Day one” skill level.', 60, NOW(), NOW()),
  (1, 'Good', 'The student completed the task and was very able.', 80, NOW(), NOW()),
  (1, 'Exceptional', 'The student demonstrated the task with exceptionally well honed skills.', 100, NOW(), NOW())           -- 100%
;

UPDATE skill_scale SET order_by = id;

-- ---------------------------------------
-- Skill question setup tables
-- ---------------------------------------

-- skill_category as a nested tree, generally has 2 levels
CREATE TABLE IF NOT EXISTS skill_category (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid VARCHAR(128) NOT NULL DEFAULT '',                       -- TODO: Use to create unique/updateable data
  institution_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  parent_id INT(10) UNSIGNED NOT NULL DEFAULT 0,              -- parent skill_group.id
  name VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT,
  publish TINYINT(1) NOT NULL DEFAULT 1,
  order_by INT(11) UNSIGNED NOT NULL DEFAULT 0,
  del TINYINT(1) NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (parent_id),
  KEY (del)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS skill_item (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid VARCHAR(128) NOT NULL DEFAULT '',                       -- TODO: Use to create unique/updateable data
  institution_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
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
  KEY (del)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS skill_tag (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid VARCHAR(128) NOT NULL DEFAULT '',                       -- TODO: Use to create unique/updateable data
  item_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  domain_id INT(10) UNSIGNED NOT NULL DEFAULT 0,              -- TODO: I do not think this belongs here.... (Ask Abdule/Simon )
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
  uid VARCHAR(128) NOT NULL DEFAULT '',                       -- TODO: Use to create unique/updateable data
  institution_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  type_group VARCHAR(190) NOT NULL DEFAULT '',
  name VARCHAR(190) NOT NULL DEFAULT '',
  order_by INT(11) UNSIGNED NOT NULL DEFAULT 0,
  del TINYINT(1) NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (del),
  UNIQUE KEY (`institution_id`, `type_group`, `name`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `item_has_type` (
  `item_id` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `type_id` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY `course_has_user_key` (`item_id`, `type_id`)
) ENGINE=InnoDB;



-- These are the instance tables.
-- TODO: OOHH!! lets create a conversation tree per bundle instance, then the staff and student can converse on it....
CREATE TABLE IF NOT EXISTS skill_entry (
  id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  institution_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  user_id INT(10) UNSIGNED NOT NULL DEFAULT 0,              -- The student user id the bundle belongs to
  title VARCHAR(255) NOT NULL DEFAULT '',                   -- A title for the assessment instance
  location VARCHAR(255) NOT NULL DEFAULT '',                -- Where did the placement/task occur
  type VARCHAR(255) NOT NULL DEFAULT '',                    -- A type tag, Basic, Case Work-up, Critical Moment, Placement Plan, Placement Review.
  status VARCHAR(64) NOT NULL DEFAULT '',                   -- pending, approved, not-approved
  praise_comment TEXT,                                      -- What went well in this placement?
  highlight_comment TEXT,                                   -- What one thing stood out?
  improve_comment TEXT,                                     -- What could you have done to improve your experience?
  different_comment TEXT,                                   -- What are you planning to do differently for your next placement?
  notes TEXT,                                               -- Staff only notes
  del TINYINT(1) NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (institution_id),
  KEY (user_id),
  KEY (del)
) ENGINE=InnoDB;



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















-- --------------------------------------------------------------------------------------------------------------
-- --------------------------------------------------------------------------------------------------------------
-- --------------------------------------------------------------------------------------------------------------
-- --------------------------------------------------------------------------------------------------------------


-- For now we will be assuming all historical data will be
--  compiled off-site in another app or DB, this should help
--  keep the CPU load down, as historical reports are 
--  rarely used. 


-- If we do this this way, then once a collection is created by a user
--   then we cannot allow delete/create function for the staff
--   I would have to be done by the system admin taking into account
--   entries that are not added to existing collections.???
-- Also fo historic reporting we need a solution to merge the data
--   alternatively this data could be merged externally
-- 

CREATE TABLE IF NOT EXISTS skill_set (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id int(10) UNSIGNED NOT NULL DEFAULT 0,
  placement_type_id int(10) UNSIGNED NOT NULL DEFAULT 0,    -- TODO: Looks like we will be attaching them to the placement type now
  uid VARCHAR(16) NOT NULL DEFAULT '',                      -- The uid can be used to link similar skills_groups across courses for historic reporting
  name VARCHAR(255) NOT NULL DEFAULT '',                    --
  description TEXT,
  
  -- TODO: These can be plugin settings
--  confirm TEXT,                                       -- If set then a checkbox will be added to the company skill-set form for valid submission
--  sa_enable TINYINT(1) NOT NULL DEFAULT 0,            -- Enable self-assessments for students 
--  show_grade TINYINT(1) NOT NULL DEFAULT 0,           -- Enable students to view their grades

  data TEXT,        -- Any extra data that may be required, preferably in a JSON string
  del TINYINT(1) NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (course_id),
  KEY del (del)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS skill_group (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  skill_set_id int(10) UNSIGNED NOT NULL DEFAULT 0,
  uid VARCHAR(16) NOT NULL DEFAULT '',                 -- The uid can be used to link similar skills_groups across courses for historic reporting 
  name VARCHAR(255) NOT NULL DEFAULT '',
  short_name VARCHAR(255) NOT NULL DEFAULT '',
  weight DECIMAL(6, 3) NOT NULL DEFAULT 0,              -- If grading is used
  description TEXT,
  order_by int(11) unsigned NOT NULL DEFAULT 0,
  del TINYINT(1) NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (skill_set_id),
  KEY (del)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS skill (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  skill_group_id int(10) UNSIGNED NOT NULL DEFAULT 0,
  uid VARCHAR(16) NOT NULL DEFAULT '',                 -- The uid can be used to link similar skills across courses for historic reporting
  name VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT,
  order_by int(11) unsigned NOT NULL DEFAULT 0,
  del TINYINT(1) NOT NULL DEFAULT 0,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL,
  KEY (skill_group_id),
  KEY (del)
) ENGINE=InnoDB;


-- These are the instance tables.
-- Dont forget to add the status system to each bundle.
CREATE TABLE IF NOT EXISTS skill_bundle (
  id int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  skill_set_id int(10) unsigned NOT NULL DEFAULT 0,
  student_id int(10) unsigned NOT NULL DEFAULT 0,     -- The student user id the bundle belongs to
  self_assessment tinyint(1) NOT NULL DEFAULT 0,      -- If set then this is a self assessment. placementId is not relevant
  notes text,
  status VARCHAR(64) NOT NULL DEFAULT '',      -- pending, approved, not-approved
  del tinyint(1) NOT NULL DEFAULT 0,
  modified datetime NOT NULL,
  created datetime NOT NULL,
  KEY (skill_set_id),
  KEY (student_id),
  KEY (del)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS skill_bundle_has_placement (
  skill_bundle_id int(10) unsigned NOT NULL DEFAULT 0,
  placement_id int(10) unsigned NOT NULL DEFAULT 0,
  assessor VARCHAR(128) NOT NULL DEFAULT '',          -- (optional) Name of person assessing the student if not supervisor.,
  daysAbsent INT(4) NOT NULL DEFAULT 0,               -- Number of days absent from placement
  PRIMARY KEY (skill_bundle_id, placement_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS skill_score (
  id int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  skill_bundle_id int(10) unsigned NOT NULL DEFAULT 0,
  skill_id int(10) unsigned NOT NULL DEFAULT 0,
  score VARCHAR(8) NOT NULL DEFAULT '',
  del tinyint(1) NOT NULL DEFAULT 0,
  modified datetime NOT NULL,
  created datetime NOT NULL,
  KEY (skill_bundle_id),
  KEY (skill_id),
  KEY (del)
) ENGINE=InnoDB;

