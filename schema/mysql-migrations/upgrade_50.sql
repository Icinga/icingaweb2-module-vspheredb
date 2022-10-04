CREATE TABLE monitoring_rule_problem (
    uuid VARBINARY(16) NOT NULL,
    current_state ENUM (
      'WARNING',
      'UNKNOWN',
      'CRITICAL'
    ) NOT NULL,
    rule_name VARCHAR(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL, -- RuleSet/Rule
    ts_created_ms BIGINT NOT NULL,
    ts_changed_ms BIGINT NOT NULL,
    PRIMARY KEY (uuid, rule_name),
  CONSTRAINT monitoring_rule_problem_object
    FOREIGN KEY uuid (uuid)
    REFERENCES object (uuid)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (50, NOW());
