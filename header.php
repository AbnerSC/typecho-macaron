<?php
/**
 * 页头：文档头部、主题变量注入、顶部导航
 *
 * @package  typecho-macaron
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
?>
<!DOCTYPE html>
<html lang="zh-CN" class="no-js">
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f5f5f7" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#161617" media="(prefers-color-scheme: dark)">
    <title>
        <?php $this->archiveTitle(array(
            'category' => '分类 %s',
            'search'   => '包含 %s 的搜索结果',
            'tag'      => '标签 %s',
            'author'   => '%s 的文章',
            'date'     => '%s 的文章'
        ), '', ' - '); ?>
        <?php $this->options->title(); ?>
        <?php if ($this->is('index')): ?>
            · <?php $this->options->description(); ?>
        <?php endif; ?>
    </title>

    <!-- 主题样式 -->
    <link rel="stylesheet" href="<?php echo macaronAssetUrl($this->options, 'assets/css/style.css'); ?>">

    <!-- 首屏主题预设：避免深浅切换闪烁 -->
    <script>
        (function () {
            var KEY = 'macaron-theme';
            var mode = '<?php echo $this->options->macaronColorMode ?: 'system'; ?>';
            var stored = null;
            try { stored = localStorage.getItem(KEY); } catch (e) {}
            var theme;
            if (stored) {
                theme = stored;
            } else if (mode === 'system') {
                theme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
            } else {
                theme = mode;
            }
            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.className = document.documentElement.className.replace('no-js', 'js');
        })();
    </script>

    <?php $this->header(); // Typecho 原生钩子：插件、Feed、自定义头部 ?>
</head>
<?php
    $sidebarMode  = $this->options->macaronSidebar ?: 'show';
    $backdropMode = $this->options->macaronBackdrop ?: 'on';
    $colorMode    = $this->options->macaronColorMode ?: 'system';
    $codeMode     = $this->options->macaronCodeHighlight ?: 'on';
?>
<body class="<?php echo $backdropMode === 'off' ? 'no-backdrop' : ''; ?>"
      data-default-mode="<?php echo $colorMode; ?>"
      data-backdrop="<?php echo $backdropMode; ?>"
      data-code-highlight="<?php echo $codeMode; ?>"
      data-sidebar="<?php echo $sidebarMode; ?>">

    <header class="site-nav">
        <div class="nav-inner">
            <a class="site-brand" href="<?php $this->options->siteUrl(); ?>">
                <span class="brand-dot"></span>
                <?php $this->options->title(); ?>
            </a>

            <button class="nav-burger" type="button" aria-label="菜单" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>

            <ul class="nav-menu">
                <?php
                    $navItems = macaronNavItems($this->options);
                    if (empty($navItems)) {
                        $navItems = [['name' => '首页', 'url' => '']];
                    }
                    $siteUrl = rtrim((string) $this->options->siteUrl, '/');
                    foreach ($navItems as $item):
                        $url = $item['url'];
                        if ($url === '' || $url === '/') {
                            $url = $siteUrl . '/';
                        } elseif (strpos($url, 'http') !== 0 && strpos($url, $siteUrl) !== 0) {
                            $url = $siteUrl . '/' . ltrim($url, '/');
                        }
                ?>
                    <li>
                        <a href="<?php echo macaronE($url); ?>"
                           <?php if (isset($item['target']) && $item['target']): ?>target="_blank" rel="noopener"<?php endif; ?>>
                            <?php echo macaronE($item['name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>

                <li>
                    <button class="theme-toggle" id="themeToggle" type="button" aria-label="切换深浅色">
                        <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                        <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                    </button>
                </li>
            </ul>
        </div>
    </header>

    <main class="site-main">
        <div class="container">
