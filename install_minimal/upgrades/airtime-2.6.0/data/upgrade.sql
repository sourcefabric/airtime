-----------------------------------------------------------------------
-- media_item
-----------------------------------------------------------------------

CREATE TABLE "media_item"
(
    "id" serial NOT NULL,
    "name" VARCHAR(512),
    "creator" VARCHAR(512),
    "source" VARCHAR(512),
    "owner_id" INTEGER,
    "description" VARCHAR(512),
    "last_played" TIMESTAMP(6),
    "play_count" INTEGER DEFAULT 0,
    "length" interval DEFAULT '00:00:00',
    "mime" VARCHAR,
    "created_at" TIMESTAMP,
    "updated_at" TIMESTAMP,
    "descendant_class" VARCHAR(100),
    PRIMARY KEY ("id")
);

-----------------------------------------------------------------------
-- media_audiofile
-----------------------------------------------------------------------

CREATE TABLE "media_audiofile"
(
    "directory" INTEGER,
    "filepath" TEXT DEFAULT '',
    "track_title" VARCHAR(512),
    "artist_name" VARCHAR(512),
    "bit_rate" INTEGER,
    "sample_rate" INTEGER,
    "album_title" VARCHAR(512),
    "genre" VARCHAR(64),
    "comments" TEXT,
    "year" INTEGER,
    "track_number" INTEGER,
    "channels" INTEGER,
    "bpm" INTEGER,
    "encoded_by" VARCHAR(255),
    "mood" VARCHAR(64),
    "label" VARCHAR(512),
    "composer" VARCHAR(512),
    "copyright" VARCHAR(512),
    "conductor" VARCHAR(512),
    "isrc_number" VARCHAR(512),
    "info_url" VARCHAR(512),
    "language" VARCHAR(512),
    "replay_gain" NUMERIC,
    "cuein" interval DEFAULT '00:00:00',
    "cueout" interval DEFAULT '00:00:00',
    "silan_check" BOOLEAN DEFAULT 'f',
    "file_exists" BOOLEAN DEFAULT 't',
    "hidden" BOOLEAN DEFAULT 'f',
    "import_status" INTEGER DEFAULT 1 NOT NULL,
    "id" INTEGER NOT NULL,
    "name" VARCHAR(512),
    "creator" VARCHAR(512),
    "source" VARCHAR(512),
    "owner_id" INTEGER,
    "description" VARCHAR(512),
    "last_played" TIMESTAMP(6),
    "play_count" INTEGER DEFAULT 0,
    "length" interval DEFAULT '00:00:00',
    "mime" VARCHAR,
    "created_at" TIMESTAMP,
    "updated_at" TIMESTAMP,
    PRIMARY KEY ("id")
);


-----------------------------------------------------------------------
-- media_webstream
-----------------------------------------------------------------------

CREATE TABLE "media_webstream"
(
    "url" VARCHAR(512) NOT NULL,
    "id" INTEGER NOT NULL,
    "name" VARCHAR(512),
    "creator" VARCHAR(512),
    "source" VARCHAR(512),
    "owner_id" INTEGER,
    "description" VARCHAR(512),
    "last_played" TIMESTAMP(6),
    "play_count" INTEGER DEFAULT 0,
    "length" interval DEFAULT '00:00:00',
    "mime" VARCHAR,
    "created_at" TIMESTAMP,
    "updated_at" TIMESTAMP,
    PRIMARY KEY ("id")
);

-----------------------------------------------------------------------
-- media_playlist
-----------------------------------------------------------------------

CREATE TABLE "media_playlist"
(
    "class_key" INTEGER,
    "rules" text DEFAULT '' NOT NULL,
    "id" INTEGER NOT NULL,
    "name" VARCHAR(512),
    "creator" VARCHAR(512),
    "source" VARCHAR(512),
    "owner_id" INTEGER,
    "description" VARCHAR(512),
    "last_played" TIMESTAMP(6),
    "play_count" INTEGER DEFAULT 0,
    "length" interval DEFAULT '00:00:00',
    "mime" VARCHAR,
    "created_at" TIMESTAMP,
    "updated_at" TIMESTAMP,
    PRIMARY KEY ("id")
);

-----------------------------------------------------------------------
-- media_content
-----------------------------------------------------------------------

