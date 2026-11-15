<?php
/**
 * typecho-macaron 主题设置面板与辅助函数
 *
 * 马卡龙（Macaron）—— 苹果现代审美 + 马卡龙柔和配色的轻量 Typecho 博客主题。
 * 本文件仅负责后台设置项注册与少量辅助逻辑，不破坏 Typecho 原生钩子与能力。
 *
 * @package  typecho-macaron
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 后台主题设置面板
 *
 * 所有配置项通过 Typecho 原生 Widget_Form 注册，读取时使用
 * $this->options->字段名 获取。
 *
 * @param \Typecho\Widget\Helper\Form $form
 */
function themeConfig($form)
{
    // 侧边栏开关
    $sidebar = new \Typecho\Widget\Helper\Form\Element\Radio(
        'macaronSidebar',
        ['show' => '开启侧边栏', 'hide' => '关闭侧边栏'],
        'show',
        '侧边栏',
        '控制全站是否展示侧边栏；关闭时为全屏无侧边纯阅读布局。'
    );
    $form->addInput($sidebar);

    // 默认色调
    $defaultColor = new \Typecho\Widget\Helper\Form\Element\Radio(
        'macaronColorMode',
        ['light' => '浅色模式', 'dark' => '深色模式', 'system' => '跟随系统'],
        'system',
        '默认色调',
        '浅色 / 深色 / 跟随系统。用户仍可在前端手动切换，切换结果记忆于本地。'
    );
    $form->addInput($defaultColor);

    // 导航链接
    $navLinks = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'macaronNavLinks',
        null,
        "首页|/\n归档|\n关于|",
        '导航链接',
        '每行一个，格式为「名称|链接」，链接可留空（默认取站点首页）。示例：首页|/ 、归档|/index.php/archives/'
    );
    $form->addInput($navLinks);

    // 页脚文字
    $footerText = new \Typecho\Widget\Helper\Form\Element\Text(
        'macaronFooterText',
        null,
        null,
        '页脚文字',
        '自定义页脚版权或备案信息，留空则使用站点名称兼底。'
    );
    $form->addInput($footerText);

    // 备案号
    $icpNumber = new \Typecho\Widget\Helper\Form\Element\Text(
        'macaronIcpNumber',
        null,
        null,
        '备案号',
        '工信部 ICP 备案号（如：京ICP备00000000号-1），展示于页脚并自动链接至备案系统；留空不显示。'
    );
    $form->addInput($icpNumber);

    // 文章目录开关
    $postToc = new \Typecho\Widget\Helper\Form\Element\Radio(
        'macaronPostToc',
        ['show' => '开启', 'hide' => '关闭'],
        'show',
        '文章目录',
        '控制文章页是否为长文自动生成目录。'
    );
    $form->addInput($postToc);

    // 液态玻璃效果开关
    $backdrop = new \Typecho\Widget\Helper\Form\Element\Radio(
        'macaronBackdrop',
        ['on' => '启用', 'off' => '关闭'],
        'on',
        '液态玻璃效果',
        '控制是否启用 iOS 26 风格液态玻璃（backdrop-filter 折射模糊 + 镜面高光边缘）；不支持时自动优雅回落。'
    );
    $form->addInput($backdrop);

    // 代码高亮开关
    $codeHighlight = new \Typecho\Widget\Helper\Form\Element\Radio(
        'macaronCodeHighlight',
        ['on' => '启用', 'off' => '关闭'],
        'on',
        '代码高亮',
        '开启后为文章代码块提供适配浅色/深色的语法高亮。'
    );
    $form->addInput($codeHighlight);

    // 首页置顶文章
    $stickyPosts = new \Typecho\Widget\Helper\Form\Element\Text(
        'macaronStickyPosts',
        null,
        null,
        '首页置顶文章',
        '置顶于首页第 1 页顶部的文章 cid，按显示顺序输入，以半角逗号或空格分隔（如：65,56,210）；留空则关闭置顶。'
    );
    $form->addInput($stickyPosts);

    // 分类页置顶开关
    $stickyCategory = new \Typecho\Widget\Helper\Form\Element\Radio(
        'macaronStickyCategory',
        ['show' => '开启', 'hide' => '关闭'],
        'hide',
        '分类页置顶',
        '开启后上述文章在其所属分类页（含子分类）同样置顶。'
    );
    $form->addInput($stickyCategory);
}

