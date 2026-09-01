# 后端代码规范总览（blog-dev / Laravel 10）

本文件是后端的**规范入口**，规定分层、命名、路由、模型、注释这些「写在哪、叫什么」的问题。
Controller / Request / Service 内部的写法细则见 [backend-controller-style.md](./backend-controller-style.md)，两份文件不重复，冲突时以 `backend-controller-style.md` 为准。

写新代码前先读这两份；老代码不符合的地方不要就地模仿，按本文件写。

---

## 一、分层与目录

按**业务域**分包，五层在各自根目录下保持同名的平行结构：

```
app/Http/Controllers/Api/<域>/XxxController.php
app/Http/Requests/Api/<域>/XxxRequest.php
app/Http/Resources/<域>/XxxResource.php        （按需，多数走 BaseResource）
app/Services/Api/<域>/XxxService.php
app/Models/<域>/Xxx.php
```

现有业务域固定为：

| 域 | 含义 |
|---|---|
| `Admin` | 后台工作台（工作日常、工作文档、待办、番茄钟、平台脚本、服务器路径等） |
| `Web` | 博客前台（文章、分类、标签、评论、微言） |
| `User` | 账号与会员（用户、会员、会员等级、头像） |
| `Permission` | 菜单与角色（仅 Models 层用此名，Controller/Request 归在 `Admin`） |
| `MiniProgram` | 小程序（相册、壁纸、素材、房源） |
| `Tobacco` | 烟草业务（历史模块，只维护不扩张） |

规则：

- **新业务一律落在已有域内**，不要为一个功能新开一个域目录。确实是新领域时，五层目录一次性建齐同名包。
- Service 复杂到需要拆分时，建**同名子目录**收纳，不要平铺：
  `Services/Api/Admin/ProductImage/{ProductImageExtractorService, Adapters/, Contracts/}`。
  参考现有 `PlatformScript/`、`ProductImage/`、`Dashboard/`。
- `Controller` 一律继承 `App\Http\Controllers\Api\Controller`（它提供 `resource()`、`queryBuilder()`、`PER_PAGE`），不要直接继承框架 `Controller` 或 `BaseController`。
- Model 一律继承 `App\Models\BaseModel`（提供软删除、时间格式、`edit()`、`getRequestFilters()`）。

---

## 二、命名

| 对象 | 规则 | 示例 |
|---|---|---|
| Controller 类 | 单数业务名 + `Controller` | `ServerPathController`、`TodoItemController` |
| Request 类 | 单数业务名 + `Request`，一个模块一个 | `ServerPathRequest` |
| Service 类 | 单数业务名 + `Service` | `ServerPathService` |
| Model 类 | 单数、与表名对应的大驼峰 | `ServerPath` → `server_paths` |
| 数据表 | 复数下划线 | `todo_items`、`work_daily_logs` |
| 字段 | 下划线小写 | `platform_id`、`due_date` |
| 方法 | 小驼峰 | `batchDelete`、`normalizePayload` |
| 私有归一化方法 | 动词开头、表达真实意图 | `normalizePayload()`，不叫 `handleData()` |

历史遗留的复数 Controller 名（`ArticlesController`、`UsersController`、`MembersController`）不再新增，改动时也不改名（会连带改路由和前端）。

### CRUD 方法名

固定为 `index` / `info` / `add` / `edit` / `delete` / `batchDelete`，**不用 Laravel 的 `store` / `update` / `destroy` / `show`**（少数历史文件仍是 RESTful 名，不要照抄）。

`list` 是允许的第七个方法，但**只用于「不分页的全量下拉数据源」**（如编辑表单里的分类下拉）。它和 `index` 的边界是死的：

- `index`：分页 + 筛选 + 排序，给列表页
- `list`：不分页、只返回 `id` 和展示字段，给下拉框

不满足这个定义的列表接口不要叫 `list`，按业务动作起名。

---

## 三、路由（routes/api.php）

URL 用 **kebab-case 资源名**，路由名用 `资源名.动作`，动作名与 Controller 方法名一致。

**同一个 Controller 有 3 条以上路由时，一律用 `Route::controller()` 分组**，不要平铺。样板（`server-path`）：

```php
Route::controller(ServerPathController::class)
    ->prefix('server-path')
    ->name('server-path.')
    ->group(function () {
        // 服务器路径列表
        Route::get('index', 'index')->name('index')
            ->middleware('filter.process:' . ServerPath::class);
        // 添加服务器路径
        Route::post('add', 'add')->name('add');
        // 批量删除
        Route::post('delete', 'batchDelete')->name('batch-delete');
        // 状态切换
        Route::post('status/{serverPath}', 'status')->name('status');

        // {serverPath} 通配必须排在具体路径之后
        Route::get('{serverPath}', 'info')->name('info');
        Route::post('{serverPath}', 'edit')->name('edit');
        Route::delete('{serverPath}', 'delete')->name('delete');
    });
```

分组写法消掉了每行重复的 `[XxxController::class, ...]` 和 name 前缀，更重要的是**把「通配路由排最后」这个约束变成了代码里的物理位置**，写的人一眼看见，不用记规范。

少于 3 条路由的 Controller 保持平铺，分组反而更啰嗦。

要点：

