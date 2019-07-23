#########################################################################
# File Name: test.sh
# Author: ykuang
# mail: ma6174@163.com
# Created Time: Fri 09 Mar 2018 11:49:21 AM CST
#########################################################################
#!/bin/bash

#配置文件路径
config_path='/data/shell/mysql/config/main.ini'

#读取配置文件方法

function __readIni(){
    INIFILE=$1; SECTION=$2; ITEM=$3
	_readIni=$(awk -F '=' '/\['$SECTION'\]/{ a=1}a==1&&$1~/'${ITEM}'/{print $2;exit}' $INIFILE)
	echo $_readIni
}

#主机地址
host=($(__readIni ${config_path} db host))
#用户名
user=($(__readIni ${config_path} db user))
#密码
password=($(__readIni ${config_path} db password))
#需要备份的数据库
dbs=($(__readIni ${config_path} db databases))
dbs_arr=${#dbs[@]}

#文件名字前缀
prefix=''

if [ $dbs_arr > 1 ]; then
   databases=${dbs[0]}
   prefix=${dbs[0]}
   var=1
   while [ "$var" -le $dbs_arr ]
   do
	   databases=${databases}" "${dbs[$var]}
	   prefix=${prefix}"_"${dbs[$var]}
	   var=$(($var + 1))
   done
else 
   databases=$dbs
   prefix=$dbs
fi

#备份日期
date=$(date +%Y-%m-%d_%H_%M_%S)
#备份路径
bakpath="/data/shell/mysql/backup"

# backup
#mkdir -p $bakpath
/usr/local/mysql/bin/mysqldump  -h${host} -u${user} -p${password} --databases ${databases} | gzip > ${bakpath}/${prefix}${date}_sql.gz
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

