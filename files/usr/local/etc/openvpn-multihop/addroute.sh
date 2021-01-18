#!/bin/sh
/sbin/route add -host ${1} $route_vpn_gateway 255.255.255.255
