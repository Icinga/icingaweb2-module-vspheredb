-- SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
-- SPDX-License-Identifier: GPL-3.0-or-later

ALTER TABLE monitoring_connection
  ADD COLUMN priority SMALLINT(5) UNSIGNED NOT NULL AFTER vcenter_uuid,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (vcenter_uuid, priority);
