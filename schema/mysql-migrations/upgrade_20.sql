-- SPDX-FileCopyrightText: 2020 Icinga GmbH <https://icinga.com>
-- SPDX-License-Identifier: GPL-3.0-or-later

TRUNCATE vspheredb_daemonlog;

INSERT INTO vspheredb_schema_migration
(schema_version, migration_time)
VALUES (20, NOW());
