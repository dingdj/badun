Spark API PHP示例说明

1. 概述
--------

本示例代码包括UTF-8和GBK两种编码格式，请根据需要参考相应的示例代码。

2. 文件说明
--------
charset.php							编码转换类
spark_function.php					工具函数类
xml2array.php						XML转换为数组类

swfobject.js						flash加载使用的js

spark_config.php					配置文件
storage.php							数据操作封装类，如果您的网站有数据库，可以替换为数据库操作的封装类

index.php							demo默认请求页
upload.php							视频上传页面
getuploadurl.php					返回上传请求参数
notify.php							视频处理完成回调接口
notify_info.php						回调数据展示页
play.php							视频播放页
userinfo.php						用户信息获取页
videolist.php						视频信息展示页
videosync.php						视频信息获取操作
video_edit.php                                          视频编辑页面
video_del.php						视频删除页面
video_edit_ajax.php					视频编辑页面AJAX提交处理
video_code.php						获取视频播放代码页面
crossdomain.xml						flash跨域请求策略文件

3. 使用说明
--------

3.1 获取API Key

Spark API的每一个通信接口都需要采用API Key来加密，因此必须先到CC视频后台获取后，才能正常使用API。

3.2 配置

网站相关的配置信息在 spark_config.php 中，使用前请将其填写完整。里面的主要参数含义如下：

userid				CC视频用户id
key					CC视频用户key
notify_url			上传视频处理完成回调接口
api_videos			api获取多个视频信息接口
api_user			api获取用户信息接口
api_playcode			api获取视频播放代码接口
api_deletevideo			api删除视频接口
api_editvideo			api编辑视频接口
api_video			api获取单一视频接口
api_category			api获取视频分类接口
3.3 启动

将示例代码放到你的网站或本地服务器任意路径下，访问 index.php 即可。

该实例代码在 Apache2.2 + PHP4/5 下测试通过。

