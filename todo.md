#TODO



#SQL Notes

Here is the process to get the SQL of the Collection Student results 


```sql

-- -------------------------------------------------------------------------


-- Query #1
-- Get a users lidt of approved entry_id's
SELECT a.collection_id, a.subject_id, a.user_id, b.id as 'entry_id'
FROM
  (
    SELECT 1 as 'collection_id', b.subject_id, a.id as 'user_id', a.name, a.uid
    FROM user a, subject_has_student b
    WHERE a.id = b.user_id AND  a.del = 0
      AND a.id = 1494           # user id
      # AND b.subject_id = 0      # subject
    GROUP BY a.id, b.subject_id
    ORDER BY b.subject_id
  ) a,
  skill_entry b
WHERE
  b.del = 0 AND
  a.user_id = b.user_id AND
  b.status = 'approved' AND
  b.collection_id = a.collection_id AND
  b.subject_id = a.subject_id AND
  b.user_id = a.user_id
;




-- Query #2
-- Get a list of all items in a collection for selected entries
SELECT a.collection_id, a.subject_id, a.user_id, b.domain_id, c.id as 'item_id', c.label, b.question, b.order_by
FROM
  (
    SELECT a.collection_id, a.subject_id, a.user_id, b.id as 'entry_id'
    FROM
      (
        SELECT 1 as 'collection_id', b.subject_id, a.id as 'user_id', a.name, a.uid
        FROM user a, subject_has_student b
        WHERE a.id = b.user_id AND  a.del = 0
              AND a.id = 1494           # user id
        # AND b.subject_id = 0      # subject
        GROUP BY a.id, b.subject_id
        ORDER BY b.subject_id
      ) a,
      skill_entry b
    WHERE
      b.del = 0 AND
      a.user_id = b.user_id AND
      b.status = 'approved' AND
      b.collection_id = a.collection_id AND
      b.subject_id = a.subject_id AND
      b.user_id = a.user_id
  ) a,
  skill_item b, skill_domain c
WHERE
  b.del = 0 AND c.del = 0 AND
  a.collection_id = b.collection_id AND
  b.domain_id = c.id
GROUP BY b.id
ORDER BY a.user_id, b.order_by
;



-- Query #3
-- Calculate the Average of each item for all entries in a collection
-- THIS IS IT!
SELECT a.collection_id, a.subject_id, a.user_id, d.domain_id, c.item_id, d.question,
  IFNULL(ROUND(AVG(NULLIF(c.value, 0)), 2), 0) AS 'avg', d.order_by, a.label
FROM
  (
    SELECT a.collection_id, a.subject_id, a.user_id, a.entry_id, b.domain_id, c.id as 'item_id', c.label, b.question, b.order_by
    FROM
      (
        SELECT a.collection_id, a.subject_id, a.user_id, b.id as 'entry_id'
        FROM
          (
            SELECT 1 as 'collection_id', b.subject_id, a.id as 'user_id', a.name, a.uid
            FROM user a, subject_has_student b
            WHERE a.id = b.user_id AND  a.del = 0
                  AND a.id = 1494           # user id
            # AND b.subject_id = 0      # subject
            GROUP BY a.id, b.subject_id
            ORDER BY b.subject_id
          ) a,
          skill_entry b
        WHERE
          b.del = 0 AND
          a.user_id = b.user_id AND
          b.status = 'approved' AND
          b.collection_id = a.collection_id AND
          b.subject_id = a.subject_id AND
          b.user_id = a.user_id
      ) a,
      skill_item b, skill_domain c
    WHERE
      b.del = 0 AND c.del = 0 AND
      a.collection_id = b.collection_id AND
      b.domain_id = c.id
    ORDER BY a.user_id, b.order_by
  ) a,
  skill_value c, skill_item d
WHERE
  d.del = 0 AND
  a.entry_id = c.entry_id AND
  c.item_id = d.id AND
  a.domain_id = d.domain_id
  -- AND a.label = 'CS'
  -- AND c.value > 0
GROUP BY a.user_id, c.item_id
ORDER BY a.user_id, d.order_by
;



-- Query #3
-- Calculate the Average of each item for all entries in a collection
SELECT a.collection_id, a.user_id, a.domain_id, a.label, b.weight,
  c.max_grade, ROUND(AVG(a.avg), 2) as 'avg',
  (ROUND(AVG(a.avg), 2)*(c.max_grade/d.scale)) as 'grade'
FROM
  (
    SELECT a.collection_id, a.subject_id, a.user_id, d.domain_id, c.item_id, d.question,
      IFNULL(ROUND(AVG(NULLIF(c.value, 0)), 2), 0) AS 'avg', d.order_by, a.label
    FROM
      (
        SELECT a.collection_id, a.subject_id, a.user_id, a.entry_id, b.domain_id, c.id as 'item_id', c.label, b.question, b.order_by
        FROM
          (
            SELECT a.collection_id, a.subject_id, a.user_id, b.id as 'entry_id'
            FROM
              (
                SELECT 1 as 'collection_id', b.subject_id, a.id as 'user_id', a.name, a.uid
                FROM user a, subject_has_student b
                WHERE a.id = b.user_id AND  a.del = 0
                      -- AND a.id = 1494           # user id
                      AND b.subject_id = 24      # subject id
                GROUP BY a.id, b.subject_id
                ORDER BY b.subject_id
              ) a,
              skill_entry b
            WHERE
              b.del = 0 AND
              a.user_id = b.user_id AND
              b.status = 'approved' AND
              b.collection_id = a.collection_id AND
              b.subject_id = a.subject_id AND
              b.user_id = a.user_id
          ) a,
          skill_item b, skill_domain c
        WHERE
          b.del = 0 AND c.del = 0 AND
          a.collection_id = b.collection_id AND
          b.domain_id = c.id
        ORDER BY a.user_id, b.order_by
      ) a,
      skill_value c, skill_item d
    WHERE
      d.del = 0 AND
      a.entry_id = c.entry_id AND
      c.item_id = d.id AND
      a.domain_id = d.domain_id
    -- AND a.label = 'CS'
    -- AND c.value > 0
    GROUP BY a.user_id, c.item_id
    ORDER BY a.user_id, d.order_by
  ) a,
  skill_domain b,
  skill_collection c,
  (
    SELECT a.collection_id, COUNT(a.id)-1 as 'scale'
    FROM skill_scale a
    GROUP BY a.collection_id
  ) d

WHERE a.domain_id = b.id AND
    c.id = a.collection_id AND
    d.collection_id = a.collection_id
    AND a.user_id = 1494           # user id

GROUP BY a.user_id, b.id
ORDER BY a.user_id, b.order_by
;

```





