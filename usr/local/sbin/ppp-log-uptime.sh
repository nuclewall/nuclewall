#!/bin/sh

/bin/echo `date -j +%Y.%m.%d-%H:%M:%S` $1 >> /conf/$2.log
