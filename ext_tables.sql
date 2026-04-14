
CREATE TABLE tx_inquiry_list_snapshot (
    identifier char(32)          NOT NULL DEFAULT '',
    items      text,
    prefill    text,
    crdate     int(11) unsigned   NOT NULL DEFAULT 0,
    PRIMARY KEY (identifier)
);