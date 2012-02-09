#!/bin/bash

#Hack to parse rabbitmq pid and place it into /var/run directory so Monit can 
#monitor it.
rabbitmqstatus=`/etc/init.d/rabbitmq-server status | grep "\[{pid"`
rabbitmqpid=`echo $rabbitmqstatus | sed "s/.*,\(.*\)\}.*/\1/"`
echo "RabbitMQ PID: $rabbitmqpid"
echo "$rabbitmqpid" > /var/run/rabbitmq.pid
