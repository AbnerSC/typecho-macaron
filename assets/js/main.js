/* ==========================================================================
   typecho-macaron 交互脚本
   原生 JS · 少量按需 · 主题切换 / 导航 / 汉堡菜单 / 目录 / 代码复制 / 返回顶部
   主题首屏应用由 header.php 内联脚本提前完成，避免闪烁；此处处理交互。
   ========================================================================== */
(function () {
    'use strict';

    var STORAGE_KEY = 'macaron-theme';

    /* ---------------------------------------------------------
       工具：读取 body 上的配置（由 header.php 注入 data-* 属性）
       --------------------------------------------------------- */
    function getConfig() {
        var b = document.body;
        return {
            defaultMode: b.getAttribute('data-default-mode') || 'system',
            backdrop: b.getAttribute('data-backdrop') !== 'off',
            codeHighlight: b.getAttribute('data-code-highlight') !== 'off'
        };
    }

    function systemDark() {
        return window.matchMedia &&
            window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    function resolveTheme(mode) {
        if (mode === 'system') return systemDark() ? 'dark' : 'light';
        return mode;
    }

    /* ---------------------------------------------------------
       主题切换：记忆本地选择，下次访问自动恢复
       --------------------------------------------------------- */
    function initThemeToggle() {
        var btn = document.getElementById('themeToggle');
        if (!btn) return;

        btn.addEventListener('click', function () {
            var current = document.documentElement.getAttribute('data-theme') ||
                (systemDark() ? 'dark' : 'light');
            var next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            try { localStorage.setItem(STORAGE_KEY, next); } catch (e) {}
        });

        // 跟随系统模式下，系统偏好变化时同步（未手动选择的前提下）
        if (window.matchMedia) {
            var mq = window.matchMedia('(prefers-color-scheme: dark)');
            var handler = function () {
                var stored = null;
                try { stored = localStorage.getItem(STORAGE_KEY); } catch (e) {}
                if (!stored) {
                    document.documentElement.setAttribute(
                        'data-theme', getConfig().defaultMode === 'system'
                            ? (systemDark() ? 'dark' : 'light')
                            : resolveTheme(getConfig().defaultMode)
                    );
                }
            };
            if (mq.addEventListener) mq.addEventListener('change', handler);
            else if (mq.addListener) mq.addListener(handler);
        }
    }

    /* ---------------------------------------------------------
       顶部导航：滚动时加阴影；参考苹果官网表现
       --------------------------------------------------------- */
    function initNavScroll() {
        var nav = document.querySelector('.site-nav');
        if (!nav) return;
        var onScroll = function () {
            nav.classList.toggle('is-scrolled', window.scrollY > 8);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* ---------------------------------------------------------
       汉堡菜单（移动端导航折叠）
       --------------------------------------------------------- */
    function initBurger() {
        var burger = document.querySelector('.nav-burger');
        var menu = document.querySelector('.nav-menu');
        if (!burger || !menu) return;

        burger.addEventListener('click', function () {
            var open = menu.classList.toggle('is-open');
            burger.classList.toggle('is-open', open);
            burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        // 点击菜单项后自动收起
        menu.addEventListener('click', function (e) {
            if (e.target.closest('a')) {
                menu.classList.remove('is-open');
                burger.classList.remove('is-open');
                burger.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /* ---------------------------------------------------------
       返回顶部
       --------------------------------------------------------- */
    function initBackToTop() {
        var btn = document.querySelector('.back-to-top');
        if (!btn) return;
        var onScroll = function () {
            btn.classList.toggle('is-visible', window.scrollY > 400);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /* ---------------------------------------------------------
       文章目录（TOC）：为长文自动生成，滚动高亮当前章节
       --------------------------------------------------------- */
    function initToc() {
        var body = document.querySelector('.article-body');
        var tocBox = document.querySelector('.toc');
        if (!body || !tocBox) return;

        var list = tocBox.querySelector('.toc-list');
        if (!list) return;

        var headings = body.querySelectorAll('h2, h3');
        if (headings.length < 2) {
            // 章节过短则不展示目录
            tocBox.style.display = 'none';
            var toggle = document.querySelector('.toc-toggle');
            if (toggle) toggle.style.display = 'none';
            return;
        }

        var items = [];
        Array.prototype.forEach.call(headings, function (h, i) {
            if (!h.id) h.id = 'toc-' + i;
            var li = document.createElement('li');
            var a = document.createElement('a');
            a.href = '#' + h.id;
            a.textContent = h.textContent;
            if (h.tagName === 'H3') a.className = 'toc-h3';
            li.appendChild(a);
            list.appendChild(li);
            items.push({ id: h.id, link: a });
        });

        // 移动端悬浮目录开关
        var toggle = document.querySelector('.toc-toggle');
        if (toggle) {
            toggle.addEventListener('click', function () {
                tocBox.classList.toggle('is-open');
            });
        }

        // 滚动高亮
        if ('IntersectionObserver' in window) {
            var current = null;
            var setActive = function (id) {
                if (current === id) return;
                current = id;
                items.forEach(function (it) {
                    it.link.classList.toggle('active', it.id === id);
                });
            };
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (en) {
                    if (en.isIntersecting) setActive(en.target.id);
                });
            }, { rootMargin: '-80px 0px -70% 0px', threshold: 0 });
            headings.forEach(function (h) { observer.observe(h); });
        }
    }

    /* ---------------------------------------------------------
       代码块：语言标签 + 复制按钮 + 轻量内置语法高亮（无第三方依赖）
       --------------------------------------------------------- */
    function escapeHtml(s) {
        return s.replace(/[&<>]/g, function (c) {
            return c === '&' ? '&amp;' : c === '<' ? '&lt;' : '&gt;';
        });
    }

    // 各语言关键字集合（合并通用 + 常见语言，命中即着色，未覆盖语言回落为纯文本）
    var KW = {
        php: 'abstract and array as break callable case catch class clone const continue declare default do echo else elseif enddeclare endfor endforeach endif enum extends final finally fn for foreach function global goto if implements include include_once instanceof interface isset list match namespace new or print private protected public readonly require require_once return static switch throw trait try unset use var while xor yield true false null',
        js: 'async await break case catch class const continue debugger default delete do else enum export extends finally for from function if import in instanceof interface let new of return static switch this throw try typeof var void while with yield true false null undefined',
        css: 'important media supports keyframes import charset',
        python: 'and as assert async await break class continue def del elif else except finally for from global if import in is lambda nonlocal not or pass raise return try while with yield True False None self',
        shell: 'if then else elif fi for while until do done case esac function export local return source echo cd ls cp mv rm mkdir touch chmod chown grep sed awk cat echo',
        html: '',
        _common: 'if else for while return function true false null var let const'
    };

    // 常见别名归一化到 KW 键名
    var LANG_ALIAS = {
        js: 'js', javascript: 'js', ts: 'js', typescript: 'js', jsx: 'js',
        py: 'python', python3: 'python',
        sh: 'shell', bash: 'shell', zsh: 'shell', console: 'shell',
        xml: 'html', svg: 'html', vue: 'html'
    };

    function buildKeywordRe(lang) {
        var key = LANG_ALIAS[lang] || lang;
        var set = KW[key] || '';
        // 未知语言时给一个最小通用集，避免整块无高亮
        if (set === '' && !KW.hasOwnProperty(key)) set = KW._common;
        var words = (set + ' ' + KW._common).trim().split(/\s+/).filter(Boolean);
        words = words.filter(function (w, i, a) { return a.indexOf(w) === i; });
        return new RegExp('^(?:' + words.join('|') + ')$');
    }

    // 极简分词：注释 / 字符串 / 数字 / 函数名 / 标识符(关键字) / HTML 标签
    function highlightBlock(code, lang) {
        var raw = code.textContent;
        var isHtml = (lang === 'html' || lang === 'xml' || lang === 'svg');
        var kwRe = buildKeywordRe(lang);
        var out = '';
        var i = 0, n = raw.length;

        function push(cls, text) {
            var e = escapeHtml(text);
            out += cls ? '<span class="token ' + cls + '">' + e + '</span>' : e;
        }

        while (i < n) {
            var ch = raw[i];
            var rest = raw.slice(i);
            var m;

            // 块注释
            if (ch === '/' && raw[i + 1] === '*') {
                m = rest.match(/^\u002F\*[\s\S]*?(?:\*\/|$)/);
                if (m) { push('comment', m[0]); i += m[0].length; continue; }
            }
            // 行注释 // 和 #（# 不用于 css 颜色，仅 php/js/sh）
            if (ch === '/' && raw[i + 1] === '/') {
                m = rest.match(/^\/\/[^\n]*/); push('comment', m[0]); i += m[0].length; continue;
            }
            if (ch === '#' && !isHtml && lang !== 'css') {
                m = rest.match(/^#[^\n]*/); push('comment', m[0]); i += m[0].length; continue;
            }
            // HTML 注释
            if (isHtml && ch === '<' && raw.slice(i, i + 4) === '<!--') {
                m = rest.match(/^<!--[\s\S]*?(?:-->|$)/); push('comment', m[0]); i += m[0].length; continue;
            }
            // 字符串
            if (ch === '"' || ch === "'" || ch === '`') {
                m = rest.match(new RegExp('^\\' + ch + '(?:\\\\.|[^\\\\' + ch + '])*(?:' + ch + '|$)'));
                if (m) { push('string', m[0]); i += m[0].length; continue; }
            }
            // HTML 标签
            if (isHtml && ch === '<') {
                m = rest.match(/^<\/?[\w-]+(?:[^>]*)>?/);
                if (m) { push('tag', m[0]); i += m[0].length; continue; }
            }
            // 数字
            if (/[0-9]/.test(ch) && !/[A-Za-z0-9_]/.test(raw[i - 1] || '')) {
                m = rest.match(/^\d[\w.]*/);
                if (m) { push('number', m[0]); i += m[0].length; continue; }
            }
            // 标识符 / 关键字 / 函数名
            if (/[A-Za-z_$]/.test(ch)) {
                m = rest.match(/^[A-Za-z0-9_$]+/);
                var word = m[0];
                if (kwRe.test(word)) push('keyword', word);
                else if (raw[i + word.length] === '(') push('function', word);
                else out += escapeHtml(word);
                i += word.length; continue;
            }
            // 其他字符
            out += escapeHtml(ch); i++;
        }
        code.innerHTML = out;
    }

    function initCodeBlocks() {
        var body = document.querySelector('.article-body');
        if (!body) return;
        var cfg = getConfig();
        var pres = body.querySelectorAll('pre');
        Array.prototype.forEach.call(pres, function (pre) {
            // 仅看紧邻的前一个兄弟节点是否为工具栏，避免同父多块时被误判跳过
            var prev = pre.previousElementSibling;
            if (prev && prev.classList && prev.classList.contains('code-toolbar')) return;

            var code = pre.querySelector('code');
            var lang = '';
            if (code && code.className) {
                var m = code.className.match(/(?:language|lang)-([\w-]+)/);
                if (m) lang = m[1];
            }

            // 先做语法高亮（基于原始文本），再注入工具栏
            if (cfg.codeHighlight && code && lang) {
                try { highlightBlock(code, lang); } catch (e) {}
            }

            var toolbar = document.createElement('div');
            toolbar.className = 'code-toolbar';
            toolbar.innerHTML =
                '<span class="code-lang">' + (lang || 'code') + '</span>' +
                '<button class="code-copy" type="button">复制</button>';

            // 工具栏独立于 pre 之上，不将 pre 纳入 flex 容器，避免版式错乱
            pre.parentNode.insertBefore(toolbar, pre);

            var btn = toolbar.querySelector('.code-copy');
            btn.addEventListener('click', function () {
                var text = code ? code.textContent : pre.textContent;
                copyText(text, btn);
            });
        });
    }

    function copyText(text, btn) {
        var done = function () {
            btn.textContent = '已复制';
            setTimeout(function () { btn.textContent = '复制'; }, 1600);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done, function () { fallback(); });
        } else {
            fallback();
        }
        function fallback() {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); done(); } catch (e) {}
            document.body.removeChild(ta);
        }
    }

    /* ---------------------------------------------------------
       图片懒加载：为正文图片补 loading="lazy"
       --------------------------------------------------------- */
    function initLazyImages() {
        var imgs = document.querySelectorAll('.article-body img, .post-card img');
        Array.prototype.forEach.call(imgs, function (img) {
            if (!img.hasAttribute('loading')) img.setAttribute('loading', 'lazy');
        });
    }

    /* ---------------------------------------------------------
       评论：取消回复、回复时填充作者
       --------------------------------------------------------- */
    function initComments() {
        var cancelLink = document.getElementById('cancel-comment-reply-link');
        if (cancelLink) {
            cancelLink.addEventListener('click', function () {
                var form = document.querySelector('.comment-form');
                if (form) form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        }
    }

    /* ---------------------------------------------------------
       任务清单：将正文列表项开头的 [ ] / [x] 字面量渲染为复选框
       --------------------------------------------------------- */
    function initTaskLists() {
        var body = document.querySelector('.article-body');
        if (!body) return;
        var items = body.querySelectorAll('li');
        Array.prototype.forEach.call(items, function (li) {
            var text = li.textContent.replace(/^\s+/, '');
            var m = text.match(/^\[( |x|X)\]\s?([\s\S]*)$/);
            if (!m) return;
            // 避免重复处理
            if (li.classList.contains('task-item')) return;
            li.classList.add('task-item');
            li.classList.add(m[1] === ' ' ? 'task-open' : 'task-done');
            var box = document.createElement('span');
            box.className = 'task-check';
            box.setAttribute('aria-hidden', 'true');
            var label = document.createElement('span');
            label.className = 'task-text';
            label.textContent = m[2];
            li.textContent = '';
            li.appendChild(box);
            li.appendChild(label);
        });
    }

    /* ---------------------------------------------------------
       表格：包裹横向滚动容器，避免窄屏溢出
       --------------------------------------------------------- */
    function initTableWrap() {
        var body = document.querySelector('.article-body');
        if (!body) return;
        var tables = body.querySelectorAll('table');
        Array.prototype.forEach.call(tables, function (t) {
            if (t.parentNode && t.parentNode.classList.contains('table-wrap')) return;
            var wrap = document.createElement('div');
            wrap.className = 'table-wrap';
            t.parentNode.insertBefore(wrap, t);
            wrap.appendChild(t);
        });
    }

    /* ---------------------------------------------------------
       启动
       --------------------------------------------------------- */
    function boot() {
        initThemeToggle();
        initNavScroll();
        initBurger();
        initBackToTop();
        initToc();
        initCodeBlocks();
        initTaskLists();
        initTableWrap();
        initLazyImages();
        initComments();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
