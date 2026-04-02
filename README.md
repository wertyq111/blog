# Blog Backend

## 项目概览

当前工作区中的后端是一个基于 Laravel 10 的 API 服务，主要为后台管理端、博客内容、小程序内容和若干业务模块提供接口。

- 主要接口入口在 `routes/api.php`
- `routes/web.php` 当前只保留默认欢迎页
- 业务代码集中在 `app/Http/Controllers/Api`、`app/Services/Api`、`app/Models`
- 初始化数据由 `database/seeders/sql/*.sql` 和 Seeder 类共同完成

当前代码里可以明显看到的模块分层：

- `Admin`：后台管理相关接口
- `User`：登录、用户、会员等接口
- `MiniProgram`：小程序内容相关接口
- `Web`：博客站点内容相关接口
- `Tobacco`：烟草业务相关接口

## 技术栈

| 名称 | 当前情况 |
| --- | --- |
| PHP | `composer.json` 要求 `^8.1` |
| Laravel | `^10.10` |
| MySQL | `docker-compose.yml` 中使用 `mysql/mysql-server:8.0` |
| Redis | `docker-compose.yml` 中使用 `redis:alpine` |
| Mailpit | 本地开发邮件捕获 |
| Vite | 用于 `resources/` 下前端资源构建 |
| PHPUnit | 单元测试与功能测试 |

## 当前目录说明

```text
blog-dev
├── app/                    Laravel 业务代码
├── config/                 框架配置
├── database/
│   ├── migrations/         数据库迁移
│   ├── seeders/            Seeder 与 SQL 初始化数据
│   └── seeders/sql/        初始数据 SQL 文件
├── public/                 对外静态资源与上传目录
├── resources/              Laravel 默认前端资源
├── routes/                 路由定义
├── runtimes/               Docker 运行时构建文件
├── tests/                  PHPUnit 测试
├── artisan                 Laravel CLI 入口
├── composer.json           PHP 依赖定义
├── docker-compose.yml      当前工作区使用的本地容器编排
└── package.json            Vite 资源构建脚本
```

## 快速开始

### 1. 环境准备

推荐按当前工作区的主流程，用 Docker + Sail 启动：

- Docker Engine / Docker Desktop
- Docker Compose v2
- 可选：本机安装 PHP 8.1+、Composer、Node.js

### 2. 配置环境变量

```bash
cp .env.example .env
```

至少先检查这些配置项：

- `APP_URL`
- `APP_PORT`
- `DB_*`
- `REDIS_*`
- `MAIL_*`

`.env.example` 当前默认暴露的开发端口是：

- 应用：`3925`
- Vite：`5174`
- MySQL：`3307`
- Redis：`6380`
- Mailpit SMTP：`1026`
- Mailpit 面板：`8026`

### 3. 安装依赖

如果本机已安装 Composer：

```bash
composer install
```

如果希望直接使用 Sail 的 Composer 镜像：

```bash
docker run --rm \
  -v "$(pwd)":/opt \
  -w /opt \
  laravelsail/php81-composer:latest \
  composer install --ignore-platform-reqs
```

前端资源依赖按需安装：

```bash
npm install
```

### 4. 启动服务

```bash
./vendor/bin/sail up -d
```

首次启动后建议执行：

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

如果你是在容器内运行 Laravel 应用，数据库和缓存服务通常应指向 compose 中的服务名：

- `DB_HOST=mysql`
- `REDIS_HOST=redis`
- `MAIL_HOST=mailpit`

如果你是在宿主机直接运行 Laravel，再使用 compose 暴露出的端口，则通常使用：

- `DB_HOST=127.0.0.1`
- `DB_PORT=3307`
- `REDIS_HOST=127.0.0.1`
- `REDIS_PORT=6380`

## 常用命令

```bash
# 启动 / 停止
./vendor/bin/sail up -d
./vendor/bin/sail down

# 进入容器
./vendor/bin/sail shell

# Artisan
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
./vendor/bin/sail artisan test

# 资源构建
npm run dev
npm run build
```

## 初始化数据

`DatabaseSeeder` 当前会导入这些初始数据来源：

- `database/seeders/sql/*.sql`
- `MenuSeeder`
- `RoleMenuSeeder`

因此首次初始化数据库时，优先使用：

```bash
./vendor/bin/sail artisan migrate --seed
```

## 测试

当前测试入口为 PHPUnit：

```bash
./vendor/bin/sail artisan test
```

测试目录：

- `tests/Feature`
- `tests/Unit`

## OpenClaw 网关说明

当前代码中的 `WorkDailyLogController` 会读取这些环境变量来调用 OpenClaw / AI 汇总能力：

- `OPENCLAW_GATEWAY_URL`
- `OPENCLAW_GATEWAY_TOKEN`
- `OPENCLAW_MODEL`
- `OPENCLAW_REPORT_MODELS`

如果容器需要访问宿主机回环地址上的网关端口，可以在宿主机做端口转发，再把 Laravel 配置指向 `host.docker.internal`。这部分只在你需要工作日报 AI 摘要能力时才需要配置。
