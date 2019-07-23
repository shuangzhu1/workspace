#########################################################################
# File Name: backup.sh
# Author: ma6174
# mail: ma6174@163.com
# Created Time: Fri 02 Mar 2018 02:07:34 PM CST
#########################################################################
#!/bin/bash
# This is a mysql datbase backup shell script.

# set mysql info
hostname="localhost"
user="root"
password="www.hn78.com"

# set database info
database="dvalley db2 db3"
bakpath="/data/shell/mysql/backup"
date=$(date +%Y-%m-%d)

# backup
#mkdir -p $bakpath
mysqldump -h${hostname} -u${user} -p${password} --databases ${database} | gzip > ${bakpath}/dvalley_${date}_sql.gz
#仅备份数据库结构
#  mysqldump -no-data -databases databasename1 databasename2 databasename3 > /path to backup/bakname.sql
#备份所有数据库
#  mysqldump -all-databases > /path to backup/bakname.sql
#备份数据库一些表
#  mysqldump -hhostname -uusername -pmypwd databasename table1 table2 table3 > /path to backup/bakname.sql
#备份一个数据库
# 无压缩 mysqldump -hhostname -uusername -pmypwd databasename > /path to backup/bakname.sql
# 有压缩 mysqldump -hhostname -uusername -pmypwd databasename ｜ gzip > /path to backup/bakname.sql.gz

#迁移到新服务器
# mysqldump -hhostname -uuser -pmypwd databasename | mysql -hnew_hostname -C databasename

#数据库还原
 #还原无压缩数据库
 # mysql －hhostname -uuser -pmypwd databasename < /path to backup/bakname.sql
 #还原压缩数据库
 # gunzip < /path to backup/bakname.sql.gz | mysql -hhostname -uusername -pmypwd databasename

