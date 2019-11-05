SET @stmt = (SELECT IF(
    (SELECT EXISTS(
        SELECT * FROM information_schema.table_constraints
        WHERE
            table_schema   = DATABASE()
            AND table_name = 'monitoring_connection'
            AND constraint_name = 'priority'
    )),
    'ALTER TABLE monitoring_connection DROP FOREIGN KEY priority',
    'SELECT 1'
));

PREPARE stmt FROM @stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
SET @stmt = NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
VALUES (16, NOW());
