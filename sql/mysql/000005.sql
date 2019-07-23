-- ----------------------------------
-- @author mifsudm@unimelb.edu.au
-- ----------------------------------


-- Fix the issue with new domains and category ids not being copied correctly when creating a new subject.



# SELECT *
# FROM skill_item a LEFT JOIN
#      skill_domain b ON (a.domain_id = b.id) LEFT JOIN
#      skill_domain c ON (b.uid = c.uid AND c.collection_id = a.collection_id) LEFT JOIN
#      skill_category d ON (a.category_id = d.id) LEFT JOIN
#      skill_category e ON (e.uid = e.uid AND e.collection_id = a.collection_id)
# WHERE a.collection_id != b.collection_id
# ;

UPDATE skill_item a LEFT JOIN
    skill_domain b ON (a.domain_id = b.id) LEFT JOIN
    skill_domain c ON (b.uid = c.uid AND c.collection_id = a.collection_id) LEFT JOIN
    skill_category d ON (a.category_id = d.id) LEFT JOIN
    skill_category e ON (e.uid = e.uid AND e.collection_id = a.collection_id)
SET a.category_id = e.id, a.domain_id = c.id
WHERE a.collection_id != b.collection_id
;

