<?php
/**
 * Cookie 设置、获取、清除 (支持数组或对象直接设置) 2009-07-9
 * 1 获取cookie: cookie('name')
 * 2 清空当前设置前缀的所有cookie: cookie(null)
 * 3 删除指定前缀所有cookie: cookie(null,'think_') | 注：前缀将不区分大小写
 * 4 设置cookie: cookie('name','value') | 指定保存时间: cookie('name','value',3600)
 * 5 删除cookie: cookie('name',null)
 * $option 可用设置prefix,expire,path,domain
 * 支持数组形式:cookie('name','value',array('expire'=>1,'prefix'=>'think_'))
 * 支持query形式字符串:cookie('name','value','prefix=tp_&expire=10000')
 * 2010-1-17 去掉自动序列化操作，兼容其他语言程序。
 */
function cookie($name, $value = '', $option = null)
{
    // 默认设置
    $config = array(
        'prefix' => C('COOKIE_PREFIX'), // cookie 名称前缀
        'expire' => C('COOKIE_EXPIRE'), // cookie 保存时间
        'path' => C('COOKIE_PATH'),   // cookie 保存路径
        'domain' => C('COOKIE_DOMAIN'), // cookie 有效域名
    );

    // 参数设置(会覆盖黙认设置)
    if (!empty($option)) {
        if (is_numeric($option)) {
            $option = array('expire' => $option);
        } else if (is_string($option)) {
            parse_str($option, $option);
        }
        $config = array_merge($config, array_change_key_case($option));
    }

    // 清除指定前缀的所有cookie
    if (is_null($name)) {
        if (empty($_COOKIE)) return;
        // 要删除的cookie前缀，不指定则删除config设置的指定前缀
        $prefix = empty($value) ? $config['prefix'] : $value;
        if (!empty($prefix))// 如果前缀为空字符串将不作处理直接返回
        {
            foreach ($_COOKIE as $key => $val) {
                if (0 === stripos($key, $prefix)) {
                    //todo:https判断
                    setcookie($_COOKIE[$key], '', time() - 3600, $config['path'], $config['domain'], false, true);
                    unset($_COOKIE[$key]);
                }
            }
        }
        return;
    }
    $name = $config['prefix'] . $name;

    if ('' === $value) {
        //return isset($_COOKIE[$name]) ? unserialize($_COOKIE[$name]) : null;// 获取指定Cookie
        return isset($_COOKIE[$name]) ? ($_COOKIE[$name]) : null;// 获取指定Cookie
    } else {
        if (is_null($value)) {
            setcookie($name, '', time() - 3600, $config['path'], $config['domain']);
            unset($_COOKIE[$name]);// 删除指定cookie
        } else {
            // 设置cookie
            $expire = !empty($config['expire']) ? time() + intval($config['expire']) : 0;
            //setcookie($name,serialize($value),$expire,$config['path'],$config['domain']);
            setcookie($name, ($value), $expire, $config['path'], $config['domain']);
            //$_COOKIE[$name] = ($value);
        }
    }
}

//获取活动封面存储地址
function getCover($file_id, $width = 100, $height = 100, $cut = true, $replace = false)
{
    // if ($coverId > 0)
    //     $cover = model('Attach')->where("attach_id=$coverId")->find();
    // if ($cover) {
    //     $cover = getImageUrl($cover['save_path'] . $cover['save_name'], $width, $height, $cut, $replace);
    // } else {
    //     $cover = THEME_PUBLIC_URL . '/images/default-cover.png';
    // }
    // return $cover;
    $file_url = cut_auto($file_id, $width, $height);
    return cut_fix($file_url, $width, $height, $type = 1, $pos = 5, $start_x = 0, $start_y = 0);
}

/**
 * 图片裁剪函数，支持指定定点裁剪和方位裁剪两种裁剪模式
 * @param <string>  $src_file       原图片路径
 * @param <int>     $new_width      裁剪后图片宽度（当宽度超过原图片宽度时，去原图片宽度）
 * @param <int>     $new_height     裁剪后图片高度（当宽度超过原图片宽度时，去原图片高度）
 * @param <int>     $type           裁剪方式，1-方位模式裁剪；0-定点模式裁剪。
 * @param <int>     $pos            方位模式裁剪时的起始方位（当选定点模式裁剪时，此参数不起作用）
 *                                      1为顶端居左，2为顶端居中，3为顶端居右；
 *                                      4为中部居左，5为中部居中，6为中部居右；
 *                                      7为底端居左，8为底端居中，9为底端居右；
 * @param <int>     $start_x        起始位置X （当选定方位模式裁剪时，此参数不起作用）
 * @param <int>     $start_y        起始位置Y（当选定方位模式裁剪时，此参数不起作用）
 * @return <string>                 裁剪图片存储路径
 *
 * 此方法只对原来图片进行裁剪，不会等比例缩放
 */

//图片裁剪
function cut_fix($file_url, $new_width, $new_height, $type = 1, $pos = 5, $start_x = 0, $start_y = 0)
{
    $pathinfo = pathinfo($file_url);
    $src_file = UPLOAD_PATH . '/' . $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '.' . $pathinfo['extension'];
    $dst_file = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '_' . $new_width . '_' . $new_height . '.' . $pathinfo['extension'];
    if (!file_exists(UPLOAD_PATH . '/' . $dst_file)) {
        if ($new_width < 1 || $new_height < 1) {
            return false;
        }
        if (!file_exists($src_file)) {
            return UPLOAD_URL . '/' . $file_url;
        }
        // 图像类型
        list($old_w, $old_h, $img_type) = getimagesize($src_file);
        $support_type = array(1, 2, 3);
        if (!in_array($img_type, $support_type, true)) {
            return false;
        }
        /* 载入图像 */
        switch ($img_type) {
            case 1 :
                $src_img = imagecreatefromgif($src_file);
                break;
            case 2 :
                $src_img = imagecreatefromjpeg($src_file);
                break;
            case 3 :
                $src_img = imagecreatefrompng($src_file);
                break;
            default:
                echo "载入图像错误!";
                exit();
        }
        /* 获取源图片的宽度和高度 */
        $src_width = imagesx($src_img);
        $src_height = imagesy($src_img);
        /* 计算剪切图片的宽度和高度 */
        $mid_width = ($src_width < $new_width) ? $src_width : $new_width;
        $mid_height = ($src_height < $new_height) ? $src_height : $new_height;
        /* 初始化源图片剪切裁剪的起始位置坐标 */
        switch ($pos * $type) {
            case 1://1为顶端居左
                $start_x = 0;
                $start_y = 0;
                break;
            case 2://2为顶端居中
                $start_x = ($src_width - $mid_width) / 2;
                $start_y = 0;
                break;
            case 3://3为顶端居右
                $start_x = $src_width - $mid_width;
                $start_y = 0;
                break;
            case 4://4为中部居左
                $start_x = 0;
                $start_y = ($src_height - $mid_height) / 2;
                break;
            case 5://5为中部居中
                $start_x = ($src_width - $mid_width) / 2;
                $start_y = ($src_height - $mid_height) / 2;
                break;
            case 6://6为中部居右
                $start_x = $src_width - $mid_width;
                $start_y = ($src_height - $mid_height) / 2;
                break;
            case 7://7为底端居左
                $start_x = 0;
                $start_y = $src_height - $mid_height;
                break;
            case 8://8为底端居中
                $start_x = ($src_width - $mid_width) / 2;
                $start_y = $src_height - $mid_height;
                break;
            case 9://9为底端居右
                $start_x = $src_width - $mid_width;
                $start_y = $src_height - $mid_height;
                break;
            default://随机
                break;
        }
        // 为剪切图像创建背景画板
        $mid_img = imagecreatetruecolor($mid_width, $mid_height);
        //拷贝剪切的图像数据到画板，生成剪切图像
        imagecopy($mid_img, $src_img, 0, 0, $start_x, $start_y, $mid_width, $mid_height);
        // 为裁剪图像创建背景画板
        $new_img = imagecreatetruecolor($new_width, $new_height);
        //拷贝剪切图像到背景画板，并按比例裁剪
        imagecopyresampled($new_img, $mid_img, 0, 0, 0, 0, $new_width, $new_height, $mid_width, $mid_height);

        /* 按格式保存为图片 */
        switch ($img_type) {
            case 1 :
                imagegif($new_img, UPLOAD_PATH . '/' . $dst_file, 100);
                break;
            case 2 :
                imagejpeg($new_img, UPLOAD_PATH . '/' . $dst_file, 100);
                break;
            case 3 :
                imagepng($new_img, UPLOAD_PATH . '/' . $dst_file, 9);
                break;
            default:
                break;
        }
    }
    return UPLOAD_URL . '/' . $dst_file;
}

//等比例缩放裁剪，由宽高的最小值决定，但不精确
function cut_auto($file_id, $width, $height)
{
    $file_info = model('Attach')->where("attach_id=" . $file_id)->find();
    if ($file_info) {
        $backimg = UPLOAD_PATH . '/' . $file_info['save_path'] . $file_info['save_name'];
        $src_file_url = $file_info['save_path'] . $file_info['save_name'];
    } else {
        $backimg = SITE_URL . '/apps/event/_static/images/zwfm.png';
    }
    $pathinfo = pathinfo($src_file_url);
    $newsfile = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '_' . $width . '_' . $height . '.' . $pathinfo['extension'];
    if (!file_exists(UPLOAD_PATH . '/' . $newsfile)) {
        list($s_w, $s_h, $exten) = getimagesize($backimg);
        //等比例固定算法
        if ($width && ($s_w / $width) > ($s_h / $height)) {
            $width = ($height / $s_h) * $s_w;
        } else {
            $height = ($width / $s_w) * $s_h;
        }

        $new = imagecreatetruecolor($width, $height);//创建一个真色彩
        //根据得到的扩展名不同不用的GD库函数处理
        if ($exten == 1) {
            $old = imagecreatefromgif($backimg);
        } elseif ($exten == 2) {
            $old = imagecreatefromjpeg($backimg);
        } elseif ($exten == 3) {
            $old = imagecreatefrompng($backimg);
        }
        //遇到gif背景透明色的处理
        $otcs = imagecolortransparent($old);
        if ($otcs >= 0 && $otcs < imagecolorstotal($old)) {
            $tran = imagecolorsforindex($old, $otcs);
            $newtran = imagecolorallocate($new, $tran["red"], $tran["green"], $tran["blue"]);
            imagefill($new, 0, 0, $newtran);
            imagecolortransparent($new, $newtran);
        }
        imagecopyresampled($new, $old, 0, 0, 0, 0, $width, $height, $s_w, $s_h);
        //根据得到的扩展名不同不用的GD库函数处理
        if ($exten == 1) {
            imagegif($new, UPLOAD_PATH . '/' . $newsfile);
        } elseif ($exten == 2) {
            imagejpeg($new, UPLOAD_PATH . '/' . $newsfile);
        } elseif ($exten == 3) {
            imagepng($new, UPLOAD_PATH . '/' . $newsfile);
        }
        imagedestroy($new);
        imagedestroy($old);
    }
    return $newsfile;
}

function cutImg($file_id, $width, $height)
{
    $file_url = cut_auto($file_id, $width, $height);
    return cut_fix($file_url, $width, $height, $type = 1, $pos = 5, $start_x = 0, $start_y = 0);
}


/**
 * 抓取的url链接内容
 * @param string $url 要抓取的url链接,可以是http,https链接
 * @param int $second 设置cURL允许执行的最长秒数
 * @return mixed
 */
function get_curl_contents($url, $second = 30)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, $second);//设置cURL允许执行的最长秒数
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//当此项为true时,curl_exec($ch)返回的是内容;为false时,curl_exec($ch)返回的是true/false

    //以下两项设置为FALSE时,$url可以为"https://login.yahoo.com"协议
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

    $content = curl_exec($ch);
    curl_close($ch);
    return $content;
}

/**
 * session管理函数
 * @param string|array $name session名称 如果为数组则表示进行session设置
 * @param mixed $value session值
 * @return mixed
 */
function session($name, $value = '')
{
    $prefix = C('SESSION_PREFIX');
    if (is_array($name)) {
        // session初始化 在session_start之前调用
        if (isset($name['prefix'])) {
            C('SESSION_PREFIX', $name['prefix']);
        }
        if (C('VAR_SESSION_ID') && isset($_REQUEST[C('VAR_SESSION_ID')])) {
            session_id($_REQUEST[C('VAR_SESSION_ID')]);
        } else if (isset($name['id'])) {
            session_id($name['id']);
        }
        ini_set('session.auto_start', 0);
        if (isset($name['name'])) {
            session_name($name['name']);
        }
        if (isset($name['path'])) {
            session_save_path($name['path']);
        }
        if (isset($name['domain'])) {
            ini_set('session.cookie_domain', $name['domain']);
        }
        if (isset($name['expire'])) {
            ini_set('session.gc_maxlifetime', $name['expire']);
        }
        if (isset($name['use_trans_sid'])) {
            ini_set('session.use_trans_sid', $name['use_trans_sid'] ? 1 : 0);
        }
        if (isset($name['use_cookies'])) {
            ini_set('session.use_cookies', $name['use_cookies'] ? 1 : 0);
        }
        if (isset($name['cache_limiter'])) {
            session_cache_limiter($name['cache_limiter']);
        }
        if (isset($name['cache_expire'])) {
            session_cache_expire($name['cache_expire']);
        }
        if (isset($name['type'])) {
            C('SESSION_TYPE', $name['type']);
        }
        if (C('SESSION_TYPE')) {
            // 读取session驱动
            $class = 'Session' . ucwords(strtolower(C('SESSION_TYPE')));
            // 检查驱动类
            if (require_once(CORE_LIB_PATH . '/Session/' . $class . '.class.php')) {
                $hander = new $class();
                $hander->execute();
            } else {
                // 类没有定义
                throw_exception(L('_CLASS_NOT_EXIST_') . ': ' . $class);
            }
        }
        // 启动session
        if (C('SESSION_AUTO_START')) {
            session_start();
        }
    } else if ('' === $value) {
        if (0 === strpos($name, '[')) { // session 操作
            if ('[pause]' == $name) {
                // 暂停session
                session_write_close();
            } else if ('[start]' == $name) {
                // 启动session
                session_start();
            } else if ('[destroy]' == $name) {
                // 销毁session
                $_SESSION = array();
                session_unset();
                session_destroy();
            } else if ('[regenerate]' == $name) {
                // 重新生成id
                session_regenerate_id();
            }
        } else if (0 === strpos($name, '?')) { // 检查session
            $name = substr($name, 1);
            if (strpos($name, '.')) {
                // 支持数组
                list($name1, $name2) = explode('.', $name);
                return $prefix ? isset($_SESSION[$prefix][$name1][$name2]) : isset($_SESSION[$name1][$name2]);
            } else {
                return $prefix ? isset($_SESSION[$prefix][$name]) : isset($_SESSION[$name]);
            }
        } else if (is_null($name)) { // 清空session
            if ($prefix) {
                unset($_SESSION[$prefix]);
            } else {
                $_SESSION = array();
            }
        } else if ($prefix) { // 获取session
            if (strpos($name, '.')) {
                list($name1, $name2) = explode('.', $name);
                return isset($_SESSION[$prefix][$name1][$name2]) ? $_SESSION[$prefix][$name1][$name2] : null;
            } else {
                return isset($_SESSION[$prefix][$name]) ? $_SESSION[$prefix][$name] : null;
            }
        } else {
            if (strpos($name, '.')) {
                list($name1, $name2) = explode('.', $name);
                return isset($_SESSION[$name1][$name2]) ? $_SESSION[$name1][$name2] : null;
            } else {
                return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
            }
        }
    } else if (is_null($value)) { // 删除session
        if ($prefix) {
            unset($_SESSION[$prefix][$name]);
        } else {
            unset($_SESSION[$name]);
        }
    } else { // 设置session
        if ($prefix) {
            if (!is_array($_SESSION[$prefix])) {
                $_SESSION[$prefix] = array();
            }
            $_SESSION[$prefix][$name] = $value;
        } else {
            $_SESSION[$name] = $value;
        }
    }
}

/**
 * 获取站点唯一密钥，用于区分同域名下的多个站点
 * @return string
 */
function getSiteKey()
{
    return md5(C('SECURE_KEY') . C('SECURE_CODE') . C('COOKIE_PREFIX'));
}

/**
 * 是否AJAX请求
 * @return bool
 */
function isAjax()
{
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        if ('xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH']))
            return true;
    }
    if (!empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')]))
        return true;
    return false;
}

/**
 * 字符串命名风格转换
 * type
 * =0 将Java风格转换为C的风格
 * =1 将C风格转换为Java的风格
 * @param string $name 字符串
 * @param integer $type 转换类型
 * @return string
 */
function parse_name($name, $type = 0)
{
    if ($type) {
        return ucfirst(preg_replace("/_([a-zA-Z])/e", "strtoupper('\\1')", $name));
    } else {
        $name = preg_replace("/[A-Z]/", "_\\0", $name);
        return strtolower(trim($name, "_"));
    }
}

/**
 * 优化格式的打印输出
 * @param string $var 变量
 * @param bool $return 是否return
 * @return mixed
 */
function dump($var, $return = false)
{
    ob_start();
    var_dump($var);
    $output = ob_get_clean();
    if (!extension_loaded('xdebug')) {
        $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
        $output = '<pre style="text-align:left">' . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
    }
    if (!$return) {
        echo '<pre style="text-align:left">';
        echo($output);
        echo '</pre>';
    } else
        return $output;
}

/**
 * 自定义异常处理
 * @param string $msg 异常消息
 * @param string $type 异常类型
 * @return string
 */
function throw_exception($msg, $type = '')
{
    header("Content-Type:text/html; charset=UTF8");
    if (defined('IS_CGI') && IS_CGI) exit($msg);
    if (class_exists($type, false))
        throw new $type($msg, $code, true);
    else
        die($msg); // 异常类型不存在则输出错误信息字串
}

/**
 * 系统自动加载ThinkPHP基类库和当前项目的model和Action对象
 * 并且支持配置自动加载路径
 * @param string $name 对象类名
 * @return void
 */
function halt($text)
{
    return dump($text);
}

/**
 * 区分大小写的文件存在判断
 * @param string $filename 文件明
 * @return bool
 */
function file_exists_case($filename)
{
    if (is_file($filename)) {
        if (IS_WIN && C('APP_FILE_CASE')) {
            if (basename(realpath($filename)) != basename($filename))
                return false;
        }
        return true;
    }
    return false;
}

/**
 * 根据PHP各种类型变量生成唯一标识号
 * @param mixed $mix 输入变量
 * @return string 输出唯一编号
 */
