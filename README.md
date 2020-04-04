[![Download nuclewall](https://img.shields.io/sourceforge/dt/nuclewall.svg)](https://sourceforge.net/projects/nuclewall/files/latest/download)
# About

- An experimental FreeBSD distribution developed for learning.
- It's a one-person project and NOT commercial.
- Forked from pfSense 2.0.
- It is open-sourced to let others curious about Unix systems, open-source and free software world can make use of it.
- It is intended to be Turkish as possible except its name.
- Advanced firewall features of pfSense were removed. It is intended as a HOTSPOT software that can be run by small enterprises and is compatible with the law of Turkey internet.
- No new features will be released. The project will continue with minor changes and bug fixes.


# Some technical changes

- Reviewed the most of functions and algorithms, made more robust and efficient.
- Implemented IP-MAC access logging compatible with the law of Turkey internet (5651).
- Developed an OpenSSL based Timestamp Authority to sign IP-MAC Address logs, compatible with the law of Turkey internet. (5651)
- Developed a samba server interface that enables sending access logs to Windows machines over SMB protocol.
- Restructured HOTSPOT user management with FreeRADIUS and MySQL to implement Authentication, Authorization and Accounting protocols seamlessly.
- Added MySQL, PostgreSQL and SQL Server clients, and developed web interface for connection management.
  HOTSPOT users can be an external data source, generally a hotel software.
- Redesigned the HOTSPOT user welcome page, added support for mobile and i18n.
- Redesigned the Web gui with jQuery and Bootstrap.
- Redesigned the command line interface.
- Removed embedded(NanoBSD) and VPN features.
