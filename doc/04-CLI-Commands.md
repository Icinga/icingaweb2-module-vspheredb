<a name="CLI-Commands"></a>CLI Commands
=======================================

Fetch all available Virtual Machines
------------------------------------

This command is mostly for test/debug reasons and gives you an output of all
**Virtual Machines** with a default set of properties:

### Usage

    icingacli vsphere fetch virtualmachines [options]


### Options  
 
| Option                    | Description                                     |
|---------------------------|-------------------------------------------------|
| `--<vhost> <host>`        | IP, host or URL to for your vCenter or ESX host |
| `--<username> <user>`     | When authenticating, this username will be used |
| `--<password> <pass>`     | The related password                            |
| `--lookup-ids`            | Replace id-references with their name           |
| `--no-ssl-verify-peer`    | Accept certificates signed by unknown CA        |
| `--no-ssl-verify-host`    | Accept certificates not matching the host name  |
| `--use-insecure-http`     | Use plaintext HTTP requests                     |
| `--proxy <proxy>`         | Use the given Proxy (ip, host or host:port      |
| `--proxy-type <type>`     | HTTP (default) or SOCKS5                        |
| `--proxy-username <user>` | Username for authenticated HTTP proxy           |
| `--proxy-password <pass>` | Password for authenticated HTTP proxy           |
| `--benchmark`             | Show resource usage summary                     |
| `--json`                  | Dump JSON output                                |


Fetch all available Host Systems
--------------------------------

This command is mostly for test/debug reasons and gives you an output of all
**Host Systems** with a default set of properties:

### Usage

    icingacli vsphere fetch hostsystems [options]


### Options

| Option                    | Description                                     |
|---------------------------|-------------------------------------------------|
| `--<vhost> <host>`        | IP, host or URL to for your vCenter or ESX host |
| `--<username> <user>`     | When authenticating, this username will be used |
| `--<password> <pass>`     | The related password                            |
| `--no-ssl-verify-peer`    | Accept certificates signed by unknown CA        |
| `--no-ssl-verify-host`    | Accept certificates not matching the host name  |
| `--use-insecure-http`     | Use plaintext HTTP requests                     |
| `--proxy <proxy>`         | Use the given Proxy (ip, host or host:port      |
| `--proxy-type <type>`     | HTTP (default) or SOCKS5                        |
| `--proxy-username <user>` | Username for authenticated HTTP proxy           |
| `--proxy-password <pass>` | Password for authenticated HTTP proxy           |
| `--benchmark`             | Show resource usage summary                     |
| `--json`                  | Dump JSON output                                |