function to_guid_string($mix)
{
    if (is_object($mix) && function_exists('spl_object_hash')) {
        return spl_object_hash($mix);
    } elseif (is_resource($mix)) {
        $mix = get_resource_type($mix) . strval($mix);
    } else {
        $mix = serialize($mix);
    }
    return md5($mix);
}

/**
 * 取得对象实例 支持调用类的静态方法
 * @param string $name 类名
 * @param string $method 方法
 * @param string $args 参数
 * @return object 对象实例
 */
function get_instance_of($name, $method = '', $args = array())
{
    static $_instance = array();
    $identify = empty($args) ? $name . $method : $name . $method . to_guid_string($args);
    if (!isset($_instance[$identify])) {
        if (class_exists($name)) {
            $o = new $name();
            if (method_exists($o, $method)) {
                if (!empty($args)) {
                    $_instance[$identify] = call_user_func_array(array(&$o, $method), $args);
                } else {
                    $_instance[$identify] = $o->$method();
                }
            } else
                $_instance[$identify] = $o;
        } else
            halt(L('_CLASS_NOT_EXIST_') . ':' . $name);
    }
    return $_instance[$identify];
}

/**
 * 自动加载类
 * @param string $name 类名
 * @return void
 */
function __autoload($name)
{
    // 检查是否存在别名定义
    if (import($name)) return;
    // 自动加载当前项目的Actioon类和Model类
    if (substr($name, -5) == "Model") {
        import(APP_LIB_PATH . 'Model/' . ucfirst($name) . '.class.php');
    } elseif (substr($name, -6) == "Action") {
        import(APP_LIB_PATH . 'Action/' . ucfirst($name) . '.class.php');
    } else {
        // 根据自动加载路径设置进行尝试搜索
        if (C('APP_AUTOLOAD_PATH')) {
            $paths = explode(',', C('APP_AUTOLOAD_PATH'));
            foreach ($paths as $path) {
                if (import($path . '/' . $name . '.class.php')) {
                    // 如果加载类成功则返回
                    return;
                }
            }
        }
    }
    return;
}

/**
 * 导入类库
 * @param string $name 类名
 * @return bool
 */
function import($filename)
{
    static $_importFiles = array();
    if (!isset($_importFiles[$filename])) {
        if (file_exists($filename)) {
            require $filename;
            $_importFiles[$filename] = true;
        } else {
            $file = explode('.', $filename);
            if (file_exists(APPS_PATH . '/' . $file[0] . '/Lib/' . $file[1] . '/' . $file[2] . '.class.php')) {
                require APPS_PATH . '/' . $file[0] . '/Lib/' . $file[1] . '/' . $file[2] . '.class.php';
                $_importFiles[$filename] = true;
            } else {
                $_importFiles[$filename] = false;
            }
        }
    }
    return $_importFiles[$filename];
}

/**
 * C函数用于读取/设置系统配置
 * @param string name 配置名称
 * @param string value 值
 * @return mixed 配置值|设置状态
 */
function C($name = null, $value = null)
{
    global $ts;
    // 无参数时获取所有
    if (empty($name)) return $ts['_config'];
    // 优先执行设置获取或赋值
    if (is_string($name)) {
        if (!strpos($name, '.')) {
            $name = strtolower($name);
            if (is_null($value))
                return isset($ts['_config'][$name]) ? $ts['_config'][$name] : null;
            $ts['_config'][$name] = $value;
            return;
        }
        // 二维数组设置和获取支持
        $name = explode('.', $name);
        $name[0] = strtolower($name[0]);
        if (is_null($value))
            return isset($ts['_config'][$name[0]][$name[1]]) ? $ts['_config'][$name[0]][$name[1]] : null;
        $ts['_config'][$name[0]][$name[1]] = $value;
        return;
    }
    // 批量设置
    if (is_array($name))
        return $ts['_config'] = array_merge((array)$ts['_config'], array_change_key_case($name));
    return null;// 避免非法参数
}

//D函数的别名
function M($name = '', $app = '@')
{
    return D($name, $app);
}

/**
 * D函数用于实例化Model
 * @param string name Model名称
 * @param string app Model所在项目
 * @return object
 */
function D($name = '', $app = '@', $inclueCommonFunction = true)
{
    static $_model = array();

    if (empty($name)) return new Model;
    if (empty($app) || $app == '@') $app = APP_NAME;

    $name = ucfirst($name);

    if (isset($_model[$app . $name]))
        return $_model[$app . $name];

    $OriClassName = $name;
    $className = $name . 'Model';

    //优先载入核心的 所以不要和核心的model重名
    if (file_exists(ADDON_PATH . '/model/' . $className . '.class.php')) {
        tsload(ADDON_PATH . '/model/' . $className . '.class.php');
    } elseif (file_exists(APPS_PATH . '/' . $app . '/Lib/Model/' . $className . '.class.php')) {
        $common = APPS_PATH . '/' . $app . '/Common/common.php';
        if (file_exists($common) && $inclueCommonFunction) {
            tsload($common);
        }
        tsload(APPS_PATH . '/' . $app . '/Lib/Model/' . $className . '.class.php');
    }

    if (class_exists($className)) {

        $model = new $className();
    } else {
        $model = new Model($name);
    }
    $_model[$app . $OriClassName] = $model;
    return $model;
}

/**
 * A函数用于实例化Action
 * @param string name Action名称
 * @param string app Model所在项目
 * @return object
 */
function A($name, $app = '@')
{
    static $_action = array();

    if (empty($app) || $app == '@') $app = APP_NAME;

    if (isset($_action[$app . $name]))
        return $_action[$app . $name];

    $OriClassName = $name;
    $className = $name . 'Action';
    tsload(APP_ACTION_PATH . '/' . $className . '.class.php');

    if (class_exists($className)) {
        $action = new $className();
        $_action[$app . $OriClassName] = $action;
        return $action;
    } else {
        return false;
    }
}

/**
 * L函数用于读取/设置语言配置
 * @param string name 配置名称
 * @param string value 值
 * @return mixed 配置值|设置状态
 */
function L($key, $data = array())
{
    $key = strtoupper($key);
    if (!isset($GLOBALS['_lang'][$key])) {
        $notValveForKey = F('notValveForKey', '', DATA_PATH . '/develop');
        if ($notValveForKey == false) {
            $notValveForKey = array();
        }
        if (!isset($notValveForKey[$key])) {
            $notValveForKey[$key] = '?app=' . APP_NAME . '&mod=' . MODULE_NAME . '&act=' . ACTION_NAME;
        }
        F('notValveForKey', $notValveForKey, DATA_PATH . '/develop');

        return $key;
    }
    if (empty($data)) {
        return $GLOBALS['_lang'][$key];
    }
    $replace = array_keys($data);
    foreach ($replace as &$v) {
        $v = "{" . $v . "}";
    }
    return str_replace($replace, $data, $GLOBALS['_lang'][$key]);
}

/**
 * 记录和统计时间（微秒）和内存使用情况
 * 使用方法:
 * <code>
 * G('begin'); // 记录开始标记位
 * // ... 区间运行代码
 * G('end'); // 记录结束标签位
 * echo G('begin','end',6); // 统计区间运行时间 精确到小数后6位
 * echo G('begin','end','m'); // 统计区间内存使用情况
 * 如果end标记位没有定义，则会自动以当前作为标记位
 * 其中统计内存使用需要 MEMORY_LIMIT_ON 常量为true才有效
 * </code>
 * @param string $start 开始标签
 * @param string $end 结束标签
 * @param integer|string $dec 小数位或者m
 * @return mixed
 */
function G($start, $end = '', $dec = 4)
{
    static $_info = array();
    static $_mem = array();
    if (is_float($end)) { // 记录时间
        $_info[$start] = $end;
    } elseif (!empty($end)) { // 统计时间和内存使用
        if (!isset($_info[$end])) $_info[$end] = microtime(TRUE);
        if (MEMORY_LIMIT_ON && $dec == 'm') {
            if (!isset($_mem[$end])) $_mem[$end] = memory_get_usage();
            return number_format(($_mem[$end] - $_mem[$start]) / 1024);
        } else {
            return number_format(($_info[$end] - $_info[$start]), $dec);
        }

    } else { // 记录时间和内存使用
        $_info[$start] = microtime(TRUE);
        if (MEMORY_LIMIT_ON) $_mem[$start] = memory_get_usage();
    }
}

/**
 * 设置和获取统计数据
 * 使用方法:
 * <code>
 * N('db',1); // 记录数据库操作次数
 * N('read',1); // 记录读取次数
 * echo N('db'); // 获取当前页面数据库的所有操作次数
 * echo N('read'); // 获取当前页面读取次数
 * </code>
 * @param string $key 标识位置
 * @param integer $step 步进值
 * @return mixed
 */
function N($key, $step = 0, $save = false)
{
    static $_num = array();
    if (!isset($_num[$key])) {
        $_num[$key] = (false !== $save) ? S('N_' . $key) : 0;
    }
    if (empty($step))
        return $_num[$key];
    else
        $_num[$key] = $_num[$key] + (int)$step;
    if (false !== $save) { // 保存结果
        S('N_' . $key, $_num[$key], $save);
    }
}

/**
 * 用于判断文件后缀是否是图片
 * @param string file 文件路径，通常是$_FILES['file']['tmp_name']
 * @return bool
 */
function is_image_file($file)
{
    $fileextname = strtolower(substr(strrchr(rtrim(basename($file), '?'), "."), 1, 4));
    if (in_array($fileextname, array('jpg', 'jpeg', 'gif', 'png', 'bmp'))) {
        return true;
    } else {
        return false;
    }
}

/**
 * 用于判断文件后缀是否是PHP、EXE类的可执行文件
 * @param string file 文件路径
 * @return bool
 */
function is_notsafe_file($file)
{
    $fileextname = strtolower(substr(strrchr(rtrim(basename($file), '?'), "."), 1, 4));
    if (in_array($fileextname, array('php', 'php3', 'php4', 'php5', 'exe', 'sh'))) {
        return true;
    } else {
        return false;
    }
}

/**
 * t函数用于过滤标签，输出没有html的干净的文本
 * @param string text 文本内容
 * @return string 处理后内容
 */
function t($text)
{
    $text = nl2br($text);
    $text = real_strip_tags($text);
    $text = addslashes($text);
    $text = trim($text);
    return $text;
}

/**
 * h函数用于过滤不安全的html标签，输出安全的html
 * @param string $text 待过滤的字符串
 * @param string $type 保留的标签格式
 * @return string 处理后内容
 */
function h($text, $type = 'html')
{
    // 无标签格式
    $text_tags = '';
    //只保留链接
    $link_tags = '<a>';
    //只保留图片
    $image_tags = '<img>';
    //只存在字体样式
    $font_tags = '<i><b><u><s><em><strong><font><big><small><sup><sub><bdo><h1><h2><h3><h4><h5><h6>';
    //标题摘要基本格式
    $base_tags = $font_tags . '<p><br><hr><a><img><map><area><pre><code><q><blockquote><acronym><cite><ins><del><center><strike>';
    //兼容Form格式
    $form_tags = $base_tags . '<form><input><textarea><button><select><optgroup><option><label><fieldset><legend>';
    //内容等允许HTML的格式
    $html_tags = $base_tags . '<meta><ul><ol><li><dl><dd><dt><table><caption><td><th><tr><thead><tbody><tfoot><col><colgroup><div><span><object><embed><param>';
    //专题等全HTML格式
    $all_tags = $form_tags . $html_tags . '<!DOCTYPE><html><head><title><body><base><basefont><script><noscript><applet><object><param><style><frame><frameset><noframes><iframe>';
    //过滤标签
    $text = real_strip_tags($text, ${$type . '_tags'});
    // 过滤攻击代码
    if ($type != 'all') {
        // 过滤危险的属性，如：过滤on事件lang js
        while (preg_match('/(<[^><]+)(ondblclick|onclick|onload|onerror|unload|onmouseover|onmouseup|onmouseout|onmousedown|onkeydown|onkeypress|onkeyup|onblur|onchange|onfocus|action|background|codebase|dynsrc|lowsrc)([^><]*)/i', $text, $mat)) {
            $text = str_ireplace($mat[0], $mat[1] . $mat[3], $text);
        }
        while (preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i', $text, $mat)) {
            $text = str_ireplace($mat[0], $mat[1] . $mat[3], $text);
        }
    }
    return $text;
}

/**
 * U函数用于生成URL地址
 * @param string $url ThinkSNS特有URL标识符
 * @param array $params URL附加参数
 * @param bool $redirect 是否自动跳转到生成的URL
 * @return string 输出URL
 */
function U($url, $params = false, $redirect = false)
{

    //普通模式
    if (false == strpos($url, '/')) {
        $url .= '//';
    }

    //填充默认参数
    $urls = explode('/', $url);
    $app = isset($urls[0]) && !empty($urls[0]) ? $urls[0] : APP_NAME;
    $mod = isset($urls[1]) && !empty($urls[1]) ? ucfirst($urls[1]) : 'Index';
    $act = isset($urls[2]) && !empty($urls[2]) ? $urls[2] : 'index';

    //组合默认路径
    $site_url = SITE_URL . '/index.php?app=' . $app . '&mod=' . $mod . '&act=' . $act;

    //填充附加参数
    if ($params) {
        if (is_array($params)) {
            $params = http_build_query($params);
            $params = urldecode($params);
        }
        $params = str_replace('&amp;', '&', $params);
        $site_url .= '&' . $params;
    }

    //开启路由和Rewrite
    if (C('URL_ROUTER_ON')) {

        //载入路由
        $router_ruler = C('router');
        $router_key = $app . '/' . $mod . '/' . $act;

        //路由命中
        if (isset($router_ruler[$router_key])) {

            //填充路由参数
            if (false == strpos($router_ruler[$router_key], '://')) {
                $site_url = SITE_URL . '/' . $router_ruler[$router_key];
            } else {
                $site_url = $router_ruler[$router_key];
            }

            //填充附加参数
            if ($params) {

                //解析替换URL中的参数
                parse_str($params, $r);
                foreach ($r as $k => $v) {
                    if (strpos($site_url, '[' . $k . ']')) {
                        $site_url = str_replace('[' . $k . ']', $v, $site_url);
                    } else {
                        $lr[$k] = $v;
                    }
                }

                //填充剩余参数
                if (isset($lr) && is_array($lr) && count($lr) > 0) {
                    $site_url .= '?' . http_build_query($lr);
                }

            }
        }
    }

    //输出地址或跳转
    if ($redirect) {
        redirect($site_url);
    } else {
        return $site_url;
    }
}

/**
 * URL跳转函数
 * @param string $url ThinkSNS特有URL标识符
 * @param integer $time 跳转延时(秒)
 * @param string $msg 提示语
 * @return void
 */
