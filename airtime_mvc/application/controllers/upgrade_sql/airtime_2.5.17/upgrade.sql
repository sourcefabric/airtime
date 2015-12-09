-----------------------------------------------------------------------
-- Allow webstream metadata to be stored without an associated scheduled track (for master/live source)
-----------------------------------------------------------------------

ALTER TABLE cc_webstream_metadata ALTER COLUMN instance_id DROP NOT NULL;