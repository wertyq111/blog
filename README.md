# 一. 项目介绍

## 1.1 项目名称
个人博客系统（后端）

## 1.2 功能描述


# 二. 系统技术栈

## 1.1 技术栈

| 名称    | 简介                                                         | 文档                  |
| ------- | ------------------------------------------------------------ | --------------------- |
| PHP     | PHP（PHP: Hypertext Preprocessor）是在服务器端执行的脚本语言，尤其适用于 Web 开发。 | https://www.php.net   |
| Laravel | Laravel 是一套简洁、优雅的 PHP Web开发框架。                 | https://laravel.com   |
| MySQL   | MySQL 是最流行的关系型数据库管理系统之一。                   | https://www.mysql.com |
| Redis   | Redis（Remote Dictionary Server )，即远程字典服务，是一个开源的Key-Value数据库。 | https://redis.io      |

## 1.2 安装运行

### 1.2.1 下载项目

```shell
$cd '目录地址'
$git clone git@github.com:wertyq111/blog.git
```

### 1.2.2 配置和安装

```shell
# 1. 先切换到项目根目录
$cd blog
    
# 2. 复制配置环境文件
$cp .env.example .env
    
# 3. 修改配置文件里的配置项，比如 mysql、redis 等具体配置
    
# 4. 修改 setup.sh 脚本文件权限
$chmod 755 setup.sh
    
# 5. 运行安装脚本，并等待安装结束
$./setup.sh 
    
# 6. 安装成功后，运行 sail 启动项目
$./vendor/laravel/sail up
    
# 7. 设置 sail 别名，以避免每次输入路径
$alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'
    
```

### 1.2.3 创建数据表并添加初始数据
```shell
#1. 导入数据表
$./vendor/bin/sail artisan migrate
    
#2. 导入初始数据
$./vendor/bin/sail artisan db:seed
    
#3. 查看数据库
$./vendor/bin/sail artisan db:show
    
```
