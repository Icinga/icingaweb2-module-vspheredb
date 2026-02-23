-- SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
-- SPDX-License-Identifier: GPL-3.0-or-later

ALTER TABLE vm_datastore_usage
  ADD COLUMN ts_updated BIGINT(20) UNSIGNED DEFAULT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
VALUES (7, NOW());
