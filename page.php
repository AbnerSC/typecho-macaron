<?php
/**
 * 独立页面（关于页等）
 *
 * @package  typecho-macaron
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$hasSidebar = (($this->options->macaronSidebar ?: 'show') === 'show');

// 归档页（自定义字段 catalog=1）与链接页（自定义字段 links 非空）
$isCatalog = ((int) ($this->fields->catalog ?: 0) === 1);
$linksRaw  = trim((string) ($this->fields->links ?: ''));
$linkItems = $linksRaw !== '' ? macaronParseLinks($linksRaw) : [];
$isLinks   = !empty($linkItems);

// 归档/链接等特殊页不展示正文目录
$hasToc = ((($this->options->macaronPostToc ?: 'show') === 'show') && !$isCatalog && !$isLinks);

$this->need('header.php');
?>

<div class="layout <?php echo $hasSidebar ? 'with-sidebar' : 'narrow'; ?>">
    <div class="content-area">

        <article class="article">
            <header class="article-head">
                <h1 class="article-title"><?php $this->title(); ?></h1>
            </header>

            <?php if ($hasToc): ?>
                <nav class="toc" aria-label="页面目录">
                    <p class="toc-title">目录</p>
                    <ul class="toc-list"></ul>
                </nav>
            <?php endif; ?>

            <div class="article-body">
            <?php if ($isCatalog): ?>
                <?php $this->widget('Widget_Contents_Post_Recent', 'pageSize=100000')->to($archivePosts); ?>
                <?php if ($archivePosts->have()): ?>
                    <div class="archive-list">
                        <?php $lastMonth = ''; ?>
                        <?php while ($archivePosts->next()): ?>
                            <?php $ts = (int) $archivePosts->created; ?>
                            <?php $ym = date('Y-m', $ts); ?>
                            <?php if ($ym !== $lastMonth): ?>
                                <?php if ($lastMonth !== ''): ?></ul><?php endif; ?>
                                <h3 class="archive-month"><?php echo date('Y', $ts); ?> 年 <?php echo date('n', $ts); ?> 月</h3>
                                <ul class="archive-items">
                                <?php $lastMonth = $ym; ?>
                            <?php endif; ?>
                                <li>
                                    <time datetime="<?php echo date('c', $ts); ?>"><?php echo date('m-d', $ts); ?></time>
                                    <a href="<?php $archivePosts->permalink(); ?>"><?php $archivePosts->title(); ?></a>
                                </li>
                            <?php if (!$archivePosts->have()): ?></ul><?php endif; ?>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">还没有任何文章。</div>
                <?php endif; ?>
            <?php elseif ($isLinks): ?>
                <div class="link-list">
                    <?php foreach ($linkItems as $link): ?>
                        <a class="link-card" href="<?php echo macaronE($link['url']); ?>" target="_blank" rel="noopener nofollow">
                            <span class="link-name"><?php echo macaronE($link['name']); ?></span>
                            <span class="link-url"><?php echo macaronE($link['url']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <?php $this->content(); ?>
            <?php endif; ?>
            </div>

            <?php if ($this->options->allowCommentAtPage && $this->allow('comment')): ?>
                <?php $this->need('comments.php'); ?>
            <?php endif; ?>
        </article>

    </div><!-- /.content-area -->

    <?php if ($hasSidebar) $this->need('sidebar.php'); ?>
</div><!-- /.layout -->

<?php if ($hasToc): ?>
    <button class="toc-toggle" type="button" aria-label="目录">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
    </button>
<?php endif; ?>

<?php $this->need('footer.php'); ?>
