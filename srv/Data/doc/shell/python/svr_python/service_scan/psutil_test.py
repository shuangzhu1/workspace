# -*- coding: utf-8 -*-
# python2.7x
# author: orangleliu@gmail.com 2014-12-12
# psutiltest.py
'''''
照着教程简单学习下psutil的使用，windows下试试
'''
import psutil
import datetime

# 查看cpu的信息
print("CPU 个数 %s" % psutil.cpu_count())
print("物理CPU个数 %s" % psutil.cpu_count(logical=False))
print("CPU uptimes")

print(psutil.cpu_times())
print("")

# 查看内存信息
print("系统总内存 %s M" % (psutil.virtual_memory().total / 1024 / 1024))
print("系统可用内存 %s M" % (psutil.virtual_memory().available / 1024 / 1024))
mem_rate = int(psutil.virtual_memory().available) / float(psutil.virtual_memory().total)
print("系统内存使用率 %s %%" % int(mem_rate * 100))

# 系统启动时间
print("系统启动时间 %s" % datetime.datetime.fromtimestamp(psutil.boot_time()).strftime("%Y-%m-%d %H:%M:%S"))

# 系统用户
users_count = len(psutil.users())
users_list = ",".join([u.name for u in psutil.users()])
print("当前有%s个用户，分别是%s" % (users_count, users_list))

# 网卡，可以得到网卡属性，连接数，当前流量等信息
net = psutil.net_io_counters()
bytes_sent = '{0:.2f} M'.format(net.bytes_recv / 1024/1024)
bytes_rcvd = '{0:.2f} M'.format(net.bytes_sent / 1024/1024)
print("网卡接收流量 %s 网卡发送流量 %s" % (bytes_rcvd, bytes_sent))

# 进程  进程的各种详细参数
# 磁盘 磁盘的使用量等等
