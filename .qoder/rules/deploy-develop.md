---
trigger: model_decision
description: 开发环境部署规则
---

# 部署与测试说明（内部）

> 本文记录将本主题部署到**远程测试站**进行验证的完整流程与踩坑要点，
> 供开发自测复用，避免每次重复试错。
>
> **隐私提醒**：文中所有服务器地址、账号、口令一律使用占位符，
> 请放在本地 `~/.ssh/config` 或环境变量中，**严禁把真实 IP / 主机名 / 密码 / 邮箱提交进仓库**。

---

## 1. 占位符约定

执行前先在本机（不入库）准备好以下变量：

| 占位符 | 含义 | 示例（仅示意，勿用真实值） |
|---|---|---|
| `${SSH_ALIAS}` | `~/.ssh/config` 中配置的测试机别名 | `testbox` |
| `${TEST_HOST}` | 测试机 IP/域名 | `10.0.0.x` |
| `${PORT}` | 对外测试端口（先检测未被占用） | `8420` |
| `${ADMIN_USER}` | Typecho 管理员用户名 | 自定 |
| `${ADMIN_PASS}` | Typecho 管理员密码 | 自定（强随机） |
| `${SITE_URL}` | 站点访问地址 | `http://${TEST_HOST}:${PORT}` |

SSH 免密：`~/.ssh/config` 配置 `Host ${SSH_ALIAS} → ${ADMIN}@${TEST_HOST}` + 密钥，全程用别名访问。

---

## 2. 前置条件

- 测试机已安装 **Docker**。
- 本机（Windows/PowerShell）可用 `ssh`、`scp`。
- 基础镜像 `php:8.2-apache`（已内置 `pdo_sqlite`，可零外部数据库跑 Typecho）。
  - 若测试机没有：`docker pull php:8.2-apache`。
- Typecho 发行包 `typecho.zip`（从官方 GitHub Releases 下载）。

---

## 3. 部署步骤

### 3.1 端口检测与目录准备

```bash
ssh ${SSH_ALIAS} "ss -lntp | grep -w ${PORT} || echo 'port free'"
ssh ${SSH_ALIAS} "mkdir -p /data/macaron/site"
```

### 3.2 下载并解压 Typecho

```bash
ssh ${SSH_ALIAS} "curl -sL -o /tmp/typecho.zip \
  https://github.com/typecho/typecho/releases/latest/download/typecho.zip \
  && unzip -q -o /tmp/typecho.zip -d /data/macaron/site"
```

### 3.3 启动容器（关键：SELinux 需 `:z`）

> **坑点**：宿主机若为 openEuler/RHEL 系且 SELinux 处于 Enforcing，
> bind-mount 必须加 `:z` 标签，否则容器内看不到挂载目录（报 `No such file or directory`）。

```bash
ssh ${SSH_ALIAS} "docker run -d --name macaron-typecho -p ${PORT}:80 \
  -v /data/macaron/site:/var/www/html:z \
  php:8.2-apache bash -c \
  'chown -R 33:33 /var/www/html && a2enmod rewrite && apache2-foreground'"
```

- `chown -R 33:33`：33 是容器内 `www-data` 的数字 UID（用数字避免用户名解析差异）。
- `a2enmod rewrite`：启用伪静态所需模块。

### 3.4 安装 Typecho（三步 HTTP 表单）

> **坑点**：官方 `typecho.zip` 发行包**不含** `usr/bin/typecho.sh` CLI 安装脚本，
> 只能走 `install.php` 的三步表单。用 curl 依次提交：

```bash
J=/tmp/cookies.txt
# step1 环境检测 → step2 建 SQLite 库 → step3 建管理员
ssh ${SSH_ALIAS} "curl -s -c $J -b $J -X POST http://127.0.0.1:${PORT}/install.php -d 'step=1'"
ssh ${SSH_ALIAS} "curl -s -c $J -b $J -X POST http://127.0.0.1:${PORT}/install.php \
  -d 'step=2&dbAdapter=SQLite&dbPrefix=typecho_&dbFile=/var/www/html/usr/typecho.db&dbNext=none'"
ssh ${SSH_ALIAS} "curl -s -c $J -b $J -X POST http://127.0.0.1:${PORT}/install.php \
  -d 'step=3&userUrl=${SITE_URL}&userName=${ADMIN_USER}&userPassword=${ADMIN_PASS}&userMail=admin@localhost'"
```