/**
 * 主题初始化钩子
 *
 * Typecho 1.2+ 中主题 functions.php 在 Widget\Archive::execute() 内、列表查询执行前加载；
 * 置顶注入改挂 beforeRender 钩子（列表查询完成后、模板渲染前触发），
 * 由 macaronInjectSticky() 调整行堆栈。
 *
 * @param Typecho_Widget $widget
 */
function themeInit($widget)
{
    \Typecho\Plugin::factory(\Widget\Archive::class)->beforeRender = 'macaronInjectSticky';
}

/**
 * 注入置顶文章（文章置顶功能核心）
 *
 * 参考 Sticky 插件在主题层实现：首页与分类页第 1 页时，将配置的置顶文章
 * 按顺序压入文章列表顶部，并从常规列表行中移除以避免重复；第 2 页起不注入，
 * 置顶文章也不再出现在后续分页中（与 Sticky 插件行为一致）。
 *
 * 行堆栈操作使用反射完成：置顶行携带 sticky 标记，模板中经
 * if ($this->sticky) 输出「置顶」徽章。
 *
 * @param \Widget\Archive $archive 当前归档组件
 */
function macaronInjectSticky($archive)
{
    $type = $archive->parameter->type;
    $isIndex = ($type === 'index' || $type === 'index_page');
    $isCategory = ($type === 'category' || $type === 'category_page');
    if (!$isIndex && !$isCategory) {
        return;
    }

    // Widget\Archive 未暴露 options 访问器，从全局 Options 组件读取主题设置
    $options = \Widget\Options::alloc();
    $raw = trim((string) $options->macaronStickyPosts);
    if ($raw === '') {
        return;
    }

    // 解析 cid 列表：兼容半角/全角逗号与空格分隔，去重保序
    $cids = [];
    foreach (preg_split('/[\s,，]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY) as $piece) {
        $cid = (int) $piece;
        if ($cid > 0 && !in_array($cid, $cids, true)) {
            $cids[] = $cid;
        }
    }
    if (!$cids) {
        return;
    }

    // 分类页：受开关控制，且仅置顶归属于当前分类（含子分类）的文章
    $categoryMids = null;
    if ($isCategory) {
        if (($options->macaronStickyCategory ?: 'hide') !== 'show') {
            return;
        }
        $categoryMids = macaronCurrentCategoryMids($archive);
        if (!$categoryMids) {
            return;
        }
    }

    $db = \Typecho\Db::get();

    // 按配置顺序取出置顶文章行（与列表查询同构：仅公开且已发布的文章）
    $rows = $db->fetchAll($archive->select()
        ->where('table.contents.type = ?', 'post')
        ->where('table.contents.status = ?', 'publish')
        ->where('table.contents.created < ?', (int) $options->time)
        ->where('table.contents.cid IN ?', $cids));
    $rowsByCid = [];
    foreach ($rows as $row) {
        $rowsByCid[(int) $row['cid']] = $row;
    }

    $stickyRows = [];
    foreach ($cids as $cid) {
        if (!isset($rowsByCid[$cid])) {
            continue;
        }
        if ($categoryMids !== null) {
            $belongsTo = $db->fetchRow($db->select()
                ->from('table.relationships')
                ->where('cid = ?', $cid)
                ->where('mid IN ?', $categoryMids)
                ->limit(1));
            if (!$belongsTo) {
                continue;
            }
        }
        $row = $rowsByCid[$cid];
        $row['sticky'] = true;   // 模板中经 $this->sticky 渲染置顶徽章
        $stickyRows[] = $row;
    }

    // 调整行堆栈：置顶行须经 Widget\Base::push() 的 filter 流程富化（日期对象、
    // permalink 等）方可渲染；第 1 页 push 后搬移至头部，其余页仅移除防重复
    $stackProp = new \ReflectionProperty(\Typecho\Widget::class, 'stack');
    $stackProp->setAccessible(true);
    $page1 = ($stickyRows && $archive->getCurrentPage() === 1);

    if ($page1) {
        // push() 追加至堆栈尾部并完成行富化，随后统一过滤并搬移
        foreach ($stickyRows as $row) {
            $archive->push($row);
        }
    }

    // 保留携带 sticky 标记的新增置顶行，移除其余与置顶 cid 重复的原始行
    $stack = array_values(array_filter($stackProp->getValue($archive), function ($row) use ($cids) {
        return isset($row['sticky']) || !in_array((int) $row['cid'], $cids, true);
    }));

    if ($page1) {
        $pushed = array_splice($stack, count($stack) - count($stickyRows));
        $stack = array_merge($pushed, $stack);
    }

    // 移除置顶行后当前页已无内容（超出实际分页范围），与核心行为一致地回落 404
    if (!$stack && $archive->getCurrentPage() > 1) {
        throw new \Typecho\Widget\Exception(_t('请求的地址不存在'), 404);
    }

    reset($stack);
    $stackProp->setValue($archive, $stack);

    // 分页计数同步排除置顶行，避免末尾出现空页（与 Sticky 插件查询层排除的行为一致）
    $countSql = $archive->getCountSql();
    if ($countSql) {
        $countSql->where('table.contents.cid NOT IN ?', $cids);
        $archive->setCountSql($countSql);
    }
}

/**
 * 获取分类页当前分类及其子分类的 mid 集合
 *
 * 与 Widget\Archive::categoryHandle 保持一致，支持 mid、slug、directory
 * 三种路由形态；无法确定当前分类时返回空数组（视为不置顶）。
 *
 * @param \Widget\Archive $archive 当前归档组件
 * @return array<int, int>
 */
function macaronCurrentCategoryMids($archive)
{
    $request = $archive->request;
    $db = \Typecho\Db::get();
    $mid = 0;

    if ($request->is('mid')) {
        $mid = (int) $request->filter('int')->get('mid');
    } else {
        $slug = $request->get('slug');
        if (!$slug && $request->is('directory')) {
            $directory = explode('/', (string) $request->get('directory'));
            $slug = $directory[count($directory) - 1];
        }
        if ($slug) {
            $category = $db->fetchRow($db->select('mid')
                ->from('table.metas')
                ->where('type = ?', 'category')
                ->where('slug = ?', $slug));
            $mid = $category ? (int) $category['mid'] : 0;
        }
    }

    if ($mid <= 0) {
        return [];
    }

    // 展开子分类：直接查表构建父子映射后广度优先收集（核心的 getAllChildIds
    // 挂在 Widget\Metas\From 上而非 Category\Rows，跨版本宿主不稳，查表最可靠）
    $mids = [$mid];
    $childrenMap = [];
    $categories = $db->fetchAll($db->select('mid', 'parent')
        ->from('table.metas')
        ->where('type = ?', 'category'));
    foreach ($categories as $category) {
        $childrenMap[(int) $category['parent']][] = (int) $category['mid'];
    }
    for ($i = 0; $i < count($mids); $i++) {
        foreach ($childrenMap[$mids[$i]] ?? [] as $child) {
            if (!in_array($child, $mids, true)) {
                $mids[] = $child;
            }
        }
    }

    return $mids;
}

/**
 * 解析后台导航链接配置
 *
 * @param Typecho_Widget $options Options 组件对象（在模板中以 $this->options 取得，
 *                                经 __get 暴露主题设置字段）
 * @return array<int, array{name:string, url:string}>
 */
function macaronNavItems($options)
{
    // Typecho options 通过 __get 提供动态属性（未设置时返回 null），
    // 且未实现 __isset，故不可用 isset() 判断，直接读取并做空值兜底。
    $raw = (string) $options->macaronNavLinks;
    $lines = preg_split('/\r\n|\r|\n/', $raw);
    $items = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        // 支持「名称|链接」与仅「名称」两种写法
        if (strpos($line, '|') !== false) {
            list($name, $url) = array_map('trim', explode('|', $line, 2));
        } else {
            $name = $line;
            $url = '';
        }
        if ($name === '') {
            continue;
        }
        $items[] = ['name' => $name, 'url' => $url];
    }

    return $items;
}

