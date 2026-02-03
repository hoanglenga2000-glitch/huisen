/**
 * ==========================================
 * 汇森科技 - 公共JS功能
 * 专业电商交互效果
 * ==========================================
 */

(function() {
    'use strict';

    // ========== 全局配置 ==========
    const CONFIG = {
        apiBase: '/huisen/api/',
        animationDuration: 600,
        debounceDelay: 300
    };

    // ========== 工具函数 ==========
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    function formatPrice(price) {
        return '¥' + parseFloat(price).toLocaleString('zh-CN');
    }

    // ========== 购物车动画 ==========
    window.flyToCart = function(element, imageUrl) {
        const img = element.querySelector('img') || element;
        const rect = img.getBoundingClientRect();

        // 创建飞行元素
        const flyEl = document.createElement('div');
        flyEl.className = 'fly-to-cart';
        flyEl.innerHTML = `<img src="${imageUrl || ''}" style="width: 60px; height: 60px; object-fit: contain; border-radius: 8px; background: #f5f5f5;">`;

        // 初始位置
        flyEl.style.cssText = `
            position: fixed;
            left: ${rect.left}px;
            top: ${rect.top}px;
            z-index: 9999;
            pointer-events: none;
        `;

        document.body.appendChild(flyEl);

        // 获取购物车图标位置
        const cartIcon = document.querySelector('.sidebar-tool-item[data-tool="cart"]') ||
                         document.querySelector('.cart-icon') ||
                         document.querySelector('[href*="cart"]');

        let targetX = window.innerWidth - 80;
        let targetY = window.innerHeight / 2;

        if (cartIcon) {
            const cartRect = cartIcon.getBoundingClientRect();
            targetX = cartRect.left + cartRect.width / 2 - 30;
            targetY = cartRect.top + cartRect.height / 2 - 30;
        }

        // 执行动画
        requestAnimationFrame(() => {
            flyEl.style.transition = 'all 0.6s cubic-bezier(0.2, 1, 0.3, 1)';
            flyEl.style.left = targetX + 'px';
            flyEl.style.top = targetY + 'px';
            flyEl.style.transform = 'scale(0.3)';
            flyEl.style.opacity = '0.5';
        });

        // 动画结束后移除元素并更新购物车徽章
        setTimeout(() => {
            flyEl.remove();
            updateCartBadge();
        }, CONFIG.animationDuration);
    };

    // 更新购物车徽章
    function updateCartBadge() {
        const badges = document.querySelectorAll('.cart-badge, .sidebar-tool-item[data-tool="cart"] .badge');
        badges.forEach(badge => {
            badge.classList.add('cart-badge-bounce');
            const currentCount = parseInt(badge.textContent) || 0;
            badge.textContent = currentCount + 1;
            setTimeout(() => badge.classList.remove('cart-badge-bounce'), 500);
        });
    }

    // ========== 回到顶部 ==========
    window.scrollToTop = function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    };

    // 监听滚动显示/隐藏回到顶部按钮
    function initScrollTop() {
        const backToTopBtn = document.querySelector('.sidebar-tool-item[data-tool="top"]');
        if (!backToTopBtn) return;

        window.addEventListener('scroll', debounce(() => {
            if (window.scrollY > 300) {
                backToTopBtn.style.opacity = '1';
                backToTopBtn.style.pointerEvents = 'auto';
            } else {
                backToTopBtn.style.opacity = '0.5';
            }
        }, 100));
    }

    // ========== 搜索自动补全 ==========
    function initSearchAutocomplete() {
        const searchInputs = document.querySelectorAll('input[name="search"], input[name="q"]');

        searchInputs.forEach(input => {
            const wrapper = input.closest('form') || input.parentElement;

            // 创建自动补全容器
            let autocompleteEl = wrapper.querySelector('.search-autocomplete');
            if (!autocompleteEl) {
                autocompleteEl = document.createElement('div');
                autocompleteEl.className = 'search-autocomplete';
                wrapper.style.position = 'relative';
                wrapper.appendChild(autocompleteEl);
            }

            // 输入事件
            input.addEventListener('input', debounce(async (e) => {
                const query = e.target.value.trim();

                if (query.length < 2) {
                    autocompleteEl.classList.remove('active');
                    return;
                }

                try {
                    const response = await fetch(`${CONFIG.apiBase}suggest.php?q=${encodeURIComponent(query)}`);
                    const data = await response.json();

                    if (data.suggestions && data.suggestions.length > 0) {
                        renderAutocomplete(autocompleteEl, data.suggestions, input);
                    } else {
                        autocompleteEl.classList.remove('active');
                    }
                } catch (err) {
                    console.log('Autocomplete fetch failed:', err);
                    autocompleteEl.classList.remove('active');
                }
            }, CONFIG.debounceDelay));

            // 失去焦点时隐藏
            input.addEventListener('blur', () => {
                setTimeout(() => autocompleteEl.classList.remove('active'), 200);
            });

            // 获得焦点时显示
            input.addEventListener('focus', () => {
                if (autocompleteEl.children.length > 0) {
                    autocompleteEl.classList.add('active');
                }
            });
        });
    }

    function renderAutocomplete(container, suggestions, input) {
        container.innerHTML = suggestions.map(item => `
            <div class="autocomplete-item" data-value="${item.name || item.model_name || item}">
                ${item.image_url ? `<img class="thumb" src="${item.image_url}" alt="">` : ''}
                <div class="info">
                    <div class="name">${item.name || item.model_name || item}</div>
                    ${item.price ? `<div class="price">${formatPrice(item.price)}</div>` : ''}
                </div>
            </div>
        `).join('');

        container.classList.add('active');

        // 点击建议项
        container.querySelectorAll('.autocomplete-item').forEach(item => {
            item.addEventListener('click', () => {
                input.value = item.dataset.value;
                container.classList.remove('active');
                input.closest('form')?.submit();
            });
        });
    }

    // ========== 热搜标签点击 ==========
    function initHotSearch() {
        document.querySelectorAll('.hot-search .tag, .hot-search a').forEach(tag => {
            tag.addEventListener('click', (e) => {
                e.preventDefault();
                const keyword = tag.textContent.trim();
                const searchInput = document.querySelector('input[name="search"], input[name="q"]');

                if (searchInput) {
                    searchInput.value = keyword;
                    searchInput.closest('form')?.submit();
                } else {
                    window.location.href = `quotes_v6.php?search=${encodeURIComponent(keyword)}`;
                }
            });
        });
    }

    // ========== 筛选器交互 ==========
    function initFilters() {
        // 价格区间确认
        const priceConfirmBtn = document.querySelector('.price-range-confirm');
        if (priceConfirmBtn) {
            priceConfirmBtn.addEventListener('click', () => {
                const minPrice = document.querySelector('input[name="price_min"]')?.value || '';
                const maxPrice = document.querySelector('input[name="price_max"]')?.value || '';

                const url = new URL(window.location);
                if (minPrice) url.searchParams.set('price_min', minPrice);
                if (maxPrice) url.searchParams.set('price_max', maxPrice);

                window.location.href = url.toString();
            });
        }

        // 存储容量筛选
        document.querySelectorAll('.filter-option[data-storage]').forEach(option => {
            option.addEventListener('click', () => {
                const storage = option.dataset.storage;
                const url = new URL(window.location);

                if (option.classList.contains('active')) {
                    url.searchParams.delete('storage');
                } else {
                    url.searchParams.set('storage', storage);
                }

                window.location.href = url.toString();
            });
        });

        // 排序选择
        document.querySelectorAll('.sort-option[data-sort]').forEach(option => {
            option.addEventListener('click', () => {
                const sort = option.dataset.sort;
                const url = new URL(window.location);
                url.searchParams.set('sort', sort);
                window.location.href = url.toString();
            });
        });
    }

    // ========== 侧边工具栏交互 ==========
    function initSidebarTools() {
        // 回到顶部
        document.querySelector('.sidebar-tool-item[data-tool="top"]')?.addEventListener('click', scrollToTop);

        // 客服弹窗
        document.querySelector('.sidebar-tool-item[data-tool="service"]')?.addEventListener('click', () => {
            // 可以替换为实际的客服系统
            alert('客服热线: 400-XXX-XXXX\n微信: huisen_tech\n工作时间: 9:00-21:00');
        });

        // 购物车悬停预览 - 已在CSS中通过hover实现
    }

    // ========== 加入询价单功能 ==========
    window.addToCart = async function(productId, skuId, quantity = 1, element = null) {
        try {
            const response = await fetch(`${CONFIG.apiBase}cart_add.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    sku_id: skuId,
                    quantity: quantity
                })
            });

            const data = await response.json();

            if (data.success) {
                // 播放飞入动画
                if (element) {
                    const img = element.closest('.product-card')?.querySelector('img') ||
                               document.querySelector('.product-main-image img');
                    if (img) {
                        flyToCart(element, img.src);
                    }
                }

                // 显示成功提示
                showToast('已加入询价单');
                return true;
            } else {
                showToast(data.message || '添加失败', 'error');
                return false;
            }
        } catch (err) {
            console.error('Add to cart failed:', err);
            showToast('网络错误，请重试', 'error');
            return false;
        }
    };

    // ========== Toast 提示 ==========
    window.showToast = function(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 24px;
            background: ${type === 'success' ? '#10b981' : '#ef4444'};
            color: white;
            border-radius: 8px;
            font-size: 14px;
            z-index: 10000;
            animation: toastIn 0.3s ease;
        `;
        toast.textContent = message;

        // 添加动画样式
        if (!document.querySelector('#toast-styles')) {
            const style = document.createElement('style');
            style.id = 'toast-styles';
            style.textContent = `
                @keyframes toastIn {
                    from { opacity: 0; transform: translateX(-50%) translateY(-20px); }
                    to { opacity: 1; transform: translateX(-50%) translateY(0); }
                }
                @keyframes toastOut {
                    from { opacity: 1; transform: translateX(-50%) translateY(0); }
                    to { opacity: 0; transform: translateX(-50%) translateY(-20px); }
                }
            `;
            document.head.appendChild(style);
        }

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'toastOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    };

    // ========== 图片懒加载增强 ==========
    function initLazyLoad() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                        }
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }

    // ========== 初始化 ==========
    function init() {
        initScrollTop();
        initSearchAutocomplete();
        initHotSearch();
        initFilters();
        initSidebarTools();
        initLazyLoad();

        console.log('汇森科技 - 公共JS已加载');
    }

    // DOM Ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
