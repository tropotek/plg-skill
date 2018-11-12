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




