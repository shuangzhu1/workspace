#########################################################################
# File Name: backup.sh
# Author: ykuang
# mail: ma6174@163.com
# Created Time: Fri 02 Mar 2018 02:07:34 PM CST
#########################################################################
#!/bin/bash
# This is a mysql datbase backup shell script.

# set mysql info
if [ ! -n "$1" ];then
	echo "请输入文件绝对路径!"
	exit;	
fi 

bakpath=$1
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

#无压缩备份恢复
#mysql －h$hostname -u$user -p$password $database < $bakpath/$database_$date_sql.gz
#有压缩备份恢复
result=$(gunzip < ${bakpath} | /usr/local/mysql/bin/mysql  -h${host} -u${user} -p${password})
echo $result
