#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import sys
import io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')
"""
==========================================
汇森科技 - 手机图片智能爬虫
==========================================

功能：
1. 从数据库读取缺少图片的产品
2. 从Bing/Google搜索高清渲染图
3. MD5去重，确保每款手机图片唯一
4. 自动更新数据库

使用方法：
pip install requests beautifulsoup4 pymysql tqdm pillow
python image_hunter.py
"""

import os
import sys
import hashlib
import time
import random
import re
import requests
from bs4 import BeautifulSoup
from urllib.parse import quote, urljoin
from pathlib import Path

# 尝试导入可选库
try:
    from tqdm import tqdm
    HAS_TQDM = True
except ImportError:
    HAS_TQDM = False
    print("[提示] 未安装tqdm，将使用简单进度显示。安装: pip install tqdm")

try:
    import pymysql
    HAS_PYMYSQL = True
except ImportError:
    HAS_PYMYSQL = False
    print("[错误] 未安装pymysql。请运行: pip install pymysql")

try:
    from PIL import Image
    from io import BytesIO
    HAS_PIL = True
except ImportError:
    HAS_PIL = False
    print("[提示] 未安装Pillow，将跳过图片质量检测。安装: pip install pillow")

# ============================================
# 配置
# ============================================
DB_CONFIG = {
    'host': 'localhost',
    'port': 3306,
    'user': 'huisen',
    'password': '123456',
    'database': '甘肃汇森',
    'charset': 'utf8mb4'
}

# 图片保存路径
SAVE_DIR = Path(r'C:\xampp\htdocs\huisen\images\auto_download')
SAVE_DIR.mkdir(parents=True, exist_ok=True)

# 已有图片目录（用于计算已存在图片的hash）
EXISTING_IMAGES_DIR = Path(r'C:\xampp\htdocs\huisen\images')

# 最小图片尺寸要求
MIN_WIDTH = 400
MIN_HEIGHT = 400
MIN_FILE_SIZE = 10 * 1024  # 10KB

# 请求头
HEADERS = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
    'Accept-Language': 'zh-CN,zh;q=0.9,en;q=0.8',
}

# ============================================
# 工具函数
# ============================================

def calculate_md5(data):
    """计算数据的MD5哈希"""
    return hashlib.md5(data).hexdigest()

def calculate_file_md5(filepath):
    """计算文件的MD5哈希"""
    try:
        with open(filepath, 'rb') as f:
            return calculate_md5(f.read())
    except:
        return None

def get_existing_hashes():
    """获取已存在图片的所有MD5哈希值"""
    hashes = set()

    # 扫描所有图片目录
    for img_dir in [EXISTING_IMAGES_DIR, SAVE_DIR]:
        if not img_dir.exists():
            continue
        for ext in ['*.jpg', '*.jpeg', '*.png', '*.webp', '*.gif']:
            for img_path in img_dir.rglob(ext):
                h = calculate_file_md5(img_path)
                if h:
                    hashes.add(h)

    print(f"[信息] 已扫描 {len(hashes)} 张现有图片的哈希值")
    return hashes

def is_valid_image(data):
    """检查图片是否有效且满足质量要求"""
    if len(data) < MIN_FILE_SIZE:
        return False, "文件太小"

    if HAS_PIL:
        try:
            img = Image.open(BytesIO(data))
            width, height = img.size
            if width < MIN_WIDTH or height < MIN_HEIGHT:
                return False, f"尺寸太小 {width}x{height}"
            # 检查是否是有效图片
            img.verify()
            return True, "OK"
        except Exception as e:
            return False, str(e)

    # 没有PIL，只检查文件头
    if data[:3] == b'\xff\xd8\xff':  # JPEG
        return True, "OK"
    if data[:8] == b'\x89PNG\r\n\x1a\n':  # PNG
        return True, "OK"
    if data[:4] == b'RIFF' and data[8:12] == b'WEBP':  # WebP
        return True, "OK"

    return False, "未知格式"

def clean_filename(name):
    """清理文件名，移除非法字符"""
    # 移除非法字符
    name = re.sub(r'[<>:"/\\|?*]', '', name)
    # 限制长度
    if len(name) > 50:
        name = name[:50]
    return name.strip()

