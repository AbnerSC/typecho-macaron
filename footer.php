<?php
/**
 * 页脚：站点信息、脚本引入
 *
 * @package  typecho-macaron
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
?>
        </div><!-- /.container -->
    </main><!-- /.site-main -->

    <footer class="site-footer">
        <div class="container">
            <div class="footer-brand">
                <a class="site-brand" href="<?php $this->options->siteUrl(); ?>">
                    <span class="brand-dot"></span>
                    <?php $this->options->title(); ?>
                </a>
            </div>
            <p class="footer-text">
                <?php
                    $footerText = trim((string) $this->options->macaronFooterText);
                    if ($footerText !== '') {
                        echo macaronE($footerText);
                    } else {
                        echo '&copy; ' . date('Y') . ' ' . macaronE($this->options->title());
                    }
                ?>
            </p>
            <p class="footer-powered">
                基于 <a href="https://typecho.org" target="_blank" rel="noopener">Typecho</a>
                驱动 · 主题 <a href="https://github.com/AbnerSC/typecho-macaron.git" target="_blank" rel="noopener">Macaron</a>
            </p>
            <?php
                $icpNumber = trim((string) $this->options->macaronIcpNumber);
                if ($icpNumber !== ''):
            ?>
            <p class="footer-beian">
                <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener nofollow"><?php echo macaronE($icpNumber); ?></a>
            </p>
            <?php endif; ?>
        </div>
    </footer>

    <button class="back-to-top" type="button" aria-label="返回顶部">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
    </button>

    <script src="<?php echo macaronAssetUrl($this->options, 'assets/js/main.js'); ?>"></script>

    <?php $this->footer(); // Typecho 原生钩子：插件统计、自定义底部 ?>
</body>
</html>
