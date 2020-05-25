-- ----------------------------------
-- @author mifsudm@unimelb.edu.au
-- ----------------------------------



# SELECT *
# FROM status a, skill_entry b, subject c
# WHERE a.subject_id = 0 AND a.course_id= 0 AND fkey = 'Skill\\Db\\Entry' AND a.fid = b.id AND  b.subject_id = c.id
# ;



UPDATE status a, skill_entry b, subject c
SET a.`course_id` = c.course_id, a.subject_id = c.id
WHERE a.subject_id = 0 AND a.course_id= 0 AND fkey = 'Skill\\Db\\Entry' AND a.fid = b.id AND  b.subject_id = c.id
;








