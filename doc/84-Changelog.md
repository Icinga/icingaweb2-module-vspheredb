<a id="Changelog"></a>Changelog
===============================

v1.1.0
------
### Breaking Changes
* This module no longer depends on the Icinga Director
* New dependencies have been introduced, [Upgrading](05-Upgrading.md) and
  [Installation](02-Installation.md) documentations contain related details

### Upgrading

This release brings Schema migrations, which can be applied with a single click
in the Frontend. Please go to *Virtualization (VMware)* - *Configuration* and
**Apply** the pending schema migration. Afterwards please restart the *Background
Daemon*.

### Fixed issues
* You can find issues and feature requests related to this release on our
  [roadmap](https://github.com/Icinga/icingaweb2-module-vspheredb/milestone/4?closed=1)

v1.0.4
------

This is a minor bugfix release. Also, it makes sure that this module will work
smoothly with the upcoming Icinga Director v1.7.0 release. It supports/tolerates
more data anomalies like unexpected NULL values. Talking to custom API ports is
now supported.

### Upgrading

Please restart the *Background Daemon* - it's error handling has been improved.

### UI
* FIX: Do not fail  with Director v1.7.0 (#118)
* FIX: Non-admin users should be allowd to customize columns (#92)
* FIX: Do not offer migration wizard in case of connection issues (#62)

### Background Daemon
* FIX: Keep retrying on initialization errors (#44)
* FIX: Do not fail for VMs with "no hardware" (#59)
* FIX: Do not warn when disconnecting in disconnected state (#59)
* FIX: Fail nicely on permission issues (#59)
* FIX: Support zero-sized DataStores (#75)
* FIX: Improve error handling for unknown classes (#48)
* FIX: Eventually force refresh for storage.perDatastoreUsage (#57)
* FIX:  ChooseDbResourceForm: fail friendly even when misconfigured (#51)

### Schema
* FIX: Support Service Tags longer than 32 characters (#60)
* FIX: Support the VmResetting Event (#58)

### All Issues and Feature Requests
* You can find issues and feature requests related to this release on our
  [roadmap](https://github.com/Icinga/icingaweb2-module-vspheredb/milestone/5?closed=1)


v1.0.3
------

This is a minor bugfix release. It improves error handling, shows Guest Disk
usage bar column per default and highlights Active Host Memory even when higher
than available VM Memory. Host System details view has been re-organized:

![Host System details](screenshot/84_changelog/0804_hostinfo-reorganized.png)

### Upgrading

Please restart the *Background Daemon* - it's error handling has been improved.

### UI
* FIX: Memory Usage shows Host Memory usage when exceeding available memory (#18)
* FEATURE: VM Guest Disk Usage now shows usage bar per default (#46)
* FEATURE: Host Information has been re-organized (#47)

### Background Daemon
* FIX: Keep retrying on initialization errors (#44)
* FIX: Avoid useless attempt to kill dead children (#45)

### All Issues and Feature Requests
* You can find issues and feature requests related to this release on our
  [roadmap](https://github.com/Icinga/icingaweb2-module-vspheredb/milestone/3?closed=1)

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
