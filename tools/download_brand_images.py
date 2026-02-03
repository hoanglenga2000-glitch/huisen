#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
============================================
手机品牌官网图片批量下载脚本
从各大手机品牌官方网站抓取高清产品图片
============================================

使用方法：
1. 安装依赖：
   pip install playwright requests Pillow
   playwright install chromium

2. 运行脚本：
   python download_brand_images.py

3. 按提示输入品牌关键词（如：华为、苹果、xiaomi）

功能：
- 支持10个主流手机品牌
- 使用 Playwright 浏览器自动化处理 JavaScript 渲染
- 自动下载高清产品图片
- 保存到 images/图片素材/{品牌}/ 目录
"""

import os
import re
import sys
import io
import time
import random
import asyncio
import hashlib
import requests
from pathlib import Path
from urllib.parse import urljoin, urlparse
from PIL import Image
from io import BytesIO

# Windows 控制台 UTF-8 支持
if sys.platform == 'win32':
    try:
        sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
        sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')
    except:
        pass

# ============================================
# 配置
# ============================================

# 图片保存根目录
SAVE_DIR = Path(__file__).parent / 'images' / '图片素材'

# 请求头
HEADERS = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept': 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
    'Accept-Language': 'zh-CN,zh;q=0.9,en;q=0.8',
    'Referer': 'https://www.google.com/',
}

# 品牌配置
# 每个品牌包含：
#   - url: 产品列表页地址
#   - name_cn: 中文名
#   - name_en: 英文名
#   - selectors: CSS 选择器列表（用于定位产品图片）
#   - link_selectors: 产品详情页链接选择器（可选）
BRAND_CONFIG = {
    "苹果": {
        "url": "https://www.apple.com/iphone/",
        "name_cn": "苹果",
        "name_en": "Apple",
        "aliases": ["apple", "iphone"],
        "selectors": [
            "img.overview-hero-hero-image",
            "img[alt*='iPhone']",
            ".hero-content img",
            ".gallery-image img",
            "picture img",
        ],
        "min_width": 300,
    },
    "华为": {
        "url": "https://consumer.huawei.com/cn/phones/",
        "name_cn": "华为",
        "name_en": "HUAWEI",
        "aliases": ["huawei", "hw"],
        "selectors": [
            ".product-card img",
            ".product-image img",
            "img[alt*='HUAWEI']",
            ".c-product-card__img img",
            ".product-list img",
        ],
        "min_width": 200,
    },
    "荣耀": {
        "url": "https://www.honor.cn/products/phones/",
        "name_cn": "荣耀",
        "name_en": "HONOR",
        "aliases": ["honor"],
        "selectors": [
            ".product-card img",
            ".product-item img",
            "img[alt*='HONOR']",
            ".product-image img",
        ],
        "min_width": 200,
    },
    "vivo": {
        "url": "https://www.vivo.com.cn/products",
        "name_cn": "vivo",
        "name_en": "vivo",
        "aliases": ["vivo"],
        "selectors": [
            ".product-card img",
            ".product-item img",
            ".phone-list img",
            "img[alt*='vivo']",
        ],
        "min_width": 200,
    },
    "iQOO": {
        "url": "https://www.iqoo.com/product",
        "name_cn": "iQOO",
        "name_en": "iQOO",
        "aliases": ["iqoo"],
        "selectors": [
            ".product-card img",
            ".product-item img",
            "img[alt*='iQOO']",
        ],
        "min_width": 200,
    },
    "小米": {
        "url": "https://www.mi.com/shop/buy/phone",
        "name_cn": "小米",
        "name_en": "Xiaomi",
        "aliases": ["xiaomi", "mi"],
        "selectors": [
            ".product-card img",
            ".goods-item img",
            "img[alt*='小米']",
            ".product-list img",
            ".card-product img",
        ],
        "min_width": 200,
    },
    "红米": {
        "url": "https://www.mi.com/shop/buy/phone",
        "name_cn": "红米",
        "name_en": "Redmi",
        "aliases": ["redmi"],
        "selectors": [
            ".product-card img",
            ".goods-item img",
            "img[alt*='Redmi']",
            ".product-list img",
        ],
        "min_width": 200,
    },
    "OPPO": {
        "url": "https://www.oppo.com/cn/smartphones/",
        "name_cn": "OPPO",
        "name_en": "OPPO",
        "aliases": ["oppo"],
        "selectors": [
            ".product-card img",
            ".product-item img",
            "img[alt*='OPPO']",
            ".phone-item img",
        ],
        "min_width": 200,
    },
    "一加": {
        "url": "https://www.oneplus.com/cn/phones",
        "name_cn": "一加",
        "name_en": "OnePlus",
        "aliases": ["oneplus"],
        "selectors": [
            ".product-card img",
            ".product-item img",
            "img[alt*='OnePlus']",
            ".phone-card img",
        ],
        "min_width": 200,
    },
    "三星": {
        "url": "https://www.samsung.com/cn/mobile/phones/",
        "name_cn": "三星",
        "name_en": "Samsung",
        "aliases": ["samsung", "galaxy"],
        "selectors": [
            ".product-card img",
            ".product-item img",
            "img[alt*='Galaxy']",
            ".product-card__img img",
            "[data-component='ProductCard'] img",
        ],
        "min_width": 200,
    },
}

# ============================================
# 工具函数
# ============================================

def find_brand(keyword: str) -> dict | None:
    """根据关键词查找品牌配置"""
    keyword_lower = keyword.lower().strip()
    
    for brand_key, config in BRAND_CONFIG.items():
        # 检查主键
        if brand_key.lower() == keyword_lower:
            return config
        # 检查中文名
        if config["name_cn"] == keyword:
            return config
        # 检查英文名
        if config["name_en"].lower() == keyword_lower:
            return config
        # 检查别名
        if keyword_lower in [a.lower() for a in config.get("aliases", [])]:
            return config
    
    return None


def clean_filename(name: str) -> str:
    """清理文件名，移除非法字符"""
    # 移除 URL 参数
    name = name.split('?')[0]
    # 获取文件名部分
    name = os.path.basename(name)
    # 替换非法字符
    name = re.sub(r'[\\/:*?"<>|]', '_', name)
    name = re.sub(r'\s+', '_', name)
    name = re.sub(r'_+', '_', name)
    return name.strip('_') or 'image'


def get_file_extension(url: str, content_type: str = '') -> str:
    """获取图片扩展名"""
    # 从 URL 获取
    parsed = urlparse(url)
    path = parsed.path.lower()
    
    if '.jpg' in path or '.jpeg' in path:
        return '.jpg'
    elif '.png' in path:
        return '.png'
    elif '.webp' in path:
        return '.webp'
    elif '.gif' in path:
        return '.gif'
    
    # 从 content-type 获取
    if 'jpeg' in content_type or 'jpg' in content_type:
        return '.jpg'
    elif 'png' in content_type:
        return '.png'
    elif 'webp' in content_type:
        return '.webp'
    elif 'gif' in content_type:
        return '.gif'
    
    return '.jpg'  # 默认


def download_image(url: str, save_path: Path, min_size: int = 5000) -> bool:
    """下载并保存图片"""
    try:
        # 跳过 base64 或 data URL
        if url.startswith('data:'):
            return False
        
        response = requests.get(url, headers=HEADERS, timeout=20, stream=True)
        response.raise_for_status()
        
        content = response.content
        
        # 检查大小
        if len(content) < min_size:
            print(f"    ✗ 图片太小: {len(content)} bytes")
            return False
        
        # 验证图片
        try:
            img = Image.open(BytesIO(content))
            
            # 检查尺寸
            if img.width < 150 or img.height < 150:
                print(f"    ✗ 尺寸太小: {img.width}x{img.height}")
                return False
            
            # 转换 RGBA 到 RGB
            if img.mode in ('RGBA', 'P', 'LA'):
                background = Image.new('RGB', img.size, (255, 255, 255))
                if img.mode == 'P':
                    img = img.convert('RGBA')
                background.paste(img, mask=img.split()[-1] if 'A' in img.mode else None)
                img = background
            elif img.mode != 'RGB':
                img = img.convert('RGB')
            
            # 保存为 JPEG
            save_path = save_path.with_suffix('.jpg')
            img.save(save_path, 'JPEG', quality=92, optimize=True)
            
            print(f"    ✓ 已保存: {save_path.name} ({img.width}x{img.height})")
            return True
            
        except Exception as e:
            print(f"    ✗ 图片处理失败: {e}")
            return False
            
    except Exception as e:
        print(f"    ✗ 下载失败: {str(e)[:60]}")
        return False


# ============================================
# Playwright 爬虫
# ============================================

async def scrape_with_playwright(config: dict) -> list[str]:
    """使用 Playwright 抓取页面图片"""
    try:
        from playwright.async_api import async_playwright
    except ImportError:
        print("❌ 未安装 Playwright，请运行:")
        print("   pip install playwright")
        print("   playwright install chromium")
        return []
    
    image_urls = []
    brand_name = config["name_cn"]
    
    print(f"\n🌐 正在访问 {brand_name} 官网...")
    print(f"   URL: {config['url']}")
    
    async with async_playwright() as p:
        # 启动浏览器
        browser = await p.chromium.launch(
            headless=False,  # 显示浏览器窗口便于调试
            args=['--disable-blink-features=AutomationControlled']
        )
        
        context = await browser.new_context(
            viewport={'width': 1920, 'height': 1080},
            user_agent='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            locale='zh-CN',
        )
        
        page = await context.new_page()
        
        try:
            # 访问页面
            await page.goto(config['url'], wait_until='networkidle', timeout=30000)
            
            # 等待页面加载
            await page.wait_for_timeout(3000)
            
            # 滚动页面加载懒加载图片
            print("   📜 滚动页面加载图片...")
            for _ in range(5):
                await page.evaluate('window.scrollBy(0, window.innerHeight)')
                await page.wait_for_timeout(800)
            
            # 回到顶部
            await page.evaluate('window.scrollTo(0, 0)')
            await page.wait_for_timeout(500)
            
            # 收集图片 URL
            print("   🔍 查找产品图片...")
            
            # 尝试所有选择器
            for selector in config.get('selectors', []):
                try:
                    images = await page.query_selector_all(selector)
                    for img in images:
                        src = await img.get_attribute('src')
                        srcset = await img.get_attribute('srcset')
                        data_src = await img.get_attribute('data-src')
                        
                        # 优先使用 srcset 中的高清图
                        if srcset:
                            # 解析 srcset，取最大的
                            parts = srcset.split(',')
                            for part in parts:
                                part = part.strip()
                                if ' ' in part:
                                    url = part.split()[0]
                                else:
                                    url = part
                                if url and url.startswith(('http', '//')):
                                    if url.startswith('//'):
                                        url = 'https:' + url
                                    image_urls.append(url)
                        
                        # 使用 data-src（懒加载）
                        if data_src and data_src.startswith(('http', '//')):
                            url = data_src if not data_src.startswith('//') else 'https:' + data_src
                            image_urls.append(url)
                        
                        # 使用 src
                        if src and src.startswith(('http', '//')):
                            url = src if not src.startswith('//') else 'https:' + src
                            image_urls.append(url)
                            
                except Exception as e:
                    continue
            
            # 如果选择器没找到，尝试获取所有图片
            if not image_urls:
                print("   ⚠️ 选择器未匹配，尝试获取所有图片...")
                all_images = await page.query_selector_all('img')
                
                for img in all_images:
                    try:
                        src = await img.get_attribute('src')
                        if src and src.startswith(('http', '//')):
                            # 过滤掉明显不是产品图的
                            lower_src = src.lower()
                            if not any(skip in lower_src for skip in ['icon', 'logo', 'avatar', 'banner', 'sprite', 'loading', '1x1', 'pixel']):
                                url = src if not src.startswith('//') else 'https:' + src
                                image_urls.append(url)
                    except:
                        continue
            
            print(f"   ✅ 找到 {len(image_urls)} 个图片链接")
            
        except Exception as e:
            print(f"   ❌ 页面访问失败: {e}")
        
        finally:
            await browser.close()
    
    # 去重
    seen = set()
    unique_urls = []
    for url in image_urls:
        # 简单去重（忽略参数）
        clean_url = url.split('?')[0]
        if clean_url not in seen:
            seen.add(clean_url)
            unique_urls.append(url)
    
    return unique_urls


# ============================================
# 主函数
# ============================================

def download_brand_images(brand_keyword: str) -> int:
    """下载指定品牌的图片"""
    # 查找品牌
    config = find_brand(brand_keyword)
    if not config:
        print(f"❌ 未找到品牌: {brand_keyword}")
        print("\n支持的品牌:")
        for key in BRAND_CONFIG.keys():
            c = BRAND_CONFIG[key]
            print(f"  - {key} ({c['name_en']})")
        return 0
    
    brand_name = config["name_cn"]
    print(f"\n{'='*60}")
    print(f"📱 开始下载 {brand_name} ({config['name_en']}) 官方图片")
    print(f"{'='*60}")
    
    # 创建保存目录
    save_dir = SAVE_DIR / brand_name
    save_dir.mkdir(parents=True, exist_ok=True)
    print(f"📁 保存目录: {save_dir}")
    
    # 使用 Playwright 抓取
    image_urls = asyncio.run(scrape_with_playwright(config))
    
    if not image_urls:
        print(f"\n❌ 未找到图片，请检查网络连接或稍后重试")
        return 0
    
    # 下载图片
    print(f"\n⬇️ 开始下载 {len(image_urls)} 张图片...")
    success = 0
    
    for i, url in enumerate(image_urls, 1):
        print(f"\n[{i}/{len(image_urls)}] {url[:80]}...")
        
        # 生成文件名
        url_hash = hashlib.md5(url.encode()).hexdigest()[:8]
        base_name = clean_filename(url)
        if len(base_name) > 50:
            base_name = base_name[:50]
        filename = f"{brand_name}_{base_name}_{url_hash}"
        save_path = save_dir / filename
        
        if download_image(url, save_path):
            success += 1
        
        # 随机延迟
        if i < len(image_urls):
            delay = random.uniform(0.5, 1.5)
            time.sleep(delay)
    
    print(f"\n{'='*60}")
    print(f"✅ 下载完成！成功: {success}/{len(image_urls)}")
    print(f"📁 图片保存在: {save_dir}")
    print(f"{'='*60}")
    
    return success


def main():
    """主入口"""
    print("""