def search_bing_images(query, num_results=5):
    """从Bing搜索图片URL"""
    urls = []

    search_url = f"https://www.bing.com/images/search?q={quote(query)}&qft=+filterui:imagesize-large+filterui:photo-photo&form=IRFLTR"

    try:
        response = requests.get(search_url, headers=HEADERS, timeout=15)
        response.raise_for_status()

        soup = BeautifulSoup(response.text, 'html.parser')

        # 查找图片链接
        for img in soup.find_all('a', class_='iusc'):
            try:
                import json
                m = img.get('m')
                if m:
                    data = json.loads(m)
                    img_url = data.get('murl', '')
                    if img_url and img_url.startswith('http'):
                        urls.append(img_url)
                        if len(urls) >= num_results:
                            break
            except:
                continue

        # 备用方法：直接查找img标签
        if not urls:
            for img in soup.find_all('img', src=True):
                src = img.get('src', '')
                if src.startswith('http') and any(ext in src.lower() for ext in ['.jpg', '.jpeg', '.png', '.webp']):
                    if 'bing.com' not in src and 'microsoft.com' not in src:
                        urls.append(src)
                        if len(urls) >= num_results:
                            break

    except Exception as e:
        print(f"  [Bing搜索失败] {e}")

    return urls

def search_baidu_images(query, num_results=5):
    """从百度搜索图片URL（备用）"""
    urls = []

    search_url = f"https://image.baidu.com/search/index?tn=baiduimage&word={quote(query)}"

    try:
        response = requests.get(search_url, headers=HEADERS, timeout=15)

        # 使用正则提取图片URL
        pattern = r'"objURL":"(https?://[^"]+)"'
        matches = re.findall(pattern, response.text)

        for url in matches[:num_results]:
            if url.startswith('http'):
                urls.append(url)

    except Exception as e:
        print(f"  [百度搜索失败] {e}")

    return urls

def download_image(url, existing_hashes, max_retries=2):
    """下载图片并验证"""
    for retry in range(max_retries):
        try:
            response = requests.get(url, headers=HEADERS, timeout=20, stream=True)
            response.raise_for_status()

            # 检查内容类型
            content_type = response.headers.get('Content-Type', '')
            if 'image' not in content_type.lower() and not any(ext in url.lower() for ext in ['.jpg', '.jpeg', '.png', '.webp']):
                return None, "非图片内容"

            data = response.content

            # 检查MD5是否已存在
            md5_hash = calculate_md5(data)
            if md5_hash in existing_hashes:
                return None, "图片已存在(MD5重复)"

            # 验证图片质量
            valid, msg = is_valid_image(data)
            if not valid:
                return None, msg

            return data, md5_hash

        except requests.exceptions.Timeout:
            if retry < max_retries - 1:
                time.sleep(1)
                continue
            return None, "下载超时"
        except Exception as e:
            return None, str(e)

    return None, "下载失败"

def get_search_keywords(product):
    """根据产品信息生成搜索关键词"""
    brand = product.get('brand', '')
    model = product.get('model_name', '')
    category = product.get('category', 'phone')

    # 基础关键词
    keywords = []

    # 针对不同品类优化关键词
    if category == 'watch' or '手表' in model or 'Watch' in model:
        keywords.append(f"{brand} {model} 智能手表 官方图")
        keywords.append(f"{model} smartwatch official")
        keywords.append(f"{brand} {model} 渲染图")
    elif category == 'tablet' or '平板' in model or 'Pad' in model or 'iPad' in model:
        keywords.append(f"{brand} {model} 平板电脑 官方图")
        keywords.append(f"{model} tablet official render")
    else:
        # 手机
        keywords.append(f"{brand} {model} 手机 官方渲染图")
        keywords.append(f"{model} official render")
        keywords.append(f"{brand} {model} 高清图")

    return keywords

# ============================================
# 主程序
# ============================================