function redirect($url, $time = 0, $msg = '')
{
    //多行URL地址支持
    $url = str_replace(array("\n", "\r"), '', $url);
    if (empty($msg))
        $msg = "系统将在{$time}秒之后自动跳转到{$url}！";
    if (!headers_sent()) {
        // redirect
        if (0 === $time) {
            header("Location: " . $url);
        } else {
            header("Content-type: text/html; charset=utf-8");
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit();
    } else {
        $str = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if ($time != 0)
            $str .= $msg;
        exit($str);
    }
}

/**
 * 用来对应用缓存信息的读、写、删除
 *
 * $expire = null/0 表示永久缓存，否则为缓存有效期
 */
function S($name, $value = '', $expire = null)
{

    static $_cache = array();   //减少缓存读取

    $cache = model('Cache');

    //$name = C('DATA_CACHE_PREFIX').$name;

    if ('' !== $value) {

        if (is_null($value)) {
            // 删除缓存
            $result = $cache->rm($name);
            if ($result) unset($_cache[$name]);
            return $result;
        } else {
            // 缓存数据
            $cache->set($name, $value, $expire);
            $_cache[$name] = $value;
        }
        return true;
    }
    if (isset($_cache[$name]))
        return $_cache[$name];
    // 获取缓存数据
    $value = $cache->get($name);
    $_cache[$name] = $value;
    return $value;
}

/**
 * 文件缓存,多用来缓存配置信息
 *
 */
function F($name, $value = '', $path = false)
{
    static $_cache = array();
    if (!$path) {
        $path = C('F_CACHE_PATH');
    }
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
    $filename = $path . '/' . $name . '.php';
    if ('' !== $value) {
        if (is_null($value)) {
            // 删除缓存
            return unlink($filename);
        } else {
            // 缓存数据
            $dir = dirname($filename);
            // 目录不存在则创建
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            return @file_put_contents($filename, "<?php\nreturn " . var_export($value, true) . ";\n?>");
        }
    }
    if (isset($_cache[$name])) return $_cache[$name];
    // 获取缓存数据
    if (is_file($filename)) {
        $value = include $filename;
        $_cache[$name] = $value;
    } else {
        $value = false;
    }
    return $value;
}

function W($name, $data = array(), $return = false)
{
    $class = $name . 'Widget';
    if (file_exists(APP_WIDGET_PATH . '/' . $class . '/' . $class . '.class.php')) {
        tsload(APP_WIDGET_PATH . '/' . $class . '/' . $class . '.class.php');
    } elseif (!empty($data['widget_appname']) && file_exists(APPS_PATH . '/' . $data['widget_appname'] . '/Lib/Widget/' . $class . '/' . $class . '.class.php')) {
        addLang($data['widget_appname']);
        tsload(APPS_PATH . '/' . $data['widget_appname'] . '/Lib/Widget/' . $class . '/' . $class . '.class.php');
    } else {
        tsload(ADDON_PATH . '/widget/' . $class . '/' . $class . '.class.php');
    }
    if (!class_exists($class))
        throw_exception(L('_CLASS_NOT_EXIST_') . ':' . $class);
    $widget = new $class();
    $content = $widget->render($data);
    if ($return)
        return $content;
    else
        echo $content;
}

// 实例化服务
function api($name, $params = array())
{
    static $_api = array();
    if (isset($_api[$name])) {
        return $_api[$name];
    }
    $OriClassName = $name;
    $className = $name . 'Api';
    require_once(ADDON_PATH . '/api/' . $name . 'Api.class.php');
    if (class_exists($className)) {
        $api = new $className(true);
        $_api[$OriClassName] = $api;
        return $api;
    } else {
        return false;
    }
}

// 实例化服务
function service($name, $params = array())
{
    return X($name, $params, 'service');
}

// 实例化服务
function widget($name, $params = array(), $return = false)
{
    return X($name, $params, 'widget');
}

// 实例化model
function model($name, $params = array())
{
    return X($name, $params, 'model');
}

// 调用接口服务
function X($name, $params = array(), $domain = 'model')
{
    static $_service = array();

    $app = C('DEFAULT_APP');

    $domain = ucfirst($domain);

    if (isset($_service[$domain . '_' . $app . '_' . $name]))
        return $_service[$domain . '_' . $app . '_' . $name];

    $class = $name . $domain;
    if (file_exists(APP_LIB_PATH . $domain . '/' . $class . '.class.php')) {
        tsload(APP_LIB_PATH . $domain . '/' . $class . '.class.php');
    } else {
        tsload(ADDON_PATH . '/' . strtolower($domain) . '/' . $class . '.class.php', true);
    }
    //服务不可用时 记录日志 或 抛出异常
    if (class_exists($class)) {
        $obj = new $class($params);
        $_service[$domain . '_' . $app . '_' . $name] = $obj;
        return $obj;
    } else {
        throw_exception(L('_CLASS_NOT_EXIST_') . ':' . $class);
    }
}

// 渲染模板
//$charset 不能是UTF8 否则IE下会乱码
function fetch($templateFile = '', $tvar = array(), $charset = 'utf-8', $contentType = 'text/html', $display = false)
{
    //注入全局变量ts
    global $ts;
    $tvar['ts'] = $ts;
    //$GLOBALS['_viewStartTime'] = microtime(TRUE);

    if (null === $templateFile)
        // 使用null参数作为模版名直接返回不做任何输出
        return;

    if (empty($charset)) $charset = C('DEFAULT_CHARSET');

    // 网页字符编码
    header("Content-Type:" . $contentType . "; charset=" . $charset);

    header("Cache-control: private");  //支持页面回跳

    //页面缓存
    ob_start();
    ob_implicit_flush(0);

    // 模版名为空.
    if ('' == $templateFile) {
        $templateFile = APP_TPL_PATH . '/' . MODULE_NAME . '/' . ACTION_NAME . '.html';

        // 模版名为ACTION_NAME
    } elseif (file_exists(APP_TPL_PATH . '/' . MODULE_NAME . '/' . $templateFile . '.html')) {
        $templateFile = APP_TPL_PATH . '/' . MODULE_NAME . '/' . $templateFile . '.html';

        // 模版是绝对路径
    } elseif (file_exists($templateFile)) {

        // 模版不存在
    } else {
        throw_exception(L('_TEMPLATE_NOT_EXIST_') . '[' . $templateFile . ']');
    }

    //模版缓存文件
    $templateCacheFile = C('TMPL_CACHE_PATH') . '/' . APP_NAME . '_' . tsmd5($templateFile) . '.php';

    //载入模版缓存
    if (!$ts['_debug'] && file_exists($templateCacheFile)) {
        //if(1==2){ //TODO  开发
        extract($tvar, EXTR_OVERWRITE);

        //载入模版缓存文件
        include $templateCacheFile;

        //重新编译
    } else {

        tshook('tpl_compile', array('templateFile', $templateFile));

        // 缓存无效 重新编译
        tsload(CORE_LIB_PATH . '/Template.class.php');
        tsload(CORE_LIB_PATH . '/TagLib.class.php');
        tsload(CORE_LIB_PATH . '/TagLib/TagLibCx.class.php');

        $tpl = Template::getInstance();
        // 编译并加载模板文件
        $tpl->load($templateFile, $tvar, $charset);
    }

    // 获取并清空缓存
    $content = ob_get_clean();

    // 模板内容替换
    $replace = array(
        '__ROOT__' => SITE_URL,           // 当前网站地址
        '__UPLOAD__' => UPLOAD_URL,         // 上传文件地址
        //'__PUBLIC__'    =>  PUBLIC_URL,       // 公共静态地址
        '__PUBLIC__' => THEME_PUBLIC_URL,   // 公共静态地址
        '__THEME__' => THEME_PUBLIC_URL,   // 主题静态地址
        '__THEMENEW__' => THEME_PUBLIC_NEW_URL,   // 新版主题静态地址
        '__THEMEW3G__' => THEME_PUBLIC_W3G_URL,   // 3G版文件静态地址
        '__APP__' => APP_PUBLIC_URL,     // 应用静态地址
        '__URL__' => __ROOT__ . '/index.php?app=' . APP_NAME . '&mod=' . MODULE_NAME,
    );

    if (C('TOKEN_ON')) {
        if (strpos($content, '{__TOKEN__}')) {
            // 指定表单令牌隐藏域位置
            $replace['{__TOKEN__}'] = $this->buildFormToken();
        } elseif (strpos($content, '{__NOTOKEN__}')) {
            // 标记为不需要令牌验证
            $replace['{__NOTOKEN__}'] = '';
        } elseif (preg_match('/<\/form(\s*)>/is', $content, $match)) {
            // 智能生成表单令牌隐藏域
            $replace[$match[0]] = $this->buildFormToken() . $match[0];
        }
    }

    // 允许用户自定义模板的字符串替换
    if (is_array(C('TMPL_PARSE_STRING')))
        $replace = array_merge($replace, C('TMPL_PARSE_STRING'));

    $content = str_replace(array_keys($replace), array_values($replace), $content);

    // 布局模板解析
    //$content = $this->layout($content,$charset,$contentType);
    // 输出模板文件
    if ($display)
        echo $content;
    else
        return $content;
}

// 输出模版
function display($templateFile = '', $tvar = array(), $charset = 'UTF8', $contentType = 'text/html')
{
    fetch($templateFile, $tvar, $charset, $contentType, true);
}

function mk_dir($dir, $mode = 0755)
{
    if (is_dir($dir) || @mkdir($dir, $mode)) return true;
    if (!mk_dir(dirname($dir), $mode)) return false;
    return @mkdir($dir, $mode);
}

/**
 * 字节格式化 把字节数格式为 B K M G T 描述的大小
 * @return string
 */
function byte_format($size, $dec = 2)
{
    $a = array("B", "KB", "MB", "GB", "TB", "PB");
    $pos = 0;
    while ($size >= 1024) {
        $size /= 1024;
        $pos++;
    }
    return round($size, $dec) . " " . $a[$pos];
}

/**
 * 获取客户端IP地址
 */
function get_client_ip($type = 0)
{
    $type = $type ? 1 : 0;
    static $ip = NULL;
    if ($ip !== NULL) return $ip[$type];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos = array_search('unknown', $arr);
        if (false !== $pos) unset($arr[$pos]);
        $ip = trim($arr[0]);
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? array($ip, $long) : array('127.0.0.1', 0);
    return $ip[$type];
}

/**
 * 用马云粑粑的东西
 * 定位当前城市
 * @return 当前城市的省市
 */
function getCurrentCity()
{
//    $city = json_decode(file_get_contents('http://ip.taobao.com/service/getIpInfo.php?ip=' . get_client_ip()), true);
//    if ($city['code'] == 0) {
//        return $city['data'];
//    } else {
//        return false;
//    }
}

/**
 * 记录日志
 * Enter description here ...
 * @param unknown_type $app_group
 * @param unknown_type $action
 * @param unknown_type $data
 * @param unknown_type $isAdmin 是否管理员日志
 */
function LogRecord($app_group, $action, $data, $isAdmin = false)
{
    static $log = null;
    if ($log == null) {
        $log = model('Logs');
    }
    return $log->load($app_group)->action($action)->record($data, $isAdmin);
}

/**
 * 验证权限方法
 * @param string $load 应用 - 模块 字段
 * @param string $action 权限节点字段
 * @param unknown_type $group 是否指定应用内部用户组
 */
function CheckPermission( $action = '')
{
    if (empty($action)) {
        return false;
    }
    //TODO临时解决方案
    if( !M('user_group_link')->where('uid='.$GLOBALS['ts']['mid'])->getField('id') ) {
        return false;
    }
    // 检查是否配置权限
    if(M('permission_node')->where(['rule'=>$action])->count() < 1){
        return true;
    }
    $Permission = model('Permission')->load();

    return $Permission->check($action);
}

/**
 * 微吧管理权限判断
 * @param int $id 微吧id
 * @param string $action 动作
 * @param int $uid 用户uid
 * @return boolean
 */
function CheckWeibaPermission($weiba_admin, $id, $action, $uid)
{
    !$uid && $uid = $GLOBALS['ts']['mid'];
    //超级管理员判断
    if (CheckPermission('core_admin', 'admin_login')) {
        return true;
    }
    if ($action) {
        //用户组权限判断
        if (CheckPermission('weiba_admin', $action)) {
            return true;
        }
    }
    //吧主判断
    if (!$weiba_admin && $id) {
        $map['weiba_id'] = $id;
        $map['level'] = array('in', '2,3');
        $weiba_admin = D('weiba_follow')->where($map)->order('level desc')->field('follower_uid,level')->findAll();
        $weiba_admin = getSubByKey($weiba_admin, 'follower_uid');
    }
    return in_array($uid, $weiba_admin);
}

function CheckTaskSwitch()
{
    $taskswitch = model('Xdata')->get('task_config:task_switch');
    !$taskswitch && $taskswitch = 1;

    return $taskswitch == 1;
}

//获取当前用户的前台管理权限
function manageList($uid)
{
    $list = model('App')->getManageApp($uid);
    return $list;
}

/**
 * 指定用户是否申请认证通过
 * @param integer $uid 用户UID
 * @return boolean 是否申请认证通过
 */
function isVerified($uid)
{
    $isMidVerify = D('user_verified')->where('verified=1 AND uid=' . $uid)->find();
    return (boolean)$isMidVerify;
}

/**
 * 取一个二维数组中的每个数组的固定的键知道的值来形成一个新的一维数组
 * @param $pArray 一个二维数组
 * @param $pKey 数组的键的名称
 * @return 返回新的一维数组
 */
function getSubByKey($pArray, $pKey = "", $pCondition = "")
{
    $result = array();
    if (is_array($pArray)) {
        foreach ($pArray as $temp_array) {
            if (is_object($temp_array)) {
                $temp_array = (array)$temp_array;
            }
            if (("" != $pCondition && $temp_array[$pCondition[0]] == $pCondition[1]) || "" == $pCondition) {
                $result[] = ("" == $pKey) ? $temp_array : isset($temp_array[$pKey]) ? $temp_array[$pKey] : "";
            }
        }
        return $result;
    } else {
        return false;
    }
}

/**
 * 获取字符串的长度
 *
 * 计算时, 汉字或全角字符占1个长度, 英文字符占0.5个长度
 *
 * @param string $str
 * @param boolean $filter 是否过滤html标签
 * @return int 字符串的长度
 */
function get_str_length($str, $filter = false)
{
    if ($filter) {
        $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
        $str = strip_tags($str);
    }
    return (strlen($str) + mb_strlen($str, 'UTF8')) / 4;
}

function getShort($str, $length = 40, $ext = '')
{
    $str = htmlspecialchars($str);
    $str = strip_tags($str);
    $str = htmlspecialchars_decode($str);
    $strlenth = 0;
    $out = '';
    preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/", $str, $match);
    foreach ($match[0] as $v) {
        preg_match("/[\xe0-\xef][\x80-\xbf]{2}/", $v, $matchs);
        if (!empty($matchs[0])) {
            $strlenth += 1;
        } elseif (is_numeric($v)) {
            //$strlenth +=  0.545;  // 字符像素宽度比例 汉字为1
            $strlenth += 0.5;    // 字符字节长度比例 汉字为1
        } else {
            //$strlenth +=  0.475;  // 字符像素宽度比例 汉字为1
            $strlenth += 0.5;    // 字符字节长度比例 汉字为1
        }

        if ($strlenth > $length) {
            $output .= $ext;
            break;
        }

        $output .= $v;
    }
    return $output;
}

/**
 * 检查字符串是否是UTF8编码
 * @param string $string 字符串
 * @return Boolean
 */
if (!function_exists('is_utf8')) {
    function is_utf8($string)
    {
        return preg_match('%^(?:
             [\x09\x0A\x0D\x20-\x7E]            # ASCII
           | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
           |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
           | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
           |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
           |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
           | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
           |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
       )*$%xs', $string);
    }
}

// 自动转换字符集 支持数组转换
function auto_charset($fContents, $from, $to)
{
    $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
    $to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
    if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
        //如果编码相同或者非字符串标量则不转换
        return $fContents;
    }
    if (is_string($fContents)) {
        if (function_exists('iconv')) {
            return iconv($from, $to, $fContents);
        } else {
            return $fContents;
        }
    } elseif (is_array($fContents)) {
        foreach ($fContents as $key => $val) {
            $_key = auto_charset($key, $from, $to);
            $fContents[$_key] = auto_charset($val, $from, $to);
            if ($key != $_key)
                unset($fContents[$key]);
        }
        return $fContents;
    } else {
        return $fContents;
    }
}

/**
 * 友好的时间显示
 *
 * @param int $sTime 待显示的时间
 * @param string $type 类型. normal | mohu | full | ymd | other
 * @param string $alt 已失效
 * @return string
 */
function friendlyDate($sTime, $type = 'normal', $alt = 'false')
{
    if (!$sTime)
        return '';
    //sTime=源时间，cTime=当前时间，dTime=时间差
    $cTime = time();
    $dTime = $cTime - $sTime;
    $dDay = intval(date("z", $cTime)) - intval(date("z", $sTime));
    //$dDay     =   intval($dTime/3600/24);
    $dYear = intval(date("Y", $cTime)) - intval(date("Y", $sTime));
    //normal：n秒前，n分钟前，n小时前，日期
    if ($type == 'normal') {
        if ($dTime < 60) {
            if ($dTime < 10) {
                return '刚刚';    //by yangjs
            } else {
                return intval(floor($dTime / 10) * 10) . "秒前";
            }
        } elseif ($dTime < 3600) {
            return intval($dTime / 60) . "分钟前";
            //今天的数据.年份相同.日期相同.
        } elseif ($dYear == 0 && $dDay == 0) {
            //return intval($dTime/3600)."小时前";
            return '今天' . date('H:i', $sTime);
        } elseif ($dYear == 0) {
            return date("m月d日 H:i", $sTime);
        } else {
            return date("Y-m-d H:i", $sTime);
        }
    } elseif ($type == 'mohu') {
        if ($dTime < 60) {
            return $dTime . "秒前";
        } elseif ($dTime < 3600) {
            return intval($dTime / 60) . "分钟前";
        } elseif ($dTime >= 3600 && $dDay == 0) {
            return intval($dTime / 3600) . "小时前";
        } elseif ($dDay > 0 && $dDay <= 7) {
            return intval($dDay) . "天前";
        } elseif ($dDay > 7 && $dDay <= 30) {
            return intval($dDay / 7) . '周前';
        } elseif ($dDay > 30) {
            return intval($dDay / 30) . '个月前';
        }
        //full: Y-m-d , H:i:s
    } elseif ($type == 'full') {
        return date("Y-m-d , H:i:s", $sTime);
    } elseif ($type == 'ymd') {
        return date("Y-m-d", $sTime);
    } else {
        if ($dTime < 60) {
            return $dTime . "秒前";
        } elseif ($dTime < 3600) {
            return intval($dTime / 60) . "分钟前";
        } elseif ($dTime >= 3600 && $dDay == 0) {
            return intval($dTime / 3600) . "小时前";
        } elseif ($dYear == 0) {
            return date("Y-m-d H:i:s", $sTime);
        } else {
            return date("Y-m-d H:i:s", $sTime);
        }
    }
}

/**
 *
 * 正则替换和过滤内容
 *
 * @param  $html
 * @author jason
 */
function preg_html($html)
{
    $p = array("/<[a|A][^>]+(topic=\"true\")+[^>]*+>#([^<]+)#<\/[a|A]>/",
        "/<[a|A][^>]+(data=\")+([^\"]+)\"[^>]*+>[^<]*+<\/[a|A]>/",
        "/<[img|IMG][^>]+(src=\")+([^\"]+)\"[^>]*+>/");
    $t = array('topic{data=$2}', '$2', 'img{data=$2}');
    $html = preg_replace($p, $t, $html);
    $html = strip_tags($html, "<br/>");
    return $html;
}

//解析数据成网页端显示格式
function parse_html($html)
{
    $html = htmlspecialchars_decode($html);
    //以下三个过滤是旧版兼容方法-可屏蔽
    $html = preg_replace("/img{data=([^}]*)}/", " ", $html);
    $html = preg_replace("/topic{data=([^}]*)}/", '<a href="$1" topic="true">#$1#</a>', $html);
    $html = preg_replace_callback("/@{uid=([^}]*)}/", "_parse_at_by_uid", $html);
    //链接替换
    $html = str_replace('[SITE_URL]', SITE_URL, $html);
    //外网链接地址处理
    //$html = preg_replace_callback('/((?:https?|ftp):\/\/(?:www\.)?(?:[a-zA-Z0-9][a-zA-Z0-9\-]*\.)?[a-zA-Z0-9][a-zA-Z0-9\-]*(?:\.[a-zA-Z0-9]+)+(?:\:[0-9]*)?(?:\/[^\x{2e80}-\x{9fff}\s<\'\"“”‘’,，。]*)?)/u', '_parse_url', $html);
    //表情处理
    $html = preg_replace_callback("/(\[.+?\])/is", _parse_expression, $html);
    //话题处理
    $html = str_replace("＃", "#", $html);
    $html = preg_replace_callback("/#([^#]*[^#^\s][^#]*)#/is", _parse_theme, $html);
    //@提到某人处理
    $html = preg_replace_callback("/@([\w\x{2e80}-\x{9fff}\-]+)/u", "_parse_at_by_uname", $html);

    return $html;
}

//解析成api显示格式
function parseForApi($html)
{
    $html = h($html);
    //以下三个过滤是旧版兼容方法-可屏蔽
    $html = preg_replace_callback("/img{data=([^}]*)}/", '_parse_img_forapi', $html);
    $html = preg_replace_callback("/@{uid=([^}]*)}/", '_parse_wap_at_by_uname', $html);
    $html = preg_replace("/topic{data=([^}]*)}/", '#$1#', $html);
    $html = str_replace(array('[SITE_URL]', '&nbsp;'), array(SITE_URL, ' '), $html);
    //@提到某人处理
    $html = preg_replace_callback("/@([\w\x{2e80}-\x{9fff}\-]+)/u", "_parse_wap_at_by_uname", $html);
    //敏感词过滤
    return $html;
}

/**
 * 格式化微博,替换话题
 * @param string $content 待格式化的内容
 * @param boolean $url 是否替换URL
 * @return string
 */
function format($content, $url = false)
{
    $content = stripslashes($content);
    return $content;
}

function replaceTheme($content)
{
    $content = str_replace("＃", "#", $content);
    $content = preg_replace_callback("/#([^#]*[^#^\s][^#]*)#/is", _parse_theme, $content);
    return $content;
}

