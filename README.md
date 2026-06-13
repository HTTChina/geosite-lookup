# GeoSite PHP Lookup

一个无 Composer 依赖的 PHP 查询项目：输入域名、URL 或 IP，输出命中的 `geosite` / `geoip` 标签。

默认优先读取 V2Ray `.dat` 数据库：

- GeoIP: <https://github.com/Loyalsoldier/geoip/releases/latest/download/geoip.dat>
- GeoSite: <https://github.com/Loyalsoldier/v2ray-rules-dat/releases/latest/download/geosite.dat>

## 运行 Web 页面

```bash
php -S 127.0.0.1:8000 -t public
```

然后打开：

```text
http://127.0.0.1:8000
```

## 命令行查询

```bash
php bin/lookup.php google.com
php bin/lookup.php 8.8.8.8
php bin/lookup.php https://chat.openai.com
```

## 数据库

真实 `.dat` 数据放在：

- `data/source/geoip.dat`
- `data/source/geosite.dat`

重新下载：

```bash
mkdir -p data/source
curl -L -o data/source/geoip.dat https://github.com/Loyalsoldier/geoip/releases/latest/download/geoip.dat
curl -L -o data/source/geosite.dat https://github.com/Loyalsoldier/v2ray-rules-dat/releases/latest/download/geosite.dat
```

也可以运行：

```bash
php bin/update-dat.php
```

下载脚本还会生成 `data/source/metadata.json`，记录本地 `geoip.dat` / `geosite.dat` 对应的 GitHub release tag、发布时间、sha256 digest 和同版本资产列表。查询结果里的 `versions` 字段以及 Web 页面上的版本徽标都来自这个文件。

脚本还会下载这些文本列表到 `data/source/lists/`：

- `apple-cn.txt`
- `china-list.txt`
- `direct-list.txt`
- `direct-tld-list.txt`
- `gfw.txt`
- `google-cn.txt`

域名查询会额外返回 `list_matches`，用于显示命中的文本列表、规则类型和行号。IP 查询仍以 `geoip.dat` 为准。

`v2ray-rules-dat` release 里那些 `apple-cn.txt`、`china-list.txt`、`direct-list.txt`、`gfw.txt`、`google-cn.txt` 等是同一批规则导出的文本列表，方便给 Clash、Surge、dnsmasq 或其他规则格式使用；`geosite.dat` 是 V2Ray/Xray 使用的二进制 geosite 数据库，`geoip.dat` 是二进制 geoip 数据库。

如果 `.dat` 文件不存在，项目会回退到示例 JSON 规则：

- `data/geosite.json`: 域名规则，支持 `domain`、`suffix`、`keyword` 三类规则。
- `data/geoip.json`: IP CIDR 规则，支持 IPv4 和 IPv6。

规则示例：

```json
{
  "label": "geosite:google",
  "rules": {
    "domain": ["google.com"],
    "suffix": ["googleapis.com"],
    "keyword": ["google"]
  }
}
```

命中逻辑：

- `domain`: 精确匹配域名。
- `suffix`: 匹配当前域名或其子域名，例如 `maps.google.com` 命中 `google.com`。
- `keyword`: 域名包含关键词即可命中。
- `cidr`: IP 落入 CIDR 段即可命中。

## 测试

```bash
php tests/run.php
```
