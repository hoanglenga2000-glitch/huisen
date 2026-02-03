#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
============================================
甘肃汇森 - 自动化图片匹配脚本
============================================
功能：自动将图片素材文件夹中的图片匹配到数据库中的手机型号
作者：AI Assistant
版本：1.0
日期：2026-01-30
============================================
"""

import os
import re
import mysql.connector
from pathlib import Path
from typing import List, Dict, Tuple
import difflib

# ============================================
# 数据库配置
# ============================================
DB_CONFIG = {
    'host': 'localhost',
    'port': 3306,
    'user': 'huisen',
    'password': '123456',
    'database': '甘肃汇森',
    'charset': 'utf8mb4',
    'collation': 'utf8mb4_unicode_ci'
}

# 图片文件夹路径（相对于脚本所在目录）
IMAGE_BASE_PATH = r'images\图片素材'
ABSOLUTE_IMAGE_PATH = r'C:\xampp\htdocs\huisen\images\图片素材'

# 支持的图片格式
IMAGE_EXTENSIONS = {'.jpg', '.jpeg', '.png', '.webp', '.gif'}

# ============================================
# 工具函数
# ============================================

def clean_model_name(model: str) -> str:
    """
    清理型号名称，去除颜色、容量等干扰信息
    用于提高匹配准确度
    """
    # 去除常见的颜色词
    colors = [
        '星野黑', '气泡粉', '浮光白', '晨曦金', '极光蓝', '钛金黑', '月光银',
        '玄黑', '白色', '黑色', '曜金黑', '冰川白', '青色', '金色', '银色',
        '紫色', '蓝色', '绿色', '红色', '粉色', '灰色', '云海白', '星穹黑',
        '沙漠', '燃', '极地灰', '月岩钛', '风羽青', '星光色', '深空灰',
        '羽砂白', '羽砂黑', '墨羽', '磐岩灰', '雅川青', '苍岭绿', '玄夜黑',
        '羽沙黑', '羽沙白', '星曜黑', '雪域白', '幻夜黑', '晓雪白', '冰霜银',
        '钛色', '原野绿', '远山青', '曜石黑', '晴空蓝', '橙色'
    ]

    clean = model
    for color in colors:
        clean = clean.replace(color, '')

    # 去除容量信息（如：128GB, 8+256GB, 12+512G）
    clean = re.sub(r'\d+\+?\d*G[B5]?', '', clean)

    # 去除4G/5G标识
    clean = re.sub(r'[-_]?[45]G(?![a-zA-Z])', '', clean)

    # 去除括号内容
    clean = re.sub(r'\([^)]*\)', '', clean)
    clean = re.sub(r'（[^）]*）', '', clean)

    # 去除版本信息
    versions = ['标准版', '尝鲜版', '至臻版', '典藏版', 'Pro', 'Max', 'Ultra', 'Plus']
    # 注意：保留Pro/Max等关键词，因为它们是型号的一部分

    # 统一分隔符
    clean = re.sub(r'[-_\s]+', ' ', clean)

    return clean.strip()


def extract_keywords(text: str) -> List[str]:
    """
    从文本中提取关键词
    """
    # 转小写
    text = text.lower()

    # 提取数字+字母组合（如：iphone15, mate60）
    pattern = r'[a-z]+\d+[a-z]*'
    keywords = re.findall(pattern, text)

    # 提取单独的数字（如型号中的15, 14）
    numbers = re.findall(r'\d+', text)

    # 提取英文单词
    words = re.findall(r'[a-z]+', text)

    return keywords + numbers + words


def calculate_similarity(str1: str, str2: str) -> float:
    """
    计算两个字符串的相似度（0-1之间）
    """
    # 转小写比较
    s1 = str1.lower()
    s2 = str2.lower()

    # 使用difflib计算相似度
    return difflib.SequenceMatcher(None, s1, s2).ratio()


def match_image_to_model(model: str, brand: str, image_files: List[str]) -> Tuple[str, float]:
    """
    为指定型号匹配最合适的图片
    返回：(图片路径, 相似度分数)
    """
    best_match = None
    best_score = 0.0

    # 清理型号名
    clean_model = clean_model_name(model)
    model_keywords = extract_keywords(clean_model)

    for img_path in image_files:
        # 获取文件名（不含扩展名）
        filename = Path(img_path).stem

        # 计算相似度
        score = calculate_similarity(clean_model, filename)

        # 关键词匹配加分
        img_keywords = extract_keywords(filename)
        keyword_matches = sum(1 for kw in model_keywords if kw in img_keywords)
        if keyword_matches > 0:
            score += keyword_matches * 0.1

        # 如果文件名包含品牌名，加分
        if brand.lower() in filename.lower():
            score += 0.05

        # 优先选择第一张图片（_1结尾）
        if filename.endswith('_1'):
            score += 0.05

        if score > best_score:
            best_score = score
            best_match = img_path

    return best_match, best_score


def scan_image_folder(base_path: str) -> Dict[str, List[str]]:
    """
    扫描图片文件夹，按品牌分类
    返回：{品牌: [图片路径列表]}
    """
    brand_images = {}

    if not os.path.exists(base_path):
        print(f"❌ 错误：图片文件夹不存在 - {base_path}")
        return brand_images

    # 遍历所有子文件夹（品牌文件夹）
    for brand_folder in os.listdir(base_path):
        brand_path = os.path.join(base_path, brand_folder)

        if not os.path.isdir(brand_path):
            continue

        images = []
        # 扫描该品牌文件夹下的所有图片
        for filename in os.listdir(brand_path):
            file_ext = Path(filename).suffix.lower()
            if file_ext in IMAGE_EXTENSIONS:
                # 保存相对路径（用于存入数据库）
                rel_path = os.path.join(IMAGE_BASE_PATH, brand_folder, filename)
                # 将反斜杠转为正斜杠（Web友好）
                rel_path = rel_path.replace('\\', '/')
                images.append(rel_path)

        if images:
            brand_images[brand_folder] = images
            print(f"  ✓ 发现品牌文件夹: {brand_folder} ({len(images)} 张图片)")

    return brand_images


# ============================================
# 主程序
# ============================================

def main():
    """
    主函数：执行图片匹配流程
    """
    print("=" * 60)
    print("      甘肃汇森 - 自动化图片匹配工具 v1.0")
    print("=" * 60)
    print()

    # Step 1: 扫描图片文件夹
    print("📁 步骤 1/4: 扫描图片文件夹...")
    brand_images = scan_image_folder(ABSOLUTE_IMAGE_PATH)

    total_images = sum(len(imgs) for imgs in brand_images.values())
    print(f"✓ 扫描完成！共发现 {len(brand_images)} 个品牌文件夹，{total_images} 张图片\n")

    if not brand_images:
        print("❌ 未找到任何图片，程序退出")
        return

    # Step 2: 连接数据库
    print("🔌 步骤 2/4: 连接数据库...")
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor(dictionary=True)
        print(f"✓ 数据库连接成功 - {DB_CONFIG['database']}\n")
    except mysql.connector.Error as e:
        print(f"❌ 数据库连接失败: {e}")
        return

    # Step 3: 检查并添加image_path字段
    print("🔧 步骤 3/4: 检查数据库表结构...")
    try:
        # 检查字段是否存在
        cursor.execute("SHOW COLUMNS FROM mobile_phones LIKE 'image_path'")
        if not cursor.fetchone():
            print("  ⚠ image_path 字段不存在，正在创建...")
            cursor.execute("""
                ALTER TABLE mobile_phones
                ADD COLUMN image_path VARCHAR(500) DEFAULT NULL
                COMMENT '手机图片路径'
            """)
            conn.commit()
            print("  ✓ image_path 字段创建成功")
        else:
            print("  ✓ image_path 字段已存在")
    except mysql.connector.Error as e:
        print(f"  ❌ 字段检查失败: {e}")
        cursor.close()
        conn.close()
        return

    # Step 4: 读取数据库中的手机型号
    print("\n📱 步骤 4/4: 开始匹配图片...")
    try:
        cursor.execute("""
            SELECT id, brand, model, image_path
            FROM mobile_phones
            WHERE brand != '未分类'
            ORDER BY brand, model
        """)
        phones = cursor.fetchall()
        print(f"  ✓ 从数据库读取到 {len(phones)} 条手机记录\n")
    except mysql.connector.Error as e:
        print(f"❌ 查询失败: {e}")
        cursor.close()
        conn.close()
        return

    # 统计信息
    matched_count = 0
    updated_count = 0
    skipped_count = 0
    failed_count = 0

    # 品牌名映射（数据库品牌名 -> 文件夹名）
    brand_mapping = {
        '苹果': ['苹果', 'Apple', 'apple'],
        'Apple': ['苹果', 'Apple', 'apple'],
        '华为': ['华为', 'Huawei', 'huawei'],
        'Huawei': ['华为', 'Huawei', 'huawei'],
        'HUAWEI': ['华为', 'Huawei', 'huawei'],
        '荣耀': ['荣耀', 'Honor', 'honor'],
        'Honor': ['荣耀', 'Honor', 'honor'],
        'HONOR': ['荣耀', 'Honor', 'honor'],
        '小米': ['小米', 'Xiaomi', 'xiaomi', 'MI'],
        'Xiaomi': ['小米', 'Xiaomi', 'xiaomi', 'MI'],
        'Redmi': ['小米', 'Xiaomi', 'xiaomi', 'Redmi'],
        'OPPO': ['Oppo', 'OPPO', 'oppo'],
        'Oppo': ['Oppo', 'OPPO', 'oppo'],
        'vivo': ['Vivo', 'vivo', 'VIVO'],
        'Vivo': ['Vivo', 'vivo', 'VIVO'],
        'VIVO': ['Vivo', 'vivo', 'VIVO'],
        '三星': ['三星', 'Samsung', 'samsung'],
        'Samsung': ['三星', 'Samsung', 'samsung'],
        'SAMSUNG': ['三星', 'Samsung', 'samsung'],
    }

    # 遍历每个手机型号
    for phone in phones:
        phone_id = phone['id']
        brand = phone['brand']
        model = phone['model']
        current_image = phone['image_path']

        # 如果已有图片路径且文件存在，跳过
        if current_image and current_image.strip():
            # 检查文件是否真实存在
            full_path = os.path.join(r'C:\xampp\htdocs\huisen', current_image.replace('/', '\\'))
            if os.path.exists(full_path):
                skipped_count += 1
                continue

        # 获取该品牌对应的图片列表
        possible_brands = brand_mapping.get(brand, [brand])
        available_images = []

        for pb in possible_brands:
            if pb in brand_images:
                available_images.extend(brand_images[pb])

        if not available_images:
            failed_count += 1
            print(f"  ⚠ [{brand}] {model} - 未找到品牌文件夹")
            continue

        # 匹配图片
        matched_image, score = match_image_to_model(model, brand, available_images)

        if matched_image and score > 0.3:  # 相似度阈值：30%
            # 更新数据库
            try:
                cursor.execute("""
                    UPDATE mobile_phones
                    SET image_path = %s
                    WHERE id = %s
                """, (matched_image, phone_id))
                conn.commit()

                matched_count += 1
                updated_count += 1
                print(f"  ✓ [{brand}] {model}")
                print(f"    → {matched_image} (相似度: {score:.2%})")

            except mysql.connector.Error as e:
                print(f"  ❌ 更新失败: {e}")
        else:
            failed_count += 1
            print(f"  ✗ [{brand}] {model} - 未找到匹配图片 (最高相似度: {score:.2%})")

    # 关闭数据库连接
    cursor.close()
    conn.close()

    # 输出统计结果
    print()
    print("=" * 60)
    print("                    匹配结果统计")
    print("=" * 60)
    print(f"  总记录数:        {len(phones)}")
    print(f"  ✓ 成功匹配:      {matched_count} ({matched_count/len(phones)*100:.1f}%)")
    print(f"  ↻ 已有图片:      {skipped_count}")
    print(f"  ✗ 匹配失败:      {failed_count}")
    print("=" * 60)
    print()

    if matched_count > 0:
        print(f"🎉 匹配完成！已成功为 {matched_count} 个型号更新图片路径")
    else:
        print("⚠ 未匹配到任何图片，请检查：")
        print("  1. 图片文件夹路径是否正确")
        print("  2. 图片文件名是否包含型号关键词")
        print("  3. 数据库中的型号名称是否准确")

    print()
    print("提示：你可以访问 http://localhost:8080/quotes.php 查看效果")
    print()


if __name__ == '__main__':
    try:
        main()
    except KeyboardInterrupt:
        print("\n\n⚠ 用户中断操作")
    except Exception as e:
        print(f"\n❌ 发生错误: {e}")
        import traceback
        traceback.print_exc()
