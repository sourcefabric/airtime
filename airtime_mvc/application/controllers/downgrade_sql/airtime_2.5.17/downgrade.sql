UPDATE cc_webstream_metadata SET instance_id=-1 WHERE instance_id IS NULL;

ALTER TABLE cc_webstream_metadata ALTER COLUMN instance_id SET NOT NULL;