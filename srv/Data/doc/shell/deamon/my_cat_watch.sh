#########################################################################
# File Name: my_cat_watch.sh
# Author: ma6174
# mail: ma6174@163.com
# Created Time: Fri 23 Mar 2018 02:45:21 PM CST
#########################################################################=
#启动 nohup ./my_cat_watch.sh >log/my_cat.log 2>&1 &
#!/bin/bash
  
# 监听的端口地址
port=8866
pid_file='/data/mycat-1.6.5/logs/mycat.pid'
bin_path='/data/mycat-1.6.5/bin/mycat'
while true;do
        server=`netstat -ntpl|grep $port`
        #端口没在运行 说明mycat服务挂了#
        if [ ! -n "$server" ] ; then
                  # 监听的 pid文件是否存在
                 if [ -f "$pid_file" ];then
                    pid=`cat $pid_file`
                    echo "开始杀死进程${$pid}"
                    echo "运行命令:kill -7 ${pid}"
                    kill_pid=`kill -7 $pid`

                    echo  "返回结果:"$kill_pid

                  else

                    pid_preg=`ps -aux|grep "[m]ycat"|awk '{print $2}'|xargs kill -s 9`

                 fi

                 echo "开始重启mycat服务"
                 echo "运行命令:${bin_path} start"
                 start_server=`$bin_path start`
                 echo "返回结果"$start_server

                 #发送短信#
                 echo "发送短信"

				 php -f /var/www/dvalley/scripts/start.php server abnormal mycat服务已挂,现在正在重启

                 sleep 10
        else
         # echo "${port}端口正在运行"
          sleep 10
        fi
done