- **`{model}` 占位符不能省**。项目大量依赖隐式模型绑定，且 `LabelRequest` / `MenuRequest` / `RoleRequest` / `UserRequest` 靠 `$this->route('label')` 取路由参数做唯一性校验，省掉占位符会同时废掉这两样。
- **列表固定挂 `filter.process:<Model>::class` 中间件**，把前端筛选参数转成 `filter[...]`；Controller 里不要手写筛选解析。
- **`{model}` 通配路由必须写在具体路径之后**。`add` 要排在 `{serverPath}` 前面，否则 `add` 会被当成模型 ID。同方法下才会冲突，不同 HTTP 方法之间不受影响。
- **改路由后用 `php artisan route:list --json` 比对改动前后的快照**（uri / name / action / middleware 四列），确认没有意外增删。注册顺序不在 `route:list` 的输出里，需要顺序验证时直接遍历 `app('router')->getRoutes()`。
- **批量删除统一 `POST <资源>/delete` → `batchDelete`，路由名 `<资源>.batch-delete`**。不要再用 `batchDelete` 作为 URL 段。
- 单条状态切换统一 `POST <资源>/status/{model}`。
- 非 CRUD 的业务动作用 `POST/GET <资源>/<动作>[/{model}]`，动作名 kebab-case，路由名同名 kebab-case（如 `work-daily/report/export` → `work-daily.report-export`）。
- 不要新增挂在 `index/` 前缀下的杂项路由（`index/getMenuList` 这类是历史遗留）。

---

## 四、Model

```php
class ServerPath extends BaseModel
{
    use HasFactory;

    protected $table = 'server_paths';       // 表名不能由类名直接推出时才写

    protected $fillable = ['code', 'name', 'url', 'target', 'sources', 'sort'];

    /**
     * 过滤参数配置
     *
     * @var array[]
     */
    protected $requestFilters = [
        'code' => ['column' => 'code'],
        'name' => ['column' => 'name'],
        'status' => ['column' => 'status', 'filterType' => 'exact'],
    ];

    public function platform()
    {
        return $this->belongsTo(WorkPlatform::class, 'platform_id');
    }
}
```

- `$fillable` **必填**，不使用 `$guarded = []`。
- `$requestFilters` 是列表筛选的唯一声明处，供 `filter.process` 中间件和 `generateAllowedFilters()` 使用。模糊匹配不写 `filterType`，精确匹配写 `'filterType' => 'exact'`。
- 保存统一走 `$model->fill($data); $model->edit();`（`BaseModel::edit()` 负责写入操作人和时间），不要直接 `save()` 或 `Model::create()`。
- 关系方法放在属性之后、访问器之前；关系方法名用小驼峰单数/复数区分一对一和一对多。
- 访问器/修改器（`getXxxAttribute`）只做**格式转换**（JSON 解码、逗号串转数组），不写业务判断。
- **不要在 Model 里 import 用不到的类**。复制样板文件后先清 `use`。

---

## 五、Resource 与返回

- 绝大多数接口直接用父类 `$this->resource($model)` / `$this->resource($paginator, ['time' => true, 'collection' => true])`，它反射调用 `BaseResource`，不需要为每个模型建 Resource 类。
- 只有**字段结构和模型差异大、且要被多个接口复用**时才建 `Http/Resources/<域>/XxxResource.php`。为单个接口裁字段不建 Resource。
- 动作型接口成功返回 `response()->json([])`。
- 其余返回与异常约定见 `backend-controller-style.md#返回与异常`。

---

## 六、异常

- 业务异常继承 `App\Exceptions\BaseException`；模块专属异常放同目录并以模块名开头（`ProductImageExtractorException`）。
- 参数问题交给 Request 返回 422，不在 Controller 里 `try/catch` 后返回成功。
- Service 不吞异常，数据不合法就抛。

---

## 七、注释

每个 `public function` 都要有 PHPDoc，这是目前落实得最好的一条约定（265/273），新代码必须保持。

```php
/**
 * 服务器路径列表 - 分页
 *
 * @param ServerPathRequest $request
 * @param ServerPath $serverPath
 * @return BaseResource
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/5/26
 */
```

- 首行一句中文说明，空一行后写标签。
- `@author` 统一 `zhouxufeng <zxf@netsun.com>`。**AI 生成的代码同样署这个作者**，不要写 `@author Codex` 之类。
- `@date` 统一 `Y/n/j`（如 `2026/5/26`），不带时间、不用 `-` 分隔、`@date` 后不加冒号。
- 改方法签名必须同步改 `@param`/`@return`/`@date`。
- 类头注释只在基类/抽象类等需要说明设计意图时写，普通业务类不强制。

---

## 八、测试

见 `backend-controller-style.md#测试`。补充两条目录约定：

- Feature 测试路径镜像 Controller：`tests/Feature/Api/<域>/XxxControllerTest.php`。
- Unit 测试路径镜像被测类：`tests/Unit/Services/Api/<域>/XxxServiceTest.php`。
- 一律 Pest 格式，**不要新增 `extends TestCase` 的 PHPUnit 类**。
- 测试在远端跑：`./vendor/bin/sail pest [路径]`。

---

## 九、新模块落地清单

| 步 | 动作 |
|---|---|
| 1 | 确定业务域，在 Models / Requests / Controllers / Services 建同名包 |
| 2 | 迁移 + Model（`$fillable`、`$requestFilters`、关系） |
| 3 | Request（业务字段规则；分页字段复用公共 `FormRequest`） |
| 4 | Controller（`index/info/add/edit/delete/batchDelete`，构造函数注入 Service） |
| 5 | 路由按第三节七行模板补齐，列表挂 `filter.process` |
| 6 | 每个 public 方法补 PHPDoc |
| 7 | 补 Pest Feature 测试，同步远端 `sail pest` 跑通 |
