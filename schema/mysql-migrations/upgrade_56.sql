DELETE FROM monitoring_rule_problem WHERE rule_name NOT LIKE '%/%';

CREATE TABLE monitoring_rule_problem_history (
    uuid VARBINARY(16) NOT NULL,
    current_state ENUM (
      'OK',
      'WARNING',
      'UNKNOWN',
      'CRITICAL'
    ) NOT NULL,
    former_state ENUM (
      'OK',
      'WARNING',
      'UNKNOWN',
      'CRITICAL'
    ) NOT NULL,
    rule_name VARCHAR(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL, -- RuleSet/Rule
    ts_changed_ms BIGINT NOT NULL,
    output MEDIUMTEXT NULL,
    PRIMARY KEY (ts_changed_ms, uuid, rule_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (56, NOW());