/**
 * 修正分页总页数
 *
 * 部分"文章置顶"类插件在首页（第1页）会就地修改 $archive->parameter->pageSize
 * （例如收缩为 原值 - 置顶数量），而 Typecho 的 pageNav/Box 分页器正是用该
 * pageSize 计算总页数，导致第1页显示的总页数被膨胀（如 19），而第2页恢复正常
 * （如 13），点击膨胀出来的尾页会 404。
 *
 * 本函数在输出分页前，将 parameter->pageSize 还原为后台配置的真实值
 * （options->pageSize），使各页总页数保持一致。主题侧无侵入修复，不改动核心与插件。
 *
 * @param Typecho_Widget $archive 当前归档组件（模板中的 $this）
 * @param int|string|null $configured 后台配置的每页数量（在模板中读取 $this->options->pageSize 传入，
 *                                    避免从外部访问 Widget 魔术属性时取不到值）
 */
function macaronFixPageNav($archive, $configured = null)
{
    $configured = (int) ($configured ?: 10);
    if ($configured > 0 && (int) $archive->parameter->pageSize !== $configured) {
        $archive->parameter->pageSize = $configured;
    }
}

/**
 * 解析链接页自定义字段 links
 *
 * 推荐格式：每行一个，「名称,链接」。同时兼容紧凑写法
 * （多个条目以空格分隔，如：Typecho,https://a.com Github,https://b.com）。
 * 若未写名称（只有 URL），则名称回退为 URL。
 *
 * @param string $raw
 * @return array<int, array{name:string, url:string}>
 */
