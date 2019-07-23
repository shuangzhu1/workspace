#########################################################################
#########################################################################
#!/bin/bash


while true;do
	server=`ps -aux|grep "/usr/local/php/bin/php -f /var/www/dvalley/scripts/start.php daemon test"|grep -v "grep"`
	server2=`ps -aux|grep "/usr/local/php/bin/php -f /var/www/dvalley/scripts/start.php robot updateToken"|grep -v "grep"`
	if [ ! "$server" ];then
		now=`date '+%Y-%m-%d %H:%M:%S'`
        echo ${now}"已被杀死">>./log/test.log 
		nohup /usr/local/php/bin/php -f /var/www/dvalley/scripts/start.php daemon test &
        sleep 10
	#else
	#	echo "没有被杀死"
	fi
	
	if [ ! "$server2" ];then
		now=`date '+%Y-%m-%d %H:%M:%S'`
        echo ${now}"已被杀死">>./log/test.log 
		nohup /usr/local/php/bin/php -f /var/www/dvalley/scripts/start.php robot updateToken &
        sleep 10
	#else
	#	echo "没有被杀死"
	fi

	sleep 5
done
