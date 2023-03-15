ALTER TABLE virtual_machine
    ADD COLUMN guest_ip_addresses TEXT DEFAULT NULL AFTER boot_order;
ALTER TABLE virtual_machine
  ADD COLUMN guest_ip_stack TEXT DEFAULT NULL AFTER guest_ip_addresses;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (58, NOW());