function replaceUrl($content)
{
    //$content = preg_replace_callback('/((?:https?|ftp):\/\/(?:[a-zA-Z0-9][a-zA-Z0-9\-]*)*(?:\/[^\x{2e80}-\x{9fff}\s<\'\"“”‘’,，。]*)?)/u', '_parse_url', $content);
    $content = str_replace('[SITE_URL]', SITE_URL, $content);
    $content = preg_replace_callback('/((?:https?|mailto|ftp):\/\/([^\x{2e80}-\x{9fff}\s<\'\"“”‘’，。}]*)?)/u', '_parse_url', $content);
    return $content;
}


/**
 * 表情替换 [格式化微博与格式化评论专用]
 * @param array $data
 */
function _parse_expression($data)
{
    if (preg_match("/#.+#/i", $data[0])) {
        return $data[0];
    }
    $allexpression = model('Expression')->getAllExpression();
    $info = $allexpression[$data[0]];
    if ($info) {
        return preg_replace("/\[.+?\]/i", "<img src='" . __THEME__ . "/image/expression/miniblog/" . $info['filename'] . "' />", $data[0]);
    } else {
        return $data[0];
    }
}

/**
 * 格式化微博,替换链接地址
 * @param string $url
 */
function _parse_url($url)
{
    $str = '<div class="url">';
    if (preg_match("/(youku.com|youtube.com|ku6.com|sohu.com|mofile.com|sina.com.cn|tudou.com|yinyuetai.com)/i", $url[0], $hosts)) {
        $str .= '<a href="' . $url[0] . '" target="_blank" event-node="show_url_detail" class="ico-url-video"></a>';
    } else if (strpos($url[0], 'taobao.com')) {
        $str .= '<a href="' . $url[0] . '" target="_blank" event-node="show_url_detail" class="ico-url-taobao"></a>';
    } else {
        $str .= '<a href="' . $url[0] . '" target="_blank" event-node="show_url_detail" class="ico-url-web"></a>';
    }
    $str .= '<div class="url-detail" style="display:none;">' . $url[0] . '</div></div>';
    return $str;
}

/**
 * 话题替换 [格式化微博专用]
 * @param array $data
 * @return string
 */
function _parse_theme($data)
{
    //如果话题被锁定，则不带链接
    if (!model('FeedTopic')->where(array('name' => $data[1]))->getField('lock')) {
        return "<a href=" . U('public/Topic/index', array('k' => urlencode($data[1]))) . ">" . $data[0] . "</a>";
    } else {
        return $data[0];
    }
}

/**
 * 根据用户昵称获取用户ID [格式化微博与格式化评论专用]
 * @param array $name
 * @return string
 */
function _parse_at_by_uname($name)
{
    $info = static_cache('user_info_uname_' . $name[1]);
    if (!$info) {
        $info = model('User')->getUserInfoByName($name[1]);
        if (!$info) {
            $info = 1;
        }
        static_cache('user_info_uname_' . $name[1], $info);
    }
    if ($info && $info['is_active'] && $info['is_audit'] && $info['is_init']) {
        return '<a href="' . $info['space_url'] . '" uid="' . $info['uid'] . '" event-node="face_card" target="_blank">' . $name[0] . "</a>";
    } else {
        return $name[0];
    }
}

/**
 * 解析at成web端显示格式
 */
function _parse_at_by_uid($result)
{
    $_userInfo = explode("|", $result[1]);
    $userInfo = model('User')->getUserInfo($_userInfo[0]);
    return '<a uid="' . $userInfo['uid'] . '" event-node="face_card" data="@{uid=' . $userInfo['uid'] . '|' . $userInfo['uname'] . '}" 
            href="' . $userInfo['space_url'] . '">@' . $userInfo['uname'] . '</a>';
}

function _parse_wap_at_by_uname($name)
{
    $info = static_cache('user_info_uname_' . $name[1]);
    if (!$info) {
        $info = model('User')->getUserInfoByName($name[1]);
        if (!$info) {
            $info = 1;
        }
        static_cache('user_info_uname_' . $name[1], $info);
    }
    if ($info && $info['is_active'] && $info['is_audit'] && $info['is_init']) {
        return '<a href="' . U('wap/Index/weibo', array('uid' => $info['uid'])) . '" >' . $name[0] . "</a>";
    } else {
        return $name[0];
    }
}

/**
 * 解析at成api显示格式
 */
function _parse_at_forapi($html)
{
    $_userInfo = explode("|", $html[1]);
    return "@" . $_userInfo[1];
}

/**
 * 解析图片成api格式
 */
function _parse_img_forapi($html)
{
    $basename = basename($html[1]);
    return "[" . substr($basename, 0, strpos($basename, ".")) . "]";
}

/**
 * 敏感词过滤
 */
function filter_keyword($html)
{
    static $audit = null;
    static $auditSet = null;
    if ($audit == null) { //第一次
        $audit = model('Xdata')->get('keywordConfig');
        $audit = explode(',', $audit);
        $auditSet = model('Xdata')->get('keyword_replaceConfig');
        $open = model('Xdata')->get('keyword_openConfig');
    }
    // 不需要替换
    if (empty($audit) || $open == '0') {
        return $html;
    }
    return str_replace($audit, $auditSet, $html);
}

//文件名
/**
 * 获取缩略图
 * @param unknown_type $filename 原图路劲、url
 * @param unknown_type $width 宽度
 * @param unknown_type $height 高
 * @param unknown_type $cut 是否切割 默认不切割
 * @return string
 */
function getThumbImage($filename, $width = 100, $height = 'auto', $cut = false, $replace = false)
{
    $filename = str_ireplace(UPLOAD_URL, '', $filename); //将URL转化为本地地址
    $info = pathinfo($filename);
    $oldFile = $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'] . '.' . $info['extension'];
    $thumbFile = $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'] . '_' . $width . '_' . $height . '.' . $info['extension'];

    $oldFile = str_replace('\\', '/', $oldFile);
    $thumbFile = str_replace('\\', '/', $thumbFile);

    $filename = '/' . ltrim($filename, '/');
    $oldFile = '/' . ltrim($oldFile, '/');
    $thumbFile = '/' . ltrim($thumbFile, '/');

    //原图不存在直接返回
    if (!file_exists(UPLOAD_PATH . $oldFile)) {
        @unlink(UPLOAD_PATH . $thumbFile);
        $info['src'] = $oldFile;
        $info['width'] = intval($width);
        $info['height'] = intval($height);
        return $info;
        //缩图已存在并且 replace替换为false
    } elseif (file_exists(UPLOAD_PATH . $thumbFile) && !$replace) {
        $imageinfo = getimagesize(UPLOAD_PATH . $thumbFile);
        $info['src'] = $thumbFile;
        $info['width'] = intval($imageinfo[0]);
        $info['height'] = intval($imageinfo[1]);
        return $info;
        //执行缩图操作
    } else {
        $oldimageinfo = getimagesize(UPLOAD_PATH . $oldFile);
        $old_image_width = intval($oldimageinfo[0]);
        $old_image_height = intval($oldimageinfo[1]);
        if ($old_image_width <= $width && $old_image_height <= $height) {
            @unlink(UPLOAD_PATH . $thumbFile);
            @copy(UPLOAD_PATH . $oldFile, UPLOAD_PATH . $thumbFile);
            $info['src'] = $thumbFile;
            $info['width'] = $old_image_width;
            $info['height'] = $old_image_height;
            return $info;
        } else {
            //生成缩略图
            // tsload( ADDON_PATH.'/library/Image.class.php' );
            // if($cut){
            //     Image::cut(UPLOAD_PATH.$filename, UPLOAD_PATH.$thumbFile, $width, $height);
            // }else{
            //     Image::thumb(UPLOAD_PATH.$filename, UPLOAD_PATH.$thumbFile, '', $width, $height);
            // }
            //生成缩略图 - 更好的方法
            if ($height == "auto") $height = 0;
            tsload(ADDON_PATH . '/library/phpthumb/ThumbLib.inc.php');
            $thumb = PhpThumbFactory::create(UPLOAD_PATH . $filename);
            if ($cut) {
                $thumb->adaptiveResize($width, $height);
            } else {
                $thumb->resize($width, $height);
            }
            $res = $thumb->save(UPLOAD_PATH . $thumbFile);
            //缩图失败
            if (!$res) {
                $thumbFile = $oldFile;
            }
            $info['width'] = $width;
            $info['height'] = $height;
            $info['src'] = $thumbFile;
            return $info;
        }
    }
}

//获取图片信息 - 兼容云
function getImageInfo($file)
{
    $cloud = model('CloudImage');
    if ($cloud->isOpen()) {
        $imageInfo = getimagesize($cloud->getImageUrl($file));
    } else {
        $imageInfo = getimagesize(UPLOAD_PATH . '/' . $file);
    }
    return $imageInfo;
}

//获取图片地址 - 兼容云
function getImageUrl($file, $width = '0', $height = 'auto', $cut = false, $replace = false)
{
    $cloud = model('CloudImage');
    if ($cloud->isOpen()) {
        $imageUrl = $cloud->getImageUrl($file, $width, $height, $cut);
    } else {
        if ($width > 0) {
            $thumbInfo = getThumbImage($file, $width, $height, $cut, $replace);
            $imageUrl = UPLOAD_URL . '/' . ltrim($thumbInfo['src'], '/');
        } else {
            $imageUrl = UPLOAD_URL . '/' . ltrim($file, '/');
        }
    }
    return $imageUrl;
}

//保存远程图片
function saveImageToLocal($url)
{
    if (strncasecmp($url, 'http', 4) != 0) {
        return false;
    }
    $opts = array(
        'http' => array(
            'method' => "GET",
            'timeout' => 30, //超时30秒
            'user_agent' => "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)"
        )
    );
    $context = stream_context_create($opts);
    $file_content = file_get_contents($url, false, $context);
    $file_path = date('/Y/md/H/');
    @mkdir(UPLOAD_PATH . $file_path, 0777, true);
    $i = pathinfo($url);
    if (!in_array($i['extension'], array('jpg', 'jpeg', 'gif', 'png'))) {
        $i['extension'] = 'jpg';
    }
    $file_name = uniqid() . '.' . $i['extension'];

    //又拍云存储
    $cloud = model('CloudImage');
    if ($cloud->isOpen()) {
        $res = $cloud->writeFile($file_path . $file_name, $file_content);
    } else {
        //本地存储
        $res = file_put_contents(UPLOAD_PATH . $file_path . $file_name, $file_content);
    }

    if ($res) {
        return $file_path . $file_name;
    } else {
        return false;
    }
}

function getImageUrlByAttachId($attachid, $width, $height)
{
    if ($attachInfo = model('Attach')->getAttachById($attachid)) {
        if ($width) {
            return getImageUrl($attachInfo['save_path'] . $attachInfo['save_name'], $width, $height, true);
        } else {
            return getImageUrl($attachInfo['save_path'] . $attachInfo['save_name']);
        }
    } else {
        return false;
    }
}

//获取附件地址 - 兼容云
function getAttachUrl($filename)
{
    //云端
    $cloud = model('CloudAttach');

    if ($cloud->isOpen()) {
        return $cloud->getFileUrl($filename);
    }
    //本地
    if (file_exists(UPLOAD_PATH . '/' . $filename)) {
        return UPLOAD_URL . '/' . $filename;
    } else {
        return '';
    }
}

function getAttachUrlByAttachId($attachid)
{
    if ($attachInfo = model('Attach')->getAttachById($attachid)) {
        return getAttachUrl($attachInfo['save_path'] . $attachInfo['save_name']);
    } else {
        return false;
    }
}

function limitNumber($str, $len)
{
    if (empty($str)) return "";
    //去掉文字中的空格
    $str = str_replace(" ", "", $str);

    if (mb_strlen($str) > $len) {
        $str = mb_substr($str, 0, $len, 'utf-8') . '..';
    } else {
        $str = $str;
    }
    return $str;
}

function getSiteLogo($logoid = '')
{
    if (empty($logoid)) {
        $logoid = $GLOBALS['ts']['site']['site_logo'];
    }
    if ($logoInfo = model('Attach')->getAttachById($logoid)) {
        $logo = getImageUrl($logoInfo['save_path'] . $logoInfo['save_name']);
    } else {
        $logo = THEME_PUBLIC_URL . '/' . C('site_logo');
    }
    return $logo;
}

//获取当前访问者的客户端类型
function getVisitorClient()
{
    //客户端类型，0：网站；1：手机版；2：Android；3：iPhone；3：iPad；3：win.Phone
    return '0';
}

//获取一条微博的来源信息
function getFromClient($type = 0, $app = 'public', $app_name)
{
    if ($app != 'public') {
        $appUpper = strtoupper($app);
        $appName = L('PUBLIC_APPNAME_' . $appUpper);
        if (empty($app_name) && $appUpper != $appName) {
            $app_name = $appName;
        }
        return '来自<a href="' . U($app) . '" target="_blank">' . $app_name . "</a>";
    }
    $type = intval($type);
    $client_type = array(
        0 => '来自网站',
        1 => '来自手机',
        2 => '来自Android客户端',
        3 => '来自iPhone客户端',
        4 => '来自iPad客户端',
        5 => '来自Windows客户端',
    );

    //在列表中的
    if (in_array($type, array_keys($client_type))) {
        return $client_type[$type];
    } else {
        return $client_type[0];
    }
}

/**
 * DES加密函数
 *
 * @param string $input
 * @param string $key
 */
function desencrypt($input, $key)
{

    //使用新版的加密方式
    tsload(ADDON_PATH . '/library/DES_MOBILE.php');
    $desc = new DES_MOBILE();
    return $desc->setKey($key)->encrypt($input);
}

/**
 * DES解密函数
 *
 * @param string $input
 * @param string $key
 */
function desdecrypt($encrypted, $key)
{
    //使用新版的加密方式
    tsload(ADDON_PATH . '/library/DES_MOBILE.php');
    $desc = new DES_MOBILE();
    return $desc->setKey($key)->decrypt($encrypted);
}


function getOAuthToken($uid)
{
    return md5($uid . uniqid());
}

function getOAuthTokenSecret()
{
    return md5(time() . uniqid());
}

// 获取字串首字母
function getFirstLetter($s0)
{
    $firstchar_ord = ord(strtoupper($s0{0}));
    if ($firstchar_ord >= 65 and $firstchar_ord <= 91) return strtoupper($s0{0});
    if ($firstchar_ord >= 48 and $firstchar_ord <= 57) return '#';
    $s = iconv("UTF-8", "gb2312", $s0);
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
    if ($asc >= -20319 and $asc <= -20284) return "A";
    if ($asc >= -20283 and $asc <= -19776) return "B";
    if ($asc >= -19775 and $asc <= -19219) return "C";
    if ($asc >= -19218 and $asc <= -18711) return "D";
    if ($asc >= -18710 and $asc <= -18527) return "E";
    if ($asc >= -18526 and $asc <= -18240) return "F";
    if ($asc >= -18239 and $asc <= -17923) return "G";
    if ($asc >= -17922 and $asc <= -17418) return "H";
    if ($asc >= -17417 and $asc <= -16475) return "J";
    if ($asc >= -16474 and $asc <= -16213) return "K";
    if ($asc >= -16212 and $asc <= -15641) return "L";
    if ($asc >= -15640 and $asc <= -15166) return "M";
    if ($asc >= -15165 and $asc <= -14923) return "N";
    if ($asc >= -14922 and $asc <= -14915) return "O";
    if ($asc >= -14914 and $asc <= -14631) return "P";
    if ($asc >= -14630 and $asc <= -14150) return "Q";
    if ($asc >= -14149 and $asc <= -14091) return "R";
    if ($asc >= -14090 and $asc <= -13319) return "S";
    if ($asc >= -13318 and $asc <= -12839) return "T";
    if ($asc >= -12838 and $asc <= -12557) return "W";
    if ($asc >= -12556 and $asc <= -11848) return "X";
    if ($asc >= -11847 and $asc <= -11056) return "Y";
    if ($asc >= -11055 and $asc <= -10247) return "Z";
    return '#';
}

// 区间调试开始
function debug_start($label = '')
{
    $GLOBALS[$label]['_beginTime'] = microtime(TRUE);
    $GLOBALS[$label]['_beginMem'] = memory_get_usage();
}

// 区间调试结束，显示指定标记到当前位置的调试
function debug_end($label = '')
{
    $GLOBALS[$label]['_endTime'] = microtime(TRUE);
    $log = 'Process ' . $label . ': Times ' . number_format($GLOBALS[$label]['_endTime'] - $GLOBALS[$label]['_beginTime'], 6) . 's ';
    $GLOBALS[$label]['_endMem'] = memory_get_usage();
    $log .= ' Memories ' . number_format(($GLOBALS[$label]['_endMem'] - $GLOBALS[$label]['_beginMem']) / 1024) . ' k';
    $GLOBALS['logs'][$label] = $log;
}

// 全站语言设置 - PHP
function setLang()
{
    // 获取当前系统的语言
    $lang = getLang();
    // 设置全站语言变量
    if (!isset($GLOBALS['_lang'])) {
        $GLOBALS['_lang'] = array();
        $_lang = array();
        if (file_exists(LANG_PATH . '/public_' . $lang . '.php')) {
            $_lang = include(LANG_PATH . '/public_' . $lang . '.php');
            $GLOBALS['_lang'] = array_merge($GLOBALS['_lang'], $_lang);
        }
        $removeApps = array('api', 'widget', 'public');
        if (!in_array(TRUE_APPNAME, $removeApps)) {
            if (file_exists(LANG_PATH . '/' . strtolower(TRUE_APPNAME) . '_' . $lang . '.php')) {
                $_lang = include(LANG_PATH . '/' . strtolower(TRUE_APPNAME) . '_' . $lang . '.php');
                $GLOBALS['_lang'] = array_merge($GLOBALS['_lang'], $_lang);
            }
        }
    }
}

//主动添加语言包
function addLang($appname)
{
    static $langHash = array();
    if (isset($langHash[$appname])) {
        return true;
    }
    $langHash[$appname] = 1;
    $lang = getLang();
    if (file_exists(LANG_PATH . '/' . $appname . '_' . $lang . '.php')) {
        $_lang = include(LANG_PATH . '/' . $appname . '_' . $lang . '.php');
        empty($_lang) && $_lang = array();
        $GLOBALS['_lang'] = array_merge($GLOBALS['_lang'], $_lang);
        return true;
    }
    return false;
}

// 全站语言设置 - JavaScript
function setLangJavsScript()
{
    // 获取当前系统的语言
    $lang = getLang();
    // 获取相应要载入的JavaScript语言包路径
    $langJsList = array();
    if (file_exists(LANG_PATH . '/public_' . $lang . '.js')) {
        $langJsList[] = LANG_URL . '/public_' . $lang . '.js';
    }
    $removeApps = array('api', 'widget', 'public');
    if (!in_array(TRUE_APPNAME, $removeApps)) {
        if (file_exists(LANG_PATH . '/' . strtolower(TRUE_APPNAME) . '_' . $lang . '.js')) {
            $langJsList[] = LANG_URL . '/' . strtolower(TRUE_APPNAME) . '_' . $lang . '.js';
        }
    }

    return $langJsList;
}

