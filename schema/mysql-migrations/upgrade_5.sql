-- SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
-- SPDX-License-Identifier: GPL-3.0-or-later

ALTER TABLE host_system
  MODIFY COLUMN runtime_power_state ENUM (
    'poweredOff',
    'poweredOn',
    'standBy',
    'unknown'
  ) NOT NULL;

INSERT INTO vspheredb_schema_migration
    (schema_version, migration_time)
VALUES (5, NOW());
