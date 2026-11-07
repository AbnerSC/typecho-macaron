<?php
/**
 * 搜索结果页
 *
 * 使用 Typecho 原生搜索能力，不引入外部搜索服务。
 *
 * @package  typecho-macaron
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$hasSidebar = (($this->options->macaronSidebar ?: 'show') === 'show');

$this->need('header.php');
?>

<div class="layout <?php echo $hasSidebar ? 'with-sidebar' : 'narrow'; ?>">
    <div class="content-area">

        <header class="page-head">
            <h1>搜索结果</h1>
            <p class="page-desc">
                关键词「<strong><?php echo $this->archiveTitle('', '', false); ?></strong>」
                <?php if ($this->have()): ?>· 共 <?php echo $this->getTotal(); ?> 条结果<?php endif; ?>
            </p>
            <form class="search-form" method="get" action="<?php $this->options->siteUrl(); ?>" style="margin-top:18px;">
                <input type="search" name="s" placeholder="搜索文章…" aria-label="搜索"
                       value="<?php echo macaronE($this->archiveSlug); ?>">
                <button class="btn search-submit" type="submit" aria-label="搜索">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.5" y2="16.5"/></svg>
                </button>
            </form>
        </header>

        <?php if ($this->have()): ?>
            <div class="post-list">
                <?php while ($this->next()): ?>
                    <article class="post-card">
                        <h2 class="card-title">
                            <a href="<?php $this->permalink(); ?>"><?php $this->title(); ?></a>
                        </h2>
                        <p class="card-excerpt"><?php $this->excerpt(200, '…'); ?></p>
                        <div class="post-meta">
                            <span class="meta-time">
                                <time datetime="<?php $this->date('c'); ?>"><?php $this->date('Y-m-d'); ?></time>
                            </span>
                            <?php $this->category(', '); ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <?php macaronFixPageNav($this, $this->options->pageSize); ?>
            <?php $this->pageNav('上一页', '下一页', 1, '…', array(
                'wrapTag'  => 'nav',
                'wrapClass'=> 'pagination',
                'itemTag'  => '',
                'textTag'  => 'span',
                'currentClass' => 'current',
                'prevClass' => 'prev',
                'nextClass' => 'next'
            )); ?>
        <?php else: ?>
            <div class="empty-state">没有找到匹配的内容，换个关键词试试。</div>
        <?php endif; ?>

    </div><!-- /.content-area -->

    <?php if ($hasSidebar) $this->need('sidebar.php'); ?>
</div><!-- /.layout -->

<?php $this->need('footer.php'); ?>