// 获取站点所使用的语言
function getLang()
{
    $defaultLang = 'zh-cn';
    $cLang = cookie('lang');
    $lang = '';
    // 判断是否已经登录
    if (isset($_SESSION['mid']) && $_SESSION['mid'] > 0) {
        $userInfo = model('User')->getUserInfo($_SESSION['mid']);
        if (isset($userInfo['lang'])) {
            return $userInfo['lang'];
        } else {
            return '';
        }
    }
    // 是否存在cookie值，如果存在显示默认的cookie语言值
    if (is_null($cLang)) {
        // 手机端直接返回默认语言
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return $defaultLang;
        }
        // 判断操作系统的语言状态
        $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $accept_language = strtolower($accept_language);
        $accept_language_array = explode(',', $accept_language);
        $lang = array_shift($accept_language_array);
        // 获取默认语言
        $fields = model('Lang')->getLangType();
        $lang = in_array($lang, $fields) ? $lang : $defaultLang;
        cookie('lang', $lang);
    } else {
        $lang = $cLang;
    }

    return $lang;
}

function ShowNavMenu($apps)
{
    $html = '';
    foreach ($apps as $app) {
        $child_menu = unserialize($app['child_menu']);
        if (empty($child_menu)) {
            continue;
        }
        foreach ($child_menu as $k => $cm) {

            if ($k == $app['app_name']) {
                //我的XXX
                $title = L('PUBLIC_MY') . L('PUBLIC_APPNAME_' . strtoupper($k));
                $url = U($cm['url']);
            } else {
                //其他导航 一般不会有其他导航
                $title = L($k);
                //地址直接是cm值
                $url = U($cm);
            }

            $html .= "<dd><a href='{$url}'>{$title}</a></dd>";
        }
    }
    return $html;
}

function showNavProfile($apps)
{
    $html = '';
    foreach ($apps as $app) {

        $child_menu = unserialize($app['child_menu']);

        if (empty($child_menu)) {
            continue;
        }
        foreach ($child_menu as $k => $cm) {

            if ($k == $app['app_name'] && $cm['public'] == 1) {
                //我的XXX 只会显示这类数据
                $title = "<img width='16' src='{$app['icon_url']}'> " . L('PUBLIC_APPNAME_' . strtoupper($k));
                $url = U('public/Profile/appprofile', array('appname' => $k));
                $html .= "<dd class='profile_{$app['app_name']}'><a href='{$url}'>{$title}</a></dd>";
            }
        }
    }
    return $html;
}

/**
 * 是否能进行邀请
 * @param integer $uid 用户ID
 */
function isInvite()
{
    $config = model('Xdata')->get('admin_Config:register');
    $result = false;
    if (in_array($config['register_type'], array('open', 'invite'))) {
        $result = true;
    }

    return $result;
}

/**
 * 传统形式显示无限极分类树
 * @param array $data 树形结构数据
 * @param string $stable 所操作的数据表
 * @param integer $left 样式偏移
 * @param array $delParam 删除关联信息参数，app、module、method
 * @param integer $level 添加子分类层级，默认为0，则可以添加无限子分类
 * @param integer $times 用于记录递归层级的次数，默认为1，调用函数时，不需要传入值。
 * @param integer $limit 分类限制字数。
 * @return string 树形结构的HTML数据
 */
function showTreeCategory($data, $stable, $left, $delParam, $level = 0, $ext = '', $times = 1, $limit = 0, $type = 1)
{
    $html = '<ul class="sort">';
    foreach ($data as $val) {
        // 判断是否有符号
        $isFold = empty($val['child']) ? false : true;
        $html .= '<li id="' . $stable . '_' . $val['id'] . '" class="underline" style="padding-left:' . $left . 'px;"><div class="c1">'.$val['id'].'</div><div class="c1">';
        if ($isFold) {
            $html .= '<a href="javascript:;" onclick="admin.foldCategory(' . $val['id'] . ')"><img id="img_' . $val['id'] . '" src="' . __THEME__ . '/admin/image/on.png" /></a>';
        }
        $html .= '<span>' . $val['title'] . '</span></div><div class="c2">';
        if ($level == 0 || $times < $level) {
            $html .= '<a href="javascript:;" onclick="admin.addTreeCategory(' . $val['id'] . ', \'' . $stable . '\', ' . $limit . ', \'' . $type . '\');">添加子分类</a>&nbsp;-&nbsp;';
        }
        $html .= '<a href="javascript:;" onclick="admin.upTreeCategory(' . $val['id'] . ', \'' . $stable . '\', ' . $limit . ', \'' . $type . '\');">编辑</a>&nbsp;-&nbsp;';
        if (empty($delParam)) {
            $html .= '<a href="javascript:;" onclick="admin.rmTreeCategory(' . $val['id'] . ', \'' . $stable . '\');">删除</a>';
        } else {
            $html .= '<a href="javascript:;" onclick="admin.rmTreeCategory(' . $val['id'] . ', \'' . $stable . '\', \'' . $delParam['app'] . '\', \'' . $delParam['module'] . '\', \'' . $delParam['method'] . '\');">删除</a>';
        }
        $ext !== '' && $html .= '&nbsp;-&nbsp;<a href="' . U('admin/Public/setCategoryConf', array('cid' => $val['id'], 'stable' => $stable)) . '&' . $ext . '">分类配置</a>';
        $html .= '</div><div class="c3">';
        $html .= '<a href="javascript:;" onclick="admin.moveTreeCategory(' . $val['id'] . ', \'up\', \'' . $stable . '\')" class="ico_top mr5"></a>';
        $html .= '<a href="javascript:;" onclick="admin.moveTreeCategory(' . $val['id'] . ', \'down\', \'' . $stable . '\')" class="ico_btm"></a>';
        $html .= '</div></li>';
        if (!empty($val['child'])) {
            $html .= '<li id="sub_' . $val['id'] . '" style="display:none;">';
            $html .= showTreeCategory($val['child'], $stable, $left + 15, $delParam, $level, $ext, $times + 1, $limit, $type);
            $html .= '</li>';
        }
    }
    $html .= '</ul>';

    return $html;
}

/**
 * 权限节点无限极分类树
 * @param array $data 树形结构数据
 * @param string $stable 所操作的数据表
 * @param integer $left 样式偏移
 * @param array $delParam 删除关联信息参数，app、module、method
 * @param integer $level 添加子分类层级，默认为0，则可以添加无限子分类
 * @param integer $times 用于记录递归层级的次数，默认为1，调用函数时，不需要传入值。
 * @param integer $limit 分类限制字数。
 * @return string 树形结构的HTML数据
 */
function showPermTreeCategory($data, $left, $delParam, $level = 0, $ext = '', $times = 1, $limit = 0, $type = 1)
{
    $html = '<ul class="sort">';
    foreach ($data as $val) {
        // 判断是否有符号
        $isFold = empty($val['child']) ? false : true;
        $html .= "<li id='{$val['id']}' class='underline' style='padding-left:{$left}px;'>".
            "<div class='c1'>";
                if ($isFold) {
                    $html .= "<a href='javascript:;' onclick='admin.foldCategory({$val['id']})'><img id='img_{$val['id']}' src='".__THEME__."/admin/image/on.png' /></a>";
                }
                $html .= "<span>{$val['title']}</span>
            </div>
            <div class='l1'>
                <span>{$val['appname']}</span>
            </div>
            <div class='l1'>
                <span>{$val['rule']}</span>
            </div>
            <div class='l1'>
                <span>{$val['note']}</span>
            </div>";

            $rulename = M('permission_node')->where('id='.$val['id'])->getField('rulename');

            $html .= "<div class='c2'>";
                if($val['is_autocephaly'] == 0){
                    if ($level == 0 || $times < $level) {
                        $html .= "<a href='javascript:;' onclick='admin.addTreeCategory({$val['id']},{$limit},\"{$rulename}—添加子菜单\");'>添加子菜单</a>&nbsp;-&nbsp;";
                    }
                }

                $html .= "<a href='javascript:;' onclick='admin.upTreeCategory({$val['id']},{$limit},\"{$rulename}—编辑\");'>编辑</a>";
                if (empty($delParam)) {
                    if($val['id'] != 10 && $val['id'] != 11 ){
                        $is_child = M('permission_node')->where('pid='.$val['id'])->getField('id');
                        if($is_child){
                            $html .= "&nbsp;-&nbsp;<a href='javascript:;' onclick='admin.rmTreeCategory({$val['id']},9);'>删除</a>";
                        }else{
                            $html .= "&nbsp;-&nbsp;<a href='javascript:;' onclick='admin.rmTreeCategory({$val['id']},0);'>删除</a>";
                        }
                    }
                } else {
                    $html .= "&nbsp;-&nbsp;<a href='javascript:;' onclick='admin.rmTreeCategory({$val['id']},{$delParam['app']},{$delParam['module']},{$delParam['method']});'>删除</a>";
                }
                $ext !== "" && $html .= "&nbsp;-&nbsp;<a href='".U('admin/Public/setCategoryConf', array('cid' => $val['id'],))."& {$ext}>菜单配置</a>";
            $html .= "</div>";
        $html .= "</li>";

        if (!empty($val['child'])) {
            $html .= "<li id='sub_{$val['id']}' style='display:none;'>";
            $html .= showPermTreeCategory($val['child'], $left + 15, $delParam, $level, $ext, $times + 1, $limit, $type);
            $html .= "</li>";
        }
    }
    $html .= "</ul>";

    return $html;
}

/**
 * 用户组显示无限极分类树
 * @param array $data 树形结构数据
 * @param string $stable 所操作的数据表
 * @param integer $left 样式偏移
 * @param array $delParam 删除关联信息参数，app、module、method
 * @param integer $level 添加子分类层级，默认为0，则可以添加无限子分类
 * @param integer $times 用于记录递归层级的次数，默认为1，调用函数时，不需要传入值。
 * @param integer $limit 分类限制字数。
 * @return string 树形结构的HTML数据
 */
function showUserGroupTree($data, $left, $delParam, $level = 0, $ext = '', $times = 1, $limit = 0, $type = 1)
{
    $html = '<ul class="sort">';
    foreach ($data as $key => $val) {
        // 判断是否有符号
        $isFold = empty($val['child']) ? false : true;
        $html .= "<li id='{$val['id']}' class='underline' style='padding-left:{$left}px;'>".
            "<div class='c1'>";
                if ($isFold) {
                    $html .= "<a href='javascript:;' onclick='admin.foldCategory({$val['id']})'><img id='img_{$val['id']}' src='".__THEME__."/admin/image/on.png' /></a>";
                }
                $html .= "<span>{$val['title']}</span>
            </div>
            <div class='l1'>
                <span>{$val['app_name']}</span>
            </div>
            <div class='l1'>
                <span>{$val['user_group_type']}</span>
            </div>
            <div class='l1'>
                <span>{$val['user_group_icon']}</span>
            </div>
            <div class='l1'>
                <span>{$val['is_authenticate']}</span>
            </div>";

            $user_group_name = M('user_group')->where('user_group_id='.$val['id'])->getField('user_group_name');

            if($val['id'] == 1){
                $html .= "<div class='c2'>";
                if ($level == 0 || $times < $level) {
                    $html .=    "<a href='javascript:;'class='disabled'>添加子角色</a>&nbsp;-&nbsp;";
                }
                $html .=    "<a href='javascript:;'class='disabled'>编辑</a>&nbsp;-&nbsp
                             <a href='javascript:;'class='disabled'>权限设置</a>
                        </div>";
            }else{
                $html .= "<div class='c2'>";
                    if($val['is_autocephaly'] == 0){
                        if ($level == 0 || $times < $level) {
                            $m_uid = M('user_group')->where(['user_group_id'=>$val['id']])->getField('uid');
                            $mhm_uid = M('school')->where(['uid'=>$m_uid])->getField('id');
                            if($m_uid && (is_school($m_uid) == $mhm_uid)){
                                $html .= "<a href='javascript:;' onclick='admin.addUserGroupTreeCategory({$val['id']},{$limit},\"{$user_group_name}—添加子角色\",$m_uid);'>添加子角色</a>&nbsp;-&nbsp;";
                            }else{
                                $html .= "<a href='javascript:;' onclick='admin.addUserGroupTreeCategory({$val['id']},{$limit},\"{$user_group_name}—添加子角色\");'>添加子角色</a>&nbsp;-&nbsp;";
                            }
                        }
                    }

                    $html .= "<a href='javascript:;' onclick='admin.upUserGroupTreeCategory({$val['id']},{$limit},\"{$user_group_name}—编辑\");'>编辑</a>&nbsp;-&nbsp;";

                    $mugid = M('user_group')->where(['pid'=>4,'uid'=>$_SESSION['mid']])->getField('user_group_id');
                    if($mugid == $val['id'] && !is_admin($_SESSION['mid'])){
                        $html .= "<a href='javascript:;'class='disabled'>权限设置</a>";
                    }else{
                        $html .= "<a href='javascript:;' onclick='admin.upUserGroupListTreeCategory({$val['id']},\"{$user_group_name}—权限设置\");'>权限设置</a>";
                    }
                    if (empty($delParam)) {
                        if($val['id'] > 5){
                            $is_child = M('permission_node')->where('pid='.$val['id'])->getField('id');
                            if($mugid == $val['id'] && !is_admin($_SESSION['mid'])){
                                $html .= "&nbsp;-&nbsp;<a href='javascript:;'class='disabled'>删除</a>";
                            }else{
                                if ($is_child) {
                                    $html .= "&nbsp;-&nbsp;<a href='javascript:;' onclick='admin.rmUserGroupTreeCategory({$val['id']},9);'>删除</a>";
                                } else {
                                    $html .= "&nbsp;-&nbsp;<a href='javascript:;' onclick='admin.rmUserGroupTreeCategory({$val['id']},0);'>删除</a>";
                                }
                            }
                        }
                    } else {
                        $html .= "<a href='javascript:;' onclick='admin.rmTreeCategory({$val['id']},{$delParam['app']},{$delParam['module']},{$delParam['method']});'>删除</a>";
                    }
                    $ext !== "" && $html .= "&nbsp;-&nbsp;<a href='".U('admin/Public/setCategoryConf', array('cid' => $val['id'],))."& {$ext}>菜单配置</a>";
                $html .= "</div>";
            }
        $html .= "</li>";

        if ($val['child']) {
            $html .= "<li id='sub_{$val['id']}' style='display:none;'>";
            $html .= showUserGroupTree($val['child'], $left + 15, $delParam, $level, $ext, $times + 1, $limit, $type);
            $html .= "</li>";
        }
    }
    $html .= "</ul>";

    return $html;
}

/**
 * 课程章节无限极分类
 * @param array $data 树形结构数据
 * @param string $stable 所操作的数据表
 * @param integer $left 样式偏移
 * @param array $delParam 删除关联信息参数，app、module、method
 * @param integer $level 添加子分类层级，默认为0，则可以添加无限子分类
 * @param integer $times 用于记录递归层级的次数，默认为1，调用函数时，不需要传入值。
 * @param integer $limit 分类限制字数。
 * @return string 树形结构的HTML数据
 */
function showTreeVideoSection($data, $stable, $left, $delParam, $level = 0, $times = 1)
{
    $html = '<ul class="sort">';
    foreach ($data as $val) {
        // 判断是否有符号
        $isFold = empty($val['child']) ? false : true;
        $html .= '<li id="' . $stable . '_' . $val['id'] . '" class="underline" style="padding-left:' . $left . 'px;"><div class="c1">';
        if ($isFold) {
            $html .= '<a href="javascript:;" onclick="admin.foldCategory(' . $val['id'] . ')"><img id="img_' . $val['id'] . '" src="' . __THEME__ . '/admin/image/on.png" /></a>';
        }
        $html .= '<span>' . $val['title'] . '</span></div><div class="c2">';
        if ($level == 0 || $times < $level) {
            $html .= '<a href="javascript:;" onclick="admin.addTreeCategory(' . $val['id'] . ', \'' . $stable . '\',\'' . $val['vid'] . '\', 1);">添加课时</a>&nbsp;-&nbsp;';
        }
        $html .= '<a href="javascript:;" onclick="admin.upTreeCategory(' . $val['id'] . ', \'' . $stable . '\');">编辑</a>&nbsp;-&nbsp;';
        if (empty($delParam)) {
            $html .= '<a href="javascript:;" onclick="admin.rmTreeCategory(' . $val['id'] . ', \'' . $stable . '\');">删除</a>';
        } else {
            $html .= '<a href="javascript:;" onclick="admin.rmTreeCategory(' . $val['id'] . ', \'' . $stable . '\', \'' . $delParam['app'] . '\', \'' . $delParam['module'] . '\', \'' . $delParam['method'] . '\');">删除</a>';
        }

        $html .= '</div><div class="c3">';
        $html .= '<a href="javascript:;" onclick="admin.moveTreeCategory(' . $val['id'] . ', \'up\', \'' . $stable . '\')" class="ico_top mr5"></a>';
        $html .= '<a href="javascript:;" onclick="admin.moveTreeCategory(' . $val['id'] . ', \'down\', \'' . $stable . '\')" class="ico_btm"></a>';


        $html .= '</div><div class="c3">';
        $html .= '<span>' . $val['v_title'] . '</span>';
        $html .= '</div></li>';

        if (!empty($val['child'])) {
            $html .= '<li id="sub_' . $val['id'] . '" style="display:none;">';
            $html .= showTreeVideoSection($val['child'], $stable, $left + 15, $delParam, $level, $times + 1);
            $html .= '</li>';
        }
    }
    $html .= '</ul>';

    return $html;
}

/**
 * 格式化分类配置页面参数为字符串
 * @param array $ext 配置页面相关参数
 * @param array $defExt 默认值HASH数组
 * @return string 格式化后的字符串
 */
function encodeCategoryExtra($ext, $defExt)
{
    $data = array();
    $i = 1;
    foreach ($ext as $key => $val) {
        if (is_array($val)) {
            $data['ext_' . $i] = $key;
            $data['arg_' . $i] = implode('-', $val);
            $data['def_' . $i] = $defExt[$key];
        } else {
            $data['ext_' . $i] = $val;
        }
        $i++;
    }
    // 处理数据
    $result = array();
    foreach ($data as $k => $v) {
        $result[] = $k . '=' . urlencode($v);
    }

    return implode('&', $result);
}

/**
 * 返回解析空间地址
 * @param integer $uid 用户ID
 * @param string $class 样式类
 * @param string $target 是否进行跳转
 * @param string $text 标签内的相关内容
 * @param boolen $icon 是否显示用户组图标，默认为true
 * @return string 解析空间地址HTML
 */
