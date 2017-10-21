<?php
$spark_config = array(
'charset' => 'utf-8',
'user_id' => '56761A7379431808',                                    //CC视频用户id
'key' => 'WmNhdVqJpAFMuAqXtbZIBqA01mNRjoLE',                        //CC视频用户key
'api_videos' => 'http://spark.bokecc.com/api/videos',               //api获取多个视频信息接口
'api_user' => 'http://spark.bokecc.com/api/user',                   //api获取用户信息接口
'api_playcode' => 'http://spark.bokecc.com/api/video/playcode',     //api获取视频播放代码接口
'api_deletevideo' => 'http://spark.bokecc.com/api/video/delete',    //api删除视频接口
'api_editvideo' => 'http://spark.bokecc.com/api/video/update',      //api编辑视频接口
'api_video' => 'http://spark.bokecc.com/api/video',                 //api获取单一视频接口
'api_category' => 'http://spark.bokecc.com/api/video/category',     //api获取视频分类接口

//notify_url			上传视频处理完成回调接口
);
$spark_config['notify_url'] = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER["REQUEST_URI"]) . '/notify.php';