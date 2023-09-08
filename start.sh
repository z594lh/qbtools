#!/bin/bash

#启动rsyslog
service rsyslog start

# 检查 MySQL 服务是否已经运行
if ! pgrep mysqld; then
    # 如果 MySQL 服务未运行，启动它
    export HOME=/root
    service stop start
    service mysql start
fi

#启动cron
service cron start


# 永远不要退出
tail -f /var/log/cron.log /var/log/syslog
