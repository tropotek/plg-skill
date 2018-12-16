-- ----------------------------------
-- @author mifsudm@unimelb.edu.au
-- ----------------------------------



#
# -- -----------------------------------------------------
# -- Use this table to store the average value of the domain
# --
# --
# -- -----------------------------------------------------
# CREATE TABLE IF NOT EXISTS skill_entry_grade (
#   id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
#
#   entry_id INT UNSIGNED NOT NULL DEFAULT 0,
#   user_id INT UNSIGNED NOT NULL DEFAULT 0,
#   domain_id INT UNSIGNED NOT NULL DEFAULT 0,
#   collection_id INT UNSIGNED NOT NULL DEFAULT 0,
#   entry_status VARCHAR(64) NOT NULL DEFAULT '',
#   placement_status VARCHAR(64) NOT NULL DEFAULT '',
#
#   domain_count INT UNSIGNED NOT NULL DEFAULT 0,
#   scale_count INT UNSIGNED NOT NULL DEFAULT 0,
#   weight FLOAT NOT NULL DEFAULT 1.0,                -- The domain weight to be applied to the average
#
#   avg FLOAT NOT NULL DEFAULT 0.0,
#   zero_avg FLOAT NOT NULL DEFAULT 0.0,
#
#   KEY (entry_id),
#   KEY (user_id),
#   KEY (domain_id),
#   KEY (collection_id)
# ) ENGINE=InnoDB;

UPDATE skill_category SET uid = '0' WHERE uid = '' OR uid IS NULL;
alter table skill_category modify uid INT UNSIGNED default 0 not null;
UPDATE skill_collection SET uid = '0' WHERE uid = '' OR uid IS NULL;
alter table skill_collection modify uid INT UNSIGNED default 0 not null;
UPDATE skill_domain SET uid = '0' WHERE uid = '' OR uid IS NULL;
alter table skill_domain modify uid INT UNSIGNED default 0 not null;
UPDATE skill_item SET uid = '0' WHERE uid = '' OR uid IS NULL;
alter table skill_item modify uid INT UNSIGNED default 0 not null;
UPDATE skill_scale SET uid = '0' WHERE uid = '' OR uid IS NULL;
alter table skill_scale modify uid INT UNSIGNED default 0 not null;

-- Fix older GOALS items
UPDATE skill_item a, skill_domain a1, skill_category b LEFT JOIN skill_domain c ON (b.collection_id = c.collection_id AND b.label = c.label)
SET a.domain_id = c.id
WHERE a.domain_id = a1.id AND a.category_id = b.id AND c.name IS NOT NULL AND a1.name != c.name
;





