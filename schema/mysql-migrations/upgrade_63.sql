CREATE TABLE tagging_category (
  uuid VARBINARY(16) NOT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  id VARCHAR(128) NOT NULL,
  name VARCHAR(128) NOT NULL,
  cardinality ENUM('SINGLE', 'MULTIPLE') NOT NULL,
  description TEXT NULL DEFAULT NULL,
  associable_types TEXT NOT NULL, -- json-encoded array, like ["VirtualMachine"]
  PRIMARY KEY (uuid),
  INDEX vcenter_uuid (vcenter_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE tagging_tag (
  uuid VARBINARY(16) NOT NULL,
  category_uuid VARBINARY(16) NOT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  id VARCHAR(128) NOT NULL,
  name VARCHAR(128) NOT NULL,
  description TEXT NULL DEFAULT NULL,
  PRIMARY KEY (uuid),
  INDEX tag_category (category_uuid),
  INDEX vcenter_uuid (vcenter_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE tagging_object_tag (
  object_uuid VARBINARY(16) NOT NULL,
  tag_uuid VARBINARY(16) NOT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  PRIMARY KEY (object_uuid, tag_uuid),
  INDEX search_by_tag (tag_uuid, object_uuid),
  INDEX vcenter_uuid (vcenter_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (63, NOW());
