#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
==========================================
汇森科技 - 全自动图片猎手系统 v2.0
==========================================

功能：
1. 本地扫描17000+张图片，智能匹配到产品
2. 不足5张时从网络下载补齐
3. 确保每个产品有5张不重复的真实图片
4. 智能入库product_images表

安装依赖：
pip install pymysql requests pillow
"""

import os
import re
import json
import hashlib
import requests
import pymysql
from pathlib import Path
from urllib.parse import quote
from collections import defaultdict

# ==================== 配置区 ====================
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'huisen',
    'charset': 'utf8mb4'
}

BASE_DIR = Path(r'C:\xampp\htdocs\huisen')
IMAGES_DIR = BASE_DIR / 'images'
DOWNLOAD_DIR = IMAGES_DIR / 'downloaded'

# 每个产品需要的图片数量
REQUIRED_IMAGES = 5

# 已使用的图片路径（全局去重）
used_images = set()

# ==================== 数据库连接 ====================
def get_db_connection():
    return pymysql.connect(**DB_CONFIG, cursorclass=pymysql.cursors.DictCursor)

# ==================== 工具函数 ====================
def normalize_model_name(name):
    """提取型号关键词用于匹配"""
    # 移除空格，转小写
    name = name.lower().strip()
    # 提取关键数字和词
    keywords = []

    # 提取数字序列 (如 16, 70, 15, 200)
    numbers = re.findall(r'\d+', name)
    keywords.extend(numbers)

    # 提取关键后缀
    suffixes = re.findall(r'(pro|max|ultra|plus|lite|rs|se|mini|fold|flip|x\d+)', name, re.I)
    keywords.extend([s.lower() for s in suffixes])

    # 提取品牌关键词
    brands = ['iphone', 'mate', 'xiaomi', 'mi', 'redmi', 'honor', 'magic', 'vivo', 'oppo', 'find', 'samsung', 'galaxy', 'huawei']
    for b in brands:
        if b in name:
            keywords.append(b)

    return keywords

def image_matches_product(filename, product_keywords, brand):
    """检查图片文件名是否匹配产品"""
    filename_lower = filename.lower()

    # 品牌映射
    brand_aliases = {
        '苹果': ['apple', 'iphone', '苹果'],
        '华为': ['huawei', 'mate', 'nova', 'p\d+', '华为'],
        '小米': ['xiaomi', 'mi', 'redmi', '小米', '红米'],
        '荣耀': ['honor', 'magic', '荣耀'],
        'OPPO': ['oppo', 'find', 'reno'],
        'vivo': ['vivo', 'iqoo', 'x\d+', 's\d+'],
        '三星': ['samsung', 'galaxy', '三星'],
    }

    # 检查品牌匹配
    brand_matched = False
    aliases = brand_aliases.get(brand, [brand.lower()])
    for alias in aliases:
        if re.search(alias, filename_lower):
            brand_matched = True
            break

    if not brand_matched:
        return 0

    # 计算关键词匹配分数
    score = 0
    for kw in product_keywords:
        if kw in filename_lower:
            score += 10
            # 数字完全匹配加分
            if re.search(rf'\b{kw}\b', filename_lower):
                score += 5

    # 优先选择高质量图片
    if any(q in filename_lower for q in ['main', 'cover', '主图', '封面', '_1.', '-1.']):
        score += 20
    if any(q in filename_lower for q in ['hd', '高清', 'large', 'big']):
        score += 10

    return score

def get_file_hash(filepath):
    """计算文件MD5哈希，用于去重"""
    try:
        with open(filepath, 'rb') as f:
            return hashlib.md5(f.read(8192)).hexdigest()
    except:
        return None

# ==================== 本地扫描模块 ====================
def scan_local_images():
    """扫描本地所有图片，建立索引"""
    print("\n[步骤1] 扫描本地图片库...")

    image_index = []
    extensions = {'.jpg', '.jpeg', '.png', '.webp', '.gif'}

    for root, dirs, files in os.walk(IMAGES_DIR):
        # 跳过downloaded目录（网络下载的图片单独处理）
        if 'downloaded' in root:
            continue

        for file in files:
            if Path(file).suffix.lower() in extensions:
                full_path = Path(root) / file
                rel_path = full_path.relative_to(BASE_DIR)
                image_index.append({
                    'filename': file,
                    'path': str(rel_path).replace('\\', '/'),
                    'full_path': str(full_path),
                    'size': full_path.stat().st_size if full_path.exists() else 0
                })

    print(f"    找到 {len(image_index)} 张本地图片")
    return image_index

def match_images_to_products(conn, image_index):
    """将本地图片匹配到产品"""
    print("\n[步骤2] 智能匹配图片到产品...")

    cursor = conn.cursor()

    # 获取所有产品
    cursor.execute("""
        SELECT id, brand, model_name
        FROM products_spu_v4
        WHERE min_price > 0
        ORDER BY id
    """)
    products = cursor.fetchall()
    print(f"    共 {len(products)} 个产品需要处理")

    # 获取已使用的图片
    cursor.execute("SELECT DISTINCT image_path FROM product_images")
    for row in cursor.fetchall():
        used_images.add(row['image_path'])
    print(f"    已有 {len(used_images)} 张图片被使用")

    product_matches = {}

    for product in products:
        pid = product['id']
        brand = product['brand']
        model = product['model_name']
        keywords = normalize_model_name(model)

        # 计算每张图片的匹配分数
        matches = []
        for img in image_index:
            if img['path'] in used_images:
                continue  # 跳过已使用的图片

            score = image_matches_product(img['filename'], keywords, brand)
            if score > 0:
                matches.append({
                    'path': img['path'],
                    'score': score,
                    'size': img['size']
                })

        # 按分数排序，取前5张
        matches.sort(key=lambda x: (-x['score'], -x['size']))
        top_matches = matches[:REQUIRED_IMAGES]

        # 标记为已使用
        for m in top_matches:
            used_images.add(m['path'])

        product_matches[pid] = {
            'brand': brand,
            'model': model,
            'images': [m['path'] for m in top_matches]
        }

        if len(top_matches) > 0:
            print(f"    [{pid}] {model}: 匹配到 {len(top_matches)} 张图片")

    return product_matches

# ==================== 网络下载模块 ====================
def download_image(url, save_path):
    """下载单张图片"""
    try:
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Referer': 'https://www.baidu.com/'
        }
        response = requests.get(url, headers=headers, timeout=15)
        if response.status_code == 200 and len(response.content) > 5000:  # 至少5KB
            save_path.parent.mkdir(parents=True, exist_ok=True)
            with open(save_path, 'wb') as f:
                f.write(response.content)
            return True
    except Exception as e:
        pass
    return False

def search_and_download_images(brand, model, needed_count):
    """从网络搜索并下载图片"""
    # 创建保存目录
    safe_brand = re.sub(r'[^\w\u4e00-\u9fff]', '', brand)
    safe_model = re.sub(r'[^\w\u4e00-\u9fff]', '', model)
    save_dir = DOWNLOAD_DIR / safe_brand / safe_model
    save_dir.mkdir(parents=True, exist_ok=True)

    downloaded = []

    # 使用Bing图片搜索API（更稳定）
    search_query = f"{model} 手机 官方图片"

    try:
        # 使用Bing图片搜索
        search_url = f"https://www.bing.com/images/search?q={quote(search_query)}&first=1&count=20&qft=+filterui:imagesize-large"
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        }

        response = requests.get(search_url, headers=headers, timeout=10)

        # 提取图片URL
        img_urls = re.findall(r'murl&quot;:&quot;(https?://[^&]+\.(?:jpg|jpeg|png|webp))', response.text, re.I)

        for i, img_url in enumerate(img_urls[:needed_count * 2]):  # 多尝试几个
            if len(downloaded) >= needed_count:
                break

            save_path = save_dir / f"{safe_model}_{i+1}.jpg"
            if download_image(img_url, save_path):
                rel_path = str(save_path.relative_to(BASE_DIR)).replace('\\', '/')
                if rel_path not in used_images:
                    downloaded.append(rel_path)
                    used_images.add(rel_path)
                    print(f"        下载成功: {save_path.name}")

    except Exception as e:
        print(f"        搜索失败: {e}")

    return downloaded

def supplement_missing_images(conn, product_matches):
    """补齐不足5张的产品图片"""
    print("\n[步骤3] 网络补齐缺失图片...")

    for pid, data in product_matches.items():
        current_count = len(data['images'])
        if current_count >= REQUIRED_IMAGES:
            continue

        needed = REQUIRED_IMAGES - current_count
        print(f"    [{pid}] {data['model']}: 需要下载 {needed} 张")

        downloaded = search_and_download_images(data['brand'], data['model'], needed)
        data['images'].extend(downloaded)

        if downloaded:
            print(f"        成功下载 {len(downloaded)} 张")

# ==================== 数据库写入模块 ====================
def save_to_database(conn, product_matches):
    """将匹配结果写入数据库"""
    print("\n[步骤4] 写入数据库...")

    cursor = conn.cursor()

    # 清空旧数据（可选，谨慎使用）
    # cursor.execute("TRUNCATE TABLE product_images")

    total_inserted = 0

    for pid, data in product_matches.items():
        if not data['images']:
            continue

        # 删除该产品的旧图片记录
        cursor.execute("DELETE FROM product_images WHERE product_id = %s", (pid,))

        # 插入新记录
        for sort_order, img_path in enumerate(data['images']):
            try:
                cursor.execute("""
                    INSERT INTO product_images (product_id, image_path, sort_order, created_at)
                    VALUES (%s, %s, %s, NOW())
                """, (pid, img_path, sort_order))
                total_inserted += 1
            except Exception as e:
                print(f"        插入失败 [{pid}]: {e}")

    conn.commit()
    print(f"    共插入 {total_inserted} 条图片记录")

def generate_report(product_matches):
    """生成报告"""
    print("\n" + "=" * 50)
    print("图片匹配报告")
    print("=" * 50)

    stats = defaultdict(int)
    insufficient = []

    for pid, data in product_matches.items():
        count = len(data['images'])
        stats[count] += 1
        if count < REQUIRED_IMAGES:
            insufficient.append(f"  [{pid}] {data['model']}: {count}张")

    print("\n图片数量分布:")
    for count in sorted(stats.keys(), reverse=True):
        print(f"  {count}张: {stats[count]}个产品")

    if insufficient:
        print(f"\n图片不足({REQUIRED_IMAGES}张)的产品:")
        for item in insufficient[:20]:
            print(item)
        if len(insufficient) > 20:
            print(f"  ... 还有 {len(insufficient) - 20} 个")

    print("\n" + "=" * 50)

# ==================== 主程序 ====================
def main():
    print("=" * 50)
    print("汇森科技 - 全自动图片猎手系统 v2.0")
    print("=" * 50)

    # 连接数据库
    print("\n连接数据库...")
    conn = get_db_connection()
    print("    连接成功!")

    try:
        # 步骤1: 扫描本地图片
        image_index = scan_local_images()

        # 步骤2: 匹配图片到产品
        product_matches = match_images_to_products(conn, image_index)

        # 步骤3: 网络补齐（可选，需要网络）
        # supplement_missing_images(conn, product_matches)

        # 步骤4: 写入数据库
        save_to_database(conn, product_matches)

        # 生成报告
        generate_report(product_matches)

        print("\n✓ 全部完成!")

    finally:
        conn.close()

if __name__ == '__main__':
    main()
