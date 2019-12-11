-- ----------------------------------
-- @author mifsudm@unimelb.edu.au
-- ----------------------------------




alter table skill_collection change profile_id course_id int unsigned default 0 not null;
drop index profile_id on skill_collection;
create index course_id on skill_collection (course_id);
drop index skill_collection_subject_id_index on skill_collection;
create index subject_id on skill_collection (subject_id);