function getUserSpace($uid, $class, $target, $text, $icon = true)
{
    // 2.8转移
    // 静态变量
    static $_userinfo = array();
    // 判断是否有缓存
    if (!isset($_userinfo[$uid])) {
        $_userinfo[$uid] = model('User')->getUserInfo($uid);
    }
    // 配置相关参数
    empty($target) && $target = '_self';
    empty($text) && $text = $_userinfo[$uid]['uname'];
    // 判断是否存在替换信息
    preg_match('|{(.*?)}|isU', $text, $match);
    if ($match) {
        if ($match[1] == 'uname') {
            $text = str_replace('{uname}', $_userinfo[$uid]['uname'], $text);
            //empty($class) && $class = 'username';  //2013/2/28  wanghaiquan
            empty($class) && $class = 'name';
        } else {
            preg_match("/{uavatar}|{uavatar\\=(.*?)}/e", $text, $face_type);
            switch ($face_type[1]) {
                case 'b':
                    $userface = 'big';
                    break;
                case 'm':
                    $userface = 'middle';
                    break;
                default:
                    $userface = 'small';
                    break;
            }
            $face = $_userinfo[$uid]['avatar_' . $userface];
            $text = '<img src="' . $face . '" />';
            empty($class) && $class = 'userface';
            $icon = false;
        }
    }
    // 组装返回信息
    $user_space_info = '<a event-node="face_card" uid="' . $uid . '" href="' . $_userinfo[$uid]['space_url'] . '" class="' . $class . '" target="' . $target . '">' . $text . '</a>';
    // 用户认证图标信息
    if ($icon) {
        $group_icon = array();
        $user_group = static_cache('usergrouplink_' . $uid);
        if (!$user_group) {
            $user_group = model('UserGroupLink')->getUserGroupData($uid);
            static_cache('usergrouplink_' . $uid, $user_group);
        }
        if (!empty($user_group)) {
            foreach ($user_group[$uid] as $value) {
                $group_icon[] = '<img title="' . $value['user_group_name'] . '" src="' . $value['user_group_icon_url'] . '" style="width:15px;height:15px;"/>';
            }
            $user_space_info .= implode('&nbsp;', $group_icon);
        }
    }

    return $user_space_info;
}

/**
 * 针对后台
 * 通过参数对链接进行跳转
 * @param integer $link 链接地址（跳转地址）
 * @param boolean $target 是否新开窗口
 * @param string $color a链接颜色
 * @param string $style a链接颜色
 * @param string $bak_text 未查到$text时所用的备用名称
 * @param string $text 文本
 * @return string 解析空间地址HTML
 *
 */
function getQuickLink($link,$text,$bak_text,$target = true,$color = '#3b5999',$style){
    if(!$text){
        $color = '#FF0000';
        $text = $bak_text;
    }
    if($target){
        $a_link = "<a href='{$link}' style='color:{$color}; {$style}' target='_blank'>{$text}</a>";
    }else{
        $a_link = "<a href='{$link}' style='color:{$color}; {$style}' target='_blank'>{$text}</a>";
    }
    return $a_link;
}

/**
 * 检查是否是以手机浏览器进入(IN_MOBILE)
 * iPad上浏览pc的内容尺寸较小，默认也为3g版
 *old
 */
function isMobile()
{
    $mobile = array();
    static $mobilebrowser_list = 'Mobile|iPhone|iPad|Android|WAP|NetFront|JAVA|OperasMini|UCWEB|WindowssCE|Symbian|Series|webOS|SonyEricsson|Sony|BlackBerry|Cellphone|dopod|Nokia|samsung|PalmSource|Xphone|Xda|Smartphone|PIEPlus|MEIZU|MIDP|CLDC';
    //note 获取手机浏览器
    if (preg_match("/$mobilebrowser_list/i", $_SERVER['HTTP_USER_AGENT'], $mobile)) {
        return true;
    } else {
        if (preg_match('/(mozilla|chrome|safari|opera|m3gate|winwap|openwave)/i', $_SERVER['HTTP_USER_AGENT'])) {
            return false;
        } else {
            if ($_GET['mobile'] === 'yes') {
                return true;
            } else {
                return false;
            }
        }
    }
}

/**
 * @return 是否为移动端
 * new
 */
function is_mobile()
{
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    $is_pc = (strpos($agent, 'windows nt')) ? true : false;
    $is_mac = (strpos($agent, 'mac os')) ? true : false;
    $is_iphone = (strpos($agent, 'iphone')) ? true : false;
    $is_android = (strpos($agent, 'android')) ? true : false;
    $is_ipad = (strpos($agent, 'ipad')) ? true : false;

    if($is_pc){
        return  false;
    }

    if($is_mac){
        return  false;
    }

    if($is_iphone){
        return  true;
    }

    if($is_android){
        return  true;
    }

    if($is_ipad){
        return  true;
    }
}

function isiPhone()
{
    return strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false;
}

function isiPad()
{
    return strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== false;
}

function isiOS()
{
    return isiPhone() || isiPad();
}

function isAndroid()
{
    return strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false;
}

/* TS2.X的兼容方法 */
function safe($text)
{
    return h($text);
}

function text($text)
{
    return t($text);
}

function real_strip_tags($str, $allowable_tags = "")
{
    $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
    return strip_tags($str, $allowable_tags);
}

function getUserFace($uid, $size)
{
    $userinfo = model('User')->getUserInfo($uid);
    switch ($size) {
        case 'b':
            $userface = $userinfo['avatar_big'];
            break;
        case 'm':
            $userface = $userinfo['avatar_middle'];
            break;
        default:
            $userface = $userinfo['avatar_small'];
            break;
    }
    return $userface;
}

function getUserName($uid)
{
    $userinfo = model('User')->getUserInfo($uid);
    return $userinfo['uname'];
}

function getUserContact($uid)
{
    $userinfo = model('User')->getUserInfo($uid);
    return $userinfo["phone"] ? $userinfo["phone"] : $userinfo['email'];
}

/**计算时间差  分别返回 秒 分 小时 天
 * @param $time要计算的时间差
 * @return bool|string
 */
function getDateDiffer($time)
{
    $startdate = intval($time);//当前时间
    $enddate = time();//结束时间
    $rtime = intval($enddate - $startdate);//计算出秒
    $date = intval($rtime / 86400);//计算天
    $hour = intval($rtime / 3600);//计算小时
    $minute = intval($rtime / 60);//计算有多少分钟
    $second = $rtime;//计算有多少秒
    $restime = $time;
    if ($second < 60) {
        $restime = $second . "秒前";
    } else if ($minute < 60) {
        $restime = $minute . "分钟前";
    } else if ($hour < 24) {
        $restime = $hour . "小时前";
    } else if ($date < 7) {
        $restime = $date . "天前";
    } else {
        $restime = date('Y-m-d H:i', $restime);
    }
    return $restime;
}

function getUserLevel($uid)
{
    $res = D("user")->where(array('uid' => $uid))->field('my_study_level')->find();
    $res = $res['my_study_level'];

    if ($res == 0) {
        return "学生";
    } else if ($res == 1) {
        return "学神";
    } else if ($res == 2) {
        return "学霸";
    } else if ($res == 3) {
        return "学渣";
    } else if ($res == 4) {
        return "学弱";
    } else {
        return "学生";
    }

}

function keyWordFilter($text)
{
    return filter_keyword($text);
}

function getFollowState($uid, $fid, $type = 0)
{
    if ($uid <= 0 || $fid <= 0)
        return 'unfollow';
    if ($uid == $fid)
        return 'unfollow';
    if (M('user_follow')->where("(uid=$uid AND fid=$fid) OR (uid=$fid AND fid=$uid)")->count() == 2) {
        return 'eachfollow';
    } else if (M('user_follow')->where("uid=$uid AND fid=$fid")->count()) {
        return 'havefollow';
    } else {
        return 'unfollow';
    }
}

function matchImages($content = '')
{
    $src = array();
    preg_match_all('/<img.*src=(.*)[>|\\s]/iU', $content, $src);
    if (count($src [1]) > 0) {
        foreach ($src [1] as $v) {
            $images [] = trim($v, "\"'"); //删除首尾的引号 ' "
        }
        return $images;
    } else {
        return false;
    }
}

function matchReplaceImages($content = '')
{
    $image = preg_replace_callback('/<img.*src=(.*)[>|\\s]/iU', "matchReplaceImagesOnce", $content);
    return $image;
}

function matchReplaceImagesOnce($matches)
{
    $matches[1] = str_replace('"', '', $matches[1]);
    return sprintf("<a class='thickbox'  href='%s'>%s</a>", $matches[1], $matches[0]);
}

//加密函数
function jiami($txt, $key = null)
{
    if (empty ($key))
        $key = C('SECURE_CODE');
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-=_";
    $nh = rand(0, 64);
    $ch = $chars [$nh];
    $mdKey = md5($key . $ch);
    $mdKey = substr($mdKey, $nh % 8, $nh % 8 + 7);
    $txt = base64_encode($txt);
    $tmp = '';
    $i = 0;
    $j = 0;
    $k = 0;
    for ($i = 0; $i < strlen($txt); $i++) {
        $k = $k == strlen($mdKey) ? 0 : $k;
        $j = ($nh + strpos($chars, $txt [$i]) + ord($mdKey [$k++])) % 64;
        $tmp .= $chars [$j];
    }
    return $ch . $tmp;
}

//解密函数
function jiemi($txt, $key = null)
{
    if (empty ($key))
        $key = C('SECURE_CODE');
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-=_";
    $ch = $txt [0];
    $nh = strpos($chars, $ch);
    $mdKey = md5($key . $ch);
    $mdKey = substr($mdKey, $nh % 8, $nh % 8 + 7);
    $txt = substr($txt, 1);
    $tmp = '';
    $i = 0;
    $j = 0;
    $k = 0;
    for ($i = 0; $i < strlen($txt); $i++) {
        $k = $k == strlen($mdKey) ? 0 : $k;
        $j = strpos($chars, $txt [$i]) - $nh - ord($mdKey [$k++]);
        while ($j < 0)
            $j += 64;
        $tmp .= $chars [$j];
    }
    return base64_decode($tmp);
}


//******************************************************************************
// 转移应用添加函数
/**
 * +----------------------------------------------------------
 * 字符串截取，支持中文和其它编码
 * +----------------------------------------------------------
 * @static
 * @access public
 * +----------------------------------------------------------
 * @param string $str 需要转换的字符串
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * +----------------------------------------------------------
 * @return string
+----------------------------------------------------------
 */
function mStr($str, $length, $charset = "utf-8", $suffix = true)
{
    return msubstr($str, 0, $length, $charset, $suffix);
}

/**
 * +----------------------------------------------------------
 * 字符串截取，支持中文和其它编码
 * +----------------------------------------------------------
 * @static
 * @access public
 * +----------------------------------------------------------
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * +----------------------------------------------------------
 * @return string
+----------------------------------------------------------
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true)
{
    if (function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
    } else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    if ($suffix && $str != $slice) return $slice . "...";
    return $slice;
}

// // 获取给定用户的用户组图标
// function getUserGroupIcon($uid){
//     static $_var = array();
//     if (!isset($_var[$uid]))
//         $_var[$uid] = model('UserGroup')->getUserGroupIcon($uid);

//     return $_var[$uid];
// }

/**
 * 检查Email地址是否合法
 *
 * @return boolean
 */
function isValidEmail($email)
{
    return preg_match("/^[_a-zA-Z\d\-\.]+@[_a-zA-Z\d\-]+(\.[_a-zA-Z\d\-]+)+$/i", $email) !== 0;
}

// 发送常用http header信息
function send_http_header($type = 'utf8')
{
    //utf8,html,wml,xml,图片、文档类型 等常用header
    switch ($type) {
        case 'utf8':
            header("Content-type: text/html; charset=utf-8");
            break;
        case 'xml':
            header("Content-type: text/xml; charset=utf-8");
            break;
    }
}

/**
 * 判断作者
 * @param unknown_type $dao
 * @param unknown_type $field
 * @param unknown_type $id
 * @param unknown_type $user
 * @return boolean
 */
function CheckAuthorPermission($dao, $id, $field = 'id', $getfield = 'uid')
{
    $map[$field] = $id;
    $value = $dao->where($map)->getField($getfield);
    return $value == $GLOBALS['ts']['mid'];
}

/**
 * 锁定表单
 *
 * @param int $life_time 表单锁的有效时间(秒). 如果有效时间内未解锁, 表单锁自动失效.
 * @return boolean 成功锁定时返回true, 表单锁已存在时返回false
 */
function lockSubmit($life_time = null)
{
    if (isset($_SESSION['LOCK_SUBMIT_TIME']) && intval($_SESSION['LOCK_SUBMIT_TIME']) > time()) {
        return false;
    } else {
        $life_time = $life_time ? $life_time : 10;
        $_SESSION['LOCK_SUBMIT_TIME'] = time() + intval($life_time);
        return true;
    }
}

/**
 * 检查表单是否已锁定
 *
 * @return boolean 表单已锁定时返回true, 否则返回false
 */
function isSubmitLocked()
{
    return isset($_SESSION['LOCK_SUBMIT_TIME']) && intval($_SESSION['LOCK_SUBMIT_TIME']) > time();
}

/**
 * 表单解锁
 *
 * @return void
 */
function unlockSubmit()
{
    unset($_SESSION['LOCK_SUBMIT_TIME']);
}

/**
 * 获取给定IP的物理地址
 *
 * @param string $ip
 * @return string
 */
function convert_ip($ip)
{
    $return = '';
    if (preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $ip)) {
        $iparray = explode('.', $ip);
        if ($iparray[0] == 10 || $iparray[0] == 127 || ($iparray[0] == 192 && $iparray[1] == 168) || ($iparray[0] == 172 && ($iparray[1] >= 16 && $iparray[1] <= 31))) {
            $return = '- LAN';
        } elseif ($iparray[0] > 255 || $iparray[1] > 255 || $iparray[2] > 255 || $iparray[3] > 255) {
            $return = '- Invalid IP Address';
        } else {
            $tinyipfile = ADDON_PATH . '/libs/misc/tinyipdata.dat';
            $fullipfile = ADDON_PATH . '/libs/misc/wry.dat';
            if (@file_exists($tinyipfile)) {
                $return = convert_ip_tiny($ip, $tinyipfile);
            } elseif (@file_exists($fullipfile)) {
                $return = convert_ip_full($ip, $fullipfile);
            }
        }
    }
    $return = iconv('GBK', 'UTF-8', $return);
    return $return;
}

/**
 * 格式化微博内容中url内容的长度
 * @param string $match 匹配后的字符串
 * @return string 格式化后的字符串
 */
function _format_feed_content_url_length($match)
{
    static $i = 97;
    $result = '{tsurl==' . chr($i) . '}';
    $i++;
    $GLOBALS['replaceHash'][$result] = $match[0];
    return $result;
}

function format_array_intval($str)
{
    if (!is_string($str)) {
        die('Parameter is not string ');
    }
    $arr = explode(',', $str);
    $arr = array_filter($arr);
    $arr = array_unique($arr);
    $arr = array_map('intval', $arr);

    return $arr;
}

/**
 * 检查一个用户VIP的类型
 * @param $uid 要查询的用户ID
 * @return integer 返回VIP状态，0不是，1月费，2年费
 */
function vipUserType($uid)
{
    static $vips = array();
    if (!isset($vips[$uid])) {
        $vips[$uid] = intval(D('ZyLearnc', 'classroom')->getVip($uid));
    }
    return $vips[$uid];
}

//判断当前用户是否是管理员
function is_admin($uid)
{
    return model('UserGroup')->isAdmin($uid);
}

//判断当前用户是否是机构
function is_school($uid)
{
    return model('UserGroup')->isSchool($uid);
}

//判断当前用户是否是讲师
function is_teacher($uid)
{
    return model('UserGroup')->isTeacher($uid);
}

//判断当前用户是否是销课员
function is_pinclass($uid)
{
    return model('UserGroup')->isPinclass($uid);
}

/**
 * 取得一个课程的实际价格
 * @param array $rs 一行记录集
 * @param integer $uid 获取价格的用户UID
 * @param boolean $autoInt 是自动去掉价格小数点后面的多余的0，默认去掉
 * @param boolean $detail 是否返回详细的价格数组。
 * @param int $type 类型 默认为点播，1点播，2直播
 *  数组包含，实际价格，包含原价，vip优惠价，限时优惠价，折扣，折扣类型(0无，1vip，2限时)。
 * @return mixed 返回实际价格或包含细节的数组
 * ->field("id,uid,video_title,mhm_id,teacher_id,v_price,t_price,vip_level,is_charge
 * endtime,starttime,limit_discount,uid,teacher_id")
 */
function getPrice($rs, $uid, $autoInt = true, $detail = false,$type = 1)
{

    //价格细节
    $prices = array(
        'oriPrice' => $rs['v_price'],
        'vipPrice' => 0,
        'disPrice' => 0,
        'discount' => 0,
        'dis_type' => 0,
        'price' => $rs['t_price'],
    );

    if($type == 1){
        //是VIP则计算VIP折扣价
        $vip_type = M('zy_learncoin')->where(array('uid' => $uid, 'vip_expire' => array('egt', time())))->getField('vip_type');
        $video_level_info = M('zy_video')->where('id=' . $rs['id'])->field('vip_level,vip_pattern')->find();

        $user_level = M('user_vip')->where('is_del=0 and id = ' . $vip_type)->getField('sort');
        $video_level = M('user_vip')->where('is_del=0 and id = ' . $video_level_info['vip_level'])->getField('sort');

        if ($user_level && $video_level) {
            $vip_pattern_config = model('Xdata')->get('admin_Config:vipPatternConfig');

            //独立模式
            if ($vip_pattern_config['vip_switch'] == 0) {
                if ($user_level == $video_level) {
                    $prices['price'] = 0;
                }
            }

            //阶梯模式
            if ($vip_pattern_config['vip_switch'] == 1) {
                if ($user_level >= $video_level) {
                    $prices['price'] = 0;
                }
            }
        }
    }

    ////限时折扣
    //if ($rs['is_tlimit'] == 1 && $rs['endtime'] > time() && $rs['starttime'] < time()) {
    //    $prices['disPrice'] = $rs['t_price'];
    //    $prices['discount'] = $rs['limit_discount'] * 10;
    //    $prices['dis_type'] = 2;
    //    $prices['price'] = $rs['t_price'];
    //}

    $teacher_id = D('ZyTeacher', 'classroom')->getTeacherStrByMap((array('uid' => $uid)), 'id');

    //如果上传者是自己、讲师是自己或者为管理员/机构管理员 则免费
    if ($uid || $rs['uid']) {
        if ($uid == $rs['uid']) {
            $prices['price'] = 0;
        }
    }
    if ($teacher_id || $rs['teacher_id']) {
        if ($teacher_id == $rs['teacher_id']) {
            $prices['price'] = 0;
        }
    }
    if (is_admin($uid) || $rs['is_charge'] == 1) {
        $prices['price'] = 0;
    }
    if (is_school($uid) !== false &&  is_school($uid) == $rs['mhm_id']) {
        $prices['price'] = 0;
    }
    if (!$prices['price']) {
        $prices['price'] = 0;
    }

    //如果小数点后面是0，则去掉
    if ($autoInt) {
        foreach ($prices as $k => $v) {
            if (is_numeric($v)) {
                $parts = explode('.', $v);
                $v = $parts[0];
                if (isset($parts[1])) {
                    $parts[1] = rtrim($parts[1], '0');
                    if ($parts[1] && $parts[1] > 0) {
                        $v .= '.' . $parts[1];
                    }
                }
                $prices[$k] = $v;
            }
        }
    }

    return $detail ? $prices : $prices['price'];
}

