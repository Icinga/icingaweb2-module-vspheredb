-- SPDX-FileCopyrightText: 2020 Icinga GmbH <https://icinga.com>
-- SPDX-License-Identifier: GPL-3.0-or-later

ALTER TABLE object
  MODIFY COLUMN moref VARCHAR(128) NOT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (31, NOW());
