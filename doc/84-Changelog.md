<a id="Changelog"></a>Changelog
===============================

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
