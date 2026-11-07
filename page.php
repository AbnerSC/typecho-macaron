<?php
/**
 * 独立页面（关于页等）
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
            </header>

            <?php if ($hasToc): ?>
                <nav class="toc" aria-label="页面目录">
                    <p class="toc-title">目录</p>
                    <ul class="toc-list"></ul>
                </nav>
            <?php endif; ?>

            <div class="article-body">
                <?php $this->content(); ?>
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
