<?php
/**
 * 不忘初心--回归自然
 *
 * @package  typecho-macaron
 * @author   AbnerSC
 * @version  1.0
 * @link     https://github.com/AbnerSC
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$hasSidebar = (($this->options->macaronSidebar ?: 'show') === 'show');

$this->need('header.php');
?>

<div class="layout <?php echo $hasSidebar ? 'with-sidebar' : 'narrow'; ?>">
    <div class="content-area">

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
                    <?php $this->tags(' ', true, ''); ?>
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

    </div><!-- /.content-area -->

    <?php if ($hasSidebar) $this->need('sidebar.php'); ?>
</div><!-- /.layout -->

<?php $this->need('footer.php'); ?>
