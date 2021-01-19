# pfSense-pkg-openvpn-multihop
Provides an easy way to setup multihop OpenVPN Connections. 


This utility will allow you to create a list of OpenVPN Tunnels and start them cascaded.                                                                                                                                                                                                                                                                                    
e.g The the second tunnel will be established trough the first tunnel and so on. 

Please see this Repo for more [details](https://github.com/ddowse/pf-tunnelactive)
                                                                                                                                                                                                                                                                                                                                                                            
																																													    Work in Progress.

# TODO: 
- If OpenVPN Client is in list - remove from Select Menu to avoid double selection
- Add route-up command to custom-options of tunnel. [details](https://github.com/ddowse/pf-tunnelactive#cascading-vpn)
- Add autorestart option

# Nice to have
- Profiles
- Auto add NAT Rules


