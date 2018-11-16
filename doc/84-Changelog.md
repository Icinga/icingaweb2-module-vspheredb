<a id="Changelog"></a>Changelog
===============================

master (will be v1.0.3)
-----------------------

### UI
* FIX: Memory Usage shows Host Memory usage when exceeding available memory
* FEATURE: VM Guest Disk Usage now shows usage bar per default

v1.0.2
------

This is a minor bugfix release. Improves documentation and error handling, deals
with standBy Hosts and extra long BIOS versions. Virtual Machine table now has
one more optional column showing Guest Tools State:

![systemctl status](screenshot/84_changelog/0803_guest-tools-column.png)

### Upgrading

This release brings a Schema migration, which can be applied with a single click
in the Frontend. Please go to *Virtualization (VMware)* - *Configuration* and
**Apply** the pending schema migration. Restarting the *Background Daemon* is not
required for the migration, but strongly suggested - it's error handling has been
improved.

### UI
* FIX: Do not fail when left with vCenters without vCenter Server (#26)
* FIX: Show less options instead of errors to non-admin users (#30)
* FIX: Redirect after deleting a vCenter Server showed an error (#36)
* FEATURE: Provide a column showing Guest Tools Status (#17, #25)

### CLI
* FIX: `CTRL-C` should not show an error before shutting down the daemon (#34)

### Background Daemon
* FIX: Allow to store Hosts being in `standBy` (#19)
* FIX: Do not fail when a VM reports no attached DataStore (#23)
* FIX: Safely roll back transactions after *any* kind of Exception (#24)

### Schema
* FIX: Support BIOS versions longer than 32 characters (#35)

### Documentation
* FIX: Mention `php-pcntl` dependency (#21)
* FIX: Explain required Username/Permissions (#23)

### All Issues and Feature Requests
* You can find issues and feature requests related to this release on our
  [roadmap](https://github.com/Icinga/icingaweb2-module-vspheredb/milestone/2?closed=1)

v1.0.1
------

This is a minor bugfix release. Fixes two rare error conditions, improves
overall error handling (makes it more robust) and comes with a nice new feature
showing even more details in the Process status:

![systemctl status](screenshot/84_changelog/0801_systemctl-status.png)

The Service Unit also shows error conditions:

![systemctl status](screenshot/84_changelog/0802_systemctl-error-status.png)


* FIX: Host System details failed on some hardware models (#16)
* FIX: Catch invalid (-1) host memory usage, found on a weird 6.5 ESXi (#14)
* FIX: Roll back transactions regardless of Exception type, not only after DB
  errors (discovered while debugging #14)
* FIX: do not loose connection to subprocess when an Exception with invalid
  (binary) characters is logged (related to #14)
* FEATURE: show active subprocess tasks in the processlist

v1.0.0
------
First public release.
