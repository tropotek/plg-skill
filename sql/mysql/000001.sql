-- ----------------------------------
-- @author mifsudm@unimelb.edu.au
-- ----------------------------------


-- Update collection Icons
UPDATE `skill_collection` SET `icon` = REPLACE(`icon`, 'fa fa-question', 'fa fa-user-circle-o');
UPDATE `skill_collection` SET `icon` = REPLACE(`icon`, 'fa fa-commenting-o', 'fa fa-user-md');



ALTER TABLE skill_collection ADD org_id int UNSIGNED DEFAULT 0 NOT NULL AFTER id;
ALTER TABLE skill_collection ADD subject_id int UNSIGNED DEFAULT 0 NOT NULL AFTER profile_id;
INSERT INTO skill_collection (org_id, uid, profile_id, subject_id, name, role, icon, color, available, active, gradable, require_placement, max_grade,
                              view_grade, include_zero, confirm, instructions, notes, del, modified, created)
  (
    SELECT b.id, b.id, b.profile_id, a.id, b.name, b.role, b.icon, b.color, b.available, b.active, b.gradable, b.require_placement, b.max_grade,
           b.view_grade, b.include_zero, b.confirm, b.instructions, b.notes, b.del, b.modified, b.created
    FROM subject a, skill_collection b
    WHERE a.profile_id = b.profile_id
  )
;

CREATE INDEX skill_collection_subject_id_index ON skill_collection (subject_id);
DROP INDEX profile_id ON skill_collection;

ALTER TABLE skill_collection ADD publish TINYINT DEFAULT 0 NOT NULL AFTER available;
UPDATE skill_collection a, skill_collection_subject b
SET a.publish = 1
WHERE a.org_id = b.collection_id AND a.subject_id = b.subject_id
;



-- Category
ALTER TABLE skill_category ADD org_id int UNSIGNED DEFAULT 0 NOT NULL AFTER id;
INSERT INTO skill_category (org_id, uid, collection_id, name, label, description, publish, order_by, del, modified, created)
    (
    SELECT b.id, b.uid, a.id, b.name, b.label, b.description, b.publish, b.order_by, b.del, b.modified, b.created
    FROM skill_collection a, skill_category b
    WHERE a.org_id > 0 AND a.org_id = b.collection_id
    ORDER BY a.id
    )
;

-- Placement Types
INSERT INTO skill_collection_placement_type (collection_id, placement_type_id)
    (
    SELECT a.id, b.placement_type_id
    FROM skill_collection a, skill_collection_placement_type b
    WHERE a.org_id > 0 AND a.org_id = b.collection_id
    ORDER BY a.id
    )
;

-- Domain
ALTER TABLE skill_domain ADD org_id int UNSIGNED DEFAULT 0 NOT NULL AFTER id;
INSERT INTO skill_domain (org_id, uid, collection_id, name, description, label, weight, active, order_by, del, modified, created)
    (
    SELECT b.id, b.uid, a.id, b.name, b.description, b.label, b.weight, b.active, b.order_by, b.del, b.modified, b.created
    FROM skill_collection a, skill_domain b
    WHERE a.org_id > 0 AND a.org_id = b.collection_id
    ORDER BY a.id
    )
;

-- Scale
ALTER TABLE skill_scale ADD org_id int UNSIGNED DEFAULT 0 NOT NULL AFTER id;
INSERT INTO skill_scale (org_id, uid, collection_id, name, description, value, order_by, del, modified, created)
    (
    SELECT b.id, b.uid, a.id, b.name, b.description, b.value, b.order_by, b.del, b.modified, b.created
    FROM skill_collection a, skill_scale b
    WHERE a.org_id > 0 AND a.org_id = b.collection_id
    ORDER BY a.id
    )
;

-- Item
ALTER TABLE skill_item ADD org_id int UNSIGNED DEFAULT 0 NOT NULL AFTER id;
INSERT INTO skill_item (org_id, uid, collection_id, category_id, domain_id, question, description, publish, order_by, del, modified, created)
    (
    SELECT b.id, b.uid, a.id, c.id, d.id, b.question, b.description, b.publish, b.order_by, b.del, b.modified, b.created
    FROM skill_collection a, skill_item b, skill_category c, skill_domain d
    WHERE a.org_id > 0 AND a.org_id = b.collection_id AND
          a.id = c.collection_id AND b.category_id = c.org_id AND
          a.id = d.collection_id AND b.domain_id = d.org_id
    ORDER BY a.id
    )
;
-- No domain
INSERT INTO skill_item (org_id, uid, collection_id, category_id, question, description, publish, order_by, del, modified, created)
    (
    SELECT b.id, b.uid, a.id, c.id, b.question, b.description, b.publish, b.order_by, b.del, b.modified, b.created
    FROM skill_collection a, skill_item b, skill_category c
    WHERE a.org_id = 4 AND a.org_id = b.collection_id AND
          a.id = c.collection_id AND b.category_id = c.org_id
    ORDER BY a.id
    )
