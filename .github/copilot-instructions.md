## 概要

这是一个基于 Laravel 的个人博客后端仓库（PHP + MySQL + Redis）。前端使用 Vite/Node；仓库也包含 Docker / Sail 配置用于本地开发。

## 快速上手（常用命令）

- 克隆仓库并进入目录：

  ```bash
  git clone <repo> && cd blog
  ```

- 复制 env 并运行安装脚本：

  ```bash
  cp .env.example .env
  chmod +x setup.sh
  ./setup.sh
  ```

- 使用 Sail（或本机 PHP）运行迁移、填充与测试：

  ```bash
  ./vendor/bin/sail up -d
  ./vendor/bin/sail artisan migrate --seed
  ./vendor/bin/sail artisan test
  ```

- Node 前端（开发/构建）：

  ```bash
  npm install
  npm run dev   # 开发
  npm run build # 生产构建
  ```

## 常用检查

- 运行测试：`./vendor/bin/sail artisan test` 或 `vendor/bin/phpunit`
- 查看日志：`storage/logs/laravel.log`

## 代码约定与结构

- 遵循 Laravel / PSR-12 代码风格。目录要点：
  - `app/Models`：Eloquent 模型
  - `app/Http/Controllers`：控制器
  - `routes`：路由定义（`web.php`、`api.php`）
  - `database/migrations`：迁移文件
  - `resources/views` / `resources/js`：前端资源

## 典型任务说明（快速提示）

- 如果需要新增 API：在 `routes/api.php` 添加路由 → 新建 Controller 方法 → 在 `app/Http/Requests` 添加验证请求类（如需要） → 写 Resource/Transformer → 添加测试。
- 数据库变更：先写迁移文件并在本地通过 `artisan migrate` 验证，再提交。

## 给 Copilot / Agent 的示例 Prompts

- "在 `app/Http/Controllers` 新增一个用于文章搜索的 API，支持标题和标签过滤并分页；请包含请求验证和测试示例。"
- "解释为什么 `tests/Feature` 下的某个用例在 CI 中失败，并提供修复建议。"
- "将 `User` 模型的邮箱验证改为使用 `unique:users,email,{{id}}` 的更新规则，并更新相关测试。"

## 推荐的 Agent 自定义（后续可创建）

- `agent:run-tests`：一键运行后端测试并返回失败日志片段。
- `agent:add-endpoint`：交互式生成 API 路由/控制器/请求类/测试的骨架。

## 贡献与 PR 要点

- 提交分支命名请使用 `feat/`、`fix/`、`chore/` 前缀。
- PR 描述请包含：变更目的、影响范围、如何在本地验证。

---

若需我基于本文件创建专门的 `AGENTS.md` 或生成 `agent` 模板（如 `agent:run-tests`），我可以继续创建对应文件和简单实现脚本。
