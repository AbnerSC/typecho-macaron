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

    // 毛玻璃效果开关
    $backdrop = new \Typecho\Widget\Helper\Form\Element\Radio(
        'macaronBackdrop',
        ['on' => '启用', 'off' => '关闭'],
        'on',
        '毛玻璃效果',
        '控制是否启用 backdrop-filter 毛玻璃效果；不支持时自动优雅回落。'
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
}

/**
 * 主题初始化钩子
 *
 * 保留 Typecho 原生行为，仅在需要时扩展；当前不改动默认路由与Feed。
 *
 * @param Typecho_Widget $widget
 */
function themeInit($widget)
{
    // 预留：后续可在此注册自定义路由，不破坏原生能力。
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