╔════════════════════════════════════════════════════════════╗
║     📱 手机品牌官网图片批量下载工具                          ║
╠════════════════════════════════════════════════════════════╣
║  支持品牌:                                                   ║
║    苹果(Apple)  华为(HUAWEI)  荣耀(HONOR)  vivo            ║
║    iQOO  小米(Xiaomi)  红米(Redmi)  OPPO                   ║
║    一加(OnePlus)  三星(Samsung)                             ║
╠════════════════════════════════════════════════════════════╣
║  使用方法:                                                   ║
║    1. 输入品牌名称（中文或英文均可）                         ║
║    2. 输入 'all' 下载所有品牌                                ║
║    3. 输入 'q' 或 'exit' 退出                               ║
╚════════════════════════════════════════════════════════════╝
""")
    
    # 确保 Playwright 已安装
    try:
        from playwright.async_api import async_playwright
    except ImportError:
        print("⚠️ 首次运行需要安装 Playwright:")
        print("   pip install playwright")
        print("   playwright install chromium")
        print("\n请安装后重新运行脚本。")
        return
    
    while True:
        print()
        keyword = input("🔍 请输入品牌名称 (输入 q 退出): ").strip()
        
        if not keyword:
            continue
        
        if keyword.lower() in ('q', 'quit', 'exit'):
            print("\n👋 再见！")
            break
        
        if keyword.lower() == 'all':
            print("\n⚠️ 批量下载所有品牌图片，这可能需要较长时间...")
            confirm = input("确认继续? (y/n): ").strip().lower()
            if confirm == 'y':
                total = 0
                for brand in BRAND_CONFIG.keys():
                    total += download_brand_images(brand)
                    time.sleep(3)  # 品牌间延迟
                print(f"\n🎉 全部完成！共下载 {total} 张图片")
            continue
        
        # 下载单个品牌
        download_brand_images(keyword)


if __name__ == '__main__':
    main()
