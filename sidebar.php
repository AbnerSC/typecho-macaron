<?php
/**
 * 侧边栏：搜索、分类、标签、近期文章
 *
 * 全部使用 Typecho 原生 Widget，不引入自定义数据源。
 *
 * @package  typecho-macaron
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
?>
<aside class="sidebar">

    <!-- 搜索 -->
    <section class="widget">
        <h3 class="widget-title">搜索</h3>
        <form class="search-form" method="get" action="<?php $this->options->siteUrl(); ?>">
            <input type="search" name="s" placeholder="搜索文章…" aria-label="搜索" required>
            <button class="btn search-submit" type="submit" aria-label="搜索">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.5" y2="16.5"/></svg>
            </button>
        </form>
    </section>

    <!-- 分类 -->
    <?php $this->widget('Widget_Metas_Category_List')->to($categories); ?>
    <?php if ($categories->have()): ?>
        <section class="widget">
            <h3 class="widget-title">分类</h3>
            <div class="tag-cloud cat-cloud">
                <?php while ($categories->next()): ?>
                    <a class="tag" href="<?php $categories->permalink(); ?>">
                        <?php $categories->name(); ?><span class="tag-count"><?php $categories->count(); ?></span>
                    </a>
                <?php endwhile; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- 标签云 -->
    <?php $this->widget('Widget_Metas_Tag_Cloud', 'ignoreZeroCount=1&limit=24')->to($tags); ?>
    <?php if ($tags->have()): ?>
        <section class="widget">
            <h3 class="widget-title">标签</h3>
            <div class="tag-cloud">
                <?php while ($tags->next()): ?>
                    <a class="tag" href="<?php $tags->permalink(); ?>">
                        <?php $tags->name(); ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- 近期文章 -->
    <?php $this->widget('Widget_Contents_Post_Recent', 'pageSize=6')->to($recent); ?>
    <?php if ($recent->have()): ?>
        <section class="widget">
            <h3 class="widget-title">近期文章</h3>
            <ul>
                <?php while ($recent->next()): ?>
                    <li><a href="<?php $recent->permalink(); ?>"><?php $recent->title(); ?></a></li>
                <?php endwhile; ?>
            </ul>
        </section>
    <?php endif; ?>

</aside>
