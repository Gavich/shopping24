#!/bin/bash

DIR="$(dirname $0)"
TO_MOUNT="$DIR/var/tcimport/images/"
sshfs root@91.222.36.20:/home/6ect/data/www/6ect.com/otto_parser/img/  $TO_MOUNT -o reconnect

