
CREATE TABLE host_list (
  list_checksum VARBINARY(20) NOT NULL,
  PRIMARY KEY (list_checksum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE host_list_member (
  list_checksum VARBINARY(20) NOT NULL, -- sha1(uuid[uuid..])
  uuid VARBINARY(20) NOT NULL,
  PRIMARY KEY (uuid, list_checksum),
  CONSTRAINT host_list_member_list
    FOREIGN KEY host_list (list_checksum)
    REFERENCES host_list (list_checksum)
    ON DELETE CASCADE
    ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE vm_list (
  list_checksum VARBINARY(20) NOT NULL,
  PRIMARY KEY (list_checksum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE vm_list_member (
  list_checksum VARBINARY(20) NOT NULL, -- sha1(uuid[uuid..])
  uuid VARBINARY(20) NOT NULL,
  PRIMARY KEY (uuid, list_checksum),
  CONSTRAINT vm_list_member_list
    FOREIGN KEY vm_list (list_checksum)
    REFERENCES vm_list (list_checksum)
    ON DELETE CASCADE
    ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
VALUES (15, NOW());
