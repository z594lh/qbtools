# 使用官方的 Ubuntu 18.04 镜像作为基础镜像
FROM ubuntu:18.04

# 复制您的 PHP 项目文件到工作目录
COPY . .

# 设置环境变量，禁用交互式配置
ENV DEBIAN_FRONTEND=noninteractive

# 更新apt包管理器并安装基本工具
RUN apt-get update && apt-get install -y \
    software-properties-common \
    apt-utils \
    curl \
    wget

# 添加PHP PPA仓库以获取最新的PHP版本
RUN add-apt-repository ppa:ondrej/php

# 安装默认版本的PHP和一些常用扩展
RUN apt-get update && apt-get install -y \
    php \
    php-cli \
    php-fpm \
    php-mysql \
    php-curl \
    php-gd \
    php-json \
    php-opcache \
    php-mbstring \
    php-zip \
    php-xml \
    php-bcmath \
    php-soap


# 安装MySQL Server 5.7和客户端
RUN apt-get install -y mysql-server-5.7 mysql-client

# 安装 Cron 服务
RUN apt-get update && apt-get install -y cron
COPY mycron /etc/cron.d/mycron
RUN chmod 0644 /etc/cron.d/mycron

#安装系统日志
RUN apt-get install -y rsyslog
#安装dos2unix
RUN apt-get install -y dos2unix

RUN dos2unix /etc/cron.d/mycron

# 创建/var/log/cron.log文件
RUN touch /var/log/cron.log
RUN chmod 666 /var/log/cron.log

# 将初始化SQL文件复制到容器中
COPY init-db.sql /tmp/init-db.sql

# 启动MySQL服务器并执行初始化SQL文件
RUN service mysql start && mysql -u root -proot < /tmp/init-db.sql

# 更改MySQL root用户密码为root并刷新权限
RUN service mysql start && mysql -u root -proot -e "ALTER USER 'root'@'%' IDENTIFIED WITH mysql_native_password BY 'root'; FLUSH PRIVILEGES;"

# 复制启动脚本到容器并赋予执行权限
COPY start.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/start.sh

# 暴露PHP-FPM默认端口（9000）和MySQL默认端口（3306）
EXPOSE 9000 3306

# 启动容器时运行start.sh
CMD ["/usr/local/bin/start.sh"]