每步返回 JSON `{"success":1,...}` 即通过；根路径返回 200 表示站点已起。

### 3.5 同步主题文件

```bash
ssh ${SSH_ALIAS} "mkdir -p /data/macaron/site/usr/themes/typecho-macaron"
# 上传主题全部文件（开发迭代时至少同步 assets/js/main.js 与 assets/css/style.css）
scp -r <本地主题目录>/\* ${SSH_ALIAS}:/data/macaron/site/usr/themes/typecho-macaron/
```

激活主题、发布测试文章可通过后台，或用一次性 PHP 脚本操作 SQLite（见 §5）。

### 3.6 浏览器验证

- 用 browser MCP：先 `navigate_page` 到目标页，再 `take_screenshot`
  （chrome-devtools 的截图在重型页面易超时，browser-use 更稳）。
- 改了静态资源后，用 `navigate_page` 的 `reload + ignoreCache` 强刷；
  `functions.php` 的 `macaronAssetUrl()` 以 `?v=filemtime` 版本号自动破缓存，
  `scp` 更新 mtime 即生效，无需手动清缓存。

---

## 4. 清理测试环境（测试确认后执行）

```bash
ssh ${SSH_ALIAS} "docker rm -f macaron-typecho"
ssh ${SSH_ALIAS} "rm -rf /data/macaron"
# 验证端口释放、容器归零
ssh ${SSH_ALIAS} "ss -lntp | grep -w ${PORT} || echo released; \
  docker ps -a --filter name=macaron-typecho -q | wc -l"
```

- **不要删** `php:8.2-apache`：它是共享基础镜像，非本次构建产物，无自定义镜像需要 `docker rmi`。
- 本地临时脚本（`mc-*` 前缀的 `.php/.sh`、截图）随手删除；
  非空目录用 `Remove-Item -Recurse -Force`（DeleteFile 工具删不了非空目录）。

---

## 5. 发布测试文章（可选，直接写库）

用于验证功能时，可在容器内跑一次性 PDO 脚本向 SQLite 插入文章。要点：

- **Markdown 必须生效**：`contents.text` 需以 `<!--markdown-->` 开头，否则前台显示原始源码。
- **保留字加引号**：`order` 是 SQLite 保留字，列名需写成 `"order"`。
- 插入后同步 `relationships`（关联默认分类 mid=1）并更新 `metas.count`。

---

## 6. 踩坑速查表

| 现象 | 根因 | 处理 |
|---|---|---|
| 容器内看不到挂载目录 | SELinux Enforcing | bind-mount 加 `:z` |
| 找不到 `typecho.sh` | 发行包不含 CLI 安装脚本 | 走 `install.php` 三步 HTTP 表单 |
| PowerShell 里 ssh 命令报错/被拆散 | 引号与 CRLF 换行 | 用 here-string 管道 `bash -s`，并先 `-replace "\r\n","\n"`；复杂脚本写成 `.sh` 上传后 `sed -i 's/\r$//'` |
| 文章显示原始 Markdown | 缺 `<!--markdown-->` 标记 | text 字段补前缀 |
| 建文章 SQL 语法错误 | `order` 保留字 | 列名加双引号 |
| 改了 CSS 前台仍是旧样式 | 浏览器缓存 / 未同步远端 | scp 同步 + `?v=filemtime` 自动破缓存 + 强刷 |
| 截图工具超时 | 重型页面等网络空闲 | 改用 browser-use：先 navigate 再 screenshot |

---

## 7. 安全底线

- 真实主机、账号、口令只存在于本地 `~/.ssh/config` 与临时环境变量，**绝不写入本仓库任何文件**。
- 提交前 `git status` / `git diff` 复查，确保无 IP、密码、邮箱、`.bak`、临时脚本残留。
