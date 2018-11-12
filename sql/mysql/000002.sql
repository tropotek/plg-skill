-- ----------------------------------
-- @author mifsudm@unimelb.edu.au
-- ----------------------------------




CREATE INDEX domain_id ON skill_item (domain_id);
CREATE INDEX order_by ON skill_item (order_by);




-- ALTER TABLE skill_value MODIFY value FLOAT;
ALTER TABLE skill_entry MODIFY average FLOAT NOT NULL DEFAULT 0.00;
ALTER TABLE skill_entry MODIFY weighted_average FLOAT NOT NULL DEFAULT 0.00;
ALTER TABLE skill_scale MODIFY value FLOAT NOT NULL DEFAULT 0.0;

