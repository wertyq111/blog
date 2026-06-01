# 后端 Controller 代码风格规范

## 目标

Controller 只负责接 HTTP 请求、调用业务对象、返回响应。参数校验放 Request，业务规则放 Service 或 Model，数据转换放明确的私有方法或专门对象里。

## Controller

- 后台 CRUD 方法统一使用 `index`、`info`、`add`、`edit`、`delete`，兼容批量删除时使用 `batchDelete`。
- 依赖用构造函数注入，不在 Controller 里 `new Service()`。
- `index` 查询不要为了统一强行塞进父类 `queryBuilder()`。简单列表可以用公共查询封装；复杂列表直接显式使用 `QueryBuilder::for()` 组装查询，最后仍统一用 `$this->resource($items, ['time' => true, 'collection' => true])` 返回。
- 简单列表指：只需要允许过滤、固定排序、分页，没有额外关联、全文搜索、日期区间、权限过滤或复杂排序。
- 复杂列表指：需要 `with()`、全文搜索、日期范围、登录人权限条件、多级业务排序、特殊 Request 参数转换等。复杂列表必须把查询条件明写在 Controller 或下沉到明确的查询对象/Service，不要把业务逻辑藏进 `$config` 数组。
- `index` 必须先看前端真实传参。当前 Vue3 常见形态是 `pageNum/pageSize` 在 API 层转成 `page/per_page`，筛选字段可能直接传 `name/code`；Vue2 表格也可能传 `page/limit` 或直接业务字段。后端 Request 要接住这些入口，再交给 `filter.process` 转成 `filter`。
- 分页条数统一用业务 Request 继承的 `$request->perPage()`，不要在 Controller 里重复判断 `per_page/limit/pageSize`。
- 单条详情和新增编辑成功统一用 `$this->resource($model)`。
- 删除成功返回 `response()->json([])`。
- Controller 不写兜底默认值。缺字段、类型错、格式错交给 Request 或底层异常尽早暴露。
- 如果数据库字段非 nullable，Request 必须要求前端传有效值；不要在 Controller 里把空字符串或缺失字段补成默认值。
- Controller 可以保留很小的输入归一化方法，例如把数组编码成 JSON，但方法名必须表达真实意图。
- 新增或修改 function 必须同步更新方法注释。注释结构参考旧体系 `controller-request-alignment-standard`：一句中文说明，随后写清 `@param`、`@return`、`@author`、`@date`。参数类型、返回类型和日期必须和当前代码一致。

## Request

- 每个业务模块优先使用独立 Request，例如 `ServerPathRequest`。
- Request 负责字段存在性、类型、长度、数组结构校验。
- 分页字段属于公共字段，统一从公共 `FormRequest` 的分页规则复用，不要在业务 Request 里重复写 `page/per_page/pageSize/limit`。
- 同一个 Request 可按路由 action 分发规则，但规则必须清楚，不要把无关接口揉在一起。
- 字段名转换继续使用项目现有 `getSnakeRequest()`，不要在 Controller 里手写大小写映射。
- Vue3 表单常见小驼峰字段会由公共 `FormRequest::prepareForValidation()` 补齐下划线字段；业务 Request 只写数据库/模型一致的下划线规则。
- 批量 ID 解析统一使用公共 `FormRequest::integerIds()`，不要在每个 Controller 里重复写数组过滤。

## Service

- Service 只处理业务算法和跨模型业务流程。
- Service 不吞异常。数据坏了就直接暴露，避免返回空数组造成误判。
- 变量名不能复用不同含义，比如模型变量和转换结果不能都叫 `$serverPath`。
- 入参类型能明确就明确，例如数组入参写 `array`，返回数组写 `: array`。

## 返回与异常

- 资源型返回走 `BaseResource` 或 `$this->resource()`。
- 纯动作接口成功可返回空 JSON：`response()->json([])`。
- 下载、导出、上传等特殊接口按语义返回，不强行套 CRUD 风格。
- 参数错误优先由 Request 返回 422；业务不成立直接抛异常，不做兼容式补丁。

## 测试

- 改 Controller 必须补最小范围 Feature 测试。
- 测试先覆盖稳定契约，再覆盖这次真正改动的业务结果。
- 后端运行验证以远端环境为准；本地只做语法检查和必要的快速反馈。

### 测试格式（Pest）

- 测试文件统一用 **Pest** 格式：文件顶部 `uses(Tests\TestCase::class);`，用例写成 `it('中文描述', function () { ... });`，断言优先用 `expect()->toBe()/toEqual()/toHaveCount()` 等链式风格，HTTP 断言仍可用 `$this->getJson(...)->assertJsonPath(...)`（`it` 闭包内 `$this` 即 `TestCase`）。新样板参考 `ServerPathControllerTest`、`TodoItemControllerTest`、`StatsAggregatorTest`。
- **不要在 Pest 文件里写 PHPUnit 类**（`it()`/`uses()` 等函数不能存在于 `extends TestCase` 的类里）。当需要改动一个仍是 PHPUnit 类风格的旧测试文件时，**整文件转成 Pest**，不要在同一文件混两种风格。
- 类的 `setUp`/`tearDown` 对应 Pest 的 `beforeEach()`/`afterEach()`；类的私有辅助方法改为**带文件前缀的全局函数**（如 `dashboardCreateUser()`、`statsInvokeStreaks()`），前缀用于避免与其它 Pest 文件的全局函数重名。
- 测试私有方法用 `ReflectionMethod`（`setAccessible(true)`）调用，不要为了可测性把私有方法改成 public。
- 涉及“今天/当前时间”的用例必须用 `Carbon::setTestNow()` 钉死到**固定日期并注明周几**，并在 `afterEach()` 用 `Carbon::setTestNow()` 复位；**禁止用相对日期**（`Carbon::today()->subDay()` 等），否则结果随运行日漂移，在“按工作日”等口径下会变成 flaky 测试。
- 本地 `vendor` 可能不含 `pest`（dev 依赖未装），后端测试一律在远端执行：`./vendor/bin/sail pest [文件路径]`。

## ServerPath 样板

`ServerPathController` 是当前后台 CRUD 新样板：

- `ServerPathRequest` 负责 `add/edit/convert/batchDelete` 校验。
- `ServerPathRequest` 同时负责 `index` 查询参数校验，分页字段来自公共 `FormRequest`，业务字段只保留 `code/name/filter`。
- `ServerPathController` 负责列表、详情、新增、编辑、删除、转换的 HTTP 编排。
- `ServerPathController@index` 用显式 `QueryBuilder::for()` 展示列表查询结构：允许过滤、排序、分页都写在当前方法里；返回继续走 `$this->resource()`。
- `ServerPathService` 负责路径转换算法，并在 `sources` JSON 错误时直接暴露异常。
