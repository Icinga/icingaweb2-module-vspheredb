SET @stmt = (SELECT IF(
    (SELECT EXISTS(
        SELECT * FROM information_schema.table_constraints
        WHERE
            table_schema   = DATABASE()
            AND table_name = 'host_system'
            AND constraint_name = 'sysinfo_uuid'
    )),
    'DROP INDEX sysinfo_uuid ON host_system',
    'SELECT 1'
));

PREPARE stmt FROM @stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
SET @stmt = NULL;

INSERT INTO vspheredb_schema_migration
(schema_version, migration_time)
VALUES (19, NOW());