CREATE TABLE "media_content"
(
    "id" serial NOT NULL,
    "playlist_id" INTEGER,
    "media_id" INTEGER,
    "position" INTEGER,
    "trackoffset" interval DEFAULT '00:00:00' NOT NULL,
    "cliplength" interval DEFAULT '00:00:00' NOT NULL,
    "cuein" interval DEFAULT '00:00:00',
    "cueout" interval DEFAULT '00:00:00',
    "fadein" DECIMAL DEFAULT 0,
    "fadeout" DECIMAL DEFAULT 0,
    PRIMARY KEY ("id")
);

ALTER TABLE "cc_show_instances" ADD CONSTRAINT "cc_recorded_media_item_fkey"
    FOREIGN KEY ("media_id")
    REFERENCES "media_item" ("id")
    ON DELETE CASCADE;

ALTER TABLE "cc_schedule" ADD CONSTRAINT "media_item_sched_fkey"
    FOREIGN KEY ("media_id")
    REFERENCES "media_item" ("id")
    ON DELETE CASCADE;

ALTER TABLE "cc_playout_history" ADD CONSTRAINT "media_item_history_fkey"
    FOREIGN KEY ("media_id")
    REFERENCES "media_item" ("id")
    ON DELETE SET NULL;

ALTER TABLE "media_item" ADD CONSTRAINT "media_item_owner_fkey"
    FOREIGN KEY ("owner_id")
    REFERENCES "cc_subjs" ("id");

ALTER TABLE "media_audiofile" ADD CONSTRAINT "audio_file_music_dir_fkey"
    FOREIGN KEY ("directory")
    REFERENCES "cc_music_dirs" ("id");

ALTER TABLE "media_audiofile" ADD CONSTRAINT "media_audiofile_FK_2"
    FOREIGN KEY ("id")
    REFERENCES "media_item" ("id")
    ON DELETE CASCADE;

ALTER TABLE "media_audiofile" ADD CONSTRAINT "media_audiofile_FK_3"
    FOREIGN KEY ("owner_id")
    REFERENCES "cc_subjs" ("id");

ALTER TABLE "media_webstream" ADD CONSTRAINT "media_webstream_FK_1"
    FOREIGN KEY ("id")
    REFERENCES "media_item" ("id")
    ON DELETE CASCADE;

ALTER TABLE "media_webstream" ADD CONSTRAINT "media_webstream_FK_2"
    FOREIGN KEY ("owner_id")
    REFERENCES "cc_subjs" ("id");

ALTER TABLE "media_playlist" ADD CONSTRAINT "media_playlist_FK_1"
    FOREIGN KEY ("id")
    REFERENCES "media_item" ("id")
    ON DELETE CASCADE;

ALTER TABLE "media_playlist" ADD CONSTRAINT "media_playlist_FK_2"
    FOREIGN KEY ("owner_id")
    REFERENCES "cc_subjs" ("id");

ALTER TABLE "media_content" ADD CONSTRAINT "media_content_playlist_fkey"
    FOREIGN KEY ("playlist_id")
    REFERENCES "media_playlist" ("id")
    ON DELETE CASCADE;

ALTER TABLE "media_content" ADD CONSTRAINT "media_content_media_fkey"
    FOREIGN KEY ("media_id")
    REFERENCES "media_item" ("id")
    ON DELETE CASCADE;

CREATE INDEX "audiofile_directory_idx" ON "media_audiofile" ("directory");
CREATE INDEX "audiofile_filepath_idx" ON "media_audiofile" ("filepath");
CREATE INDEX "history_item_starts_index" ON "cc_playout_history" ("starts");
CREATE INDEX "playout_history_metadata_idx" ON "cc_playout_history_metadata" ("history_id");
CREATE INDEX "playout_history_template_field_i" ON "cc_playout_history_template_field" ("template_id");
CREATE INDEX "media_content_playlist_idx" ON "media_content" ("playlist_id");
CREATE INDEX "media_content_media_idx" ON "media_content" ("media_id");


-----------------------------------------------------------------------
-- function to insert media into new tables.
-- need to iterate through one by one so we can update any media ids in cc_schedule, cc_playout_history etc.
-----------------------------------------------------------------------

CREATE OR REPLACE FUNCTION migrateCcFiles() RETURNS int4 AS $$

DECLARE r RECORD;
DECLARE media_primary_key integer;

