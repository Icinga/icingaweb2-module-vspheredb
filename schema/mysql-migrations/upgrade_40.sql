UPDATE monitoring_rule_set SET settings = replace(settings, '"Default/', '"ObjectPolicy/');

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (40, NOW());
