ALTER TABLE host_system
 MODIFY COLUMN bios_version VARCHAR(64) DEFAULT NULL;

INSERT INTO vspheredb_schema_migration
    (schema_version, migration_time)
VALUES (6, NOW());