BEGIN

    -----------------------------------------------------------
    -- Transfer all cc_files to media_audiofile and media_item
    -----------------------------------------------------------
    FOR r IN SELECT * from cc_files LOOP
 
        insert into media_item (name, creator, source, owner_id, description, last_played, play_count, length, mime, created_at, updated_at, descendant_class)
        values (r.track_title, r.artist_name, r.album_title, r.owner_id, null, r.lptime, 0, r.length, r.mime, r.utime, r.mtime, 'Airtime\MediaItem\AudioFile')
        returning id into media_primary_key;

        insert into media_audiofile (name, creator, source, owner_id, description, last_played, play_count, length, mime, created_at, updated_at,
            directory, filepath, track_title, artist_name, bit_rate, sample_rate, album_title, genre, comments, year, track_number, channels, bpm, encoded_by,
            mood, label, composer, copyright, conductor, isrc_number, info_url, language, replay_gain, cuein, cueout, silan_check, file_exists, hidden, import_status)
        values (r.track_title, r.artist_name, r.album_title, r.owner_id, null, r.lptime, 0, r.length, r.mime, r.utime, r.mtime,
                r.directory, null, r.track_title, r.artist_name, r.bit_rate, r.sample_rate, r.album_title, r.genre, r.comments, (r.year), r.track_number, r.channels, r.bpm, r.encoded_by,
                r.mood, r.label, r.composer, r.copyright, r.conductor, r.isrc_number, r.info_url, r.language, r.replay_gain, r.cuein, r.cueout, r.silan_check, r.file_exists, r.hidden, r.import_status)
            
    END LOOP;

return 1;
END;
$$ 
LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION migrateCcWebstream() RETURNS int4 AS $$

DECLARE r RECORD;
DECLARE media_primary_key integer;

BEGIN

    -----------------------------------------------------------
    -- Transfer all cc_webstream to media_webstream and media_item
    -----------------------------------------------------------
    FOR r IN SELECT * from cc_webstream LOOP
 
        insert into media_item (name, creator, source, owner_id, description, last_played, play_count, length, mime, created_at, updated_at, descendant_class)
        values (r.name, null, null, r.owner_id, null, r.lptime, 0, r.length, r.mime, r.utime, r.mtime, 'Airtime\MediaItem\Webstream')
        returning id into media_primary_key;

        insert into media_audiofile (name, creator, source, owner_id, description, last_played, play_count, length, mime, created_at, updated_at, url)
        values (r.track_title, r.artist_name, r.album_title, r.owner_id, null, r.lptime, 0, r.length, r.mime, r.utime, r.mtime, r.url)
            
    END LOOP;

return 1;
END;
$$ 
LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION migrateCcPlaylist() RETURNS int4 AS $$

DECLARE r RECORD;
DECLARE media_primary_key integer;

BEGIN

    -----------------------------------------------------------
    -- Transfer all cc_playlist to media_playlist and media_item
    -----------------------------------------------------------
    FOR r IN SELECT * from cc_playlist LOOP
 
        insert into media_item (name, creator, source, owner_id, description, last_played, play_count, length, mime, created_at, updated_at, descendant_class)
        values (r.track_title, r.artist_name, r.album_title, r.owner_id, null, r.lptime, 0, r.length, r.mime, r.utime, r.mtime, 'Airtime\MediaItem\Playlist')
        returning id into media_primary_key;

        insert into media_audiofile (name, creator, source, owner_id, description, last_played, play_count, length, mime, created_at, updated_at, class_key, rules)
        values (r.track_title, r.artist_name, r.album_title, r.owner_id, null, r.lptime, 0, r.length, r.mime, r.utime, r.mtime, 0, null);
            
    END LOOP;
    

return 1;
END;
$$ 
LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION migrateCcBlock() RETURNS int4 AS $$

DECLARE r RECORD;
DECLARE media_primary_key integer;
DECLARE class_key integer;

BEGIN

    -----------------------------------------------------------
    -- Transfer all cc_block to media_playlist and media_item
    -----------------------------------------------------------
    FOR r IN SELECT * from cc_block LOOP

        IF r.type = 'static' THEN
            class_key := 0;
        ELSE
            class_key := 1;
        END IF;
 
        insert into media_item (name, creator, source, owner_id, description, last_played, play_count, length, mime, created_at, updated_at, descendant_class)
        values (r.track_title, r.artist_name, r.album_title, r.owner_id, null, r.lptime, 0, r.length, r.mime, r.utime, r.mtime, 'Airtime\MediaItem\Playlist')
        returning id into media_primary_key;

        insert into media_audiofile (name, creator, source, owner_id, description, last_played, play_count, length, mime, created_at, updated_at, class_key, rules)
        values (r.track_title, r.artist_name, r.album_title, r.owner_id, null, r.lptime, 0, r.length, r.mime, r.utime, r.mtime, class_key, null);
            
    END LOOP;
    

return 1;
END;
$$ 
LANGUAGE plpgsql;
