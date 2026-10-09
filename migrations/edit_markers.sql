-- Apply once to databases created before edit markers were added to schema.sql.
ALTER TABLE recipes
    ADD COLUMN is_edited TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE comments
    ADD COLUMN is_edited TINYINT(1) NOT NULL DEFAULT 0;
