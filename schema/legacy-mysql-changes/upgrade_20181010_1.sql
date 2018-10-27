ALTER TABLE virtual_machine
  ADD COLUMN hardware_numcorespersocket TINYINT UNSIGNED DEFAULT 1 NOT NULL
   AFTER hardware_numcpu;
