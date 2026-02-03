<?php
/**
 * 自动配置 hosts 文件的工具
 * 注意：需要以管理员权限运行
 */

$hostsFile = 'C:\Windows\System32\drivers\etc\hosts';
$domain = 'huisen666';
$ip = '127.0.0.1';

// 检查是否以管理员权限运行
if (!is_writable($hostsFile)) {
    echo "❌ 错误：无法写入 hosts 文件\n";
    echo "请以管理员身份运行此脚本\n";
    echo "\n手动配置步骤：\n";
    echo "1. 以管理员身份打开记事本\n";
    echo "2. 打开文件：C:\\Windows\\System32\\drivers\\etc\\hosts\n";
    echo "3. 在文件末尾添加：127.0.0.1    huisen666\n";
    echo "4. 保存文件\n";
    echo "5. 运行命令：ipconfig /flushdns\n";
    exit(1);
}

// 读取现有内容
$content = file_get_contents($hostsFile);

// 检查是否已存在
if (strpos($content, $domain) !== false) {
    echo "✅ hosts 文件中已存在 $domain 的配置\n";
    echo "如果仍无法访问，请尝试：\n";
    echo "1. 运行：ipconfig /flushdns\n";
    echo "2. 重启浏览器\n";
    exit(0);
}

// 添加新配置
$newLine = "\n$ip    $domain    # 汇森科技本地开发\n";
file_put_contents($hostsFile, $content . $newLine, LOCK_EX);

echo "✅ 已成功添加 $domain 到 hosts 文件\n";
echo "\n下一步：\n";
echo "1. 运行命令刷新DNS：ipconfig /flushdns\n";
echo "2. 重启浏览器\n";
echo "3. 访问：http://$domain/index.php\n";
