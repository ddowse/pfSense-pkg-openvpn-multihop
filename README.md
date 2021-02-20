# pfSense-pkg-openvpn-multihop
Provides an easy way to setup multihop OpenVPN Connections. 

This utility will allow you to create a list of OpenVPN Tunnels and start them cascaded.

e.g The the second tunnel will be established trough the first tunnel and so on.   

Please see this Repo for more [details](https://github.com/ddowse/pf-tunnelactive) in Setup and preperations.


## Build

You will need a FreeBSD build environment. 

```bash:
git clone git@github.com:pfsense/FreeBSD-ports.git pfSense-ports
cd pfSense-ports/security
git clone https://github.com/ddowse/pfSense-pkg-openvpn-multihop
cd pfSense-pkg-openvpn-multihop
make package
```

Please check the [pfSense package development documentation](https://docs.netgate.com/pfsense/en/latest/development/developing-packages.html#testing-building-individual-packages) for more information.


## Installation 

```bash:
pkg add https://github.com/ddowse/pfSense-pkg-openvpn-multihop/releases/download/v1.0/pfSense-pkg-openvpn-multihop-1.0.txz
```

## Preperations (in General)

- **Create Backup of your configuration!**

- Make sure that your OpenVPN Clients connected succesfully to your provider
- Assign the *Network Ports* to an Interfacae under Interfaces > Interface Assignments
- Make sure that NAT is set properly to **Manual Outbound NAT** 
- Make sure that NAT on each VPN Interface is set


e.g:

```bash:
  nat on ovpnc1 inet all -> (ovpnc1) port 1024:65535 round-robin
```

![readme-nat](readme-nat.png)

- Set Interface to *any* in OpenVPN Client Configuration.

## Usage

- If 'keepalive' is checked a background process is started once all tunnels are up. It will check the status of all tunnels one by one every 3 seconds. If any tunnel of the configured tunnels is down, all tunnels brought down and the cascade will be restarted. 

## Create 

- Navigate to VPN > OpenVPN > Client Multihop
- Click the add button 
- Choose 2 OpenVPN Clients from the dropdown menue
- Click save
- Click apply to save and start
- Wait aprox. 30 seconds for page to refresh and checkmarks turned green

## Extend 

- Click the add Button
- Choose OpenVPN Client
- Save and click apply
- Wait aprox. 30 seconds for page to refresh and checkmarks turned green

## Start

- Will start the tunnels one by one. 
- Will start background process if keepalive was checked on creation.

## Stop

- Click stop this will also kill the keepalive process

## Delete

- Will remove all configuration, can't be undone. 


## Trouble shooting

- Click Stop
- Click Start
 

Check routing like this

```bash:
netstat -4nr
```

Check tunnel(s) for passing openvpn traffic
Interface and Port may vary

```bash:
tcpdump -nv -i ovpnc1 port 1149
```

Check your IP.

```bash:
curl ifconfig.co
```

Don't forget to check your logs(!)

## Technical 

- Creating a 2 Tunnel cascade works by adding to the custom-options field of the first tunnel a route-up command
and remove any route exec settings e.g do not set default routing. 

```bash
route-up "/usr/local/etc/openvpn-multihop/addroute.sh 95.211.95.232"
```

The IP is the OpenVPN Server of the next tunnel. 

The script (addroute.sh)  

```bash:
/sbin/route add -host ${1} $route_vpn_gateway 255.255.255.255
```

This will add to the routing table:

```bash:
95.211.95.232/32   10.3.3.2           UGS      ovpnc1
```

Connecting now to the IP 95.211.95.232 will go trough the first tunnel. 

When the tunnel configuration is extended. The steps as before are repeated and route-up and route-exec are changed to the new exit.


## Issues

- Please report any issues via github
- Thorough testing was done with Perfect Privacy VPN *only*

## Acknowledgment

**"John"** had the idea for this package and provided the initial financial support to make it possible. Thanks. 
