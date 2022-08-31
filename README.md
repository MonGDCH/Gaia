# Gaia

命名源于`盖亚奥特曼`的基于`Workerman`的多进程服务管理框架


## docker

```bash

docker run -it -d -p 8080:8080 -v C:\Users\Administrator\Desktop\Gaia:/home/www/gaia --name gaia 8009921198f7

docker exec -it a0bc44b5046a bash

```


## tips

1. config配置和process进程管理因为是在主进程`onWorkerStart`外加载，而`linux`环境使用`posix_kill`进行`Workerman`进程重启是不会重新加载主进程文件的，故如果修改，在`linux`环境下需要重新启动`Workerman`服务