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

### 1.2.4 Docker 容器访问宿主机 `127.0.0.1:18789` 的 openclaw agent

当 openclaw agent 只监听在宿主机回环地址（`127.0.0.1:18789`）时，`bridge` 网络中的容器无法直接访问该端口。可使用宿主机端口转发进行适配。

#### 方案：在宿主机使用 `socat` 转发

1. 在宿主机安装并启动 `socat`，将 `0.0.0.0:18790` 转发到 `127.0.0.1:18789`：

```shell
socat TCP-LISTEN:18790,fork,bind=0.0.0.0,reuseaddr TCP:127.0.0.1:18789
```

2. `docker-compose.yml`（本项目已包含）确保应用容器包含如下配置：

```yaml
extra_hosts:
  - 'host.docker.internal:host-gateway'
```

3. 在 Laravel `.env` 中配置 openclaw agent 地址为宿主机转发端口：

```env
OPENCLAW_AGENT_URL=http://host.docker.internal:18790
```

4. 容器内连通性验证：

```shell
curl -v http://host.docker.internal:18790
```

> 说明：不建议直接使用 `network_mode: host` 替代上述方案，会降低容器网络隔离能力。
