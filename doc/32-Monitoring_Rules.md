<a id="Monitoring_Rules"></a>Monitoring Rules
=============================================

Our simple Check Commands have been available since a long time, but since v1.4
they learned many new tricks, and their behavior can be tweaked directly in the
Web frontend. Via **Monitoring Rules** you can reach the related overview page:

![Monitoring Rules - Menu](screenshot/03_checks/0305-monitoring_menu.png)

Please navigate to **Hosts**, **Virtual Machines** or **Data Stores** for a related
tree representation:

![Monitoring Rule Hierarchy](screenshot/03_checks/0304-monitoring_rule_hierarchy.png)

This depends on whether and how you organized your vSphere Objects in folders.
At every level in this hierarchy, you can configure, override and also disable
related Checks:

![Monitoring Rules](screenshot/03_checks/0303-monitoring_rules.png)

Some Rules allow for multiple instances, currently Disk Checks are the only such
implementation:

![Monitoring Disks](screenshot/03_checks/0306-monitoring_disks.png)

You can add as many variants as you want at every node, and you can still extend,
override or even disable them for a specific subtree.

All changes, once stored, have immediate effect on related Check Commands:

![Sample Check Command Output](screenshot/03_checks/0301-check_command.png)

Virtual Machines are the object type with the most available Rule Types for now.
They offer a related **Monitoring** tab to show what the Check Command would
tell you:

![Monitoring Details - UI](screenshot/03_checks/0302-monitoring_details.png)

You can show applied settings in case you need to investigate a specific Check:

![Show Rule-related settings](screenshot/03_checks/0307-monitoring_rule_detailled_settings.png)


If you want to execute the related Check Plugin, please read more about our
[Check Commands](31-Check_Commands.md).