/**
 * @param $album_id
 * @param $uid
 * @param boolean $autoInt 是自动去掉价格小数点后面的多余的0，默认去掉
 * @param boolean $detail 是否返回详细的价格数组。
 */
function getAlbumPrice($album_id,$uid, $autoInt = true, $detail = true){
    $album = D("Album",'classroom')->where(['id'=>$album_id])->field('id,price,mhm_id,album_title')->find();
    $video_ids       = trim(D("Album",'classroom')->getVideoId($album_id), ',');
    $v_map['id']     = array('in', array($video_ids));
    $v_map["is_del"] = 0;
    $album_info      = M("zy_video")->where($v_map)->field("t_price")->select();

    //计算课程原价之和
    $oprice = 0;
    foreach ($album_info as $key => $video) {
        $oprice += $video['t_price'];
    }

    $prices = $album['price'];
    //如果是超级管理员
    if(is_admin($uid)){
        $prices = 0;
    }

    //如果是班级机构管理员
    if($album['mhm_id'] && ($album['mhm_id'] == is_school($uid))){
        $prices = 0;
    }

    //价格细节 disPrice优惠
    $prices = array(
        'oriPrice' => $oprice,
        'price' => $prices,
        'disPrice' => ($oprice - $prices) > 0 ? ($oprice - $prices) : 0.00,
    );

    //如果小数点后面是0，则去掉
    if ($autoInt) {
        foreach ($prices as $k => $v) {
            if (is_numeric($v)) {
                $parts = explode('.', $v);
                $v = $parts[0];
                if (isset($parts[1])) {
                    $parts[1] = rtrim($parts[1], '0');
                    if ($parts[1] && $parts[1] > 0) {
                        $v .= '.' . $parts[1];
                    }
                }
                $prices[$k] = $v;
            }
        }
    }

    return $detail ? $prices : $prices['price'];
}
/**
 * 取得平台配置的所有分成比例
 * @return array 所有分成比例
 */
function getAllProportion()
{
    $old_proportion = model('Xdata')->get('admin_Config:divideIntoConfig');

    //平台与机构分成比例
    $platform_and_school = array_filter(explode(':', $old_proportion['platform_and_school']));
    if (empty($platform_and_school)) {
        return 1;
    }
    //平台与机构自营分成比例
    $school_self_support = array_filter(explode(':', $old_proportion['school_self_support']));
    if (empty($platform_and_school)) {
        return 2;
    }
    //分享者与机构分成比例
    $share_platform = array_filter(explode(':', $old_proportion['share_platform']));
    if (empty($share_platform)) {
        return 3;
    }
    // 购买用户机构与课程所属机构分成比例
    $school_and_buyschool = array_filter(explode(':', $old_proportion['school_and_buyschool']));
    if (empty($school_and_buyschool)) {
        return 4;
    }

    $proportion['pac_platform'] = floatval($platform_and_school[0]);//平台与机构-平台
    $proportion['pac_school'] = floatval($platform_and_school[1]);//平台与机构-机构
    $proportion['sss_platform'] = floatval($school_self_support[0]);//机构自营-平台
    $proportion['sss_school'] = floatval($school_self_support[1]);//机构自营-机构
    $proportion['sp_share'] = floatval($share_platform[0]);//分享者与机构-分享者
    $proportion['sp_school'] = floatval($share_platform[1]);//分享者与机构-机构
    $proportion['ouats_theschool'] = floatval($school_and_buyschool[0]);// 购买用户机构与课程所属机构分成比例-课程所属机构
    $proportion['ouats_buyschool'] = floatval($school_and_buyschool[1]);// 购买用户机构与课程所属机构分成比例-购买用户机构

    return $proportion;
}

/**
 * 取得应用配置信息
 * @param string $name 配置项名称，为null时取得$type的数组
 * @param string $type 配置项类型，为null时取得完整的配置数组
 * @param string $default 默认值，如果找不到配置项将返回次值
 * @return mixed 如果有相应的配置则取得配置，否则取得默认值
 */

function getAppConfig($name = null, $type = 'basic', $default = null)
{
    //使用静态变量，暂留缓存提高速度
    static $appConfigs = null;
    if ($appConfigs === null) {
        $appConfigs = model('Xdata')->lget('classroom_AdminConfig');
    }
    if (is_null($type)) return $appConfigs;
    if (isset($appConfigs[$type])) {
        if (is_null($name)) {
            return $appConfigs[$type];
        } elseif (isset($appConfigs[$type][$name])) {
            return $appConfigs[$type][$name];
        }
    }
    return $default;
}

/**
 * 获取客服QQ
 */
function getConnectQQ()
{
    $qqlist = explode("\n", getAppConfig('qqlist', 'page'));
    foreach ($qqlist as $key => $qq) {
        $qqlist[$key] = preg_replace('/\r|\n/', '', $qq);
        $qqlist[$key] = explode(' ', $qqlist[$key]);
    }
    return $qqlist;
}

/**
 * 标准化整数数字的逗号分隔值
 * @param string|array $list 逗号分隔值列表或一维数组列表
 * @param integer $gt 数字列表中的数字必须大于此数，否则会被过滤，默认为null，不使用过滤功能
 * @param boolean $unique 是否去除列表中重复的数字，默认不去除
 * @param boolean $both 是否在返回结果字符串两端加上分隔符号，注意当列表字符串为空，则不会添加分隔符号
 * @param string $delimiter 分隔符号，默认是半角逗号
 * @return string 返回处理过的数字列表
 *
 * 附：前端容错输入数字列表，理论上，没测试过
 * $list = preg_replace(array('/\s/','/，/','/,+/'),array('',',',','),$string);
 */
function getCsvInt($list, $gt = null, $unique = false, $both = false, $delimiter = ',')
{
    if (is_string($list)) {
        $list = explode($delimiter, trim($list, $delimiter));
    }

    //全部整型化
    foreach ($list as $key => $val) {
        $val = intval($val);
        if (null === $gt) {
            $list[$key] = $val;
        } elseif ($val > $gt) {
            $list[$key] = $val;
        } else {
            unset($list[$key]);
        }
    }

    //唯一值
    if ($unique) $list = array_unique($list);
    $list = implode($delimiter, $list);
    if ($both && $list) {
        $list = "{$delimiter}{$list}{$delimiter}";
    }
    return $list;
}

/*
 * 产生随机字符串
 * 产生一个指定长度的随机字符串,并返回给用户
 * @access public
 * @param int $len 产生字符串的位数
 * @return string
 */

function genRandomString($len = 6)
{
    $chars = array(
        "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
        "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
        "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
        "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
        "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
        "3", "4", "5", "6", "7", "8", "9"
    );
    $charsLen = count($chars) - 1;
    shuffle($chars);    // 将数组打乱
    $output = "";
    for ($i = 0; $i < $len; $i++) {
        $output .= $chars[mt_rand(0, $charsLen)];
    }
    return $output;
}

/**
 * 加密函数
 * @param string $txt 加密字符串
 * @param string $key 加密密钥        必须和解密密钥一致
 */
function sunjiami($txt, $key = '')
{
    if (empty ($key)) $key = "ashang408408";
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-=_';
    $nh = rand(0, 64);
    $ch = $chars [$nh];
    $mdKey = md5($key . $ch);
    $mdKey = substr($mdKey, $nh % 8, $nh % 8 + 7);
    $txt = base64_encode($txt);
    $tmp = '';
    $i = 0;
    $j = 0;
    $k = 0;
    for ($i = 0; $i < strlen($txt); $i++) {
        $k = $k == strlen($mdKey) ? 0 : $k;
        $j = ($nh + strpos($chars, $txt [$i]) + ord($mdKey [$k++])) % 64;
        $tmp .= $chars [$j];
    }
    return $ch . $tmp;
}

/**
 * 解密函数
 * @param string $txt 解密字符串
 * @param string $key 解密密钥       必须和加密密钥一致
 */
function sunjiemi($txt, $key = '')
{
    if (empty ($key)) $key = "ashang408408";
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-=_';
    $ch = $txt [0];
    $nh = strpos($chars, $ch);
    $mdKey = md5($key . $ch);
    $mdKey = substr($mdKey, $nh % 8, $nh % 8 + 7);
    $txt = substr($txt, 1);
    $tmp = '';
    $i = 0;
    $j = 0;
    $k = 0;
    for ($i = 0; $i < strlen($txt); $i++) {
        $k = $k == strlen($mdKey) ? 0 : $k;
        $j = strpos($chars, $txt [$i]) - $nh - ord($mdKey [$k++]);
        while ($j < 0)
            $j += 64;
        $tmp .= $chars [$j];
    }
    return base64_decode($tmp);
}


/*
 * 下载附件
 * name 附件的文件信息
 */
function downloadFile($fileurl, $filepath)
{
    $_file = pathinfo($filepath);
    if (!file_exists($filepath)) {
        header("Content-type: text/html; charset=utf-8");
        echo "File not found!";
        exit;
    } else {
        $file = fopen($filepath, "r");
        Header("Content-type: application/octet-stream");
        Header("Accept-Ranges: bytes");
        Header("Accept-Length: " . filesize($filepath));
        Header("Content-Disposition: attachment; filename=" . $_file['basename']);
        echo fread($file, filesize($filepath));
        fclose($file);
    }
}

/**
 * 查询一个用户是否购买过一个班级
 * @param $uid 要查询的用户
 * @param $vid 班级ID
 * @return mixed 如果购买过则返回订单ID，否则返回false
 */
function isBuyAlbum($uid, $aid)
{
    return D('ZyOrder', 'classroom')->isBuyAlbum($uid, $aid);
}

/**
 * 查询一个用户是否购买过一个课程
 * @param $uid 要查询的用户
 * @param $vid 课程ID
 * @return mixed 如果购买过则返回订单ID，否则返回false
 */
function isBuyVideo($uid, $vid)
{
    return D('ZyOrder', 'classroom')->isBuyVideo($uid, $vid);
}


/**
 * 模拟post进行url请求
 * @param string $url
 * @param array $post_data
 */
function request_post($url = '', $post_data = array())
{
    if (empty($url) || empty($post_data)) {
        return false;
    }

    $o = "";
    foreach ($post_data as $k => $v) {
        $o .= "$k=" . urlencode($v) . "&";
    }
    $post_data = substr($o, 0, -1);

    $postUrl = $url;
    $curlPost = $post_data;
    $ch = curl_init();//初始化curl
    curl_setopt($ch, CURLOPT_URL, $postUrl);//抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    $data = curl_exec($ch);//运行curl
    curl_close($ch);

    return $data;
}

/**
 * 模拟post进行url请求 xml报文
 * @param string $url
 * @param array $post_data
 */
function request_post_xml($url = '', $post_data = array())
{
    if (empty($url) || empty($post_data)) {
        return false;
    }

    $header[] = "Content-type: text/xml";      //定义content-type为xml,注意是数组
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        printcurl_error($ch);
    }
    curl_close($ch);

    return $response;
}

/**
 * 短信验证码模板
 */
function get_code_tpl($rnum = '111111')
{
    return "您本次获取的验证码为" . $rnum . "请在页面指定处填写，请勿随意告知其他任何人！如非本人操作，请忽略此信息！";
}

/**
 * 去重函数
 * @return string
 */
function getUniqure($arr)
{
    return array_flip(array_flip($arr));
}


/**
 * 将一个平面的二维数组按照指定的字段生成树状结构
 * @param array $array 需要生成树形结构的二维数组/查询结果集
 * @param mixed $id_key 节点ID列的键名
 * @param mixed $pid_key 节点父ID列的键名
 * @param mixed $child_key 保存子节点的键名
 * @param array $refs 返回结果中包含节点引用
 * @return array 树形结构的数组
 */
function array2tree(array $array, $id_key, $pid_key, $child_key, &$refs = null)
{
    $refs = array();
    foreach ($array as $key => $val) {
        $array[$key][$child_key] = array();
        $refs[$val[$id_key]] =& $array[$key];
    }
    $tree = array();
    foreach ($array as $key => $val) {
        $pid = $val[$pid_key];
        if ($pid) {
            if (isset($refs[$pid])) {
                $parent =& $refs[$pid];
                $parent[$child_key][] =& $array[$key];
            } else {
                $tree[] =& $array[$key];
            }
        } else {
            $tree[] =& $array[$key];
        }
    }
    return $tree;
}

/**
 * 对域名进行处理变成顶级域名
 * @param $domain 需要处理的域名前缀
 * @return string
 */
function getDomain($domain,$id) {
    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
//    $new_domain = preg_replace('#'.$http_type.'\w+#',$http_type.$domain,$http_type.$_SERVER ['HTTP_HOST']);//一级域名
    $new_domain = $http_type.$domain.'.'.$_SERVER ['HTTP_HOST'];//二级域名
    if(!$domain || $domain == 'www'){
        $new_domain = U('school/School/index',['id'=>$id]);
    }
    return $new_domain;
}


/**
 * 获取客户端信息
 *
 * 在本地测试可能取到的是127.0.0.1 所以可能有小报错,请关闭报错即可!
 */

/**
 * 获取用户浏览器型号。新加浏览器，修改代码，增加特征字符串.把IE加到12.0 可以使用5-10年了.
 */
function getBrowser()
{
    $br = $_SERVER['HTTP_USER_AGENT'];
    if (!empty($br)) {
        if (strpos($br, 'Maxthon')) {
            $browser = 'Maxthon';
        } elseif (strpos($br, 'MSIE 12.0')) {
            $browser = 'Internet Explorer';
        } elseif (strpos($br, 'MSIE 11.0')) {
            $browser = 'Internet Explorer';
        } elseif (strpos($br, 'MSIE 10.0')) {
            $browser = 'Internet Explorer';
        } elseif (strpos($br, 'MSIE 9.0')) {
            $browser = 'Internet Explorer';
        } elseif (strpos($br, 'MSIE 8.0')) {
            $browser = 'Internet Explorer';
        } elseif (strpos($br, 'MSIE 7.0')) {
            $browser = 'Internet Explorer';
        } elseif (strpos($br, 'MSIE 6.0')) {
            $browser = 'Internet Explorer';
        } elseif (strpos($br, 'NetCaptor')) {
            $browser = 'NetCaptor';
        } elseif (strpos($br, 'Netscape')) {
            $browser = 'Netscape';
        } elseif (strpos($br, 'Lynx')) {
            $browser = 'Lynx';
        } elseif (strpos($br, 'OPR')) {
            $browser = 'Opera';
        } elseif (strpos($br, 'Chrome')) {
            $browser = 'Google Chrome';
        } elseif (strpos($br, 'Firefox')) {
            $browser = 'Mozilla Firefox';
        } elseif (strpos($br, 'Safari')) {
            $browser = 'Safari';
        } elseif (strpos($br, 'iphone') || strpos($br, 'ipod')) {
            $browser = 'iphone';
        } elseif (strpos($br, 'ipad')) {
            $browser = 'iphone';
        } elseif (strpos($br, 'android')) {
            $browser = 'android';
        } else {
            $browser = 'other';
        }
        return $browser;
    }else{
        return false;
    }
}

//获得访客浏览器版本型号
function getBrowserVer(){
    if (empty($_SERVER['HTTP_USER_AGENT'])){    //当浏览器没有发送访问者的信息的时候
        return 'other';
    }
    $agent = $_SERVER['HTTP_USER_AGENT'];
    if (preg_match('/MSIE\s(\d+)\..*/i', $agent, $regs))
        return explode(' ',explode(';',$regs[0] )[0])[1];
    elseif (preg_match('/FireFox\/(\d+)\..*/i', $agent, $regs))
        return explode('/',$regs[0])[1];
    elseif (preg_match('/OPR[\s|\/](\d+)\..*/i', $agent, $regs))
        return explode('/',$regs[0])[1];
    elseif (preg_match('/Chrome\/(\d+)\..*/i', $agent, $regs))
        return explode('/',explode(' ',$regs[0] )[0])[1];
    elseif ((strpos($agent,'Chrome') == false) && preg_match($agent, $regs))
        return explode('/',$regs[0])[1];
    elseif (preg_match('/AppleWebKit\/(\d+)\..*/i', $agent, $regs))
        return explode(' ',explode('/',$regs[0])[1])[0];
    else
        return 'unknow';
}

//获得访客浏览器语言
function getBrowserLang() {
    if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $lang = substr($lang, 0, 5);
        if (preg_match("/zh-cn/i", $lang)) {
            $lang = "简体中文";
        } elseif (preg_match("/zh/i", $lang)) {
            $lang = "繁体中文";
        } else {
            $lang = "English";
        }
        return $lang;
    } else {
        return false;
    }
}

