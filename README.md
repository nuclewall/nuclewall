# About

- An experimental FreeBSD distribution developed for learning.
- It’s a one-person project and NOT commercial.
- Forked from pfSense 2.0.
- It is open-sourced to let others curious about Unix systems, open-source and free software world can make use of it.
- It is intended to be Turkish as possible except its name.
- Advanced firewall features of pfSense were removed. It is intended as a HOTSPOT software that can be run by small enterprises and is compatible with the law of Turkey internet.
- No new features will be released. The project will continue with minor changes and bug fixes.


# Some technical changes

- Most of functions and algorithms are reviewed and made more robust and efficient.
- IP-MAC access logging compatible with the law of Turkey internet (5651) is implemented.
- An OpenSSL based Timestamp Authority is developed to sign IP-MAC Address logs, compatible with the law of Turkey internet. (5651)
- A samba server interface is developed that enables sending access logs to Windows machines over SMB protocol.
- HOTSPOT user management is restructured with FreeRADIUS and MySQL to implement Authentication, Authorization and Accounting protocols seamlessly.
- MySQL, PostgreSQL and SQL Server clients are supported. HOTSPOT users can be an external data source, generally a hotel software.
- HOTSPOT user welcome page is redesigned, mobile page and i8n are supported.
- Squid and SquidGuard that enables data saving and content filtering features are supported.
- Web gui is redesigned with jQuery and Bootstrap.
- Command line interface is redesigned.
