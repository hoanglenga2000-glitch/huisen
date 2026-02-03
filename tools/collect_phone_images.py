#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
============================================
甘肃汇森 - 手机图片自动采集脚本
从搜索引擎下载手机产品图片
============================================

使用方法：
1. 安装依赖：pip install requests beautifulsoup4 pillow mysql-connector-python
2. 运行脚本：python collect_phone_images.py

功能：
- 扫描数据库中缺少图片的产品
- 使用搜索引擎查找产品图片
- 下载并保存到 images/phones/ 目录
- 更新数据库中的 image_url 字段
"""

import os
import re
import json
import time
import random
import hashlib
import sys
import io
import requests
from pathlib import Path
from urllib.parse import quote, urljoin, urlparse
from PIL import Image
from io import BytesIO

# 修复 Windows 控制台 Unicode 编码问题
if sys.platform == 'win32':
    try:
        # 尝试设置 UTF-8 编码
        sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
        sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')
    except:
        pass
try:
    import pymysql
    pymysql.install_as_MySQLdb()
    USE_PYMYSQL = True
except ImportError:
    import mysql.connector
    USE_PYMYSQL = False

# ============================================
# 配置
# ============================================

# 数据库配置
DB_CONFIG = {
    'host': 'localhost',
    'port': 3306,
    'user': 'huisen',
    'password': '123456',
    'database': '甘肃汇森',  # 注意：数据库名是中文
    'charset': 'utf8mb4'
}

# 图片保存目录
IMAGE_DIR = Path(__file__).parent / 'images' / 'phones'

# 请求头
HEADERS = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
    'Accept-Language': 'zh-CN,zh;q=0.9,en;q=0.8',
    'Accept-Encoding': 'gzip, deflate',
    'Connection': 'keep-alive',
}

# 品牌关键词映射
BRAND_KEYWORDS = {
    '苹果': ['Apple', 'iPhone'],
    '华为': ['Huawei', 'HUAWEI'],
    '荣耀': ['Honor', 'HONOR'],
    'OPPO': ['OPPO'],
    'vivo': ['vivo', 'VIVO'],
    '小米': ['Xiaomi', 'MI'],
    '红米': ['Redmi'],
    '三星': ['Samsung', 'Galaxy'],
    '一加': ['OnePlus'],
    '魅族': ['Meizu'],
    '真我': ['realme'],
}

# ============================================
# 工具函数
# ============================================

def clean_model_name(model: str, brand: str) -> str:
    """清理型号名称，提取核心型号"""
    clean = model
    
    # 移除品牌前缀
    if clean.startswith(brand):
        clean = clean[len(brand):]
    
    # 移除颜色
    colors = ['星野黑', '气泡粉', '浮光白', '晨曦金', '极光蓝', '钛金黑', '月光银', 
              '玄黑', '白色', '黑色', '金色', '银色', '紫色', '蓝色', '绿色', '红色',
              '云海白', '星穹黑', '沙漠', '极地灰', '月岩钛', '风羽青', '深空灰']
    for color in colors:
        clean = clean.replace(color, '')
    
    # 移除存储规格
    clean = re.sub(r'\d+\+\d+G[B5]?', '', clean, flags=re.IGNORECASE)
    clean = re.sub(r'\d+G[B]?', '', clean, flags=re.IGNORECASE)
    
    # 移除 5G/4G
    clean = re.sub(r'[-_]?[45]G(?![a-zA-Z])', '', clean, flags=re.IGNORECASE)
    
    # 移除括号内容
    clean = re.sub(r'\*?\([^)]*\)', '', clean)
    clean = re.sub(r'\*?\（[^）]*）', '', clean)
    
    # 清理分隔符
    clean = re.sub(r'[-_]+', ' ', clean)
    clean = clean.strip(' -_')
    
    return clean if clean else model


def generate_search_query(brand: str, model: str) -> list:
    """生成多个搜索查询词（返回列表，按优先级排序）"""
    clean_model = clean_model_name(model, brand)
    
    # 获取品牌英文名
    brand_en = BRAND_KEYWORDS.get(brand, [brand])[0]
    
    # 构建多个搜索词（按优先级）
    queries = [
        f"{brand} {clean_model} 手机 官方渲染图",
        f"{brand_en} {clean_model} official render",
        f"{brand} {clean_model} 产品图",
        f"{brand_en} {clean_model} product image",
        f"{brand} {clean_model} 手机",
    ]
    
    return queries


def generate_filename(brand: str, model: str) -> str:
    """生成图片文件名"""
    clean_model = clean_model_name(model, brand)
    
    # 构建安全的文件名
    safe_name = re.sub(r'[\\/:*?"<>|]', '_', f"{brand}_{clean_model}")
    safe_name = re.sub(r'\s+', '_', safe_name)
    safe_name = re.sub(r'_+', '_', safe_name)
    safe_name = safe_name.strip('_')
    
    return f"{safe_name}.jpg"


def download_image(url: str, save_path: Path, min_size: int = 10000) -> bool:
    """下载并保存图片"""
    try:
        print(f"    下载: {url[:80]}...")
        
        response = requests.get(url, headers=HEADERS, timeout=15, stream=True)
        response.raise_for_status()
        
        # 检查内容类型
        content_type = response.headers.get('content-type', '')
        if 'image' not in content_type.lower() and 'octet-stream' not in content_type.lower():
            print(f"    ✗ 非图片内容: {content_type}")
            return False
        
        # 读取内容
        content = response.content
        
        # 检查大小
        if len(content) < min_size:
            print(f"    ✗ 图片太小: {len(content)} bytes")
            return False
        
        # 验证是否为有效图片
        try:
            img = Image.open(BytesIO(content))
            
            # 检查尺寸
            if img.width < 200 or img.height < 200:
                print(f"    ✗ 图片尺寸太小: {img.width}x{img.height}")
                return False
            
            # 转换为RGB
            if img.mode in ('RGBA', 'P'):
                img = img.convert('RGB')
            
            # 调整大小（保持宽高比）
            max_size = 800
            if img.width > max_size or img.height > max_size:
                ratio = min(max_size / img.width, max_size / img.height)
                new_size = (int(img.width * ratio), int(img.height * ratio))
                img = img.resize(new_size, Image.LANCZOS)
            
            # 保存
            img.save(save_path, 'JPEG', quality=90, optimize=True)
            print(f"    ✓ 保存成功: {save_path.name} ({img.width}x{img.height})")
            return True
            
        except Exception as e:
            print(f"    ✗ 图片处理失败: {e}")
            return False
            
    except requests.exceptions.RequestException as e:
        print(f"    ✗ 下载失败: {e}")
        return False


def search_bing_images(query: str, count: int = 5) -> list:
    """使用 Bing 图片搜索"""
    image_urls = []
    
    try:
        search_url = f"https://www.bing.com/images/search?q={quote(query)}&form=HDRSC2&first=1&qft=+filterui:imagesize-large"
        
        response = requests.get(search_url, headers=HEADERS, timeout=15)
        response.raise_for_status()
        
        # 提取图片URL - 多种模式匹配
        patterns = [
            r'murl":"(https?://[^"]+\.(?:jpg|jpeg|png|webp))',
            r'"murl":"(https?://[^"]+)"',
            r'imgurl":"(https?://[^"]+\.(?:jpg|jpeg|png|webp))',
            r'mediaurl":"(https?://[^"]+\.(?:jpg|jpeg|png|webp))',
        ]
        
        seen = set()
        for pattern in patterns:
            matches = re.findall(pattern, response.text, re.IGNORECASE)
            for url in matches:
                url = url.replace('\\u0026', '&').replace('\\/', '/')
                # 过滤掉无效URL
                if url.startswith('http') and url not in seen:
                    # 排除一些明显不是产品图的URL
                    if not any(skip in url.lower() for skip in ['icon', 'logo', 'avatar', 'profile']):
                        seen.add(url)
                        image_urls.append(url)
                        if len(image_urls) >= count * 2:
                            break
            if len(image_urls) >= count * 2:
                break
                    
    except Exception as e:
        print(f"    Bing搜索失败: {e}")
    
    return image_urls[:count]


def search_baidu_images(query: str, count: int = 5) -> list:
    """使用百度图片搜索"""
    image_urls = []
    
    try:
        search_url = f"https://image.baidu.com/search/index?tn=baiduimage&word={quote(query)}"
        
        response = requests.get(search_url, headers=HEADERS, timeout=15)
        response.raise_for_status()
        
        # 提取图片URL
        pattern = r'"objURL":"(https?://[^"]+)"'
        matches = re.findall(pattern, response.text)
        
        for url in matches[:count]:
            image_urls.append(url)
            
    except Exception as e:
        print(f"    百度搜索失败: {e}")
    
    return image_urls


# ============================================
# 主逻辑
# ============================================

def get_products_without_images(conn) -> list:
    """获取缺少图片的产品"""
    if USE_PYMYSQL:
        cursor = conn.cursor()
    else:
        cursor = conn.cursor(dictionary=True)
    
    # 获取所有产品（包括image_url字段）
    cursor.execute("""
        SELECT DISTINCT brand, model, image_url
        FROM mobile_phones 
        WHERE brand != '未分类'
        ORDER BY brand, model
    """)
    
    if USE_PYMYSQL:
        columns = [desc[0] for desc in cursor.description]
        all_products = [dict(zip(columns, row)) for row in cursor.fetchall()]
    else:
        all_products = cursor.fetchall()
    
    # 检查哪些没有图片或图片无效
    missing = []
    for p in all_products:
        has_image = False
        
        # 1. 检查数据库中的image_url是否有效
        if p.get('image_url'):
            img_path = p['image_url']
            # 处理相对路径
            if not img_path.startswith('http'):
                if img_path.startswith('images/'):
                    full_path = Path(__file__).parent / img_path
                else:
                    full_path = IMAGE_DIR / img_path
                
                if full_path.exists() and full_path.stat().st_size > 10000:
                    has_image = True
        
        # 2. 检查是否有匹配的文件
        if not has_image:
            filename = generate_filename(p['brand'], p['model'])
            filepath = IMAGE_DIR / filename
            
            if filepath.exists() and filepath.stat().st_size > 10000:
                has_image = True
            else:
                # 检查是否有类似文件（模糊匹配）
                clean_model = clean_model_name(p['model'], p['brand'])
                # 提取关键词进行匹配
                keywords = re.findall(r'[A-Za-z]+|\d+', clean_model)
                if keywords:
                    pattern = f"*{p['brand']}*{keywords[0]}*"
                    similar = list(IMAGE_DIR.glob(pattern))
                    if similar:
                        has_image = True
        
        if not has_image:
            missing.append(p)
    
    cursor.close()
    return missing


def update_database_image(conn, brand: str, model: str, image_path: str):
    """更新数据库中的图片路径"""
    cursor = conn.cursor()
    
    try:
        # 更新该品牌和型号的所有记录
        # 使用精确匹配和模糊匹配
        clean_model = clean_model_name(model, brand)
        
        # 先尝试精确匹配
        cursor.execute("""
            UPDATE mobile_phones 
            SET image_url = %s 
            WHERE brand = %s AND model = %s
        """, (image_path, brand, model))
        
        affected = cursor.rowcount
        
        # 如果精确匹配没有更新任何记录，尝试模糊匹配
        if affected == 0:
            cursor.execute("""
                UPDATE mobile_phones 
                SET image_url = %s 
                WHERE brand = %s AND model LIKE %s AND (image_url IS NULL OR image_url = '' OR image_url LIKE %s)
            """, (image_path, brand, f"%{clean_model}%", '%placehold%'))
            affected = cursor.rowcount
        
        conn.commit()
        print(f"    更新了 {affected} 条记录")
        
    except Exception as e:
        print(f"    ✗ 数据库更新失败: {e}")
        if not USE_PYMYSQL:
            conn.rollback()
    finally:
        cursor.close()


def main():
    """主函数"""
    print("=" * 60)
    print("甘肃汇森 - 手机图片自动采集脚本")
    print("=" * 60)
    print()
    
    # 确保目录存在
    IMAGE_DIR.mkdir(parents=True, exist_ok=True)
    print(f"图片保存目录: {IMAGE_DIR}")
    
    # 连接数据库
    print("\n连接数据库...")
    print(f"  主机: {DB_CONFIG['host']}:{DB_CONFIG['port']}")
    print(f"  用户: {DB_CONFIG['user']}")
    print(f"  数据库: {DB_CONFIG['database']}")
    
    conn = None
    try:
        # 使用 pymysql 或 mysql.connector 连接
        print("  正在连接MySQL服务器...")
        
        if USE_PYMYSQL:
            # 使用 pymysql（对中文数据库名支持更好）
            conn = pymysql.connect(
                host=DB_CONFIG['host'],
                port=DB_CONFIG['port'],
                user=DB_CONFIG['user'],
                password=DB_CONFIG['password'],
                database=DB_CONFIG['database'],
                charset=DB_CONFIG['charset'],
                cursorclass=pymysql.cursors.DictCursor
            )
            print("  ✓ MySQL服务器连接成功（使用 pymysql）")
        else:
            # 使用 mysql.connector（先连接服务器，再切换数据库）
            conn = mysql.connector.connect(
                host=DB_CONFIG['host'],
                port=DB_CONFIG['port'],
                user=DB_CONFIG['user'],
                password=DB_CONFIG['password'],
                charset=DB_CONFIG['charset'],
                use_unicode=True,
                autocommit=False
            )
            print("  ✓ MySQL服务器连接成功")
            
            # 切换到指定数据库
            print(f"  正在切换到数据库: {DB_CONFIG['database']}")
            cursor = conn.cursor()
            db_name = DB_CONFIG['database']
            cursor.execute(f"USE `{db_name}`")
            cursor.execute("SELECT DATABASE()")
            current_db = cursor.fetchone()[0]
            cursor.close()
            
            if current_db != db_name:
                print(f"✗ 数据库切换失败")
                print(f"  期望数据库: {db_name}")
                print(f"  当前数据库: {current_db}")
                conn.close()
                return
        
        print("✓ 数据库连接成功")
            
    except Exception as e:
        error_type = type(e).__name__
        print(f"✗ 数据库连接失败 ({error_type}): {e}")
        
        # 详细错误信息
        if USE_PYMYSQL:
            if hasattr(e, 'args') and len(e.args) > 0:
                print(f"  错误详情: {e.args[0]}")
            if hasattr(e, 'errno'):
                print(f"  错误代码: {e.errno}")
        else:
            if hasattr(e, 'errno'):
                print(f"  错误代码: {e.errno}")
            if hasattr(e, 'msg'):
                print(f"  错误信息: {e.msg}")
        
        print(f"\n请检查数据库配置：")
        print(f"  - 数据库名: {DB_CONFIG['database']}")
        print(f"  - 用户名: {DB_CONFIG['user']}")
        print(f"  - 主机: {DB_CONFIG['host']}:{DB_CONFIG['port']}")
        print(f"\n提示：")
        print(f"  1. 确保 MySQL 服务已启动（检查 XAMPP MySQL 是否运行）")
        print(f"  2. 确保数据库 '{DB_CONFIG['database']}' 已创建")
        print(f"  3. 确保用户 '{DB_CONFIG['user']}' 有访问权限")
        print(f"  4. 尝试在 phpMyAdmin 中手动连接测试")
        
        import traceback
        print(f"\n详细错误信息：")
        traceback.print_exc()
        
        if conn:
            try:
                conn.close()
            except:
                pass
        return
    
    # 获取缺少图片的产品
    print("\n扫描缺少图片的产品...")
    missing = get_products_without_images(conn)
    print(f"找到 {len(missing)} 个产品需要采集图片")
    
    if not missing:
        print("\n所有产品都已有图片！")
        conn.close()
        return
    
    # 按品牌分组
    by_brand = {}
    for p in missing:
        by_brand.setdefault(p['brand'], []).append(p)
    
    print("\n按品牌分布:")
    for brand, products in by_brand.items():
        print(f"  {brand}: {len(products)} 个")
    
    # 开始采集
    print("\n" + "=" * 60)
    print("开始采集图片...")
    print("=" * 60)
    
    success_count = 0
    fail_count = 0
    
    for i, p in enumerate(missing, 1):
        brand = p['brand']
        model = p['model']
        
        print(f"\n[{i}/{len(missing)}] {brand} {model}")
        
        # 生成搜索词和文件名
        queries = generate_search_query(brand, model)
        filename = generate_filename(brand, model)
        save_path = IMAGE_DIR / filename
        
        # 尝试多个搜索词
        image_urls = []
        for query in queries:
            print(f"  搜索: {query}")
            urls = search_bing_images(query, count=3)
            if urls:
                image_urls.extend(urls)
                print(f"    找到 {len(urls)} 张图片")
                # 如果找到足够多的图片，停止搜索
                if len(image_urls) >= 5:
                    break
        
        # 如果 Bing 没有结果，尝试百度
        if not image_urls:
            print("  Bing 无结果，尝试百度...")
            for query in queries[:2]:  # 只尝试前两个查询词
                urls = search_baidu_images(query, count=3)
                if urls:
                    image_urls.extend(urls)
                    if len(image_urls) >= 5:
                        break
        
        if not image_urls:
            print("  ✗ 未找到图片")
            fail_count += 1
            continue
        
        # 去重
        seen_urls = set()
        unique_urls = []
        for url in image_urls:
            if url not in seen_urls:
                seen_urls.add(url)
                unique_urls.append(url)
        
        print(f"  共找到 {len(unique_urls)} 张候选图片")
        
        # 尝试下载
        downloaded = False
        for url in unique_urls[:10]:  # 最多尝试10张
            if download_image(url, save_path):
                downloaded = True
                success_count += 1
                
                # 更新数据库
                image_rel_path = f"images/phones/{filename}"
                update_database_image(conn, brand, model, image_rel_path)
                print(f"  ✓ 数据库已更新: {image_rel_path}")
                
                break
        
        if not downloaded:
            fail_count += 1
            print("  ✗ 所有候选图片下载失败")
        
        # 随机延迟，避免被封
        if i < len(missing):  # 最后一个不需要延迟
            delay = random.uniform(2.0, 4.0)
            print(f"  等待 {delay:.1f} 秒...")
            time.sleep(delay)
    
    # 完成
    conn.close()
    
    print("\n" + "=" * 60)
    print("采集完成!")
    print("=" * 60)
    print(f"成功: {success_count}")
    print(f"失败: {fail_count}")
    print(f"图片保存在: {IMAGE_DIR}")


if __name__ == '__main__':
    main()
