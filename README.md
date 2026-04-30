# 个人博客后端

这是当前 blog 工作区的 Laravel 10 API 后端，给 Vue 管理后台、博客内容、小程序内容、会员体系、烟草业务和开发工作台提供接口。

## 当前技术栈

| 项目 | 当前情况 |
| --- | --- |
| PHP | `^8.1` |
| Laravel | `^10.10` |
| 运行方式 | Laravel Sail，入口是 `docker-compose.yml` |
| 应用服务名 | `blog` |
| 数据库 | MySQL 8.0 |
| 缓存 | Redis Alpine |
| 邮件调试 | Mailpit |
| 登录认证 | `php-open-source-saver/jwt-auth` |
| 测试 | PHPUnit 10，入口是 `php artisan test` |
| 资源构建 | Vite 4，仅用于 Laravel `resources/` 资源 |

## 目录结构

```text
blog-dev
├── app/                    Laravel 业务代码
├── config/                 框架和扩展配置
├── database/
│   ├── migrations/         数据库迁移
│   ├── seeders/            Seeder 类
│   └── seeders/sql/        DatabaseSeeder 会导入的 SQL 初始数据
├── public/                 对外静态资源和上传入口
├── resources/              Laravel 默认前端资源
├── routes/                 API 和 Web 路由
├── runtimes/               Sail 镜像文件
├── tests/                  PHPUnit 测试
├── .env.example            本地环境模板
├── artisan                 Laravel CLI 入口
├── composer.json           PHP 依赖和 Composer 脚本
├── docker-compose.yml      Sail 服务：app、MySQL、Redis、Mailpit
└── package.json            Vite 脚本
```

## 主要模块

| 模块 | 主要路径 |
| --- | --- |
| 后台和工作台 | `app/Http/Controllers/Api/Admin`、`app/Services/Api/Admin` |
| 用户和会员 | `app/Http/Controllers/Api/User`、`app/Services/Api/User` |
| 博客内容 | `app/Http/Controllers/Api/Web`、`app/Models/Web` |
| 小程序内容 | `app/Http/Controllers/Api/MiniProgram`、`app/Models/MiniProgram` |
| 烟草业务 | `app/Http/Controllers/Api/Tobacco`、`app/Models/Tobacco` |
| 通用 API 层 | `app/Http/Middleware`、`app/Http/Requests/Api`、`app/Http/Resources` |

主路由入口是 `routes/api.php`。`routes/web.php` 当前只保留 Laravel 默认 Web 入口。

## 端口变量

端口统一从 `.env` 读取，默认值放在 `.env.example`：

| 服务 | 环境变量 |
| --- | --- |
| Laravel 应用 | `APP_PORT` |
| Vite | `VITE_PORT` |
| MySQL | `FORWARD_DB_PORT` |
| Redis | `FORWARD_REDIS_PORT` |
| Mailpit SMTP | `FORWARD_MAILPIT_PORT` |
| Mailpit 面板 | `FORWARD_MAILPIT_DASHBOARD_PORT` |

## 环境变量

先创建本地 `.env`：

```bash
cp .env.example .env
```

如果 Laravel 跑在 Sail 容器里，数据库、Redis、Mailpit 使用 Docker 服务名：

```dotenv
DB_HOST=mysql
DB_PORT=3306
REDIS_HOST=redis
REDIS_PORT=6379
MAIL_HOST=mailpit
MAIL_PORT=1025
```

如果 Laravel 跑在宿主机，只借用 Docker 里的 MySQL、Redis、Mailpit，使用转发端口：

```dotenv
DB_HOST=127.0.0.1
DB_PORT=${FORWARD_DB_PORT}
REDIS_HOST=127.0.0.1
REDIS_PORT=${FORWARD_REDIS_PORT}
MAIL_HOST=127.0.0.1
MAIL_PORT=${FORWARD_MAILPIT_PORT}
```

业务功能按需读取这些配置：

| 功能 | 主要环境变量 |
| --- | --- |
| JWT 登录 | `JWT_SECRET`、`JWT_ALGO`、`JWT_TTL`、`JWT_REFRESH_TTL` |
| 七牛上传 | `QINIU_ACCESS_KEY`、`QINIU_SECRET_KEY`、`QINIU_BUCKET`、`QINIU_DOMAIN` |
| 高德天气 | `WEATHER_AMAP_KEY` |
| 微信小程序 | `WECHAT_MINI_APP_APPID`、`WECHAT_MINI_APP_SECRET` |
| 短信 | `SMS_ALIYUN_ACCESS_KEY_ID`、`SMS_ALIYUN_ACCESS_KEY_SECRET`、`SMS_ALIYUN_TEMPLATE_REGISTER` |
| 客户端 IP 映射 | `CLIENT_IP_OVERRIDE_SOURCE`、`CLIENT_IP_OVERRIDE_TARGET` |
| 工作日报 AI 汇总 | `OPENCLAW_GATEWAY_URL`、`OPENCLAW_GATEWAY_TOKEN`、`OPENCLAW_MODEL`、`OPENCLAW_REPORT_MODELS`、`OPENCLAW_BAILIAN_API_KEY` |

## 安装依赖

优先用 Sail 的 Composer 镜像安装 PHP 依赖：

```bash
docker run --rm \
  -v "$(pwd)":/opt \
  -w /opt \
  laravelsail/php81-composer:latest \
  composer install --ignore-platform-reqs
```

如果本机 PHP 和 Composer 版本已满足要求，也可以直接运行：

```bash
composer install
```

只有需要构建 Laravel `resources/` 资源时，才需要安装 Node 依赖：

```bash
npm install
```

## 启动

在 `blog-dev` 目录启动后端服务：

```bash
./vendor/bin/sail up -d
```

首次初始化：

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

默认访问地址：

| 目标 | 地址 |
| --- | --- |
| API 前缀 | `http://localhost:${APP_PORT}/api` |
| Mailpit 面板 | `http://localhost:${FORWARD_MAILPIT_DASHBOARD_PORT}` |

## 初始化数据

`DatabaseSeeder` 先导入 SQL，再执行菜单 Seeder：

1. `database/seeders/sql/users.sql`
2. `database/seeders/sql/web_info.sql`
3. `database/seeders/sql/im_chat_groups.sql`
4. `database/seeders/sql/im_chat_group_users.sql`
5. `database/seeders/sql/cities.sql`
6. `database/seeders/sql/level.sql`
7. `database/seeders/sql/member_level.sql`
8. `database/seeders/sql/positions.sql`
9. `database/seeders/sql/roles.sql`
10. `database/seeders/sql/user_role.sql`
11. `database/seeders/sql/configs.sql`
12. `MenuSeeder`
13. `RoleMenuSeeder`

新库初始化直接执行：

```bash
./vendor/bin/sail artisan migrate --seed
```

## 常用命令

```bash
# 容器
./vendor/bin/sail up -d
./vendor/bin/sail down
./vendor/bin/sail shell

# Laravel
./vendor/bin/sail artisan route:list
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
./vendor/bin/sail artisan test

# 格式化
./vendor/bin/sail pint

# Laravel resources
npm run dev
npm run build
```

## 测试

后端测试入口：

```bash
./vendor/bin/sail artisan test
```

当前测试目录：

| 路径 | 内容 |
| --- | --- |
| `tests/Unit` | Filter、MenuService、迁移辅助逻辑等单元测试 |
| `tests/Feature` | HTTP 功能测试 |