def main():
    print("=" * 60)
    print("  汇森科技 - 手机图片智能爬虫 v1.0")
    print("=" * 60)
    print()

    if not HAS_PYMYSQL:
        print("[错误] 请先安装pymysql: pip install pymysql")
        return

    # 连接数据库
    print("[1/4] 连接数据库...")
    try:
        conn = pymysql.connect(**DB_CONFIG)
        cursor = conn.cursor(pymysql.cursors.DictCursor)
        print("  [OK] 数据库连接成功")
    except Exception as e:
        print(f"  [FAIL] 数据库连接失败: {e}")
        print("\n请检查:")
        print("  1. MySQL服务是否启动")
        print("  2. 数据库配置是否正确")
        return

    # 查询需要图片的产品
    print("\n[2/4] 查询所有产品，检查图片状态...")
    try:
        # 获取所有产品
        cursor.execute("""
            SELECT id, brand, model_name, category, image_url
            FROM products_spu_v3
            ORDER BY brand, model_name
        """)
        all_products = cursor.fetchall()
        print(f"  [OK] 数据库共 {len(all_products)} 个产品")

        # 检查哪些产品需要图片
        products = []
        seen_images = set()
        base_path = Path(r'C:\xampp\htdocs\huisen')

        for p in all_products:
            img_url = p.get('image_url', '')
            need_image = False

            # 情况1: 图片URL为空
            if not img_url or img_url.strip() == '':
                need_image = True
                reason = "无图片"
            # 情况2: 图片文件不存在
            elif not (base_path / img_url).exists():
                need_image = True
                reason = "文件不存在"
            # 情况3: 图片URL重复（多个产品用同一张图）
            elif img_url in seen_images:
                need_image = True
                reason = "图片重复"
            else:
                seen_images.add(img_url)

            if need_image:
                products.append(p)

        print(f"  [OK] 找到 {len(products)} 个需要图片的产品")

    except Exception as e:
        print(f"  [FAIL] 查询失败: {e}")
        products = []

    if not products:
        print("\n[完成] 所有产品都已有图片！")
        conn.close()
        return

    # 获取已存在图片的哈希值
    print("\n[3/4] 扫描现有图片...")
    existing_hashes = get_existing_hashes()

    # 开始爬取
    print(f"\n[4/4] 开始爬取图片...")
    print("-" * 60)

    success_count = 0
    fail_count = 0
    skip_count = 0

    # 使用tqdm或简单进度
    if HAS_TQDM:
        iterator = tqdm(products, desc="爬取进度", unit="个")
    else:
        iterator = products

    for idx, product in enumerate(iterator):
        product_id = product['id']
        brand = product['brand']
        model = product['model_name']
        category = product.get('category', 'phone')

        if not HAS_TQDM:
            print(f"\n[{idx+1}/{len(products)}] {brand} {model}")

        # 生成搜索关键词
        keywords_list = get_search_keywords(product)

        image_saved = False

        for keywords in keywords_list:
            if image_saved:
                break

            # 搜索图片
            urls = search_bing_images(keywords, num_results=8)

            # 如果Bing没结果，尝试百度
            if not urls:
                urls = search_baidu_images(keywords, num_results=5)

            if not urls:
                continue

            # 尝试下载
            for url in urls:
                if image_saved:
                    break

                data, result = download_image(url, existing_hashes)

                if data is None:
                    continue

                # 保存图片
                try:
                    # 生成文件名
                    clean_name = clean_filename(f"{brand}_{model}")

                    # 确定扩展名
                    if url.lower().endswith('.png'):
                        ext = '.png'
                    elif url.lower().endswith('.webp'):
                        ext = '.webp'
                    else:
                        ext = '.jpg'

                    filename = f"{clean_name}_{product_id}{ext}"
                    filepath = SAVE_DIR / filename

                    # 保存
                    with open(filepath, 'wb') as f:
                        f.write(data)

                    # 更新数据库
                    relative_path = f"images/auto_download/{filename}"
                    cursor.execute(
                        "UPDATE products_spu_v3 SET image_url = %s WHERE id = %s",
                        (relative_path, product_id)
                    )
                    conn.commit()

                    # 添加到已存在哈希
                    existing_hashes.add(result)

                    success_count += 1
                    image_saved = True

                    if not HAS_TQDM:
                        print(f"  [OK] 保存成功: {filename}")

                except Exception as e:
                    if not HAS_TQDM:
                        print(f"  [FAIL] 保存失败: {e}")

            # 防止请求过快
            time.sleep(random.uniform(0.5, 1.5))

        if not image_saved:
            fail_count += 1
            if not HAS_TQDM:
                print(f"  [FAIL] 未找到合适图片")

    # 关闭连接
    conn.close()

    # 输出统计
    print("\n" + "=" * 60)
    print("  爬取完成！")
    print("=" * 60)
    print(f"  成功: {success_count} 个")
    print(f"  失败: {fail_count} 个")
    print(f"  跳过: {skip_count} 个")
    print(f"\n  图片保存位置: {SAVE_DIR}")
    print()

if __name__ == '__main__':
    main()