function macaronParseLinks($raw)
{
    $raw = trim((string) $raw);
    if ($raw === '') {
        return [];
    }

    $links = [];
    $lines = preg_split('/\r\n|\r|\n/', $raw);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        // 先按“下一个条目边界”（空白后紧跟 名称,http）切分，兼容单行多条目
        $segments = preg_split('/\s+(?=\S*,\s*https?:\/\/)/i', $line);
        foreach ($segments as $seg) {
            $seg = trim($seg);
            if ($seg === '') {
                continue;
            }
            if (strpos($seg, ',') !== false) {
                list($name, $url) = array_map('trim', explode(',', $seg, 2));
            } else {
                $url = $seg;
                $name = $seg;
            }
            if ($url === '') {
                continue;
            }
            $links[] = ['name' => ($name !== '' ? $name : $url), 'url' => $url];
        }
    }

    return $links;
}

/**
 * 输出安全转义后的文本
 *
 * @param string $text
 * @return string
 */
function macaronE($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

/**
 * 生成带版本号（基于文件修改时间）的主题静态资源 URL
 *
 * 避免修改 CSS/JS 后浏览器使用旧缓存。
 *
 * @param Typecho_Widget $options Options 组件（$this->options）
 * @param string $rel 相对主题目录的路径，如 assets/js/main.js
 * @return string
 */
function macaronAssetUrl($options, $rel)
{
    $base = rtrim((string) $options->themeUrl, '/');
    $rel  = ltrim($rel, '/');
    $ver  = '1';
    $file = $options->themeFile($options->theme, $rel);
    if (is_file($file)) {
        $ver = (string) filemtime($file);
    }
    return $base . '/' . $rel . '?v=' . $ver;
}
