#!/bin/sh

export DISPLAY=:0

process=wnw_engine
makerun="/mnt/sda1/wnw/wnw_engine.py"

if ps | grep -v grep | grep $process > /dev/null
	then
		exit
	else
		$makerun &
	fi
exit
