<?php
/**
 * 评论列表与评论表单
 *
 * 适配 Typecho 原生评论体系，保留 listComments / commentUrl / cancelCommentReply
 * 等原生钩子，不破坏回复、分页、鉴权能力。
 *
 * @package  typecho-macaron
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

if (!function_exists('threadedComments')) {
/**
 * 单条评论渲染回调 —— 由 Widget_Data_Comments::listComments() 调用
 *
 * @param Widget_Comments_Item $comment
 * @param array                $options
 */
function threadedComments($comment, $options)
{
    $commentClass = '';
    if ($comment->authorId) {
        $commentClass = ($comment->authorId == $comment->ownerId)
            ? ' comment-by-author' : ' comment-by-user';
    }
    if ($comment->status === 'waiting') {
        $commentClass .= ' comment-waiting';
    }
?>
    <li class="comment-item<?php echo $commentClass; ?>" <?php $comment->theId(); ?>>
        <div class="comment-head">
            <?php echo getCommentAvatar($comment->mail, 80); ?>
            <span class="comment-author"><?php $comment->author(true); ?></span>
            <span class="comment-time"><?php $comment->dateTrans(); ?></span>
        </div>
        <div class="comment-content">
            <?php $comment->content(); ?>
        </div>
        <div class="comment-reply">
            <?php $comment->reply('回复'); ?>
        </div>
    </li>
<?php } }

if (!function_exists('getCommentAvatar')) {
/**
 * Gravatar 头像辅助函数（带本地回落，主题内唯一实现）
 *
 * @param string $mail
 * @param int    $size
 * @return string
 */
function getCommentAvatar($mail, $size = 80)
{
    $url = 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($mail)))
        . '?s=' . (int) $size . '&d=mm';
    return '<img class="comment-avatar" src="' . $url . '" width="' . (int) $size
        . '" height="' . (int) $size . '" alt="" loading="lazy">';
}
}
?>

<div class="comments" id="comments">
    <?php $this->comments()->to($comments); ?>

    <?php if ($comments->have()): ?>
        <h3 class="comments-title"><?php $this->commentsNum('评论', '%d 条评论'); ?></h3>

        <ol class="comment-list">
            <?php $comments->listComments(); ?>
        </ol>

        <?php $comments->pageNav('上一页', '下一页', 2, '…', array(
            'wrapTag'  => 'nav',
            'wrapClass'=> 'pagination',
            'itemTag'  => '',
            'textTag'  => 'span',
            'currentClass' => 'current',
            'prevClass' => 'prev',
            'nextClass' => 'next'
        )); ?>
    <?php endif; ?>

    <?php if ($this->allow('comment')): ?>
        <div id="<?php $this->respondId(); ?>" class="respond">
            <div class="cancel-comment-reply">
                <?php $this->cancelCommentReply(); ?>
            </div>

            <h3 class="comments-title">添加评论</h3>

            <form method="post" action="<?php $this->commentUrl(); ?>" id="comment-form" class="comment-form" role="form">
                <?php if ($this->user->hasLogin()): ?>
                    <p class="comment-logged-in">
                        以 <strong><?php $this->user->screenName(); ?></strong> 身份评论 ·
                        <a href="<?php $this->options->logoutUrl(); ?>">退出</a>
                    </p>
                <?php else: ?>
                    <div class="field-row">
                        <div class="field">
                            <label for="author">称呼 <span class="required">*</span></label>
                            <input type="text" name="author" id="author"
                                   value="<?php $this->remember('author'); ?>" required>
                        </div>
                        <div class="field">
                            <label for="mail">邮箱 <?php if ($this->options->requireEmail): ?><span class="required">*</span><?php endif; ?></label>
                            <input type="email" name="mail" id="mail"
                                   value="<?php $this->remember('mail'); ?>"
                                   <?php if ($this->options->requireEmail): ?>required<?php endif; ?>>
                        </div>
                        <div class="field">
                            <label for="url">网站</label>
                            <input type="url" name="url" id="url" placeholder="https://"
                                   value="<?php $this->remember('url'); ?>">
                        </div>
                    </div>
                <?php endif; ?>

                <div class="field">
                    <label for="text">评论内容</label>
                    <textarea name="text" id="text" rows="6" required></textarea>
                </div>

                <div class="field">
                    <button type="submit" class="btn">提交评论</button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <p class="comments-notice">评论已关闭。</p>
    <?php endif; ?>
</div><!-- /.comments -->