;
INSERT INTO skill_item (org_id, uid, collection_id, category_id, question, description, publish, order_by, del, modified, created)
    (
    SELECT b.id, b.uid, a.id, c.id, b.question, b.description, b.publish, b.order_by, b.del, b.modified, b.created
    FROM skill_collection a, skill_item b, skill_category c
    WHERE a.org_id = 5 AND a.org_id = b.collection_id AND
          a.id = c.collection_id AND b.category_id = c.org_id
    ORDER BY a.id
    )
;


-- Entry
UPDATE skill_entry a, skill_collection b
  SET a.collection_id = b.id
  WHERE a.collection_id = b.org_id AND a.subject_id = b.subject_id
;

-- Value
# TODO Take note, the speed of this query is fast and low mem usage because we left the skill_value table until last ;-)
# SELECT c.id as 'entry_id', a.item_id, a.value, b.id as 'new_item_id', b.collection_id
# FROM skill_entry c, skill_item b, skill_value a
#   WHERE c.collection_id = b.collection_id AND a.item_id = b.org_id AND a.entry_id = c.id
# ;

UPDATE skill_entry c, skill_item b, skill_value a
  SET a.item_id = b.id
  WHERE c.collection_id = b.collection_id AND a.item_id = b.org_id AND a.entry_id = c.id
;
ALTER TABLE `skill_value` ADD INDEX(`entry_id`);
ALTER TABLE `skill_value` ADD INDEX(`item_id`);




--- SAF custom fix



UPDATE `skill_category` t SET t.`uid` = '1' WHERE t.`id` = 34;
UPDATE `skill_category` t SET t.`uid` = '1' WHERE t.`id` = 31;
UPDATE `skill_category` t SET t.`uid` = '3' WHERE t.`id` = 36;
UPDATE `skill_category` t SET t.`uid` = '2' WHERE t.`id` = 35;
UPDATE `skill_category` t SET t.`uid` = '2' WHERE t.`id` = 32;
UPDATE `skill_category` t SET t.`uid` = '3' WHERE t.`id` = 33;

UPDATE `skill_category` t SET t.`uid` = '3' WHERE t.`id` = 553;
UPDATE `skill_category` t SET t.`uid` = '3' WHERE t.`id` = 550;
UPDATE `skill_category` t SET t.`uid` = '1' WHERE t.`id` = 548;
UPDATE `skill_category` t SET t.`uid` = '1' WHERE t.`id` = 551;
UPDATE `skill_category` t SET t.`uid` = '2' WHERE t.`id` = 549;
UPDATE `skill_category` t SET t.`uid` = '2' WHERE t.`id` = 552;

UPDATE `skill_category` t SET t.`org_id` = 31 WHERE t.`id` = 551;
UPDATE `skill_category` t SET t.`org_id` = 33 WHERE t.`id` = 550;
UPDATE `skill_category` t SET t.`org_id` = 31 WHERE t.`id` = 548;
UPDATE `skill_category` t SET t.`org_id` = 32 WHERE t.`id` = 549;
UPDATE `skill_category` t SET t.`org_id` = 33 WHERE t.`id` = 553;
UPDATE `skill_category` t SET t.`org_id` = 32 WHERE t.`id` = 552;


UPDATE `skill_collection` t SET t.`org_id` = 4 WHERE t.`id` = 69;
UPDATE `skill_collection` t SET t.`org_id` = 4 WHERE t.`id` = 70;

INSERT INTO skill_item (org_id, uid, collection_id, category_id, question, description, publish, order_by, del, modified, created)
    (
    SELECT b.id, b.uid, a.id, c.id, b.question, b.description, b.publish, b.order_by, b.del, b.modified, b.created
    FROM skill_collection a, skill_item b, skill_category c
    WHERE (a.id = 69 OR a.id = 70) AND a.org_id = b.collection_id AND
          a.id = c.collection_id AND b.category_id = c.org_id
    ORDER BY a.id
    )
;




INSERT INTO skill_item (org_id, uid, collection_id, category_id, question, description, publish, order_by, del, modified, created)
    (
    SELECT b.id, b.uid, a.id, c.id, b.question, b.description, b.publish, b.order_by, b.del, b.modified, b.created
    FROM skill_collection a, skill_item b, skill_category c
    WHERE (a.id = 22 OR a.id = 23) AND a.org_id = b.collection_id AND
          a.id = c.collection_id AND b.category_id = c.org_id
    ORDER BY a.id
    )
;







-- -----------------------------------------
-- Delete temp and unneeded cols (may keep until all data is verified by Alana or someone?)
-- -----------------------------------------
-- DROP TABLE skill_collection_subject;   -- No longer needed
-- delete old skill_category where collection Id = old collection_id
-- delete old skill_domain where collection Id = old collection_id
-- delete old skill_item where collection Id = old collection_id
-- delete old skill_scale where collection Id = old collection_id
-- delete old skill_collection_placement_type where collection Id = old collection_id
-- deleteold skill_collection where org_id = 0    (Do Last)

#alter table skill_collection drop column org_id;
#alter table skill_collection drop column profile_id;
#alter table skill_category drop column org_id;
#alter table skill_domain drop column org_id;
#alter table skill_scale drop column org_id;
#alter table skill_item drop column org_id;


#alter table skill_entry drop column subject_id;


#OPTIMIZE TABLE `skill_value`;

