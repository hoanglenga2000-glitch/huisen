#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
==========================================
汇森科技 - 图片整理自动化脚本 v1.0
==========================================

功能：
1. 自动创建标准目录结构
2. 根据文件名关键词自动分类移动图片
3. 输出整理日志

使用方法：
python image_organizer.py
"""

import os
import shutil
import re
from datetime import datetime
from pathlib import Path

# 配置
BASE_DIR = Path(__file__).parent.parent / 'images'
PRODUCTS_DIR = BASE_DIR / 'products'
LOG_FILE = BASE_DIR / f'organize_log_{datetime.now().strftime("%Y%m%d_%H%M%S")}.txt'

# 品牌关键词映射
BRAND_KEYWORDS = {
    'apple': ['iphone', 'ipad', 'apple', 'airpods', 'macbook', 'watch'],
    'huawei': ['huawei', 'mate', 'p60', 'p70', 'nova', 'pura'],
    'xiaomi': ['xiaomi', 'mi ', 'redmi', '小米', '红米'],
    'honor': ['honor', 'magic', '荣耀'],
    'oppo': ['oppo', 'find', 'reno'],
    'vivo': ['vivo', 'iqoo', 'x100', 'x200'],
    'samsung': ['samsung', 'galaxy', '三星'],
    'oneplus': ['oneplus', '一加'],
    'realme': ['realme', '真我'],
    'motorola': ['motorola', 'moto', '摩托罗拉'],
    'nokia': ['nokia', '诺基亚'],
    'meizu': ['meizu', '魅族'],
    'zte': ['zte', '中兴', 'nubia', '努比亚'],
    'lenovo': ['lenovo', '联想'],
    'asus': ['asus', 'rog', '华硕'],
    'sony': ['sony', 'xperia', '索尼'],
    'google': ['google', 'pixel'],
    'accessories': ['耳机', '充电器', '数据线', '保护壳', '贴膜', '配件'],
    'tablets': ['平板', 'pad', 'tablet'],
    'watches': ['手表', 'watch', 'band', '手环'],
    'other': []  # 默认分类
}

# 型号提取正则
MODEL_PATTERNS = {
    'apple': r'(iphone\s*\d+\s*(pro\s*max|pro|plus|mini)?|ipad\s*(pro|air|mini)?)',
    'huawei': r'(mate\s*\d+\s*(pro\s*plus|pro|rs)?|p\d+\s*(pro\s*plus|pro|art)?|nova\s*\d+)',
    'xiaomi': r'(mi\s*\d+\s*(ultra|pro)?|redmi\s*(note\s*)?\d+\s*(pro\s*plus|pro)?|小米\s*\d+)',
    'honor': r'(magic\s*\d+\s*(pro\s*plus|pro|rs)?|荣耀\s*\d+)',
    'oppo': r'(find\s*(x\d+|n\d+)\s*(ultra|pro)?|reno\s*\d+\s*pro?)',
    'vivo': r'(x\d+\s*(ultra|pro\s*plus|pro)?|iqoo\s*\d+\s*pro?)',
    'samsung': r'(galaxy\s*(s\d+|z\s*fold|z\s*flip)\s*(ultra|plus)?)',
}

class ImageOrganizer:
    def __init__(self):
        self.stats = {
            'moved': 0,
            'skipped': 0,
            'errors': 0,
            'unrecognized': []
        }
        self.log_messages = []

    def log(self, message):
        """记录日志"""
        timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        log_entry = f"[{timestamp}] {message}"
        self.log_messages.append(log_entry)
        print(log_entry)

    def create_directory_structure(self):
        """创建标准目录结构"""
        self.log("=== 创建目录结构 ===")

        directories = [
            'products/apple',
            'products/huawei',
            'products/xiaomi',
            'products/honor',
            'products/oppo',
            'products/vivo',
            'products/samsung',
            'products/oneplus',
            'products/realme',
            'products/motorola',
            'products/nokia',
            'products/other',
            'products/accessories',
            'products/tablets',
            'products/watches',
            'banners',
            'data_screen',
            'thumbnails',
            'details',
        ]

        for dir_path in directories:
            full_path = BASE_DIR / dir_path
            if not full_path.exists():
                full_path.mkdir(parents=True, exist_ok=True)
                self.log(f"创建目录: {dir_path}")
            else:
                self.log(f"目录已存在: {dir_path}")

    def identify_brand(self, filename):
        """根据文件名识别品牌"""
        filename_lower = filename.lower()

        for brand, keywords in BRAND_KEYWORDS.items():
            for keyword in keywords:
                if keyword.lower() in filename_lower:
                    return brand

        return None

    def extract_model(self, filename, brand):
        """提取型号信息"""
        if brand in MODEL_PATTERNS:
            pattern = MODEL_PATTERNS[brand]
            match = re.search(pattern, filename.lower())
            if match:
                return match.group(1).replace(' ', '_').strip('_')
        return None

    def generate_new_filename(self, original_name, brand, model=None):
        """生成标准化文件名"""
        # 获取文件扩展名
        ext = Path(original_name).suffix.lower()

        # 尝试提取颜色信息
        colors = ['black', 'white', 'blue', 'gold', 'silver', 'green', 'purple', 'pink', 'red', 'gray', 'titanium',
                  '黑', '白', '蓝', '金', '银', '绿', '紫', '粉', '红', '灰', '钛']
        color = None
        for c in colors:
            if c in original_name.lower():
                color = c
                break

        # 构建新文件名
        parts = [brand]
        if model:
            parts.append(model)
        if color:
            parts.append(color)

        # 添加时间戳避免重名
        timestamp = datetime.now().strftime('%H%M%S%f')[:10]
        parts.append(timestamp)

        return '_'.join(parts) + ext

    def organize_images(self):
        """整理图片"""
        self.log("=== 开始整理图片 ===")

        # 支持的图片格式
        image_extensions = {'.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp', '.svg'}

        # 扫描根目录下的图片
        for file_path in BASE_DIR.iterdir():
            if not file_path.is_file():
                continue

            if file_path.suffix.lower() not in image_extensions:
                continue

            filename = file_path.name

            # 跳过已经处理过的文件
            if filename.startswith('organize_log'):
                continue

            # 识别品牌
            brand = self.identify_brand(filename)

            if brand:
                # 提取型号
                model = self.extract_model(filename, brand)

                # 目标目录
                if brand == 'accessories':
                    target_dir = PRODUCTS_DIR / 'accessories'
                elif brand == 'tablets':
                    target_dir = PRODUCTS_DIR / 'tablets'
                elif brand == 'watches':
                    target_dir = PRODUCTS_DIR / 'watches'
                else:
                    target_dir = PRODUCTS_DIR / brand
                    if model:
                        target_dir = target_dir / model

                target_dir.mkdir(parents=True, exist_ok=True)

                # 移动文件
                try:
                    new_filename = self.generate_new_filename(filename, brand, model)
                    target_path = target_dir / new_filename

                    # 如果目标文件已存在，添加序号
                    counter = 1
                    while target_path.exists():
                        stem = target_path.stem
                        target_path = target_dir / f"{stem}_{counter}{target_path.suffix}"
                        counter += 1

                    shutil.move(str(file_path), str(target_path))
                    self.stats['moved'] += 1
                    self.log(f"移动: {filename} -> {target_path.relative_to(BASE_DIR)}")

                except Exception as e:
                    self.stats['errors'] += 1
                    self.log(f"错误: 移动 {filename} 失败 - {str(e)}")
            else:
                self.stats['unrecognized'].append(filename)
                self.log(f"无法识别: {filename}")

        self.log(f"=== 整理完成 ===")
        self.log(f"成功移动: {self.stats['moved']} 个文件")
        self.log(f"无法识别: {len(self.stats['unrecognized'])} 个文件")
        self.log(f"错误: {self.stats['errors']} 个")

    def scan_existing_folders(self):
        """扫描现有的图片素材文件夹"""
        self.log("=== 扫描现有图片素材 ===")

        # 扫描 图片素材 文件夹
        source_dir = BASE_DIR / '图片素材'
        if source_dir.exists():
            self.log(f"发现图片素材目录: {source_dir}")
            self.scan_and_index_folder(source_dir)

        # 扫描 phones 文件夹
        phones_dir = BASE_DIR / 'phones'
        if phones_dir.exists():
            self.log(f"发现phones目录: {phones_dir}")
            self.scan_and_index_folder(phones_dir)

    def scan_and_index_folder(self, folder):
        """扫描并索引文件夹中的图片"""
        image_extensions = {'.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp'}
        count = 0

        for root, dirs, files in os.walk(folder):
            for file in files:
                if Path(file).suffix.lower() in image_extensions:
                    count += 1

        self.log(f"  - 发现 {count} 张图片")
        return count

    def save_log(self):
        """保存日志文件"""
        with open(LOG_FILE, 'w', encoding='utf-8') as f:
            f.write('\n'.join(self.log_messages))

            if self.stats['unrecognized']:
                f.write('\n\n=== 需要人工处理的文件 ===\n')
                for filename in self.stats['unrecognized']:
                    f.write(f"  - {filename}\n")

        print(f"\n日志已保存到: {LOG_FILE}")

    def run(self):
        """运行整理流程"""
        self.log("========================================")
        self.log("汇森科技 - 图片整理自动化脚本")
        self.log("========================================")
        self.log(f"工作目录: {BASE_DIR}")

        # 1. 创建目录结构
        self.create_directory_structure()

        # 2. 扫描现有素材
        self.scan_existing_folders()

        # 3. 整理根目录下的图片
        self.organize_images()

        # 4. 保存日志
        self.save_log()

        return self.stats


if __name__ == '__main__':
    organizer = ImageOrganizer()
    stats = organizer.run()

    print("\n" + "=" * 40)
    print("整理统计:")
    print(f"  成功移动: {stats['moved']} 个文件")
    print(f"  无法识别: {len(stats['unrecognized'])} 个文件")
    print(f"  错误: {stats['errors']} 个")
    print("=" * 40)
