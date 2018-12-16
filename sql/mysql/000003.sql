-- ----------------------------------
-- @author mifsudm@unimelb.edu.au
-- ----------------------------------


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




