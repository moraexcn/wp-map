# WordPress 足迹地图插件

基于高德地图API的WordPress足迹地图插件，支持添加地点标记、描述和前端地图自定义展示。

## 前端预览
<img width="800" height="937" alt="image" src="https://github.com/user-attachments/assets/f5db3e8e-5f74-4a6a-995b-b30ae6f2b5e0" />
<img width="800" height="935" alt="image" src="https://github.com/user-attachments/assets/4ac2e6bd-b82f-47e8-8875-ee16ccfc24d2" />

## 后台预览
<img width="800" height="580" alt="image" src="https://github.com/user-attachments/assets/cd6e0ba9-f082-4ae8-a0aa-0467a6f56cdc" />
<img width="800" height="955" alt="image" src="https://github.com/user-attachments/assets/5986f4fa-62a1-4c3e-a33e-ad79a5bf50dc" />
<img width="800" height="961" alt="image" src="https://github.com/user-attachments/assets/1287c109-e721-4675-884a-9f357aead124" />

## 功能特点

-  **地图标记管理** - 在地图上添加、编辑、删除足迹标记
-  **高德地图集成** - 使用高德地图API提供高质量的地图服务
-  **自定义样式** - 支持多种地图主题和标记颜色自定义
-  **关联链接** - 可将足迹与WordPress文章、页面或自定义链接关联
-  **响应式设计** - 完美适配桌面和移动设备
-  **多语言支持** - 内置中文语言包，易于扩展
-  **SEO友好** - 支持自定义页面标题、描述和URL路径
-  **实时预览** - 在添加足迹页面可随时查看地图效果

## 系统要求

- **WordPress版本**: 6.8+
- **PHP版本**: 8.3+
- **高德地图API密钥**: 需要在[高德开放平台](https://lbs.amap.com/)申请

## 安装指南

### 1. 下载插件

从GitHub仓库下载最新版本的插件压缩包。
### 2. 上传安装

1. 登录WordPress后台，进入"插件" -> "安装插件"
2. 点击"上传插件"，选择下载的插件压缩包
3. 点击"现在安装"并启用插件

### 3. 配置API密钥

1. 进入"插件" -> "足迹" -> "足迹设置"
2. 在"JavaScript API密钥"和"Web服务API密钥"字段中填入从高德开放平台获取的API密钥
   - JavaScript API密钥：用于在页面中显示地图
   - Web服务API密钥：用于地址搜索和地理编码
3. 保存设置

## 使用指南

### 添加足迹

1. 进入"插件" -> "足迹" -> "添加足迹"
2. 填写必要信息：
   - **地点名称** - 足迹的名称
   - **访问日期** - 到访该地点的日期
   - **位置信息** - 通过地址搜索或直接在地图上点击选择位置
3. 可选信息：
   - **地点描述** - 关于此地点的详细描述
   - **关联链接** - 关联到WordPress文章、页面或自定义URL
4. 点击"添加足迹"保存
5. **实时预览** - 点击标题右侧的"查看地图"按钮可随时预览当前所有足迹在地图上的显示效果

### 管理足迹

在"足迹列表"页面可以：
- 查看所有已添加的足迹
- 编辑现有足迹
- 删除不需要的足迹
- 查看与足迹关联的页面或文章

### 自定义地图显示

在"足迹设置"页面可以：
- 设置地图中心点和默认缩放级别
- 自定义地图主题（标准、远山黛、月光银等）
- 设置不同类型标记的颜色
- 自定义前端地图页面的URL路径、标题和描述
- 调整地图高度

## 前端访问

配置完成后，可以通过以下URL访问前端地图页面：
```
https://yourdomain.com/[自定义路径]
```

默认路径为 map ，例如：`https://yourdomain.com/map`

## 插件文件结构

```
wp-map/
├── admin/                      # 后台管理相关文件
│   ├── css/                    # 后台样式文件
│   ├── js/                     # 后台JavaScript文件
│   └── partials/               # 后台页面模板
│       ├── add-footprint.php   # 添加足迹页面（包含查看地图按钮）
│       ├── footprints-list.php # 足迹列表页面
│       └── settings-page.php   # 设置页面
├── includes/                   # 核心功能类文件
│   ├── class-wp-map-admin.php   # 后台管理类
│   ├── class-wp-map-api.php      # API处理类
│   ├── class-wp-map-frontend.php # 前端显示类
│   ├── class-wp-map-init.php     # 插件初始化类
│   ├── class-wp-map-install.php  # 插件安装类
│   └── class-wp-map-loader.php   # 钩子加载器类
├── languages/                 # 多语言文件
│   └── wp-map-zh_CN.po         # 中文语言包
├── public/                     # 前端资源文件
│   ├── css/                    # 前端样式文件
│   └── js/                     # 前端JavaScript文件
├── templates/                  # 前端模板文件
│   └── map-page.php           # 地图显示页面模板
├── wp-map.php                 # 插件主文件
└── README.md                  # 本文档
```

## 常见问题

### 如何获取高德地图API密钥？

1. 访问[高德开放平台](https://lbs.amap.com/)并注册/登录
2. 创建应用并选择"Web服务"
3. 获取"JavaScript API"和"Web服务API"的Key
4. 在插件设置页面填入获取到的Key
5. [高德地图 API 申请指南](https://www.moraex.com/wp-map)

### 地图不显示怎么办？

1. 检查API密钥是否正确填写
2. 确认API密钥是否有对应的权限
3. 检查浏览器控制台是否有错误信息
4. 确认服务器网络能正常访问高德地图API

### 自定义地图路径后页面404？

保存设置后，WordPress需要刷新重写规则。可以：
1. 进入"设置" -> "固定链接"页面，不做修改直接保存
2. 或者重新保存一次插件设置

## 更新日志

### v1.0  (2025-12-10)
- 插件初始版本

## 许可证

本插件采用GPL v2.0或更高版本许可证。详见[LICENSE](LICENSE)文件。



