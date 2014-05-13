----------------------------------------------------------------------------------
--calculate_position()
----------------------------------------------------------------------------------
DROP FUNCTION calculate_position() CASCADE;

--remove this trigger for group adds/delete

CREATE OR REPLACE FUNCTION media_cycle_check() RETURNS trigger AS $media_cycle_check$
    
    DECLARE media_id_count integer;

    BEGIN

	RAISE NOTICE 'NEW.media_id is currently %', NEW.media_id;

	-- Make sure not inserting into itself.
        IF NEW.media_id = NEW.playlist_id THEN
            RAISE EXCEPTION 'insertion will cause media self cycle';
        END IF;

        WITH RECURSIVE media_child_list(media_id) AS (
           SELECT mc.media_id FROM media_content mc
           WHERE mc.playlist_id = NEW.playlist_id
           
           UNION ALL
           
           SELECT mc.media_id FROM media_child_list ml, media_content mc
           WHERE ml.media_id = mc.playlist_id
        )
        SELECT into media_id_count count(media_id) FROM media_child_list where media_id = NEW.playlist_id;

        RAISE NOTICE 'media_id_count is currently %', media_id_count;

        -- Make sure not creating a child cycle.
        IF media_id_count > 0 THEN
            RAISE EXCEPTION 'insertion will cause media child cycle';
        END IF;

        WITH RECURSIVE media_parent_list(media_id) AS (
           SELECT mc.playlist_id as media_id FROM media_content mc
           WHERE mc.media_id = NEW.playlist_id
           
           UNION ALL
           
           SELECT mc.playlist_id as media_id FROM media_parent_list ml, media_content mc
           WHERE mc.media_id = ml.media_id
        )
        SELECT into media_id_count count(media_id) FROM media_parent_list where media_id = NEW.media_id;

        RAISE NOTICE 'media_id_count is currently %', media_id_count;

        -- Make sure not creating a parent cycle.
        IF media_id_count > 0 THEN
            RAISE EXCEPTION 'insertion will cause media parent cycle';
        END IF;

	RETURN NEW;
    END;
$media_cycle_check$ LANGUAGE plpgsql;

DROP TRIGGER media_cycle_check ON media_content;

CREATE TRIGGER media_cycle_check BEFORE INSERT ON media_content
    FOR EACH ROW EXECUTE PROCEDURE media_cycle_check();

