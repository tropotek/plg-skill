-- ---------------------------------
-- Install SQL
-- 
-- Author: Michael Mifsud <info@tropotek.com>
-- ---------------------------------


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

