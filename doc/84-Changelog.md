<a id="Changelog"></a>Changelog
===============================

master
------
Replicates object to a database. The module has been renamed and refactored
completely. Currently under heavy development, it might break and/or change
at any time without pre-announcement. So, please do not use it in production.

1.1.0
-----

Import now ships real Hashes/Dictionaries. Formerly, flat dot-separated keys
have been used. This is a breaking change compared to 1.0.0. However, as Sync
initially wasn't really possible without the help of PropertyModifiers, this
was definitively the way to go.

1.0.0
-----

First stable release. Provides a Director Import Source for Virtual Machines and
Host Systems. Also, some CLI-Tools for debugging (or automation) purposes are
available.

