<?php
/**
 * 文章详情页
 *
 * @package  typecho-macaron
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$hasSidebar = (($this->options->macaronSidebar ?: 'show') === 'show');
$hasToc     = (($this->options->macaronPostToc ?: 'show') === 'show');

$this->need('header.php');
?>

<div class="layout <?php echo $hasSidebar ? 'with-sidebar' : 'narrow'; ?>">
    <div class="content-area">

        <article class="article">
            <header class="article-head">
                <h1 class="article-title"><?php $this->title(); ?></h1>
                <div class="article-meta">
                    <span class="meta-time">
                        <time datetime="<?php $this->date('c'); ?>"><?php $this->date('Y-m-d H:i'); ?></time>
                    </span>
                    <span><?php $this->author(); ?></span>
                    <?php $this->category(', '); ?>
                    <span><?php $this->commentsNum('%d 评论'); ?></span>
                </div>
            </header>

            <?php if ($hasToc): ?>
                <!-- 文章目录（长文由 JS 自动生成，章节不足时自动隐藏） -->
                <nav class="toc" aria-label="文章目录">
                    <p class="toc-title">目录</p>
                    <ul class="toc-list"></ul>
                </nav>
            <?php endif; ?>

            <div class="article-body">
                <?php $this->content(); ?>
            </div>

            <?php if ($this->haveTags()): ?>
                <div class="article-tags">
                    <?php $this->tags('', true, ''); ?>
                </div>
            <?php endif; ?>

            <nav class="post-nav">
                <div class="post-nav-prev">
                    <?php $this->thePrev('%s', '<span class="nav-empty">已是第一篇</span>'); ?>
                </div>
                <div class="post-nav-next">
                    <?php $this->theNext('%s', '<span class="nav-empty">已是最后一篇</span>'); ?>
                </div>
            </nav>

            <?php $this->need('comments.php'); ?>
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