//获取访客操作系统  升级版
function getOs() {
    $Agent = $_SERVER['HTTP_USER_AGENT'];
    if (!empty($Agent)){
        if (@eregi('win', $Agent) && strpos($Agent, '95')) {
            $os = 'Windows 95';
        } elseif (@eregi('win 9x', $Agent) && strpos($Agent, '4.90')) {
            $os = 'Windows ME';
        } elseif (@eregi('win', $Agent) && @eregi('98', $Agent)) {
            $os = 'Windows 98';
        } elseif (@eregi('win', $Agent) && @eregi('nt 5.0', $Agent)) {
            $os = 'Windows 2000';
        } elseif (@eregi('win', $Agent) && @eregi('nt 6.0', $Agent)) {
            $os = 'Windows Vista';
        } elseif (@eregi('win', $Agent) && @eregi('nt 10', $Agent)) {
            $os = 'Windows 10';
        } elseif (@eregi('win', $Agent) && @eregi('nt 8', $Agent)) {
            $os = 'Windows 8';
        } elseif (@eregi('win', $Agent) && @eregi('nt 6', $Agent)) {
            $os = 'Windows 7';
        } elseif (@eregi('win', $Agent) && @eregi('nt 5', $Agent)) {
            $os = 'Windows XP';
        } elseif (@eregi('win', $Agent) && @eregi('nt', $Agent)) {
            $os = 'Windows NT';
        } elseif (@eregi('win', $Agent) && ereg('32', $Agent)) {
            $os = 'Windows 32';
        } elseif (@eregi('Linux; Android', $Agent)) {
            $os = 'Android';
        } elseif (@eregi('Macintosh;', $Agent)) {
            $os = 'Mac OS';
        } elseif (@eregi('iPhone;', $Agent) && @eregi('Mac OS', $Agent)) {
            $os = 'iPhone OS';
        } elseif (@eregi('iPad;', $Agent) && @eregi('Mac OS', $Agent)) {
            $os = 'iPad OS';
        } elseif (@eregi('linux', $Agent)) {
            $os = 'Linux';
        } elseif (@eregi('unix', $Agent)) {
            $os = 'Unix';
        } else if (@eregi('sun', $Agent) && @eregi('os', $Agent)) {
            $os = 'SunOS';
        } elseif (@eregi('ibm', $Agent) && @eregi('os', $Agent)) {
            $os = 'IBM OS/2';
        } elseif (@eregi('Mac', $Agent) && @eregi('PC', $Agent)) {
            $os = 'Macintosh';
        } elseif (@eregi('PowerPC', $Agent)) {
            $os = 'PowerPC';
        } elseif (@eregi('AIX', $Agent)) {
            $os = 'AIX';
        } elseif (@eregi('HPUX', $Agent)) {
            $os = 'HPUX';
        } elseif (@eregi('NetBSD', $Agent)) {
            $os = 'NetBSD';
        } elseif (@eregi('BSD', $Agent)) {
            $os = 'BSD';
        } elseif (ereg('OSF1', $Agent)) {
            $os = 'OSF1';
        } elseif (ereg('IRIX', $Agent)) {
            $os = 'IRIX';
        } elseif (@eregi('FreeBSD', $Agent)) {
            $os = 'FreeBSD';
        } elseif ($os == '') {
            $os = 'other';
        }else{
            $os = '未知';
        }
    }else{
        $os = false;
    }
    return $os;
}

//获得访客真实ip
function getIp() {
    if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { //获取代理ip
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    }
    if ($ip) {
        $ips = array_unshift($ips, $ip);
    }
    $count = count($ips);
    for ($i = 0; $i < $count; $i++) {
        if (!preg_match("/^(10|172\.16|192\.168)\./i", $ips[$i])) { //排除局域网ip
            $ip = $ips[$i];
            break;
        }
    }
    $tip = empty($_SERVER['REMOTE_ADDR']) ? $ip : $_SERVER['REMOTE_ADDR'];
    return $tip;
}

/*
 * 根据ip获得访客所在地地名 暂时精度不准 定位到省  采用马云粑粑的版本即可
 * @param string $ip 填写ip地址 不填则使用本类方法 默认空
 * @param string $type 返回类型 string 为字符 array 为数组 默认为string
 * @param int $n 返回字符长度 1为 市(区) 2为 省级 市(区) 3为 国家 省级 市(区) 默认为3
 */
function getRegion($dy_type = 1, $ip = '',$type="string",$n=3) {
    if($dy_type == 1){
        $login_area_info = getCurrentCity();
        $region = $login_area_info['region']." ".$login_area_info['city']." ".$login_area_info['county'];

        return $region;
    }else {
        if (empty($ip)) {
            $ip = getIp();
        }
        $ipadd = file_get_contents("http://int.dpool.sina.com.cn/iplookup/iplookup.php?ip=" . $ip); //根据新浪api接口获取
        if ($ipadd) {
            $charset = iconv("gbk", "utf-8", $ipadd);
            preg_match_all("/[\x{4e00}-\x{9fa5}]+/u", $charset, $ipadds);
            if ($type == 'string') {
                if ($n == 3) {
                    $region = $ipadds[0][0] . " " . $ipadds[0][1] . " " . $ipadds[0][2];
                } elseif ($n == 2) {
                    $region = $ipadds[0][1] . " " . $ipadds[0][2];
                } elseif ($n == 1) {
                    $region = $ipadds[0][2];
                }
            } elseif ($type == 'array') {
                $region = array(
                    'countries' => $ipadds[0][0],
                    'province' => $ipadds[0][1],
                    'city' => $ipadds[0][2]
                );
            }

            return $region;
        } else {
            return false;
        }
    }
}

//获取光慧的公共参数
function _ghdata(){
    $data['customer']      = model('Xdata')->get('live_AdminConfig:ghConfig')['customer'];
    $data['timestamp']     = time() * 1000;
    $str = md5( $data['customer'] . $data['timestamp'] . model('Xdata')->get('live_AdminConfig:ghConfig')['secretKey'] );
    $data['s'] = substr($str , 0 , 10 ) . substr($str ,-10 );
    $data['fee'] = 0;
    return $data;
}

/**
 * CC直播加密hash
 * 功能：将一个Map按照Key字母升序构成一个QueryString. 并且加入时间混淆的hash串
 * @param queryMap  query内容
 * @param time  加密时候，为当前时间；解密时，为从querystring得到的时间；
 * @param salt   加密salt
 * @return
 */
function  createHashedQueryString($queryMap,$time) {

    ksort($queryMap);

    $param = '';
    foreach($queryMap as $key => $value){
        $param .= $key.'='.$value.'&';
    }
    $param_code = trim($param,'&');

    if(!$time){
        $time = time();
    }
    $param = $param_code.'&time='.$time.'&salt='.model('Xdata')->get('live_AdminConfig:ccConfig')['api_key'];

    $param_arr[0] = strtoupper(md5($param));
    $param_arr[1] = $param_code;

    return $param_arr;
}

/**
 * CC点播加密hash
 * 功能：将一个Map按照Key字母升序构成一个QueryString. 并且加入时间混淆的hash串
 * @param queryMap  query内容
 * @param time  加密时候，为当前时间；解密时，为从querystring得到的时间；
 * @param salt   加密salt
 * @return
 */
function  createVideoHashedQueryString($queryMap) {

    ksort($queryMap);

    $param = '';
    foreach($queryMap as $key => $value){
        $param .= $key.'='.$value.'&';
    }
    $param_code = trim($param,'&');

    $param = $param_code.'&time='.time().'&salt='.model('Xdata')->get('classroom_AdminConfig:ccyun')['cc_apikey'];

    $param_arr[0] = md5($param);
    $param_arr[1] = $param_code;

    return $param_arr;
}

/****
 * 微吼直播加密sign
 * 本示例代码是webinar_start_url接口的sign计算。
 * 其他接口所签参数依据该接口专有参数确定。
 */
function createSignQueryString($params){
    $secret_key = model('Xdata')->get('live_AdminConfig:whConfig')['secretKey'];

    ksort($params);

    array_walk($params,function(&$value,$key){
        $value = $key . $value;
    });

    // 拼接,在首尾各加上$secret_key,计算MD5值
    $sign = md5($secret_key . implode('',$params) . $secret_key);

    return $sign;
}

//根据url读取文本
function getDataByUrl($url , $type = true){
    return json_decode(file_get_contents($url) , $type);
}

//根据url读取文本post
function getDataByPostUrl($url, $post = null)
{
    $context = array();

    if (is_array($post))
    {
        ksort($post);

        $context['http'] = array
        (
            'timeout'=>60,
            'method' => 'POST',
            'content' => http_build_query($post, '', '&'),
        );
    }

    return json_decode(file_get_contents($url, false, stream_context_create($context)));
}

/**
 * 将秒转换为时分秒
 * @param $seconds 要转换的参数（秒）
 * @return $seconds 标准时间格式（00:00:00）
 */
function secondsToHour($seconds,$type){
    if($type) {
        $leftsecond = intval($seconds);
        $day1 = floor ($leftsecond / (60 * 60 * 24));
        $hour = floor (($leftsecond - $day1 * 24 * 60 * 60) / 3600);
        $minute = floor (($leftsecond - $day1 * 24 * 60 * 60 - $hour * 3600) / 60);
        $second = floor ($leftsecond - $day1 * 24 * 60 * 60 - $hour * 3600 - $minute * 60);
        return $day1 . "天" . $hour . "时" . $minute . "分" . $second . "秒";
    }
    if(intval($seconds) < 60)
        $tt ="00:00:".sprintf("%02d",intval($seconds%60));
    if(intval($seconds) >=60){
        $h =sprintf("%02d",intval($seconds/60));
        $s =sprintf("%02d",intval($seconds%60));
        if($s == 60){
            $s = sprintf("%02d",0);
            ++$h;
        }
        $t = "00";
        if($h == 60){
            $h = sprintf("%02d",0);
            ++$t;
        }
        if($t){
            $t  = sprintf("%02d",$t);
        }
        $tt= $t.":".$h.":".$s;
    }
    if(intval($seconds)>=60*60){
        $t= sprintf("%02d",intval($seconds/3600));
        $h =sprintf("%02d",intval($seconds/60)-$t*60);
        $s =sprintf("%02d",intval($seconds%60));
        if($s == 60){
            $s = sprintf("%02d",0);
            ++$h;
        }
        if($h == 60){
            $h = sprintf("%02d",0);
            ++$t;
        }
        if($t){
            $t  = sprintf("%02d",$t);
        }
        $tt= $t.":".$h.":".$s;
    }
    return  $seconds>0?$tt:'00:00:00';

    if($type){
        $seconds_str = array_filter(explode(':',$tt));
        $seconds_str = "$seconds_str[0]小时$seconds_str[1]分$seconds_str[2]秒";
        return  $seconds>0?$seconds_str:'00小时00分00秒';
    }else{
        return  $seconds>0?$tt:'00:00:00';
    }
}

/**
 * 邮箱、手机账号、名字中间字符串以*隐藏
 * @param $str
 * @param int $name_type 默认为1 中文时：0 只保留字符串首尾字符，隐藏中间用*代替，1只保留姓
 * @param int $num
 * @return mixed|string 替换后的字符串
 */
function hideStar($str,$name_type = 1,$num = 0) {
    if (preg_match( "/^[a-z0-9]+([._\\-]*[a-z0-9])*@([a-z0-9]+[-a-z0-9]*[a-z0-9]+.){1,63}[a-z0-9]+$/", $str)) {
        $email_array = explode("@", $str);
        $prevfix = (strlen($email_array[0]) < 4) ? "" : substr($str, 0, 3); //邮箱前缀
        $count = 0;
        $str = preg_replace('/([\d\w+_-]{0,100})@/', '***@', $str, -1, $count);
        $rs = $prevfix . $str;
    } elseif(preg_match('/^(\+86)?1[34578]{1}\d{9}$/', $str)) {//手机
        $rs = preg_replace('/(1[34578]{1}[0-9])[0-9]{4}([0-9]{4})/i', '$1****$2', $str);
    } elseif(preg_match('/([\d]{4})([\d]{4})([\d]{4})([\d]{4})([\d]{0,})?/', $str)) {//银行卡
        $prefix = substr($str,0,4);
        $suffix = substr($str,-4,4);
        $rs = $prefix."************".$suffix;
    } else {//姓名
        if($name_type == 1){//只保留姓
            if ($num && mb_strlen($str, 'UTF-8') > $num) {
                return mb_substr($str, 0, 4) . '*';
            }

            if ($num && mb_strlen($str, 'UTF-8') <= $num) {
                return $str;
            }

            $doubleSurname = [
                '欧阳', '太史', '端木', '上官', '司马', '东方', '独孤', '南宫','万俟', '闻人', '夏侯', '诸葛', '尉迟', '公羊', '赫连',
                '澹台', '皇甫', '宗政', '濮阳','公冶', '太叔', '申屠', '公孙', '慕容', '仲孙', '钟离', '长孙', '宇文', '司徒', '鲜于',
                '司空', '闾丘', '子车', '亓官', '司寇', '巫马', '公西', '颛孙', '壤驷', '公良', '漆雕', '乐正','宰父', '谷梁', '拓跋',
                '夹谷', '轩辕', '令狐', '段干', '百里', '呼延', '东郭', '南门', '羊舌','微生', '公户', '公玉', '公仪', '梁丘', '公仲',
                '公上', '公门', '公山', '公坚', '左丘', '公伯','西门', '公祖', '第五', '公乘', '贯丘', '公皙', '南荣', '东里', '东宫',
                '仲长', '子书', '子桑','即墨', '达奚', '褚师', '吴铭'
            ];

            $surname = mb_substr($str, 0, 2,'UTF-8');
            if (in_array($surname, $doubleSurname)) {
                $rs = mb_substr($str, 0, 2,'UTF-8') . str_repeat('*', (mb_strlen($str, 'UTF-8') - 2));
            } else {
                $rs = mb_substr($str, 0, 1,'UTF-8') . str_repeat('*', (mb_strlen($str, 'UTF-8') - 1));
            }
        } else {//只保留字符串首尾字符，隐藏中间用*代替（两个字符时只显示第一个）
            $strlen     = mb_strlen($str, 'utf-8');
            $firstStr   = mb_substr($str, 0, 1, 'utf-8');
            $lastStr    = mb_substr($str, -1, 1, 'utf-8');
            $strlen == 2 ? $rs = $firstStr . str_repeat('*', mb_strlen($str, 'utf-8') - 1) : $rs = $firstStr . str_repeat("*", $strlen - 2) . $lastStr;
        }
    }
    return $rs;
}
/**
 * 分析筛选条件
 */
function parse_params_map($param_name = ''){
    $reg = '#([a-zA-Z]+)(\d+)#';
    $string = $_GET['squery'];
    preg_match_all($reg,$string,$data);
    $map = array();
    if($data[1]){
        foreach($data[1] as $k=>$v){
            $map[$v] = array_key_exists($v,$map) ? $map[$v].','.$data[2][$k] : $data[2][$k];
        }
    }
    return ($param_name != '' && isset($map[$param_name])) ?  $map[$param_name] : $map;
}

/**
 * 获取筛选条件的参数值
 * @param string $param 参数
 * @param boolean $replace 是否替换参数
 * @return string 筛选条件参数值
 */
function get_params_url($param = '',$replace = false,$change_value = '',$delete_params = array()){
    $url = $_GET['squery'];
    $ext_params = $_GET;
    unset($ext_params['app'],$ext_params['mod'],$ext_params['act'],$ext_params['squery']);
    if(!$url){
        $url = (stripos($param,'.html') !== false) ? $param : $param.'.html';
        return (stripos($url,'?') !== false) ?  $url.'&'.http_build_query($ext_params): $url.'?'.http_build_query($ext_params);
    }
    // 交换值不为空
    if($change_value != '') {

        // 检测参数是否存在
        preg_match('/([a-zA-Z]+)(\d+)/',$param,$parName);
        if(!check_in_params($parName[1])){
            $url .= $param;
            $url = (stripos($url,'.html') !== false) ? $url : $url.'.html';
            return (stripos($url,'?') !== false) ?  $url.'&'.http_build_query($ext_params): $url.'?'.http_build_query($ext_params);
        }
        // 参数存在时,替换值
        if(preg_match('/(^|\d)'.$param.'$|(^|\d)'.$param.'[a-zA-Z]/',$url)){
            // 与参数值一相等
            //$url = preg_replace('/(^|\d)'.$param.'$|(^|\d)'.$param.'([a-zA-Z])/','$1'.$change_value.'$2',$url);
            $url = preg_replace('/(?<![a-zA-Z])'.$param.'/',$change_value,$url,1);
        }elseif(preg_match('/(^|\d)'.$change_value.'$|(^|\d)'.$change_value.'[a-zA-Z]/',$url)){
            // 与参数值二相等
            //$url = preg_replace('/(^|\d)'.$change_value.'$|(^|\d)'.$change_value.'([a-zA-Z])/','$1'.$param.'$2',$url);
            $url = preg_replace('/(?<![a-zA-Z])'.$change_value.'/',$param,$url,1);
        }else{
            // 只是存在参数,值替换
            $url = preg_replace('/(?<![a-zA-Z])'.$parName[1].'\d+/',$param,$url,1);
        }

    }else{
        // 不存在交换值,只是执行替换
        if($replace){
            preg_match('/([a-zA-Z]+)(\d+)/',$param,$parName);
            $url = preg_replace('/(^|\d)'.$parName[1].'\d+/','$1'.$param,$url,1);
        }
        if(!empty($delete_params)){
            $par = implode('|',$delete_params);
            $url = preg_replace('/(^|\d)('.$par.')\d+/','$1',$url);
        }
        // 如果参数不存在,拼接参数
        !check_in_params($parName[1]) && $url .= $param;
    }

    $url = (stripos($url,'.html') !== false) ? $url : $url.'.html';
    return (stripos($url,'?') !== false) ?  $url.'&'.http_build_query($ext_params): $url.'?'.http_build_query($ext_params);
}

/**
 * 检测某个参数是否存在
 */
function check_in_params($param_name = '',$param_value = '',$get_value = false){
    $re = explode(',',parse_params_map($param_name));
    if($get_value === true) return $re;
    if($param_value !== ''){
        return in_array($param_value,$re);
    }
    return $re;
}

/**
 * 显示多级分类
 * $config
 *  type string course|live|exam|wenda
 *  params array ['a','b','c']
 */
function showCatetreeForHtml($data,$config,$id = 'id',$selected_id = 0){
    $level = 0;
    $checked = array_merge(array_keys($data),[$selected_id]);
    foreach($data as $pid =>$menu){
        // 顶级
        $html .= ($level == 0) ? '<ul class="select-list pb20">' : '<div class="subs"><ul class="sub-course">';
        $p = $config['params'][$level] ?: $config['params'][0];
        $selected = $selected_id == $pid ?  'class="selected"' : '';
        $html .= '<li '.$selected.'><a href="/'.$config['type'].'/'.get_params_url($p.$pid,true).'">全部</a></li>';

        is_array($menu) && $html .= call_user_func(function() use ($menu,$id,$config,$checked,$p){
            foreach($menu as $v){
                $active = in_array($v[$id],$checked) ? 'class="selected"' : '';
                $html .= '<li '.$active.'><a href="/'.$config['type'].'/'.get_params_url($p.$v[$id],true,'',array_diff($config['params'],array($p))).'">'.$v['title'].'</a></li>';
            }
            return $html.'</ul>';
        });
        count($config['params']) > 1 && array_shift($config['params']);
        $level++;
    }
    for($i = 1; $i < $level; $i++){
        $html .= '</div>';
    }
    return $html;

}

function dd() {
    array_map(function($x) { echo "<pre>";var_dump($x); }, func_get_args());
    die;
}
