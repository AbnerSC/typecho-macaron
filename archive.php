<?php
/**
 * 归档页：分类 / 标签 / 日期 / 作者 列表
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
            <h1><?php $this->archiveTitle(array(
                'category' => '分类 · %s',
                'search'   => '搜索 · %s',
                'tag'      => '标签 · %s',
                'author'   => '作者 · %s',
                'date'     => '%s'
            ), '', ''); ?></h1>
            <?php if ($this->have()): ?>
                <p class="page-desc">共 <?php echo $this->getTotal(); ?> 篇文章</p>
            <?php endif; ?>
        </header>

        <?php if ($this->have()): ?>
            <div class="post-list">
                <?php while ($this->next()): ?>
                    <article class="post-card <?php echo $this->sticky ? 'is-sticky' : ''; ?>">
                        <?php if ($this->sticky): ?>
                            <span class="sticky-badge">置顶</span>
                        <?php endif; ?>
                        <h2 class="card-title">
                            <a href="<?php $this->permalink(); ?>"><?php $this->title(); ?></a>
                        </h2>
                        <p class="card-excerpt"><?php $this->excerpt(160, '…'); ?></p>
                        <div class="post-meta">
                            <span class="meta-time">
                                <time datetime="<?php $this->date('c'); ?>"><?php $this->date('Y-m-d'); ?></time>
                            </span>
                            <?php $this->category(', '); ?>
                            <a href="<?php $this->permalink(); '#comments'; ?>">
                                <?php $this->commentsNum('%d 评论'); ?>
                            </a>
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
            <div class="empty-state">这里还没有内容。</div>
        <?php endif; ?>

    </div><!-- /.content-area -->

    <?php if ($hasSidebar) $this->need('sidebar.php'); ?>
</div><!-- /.layout -->

<?php $this->need('footer.php'); ?>
