-- SPDX-FileCopyrightText: 2020 Icinga GmbH <https://icinga.com>
-- SPDX-License-Identifier: GPL-3.0-or-later

ALTER TABLE alarm_history
  MODIFY COLUMN full_message MEDIUMTEXT DEFAULT NULL;

ALTER TABLE vm_event_history
  MODIFY COLUMN full_message MEDIUMTEXT DEFAULT NULL;

INSERT INTO vspheredb_schema_migration
(schema_version, migration_time)
VALUES (21, NOW());
