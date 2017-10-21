<?php

/**
 * 订单管理
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php');

class AdminOrderAction extends AdministratorAction {

    //课程订单模型对象
    protected $order = null;
    //套餐订单模型对象
    protected $orderAlbum = null;
    //约课订单模型对象
    protected $orderCourse = null;
    /**
     * 初始化，配置页面标题；创建模型对象
     * @return void
     */
    public function _initialize() {
        parent::_initialize();
        $this->pageTab[] = array('title' => '课程订单', 'tabHash' => 'index', 'url' => U('classroom/AdminOrder/index'));
        $this->pageTab[] = array('title' => '套餐订单', 'tabHash' => 'album', 'url' => U('classroom/AdminOrder/album'));
        $this->pageTab[] = array('title' => '直播课堂订单', 'tabHash' => 'live', 'url' => U('classroom/AdminOrder/live'));
        $this->pageTab[] = array('title' => '线下课订单', 'tabHash' => 'meetingcourse', 'url' => U('classroom/AdminOrder/meetingcourse'));
        /*$this->pageTab[] = array('title' => '并发量订单', 'tabHash' => 'concurrent', 'url' => U('classroom/AdminOrder/concurrent'));*/
        $this->pageTitle['index'] = '课程订单 - 交易记录';
        $this->pageTitle['album'] = '套餐订单 - 交易记录';
        $this->pageTitle['live'] = '直播课堂订单 - 交易记录';
        $this->pageTitle['meetingcourse'] = '线下课订单 - 交易记录';
        /*$this->pageTitle['concurrent'] = '并发量订单 - 交易记录';*/
        //默认搜索提交地址
        $this->searchPostUrl = U(APP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME, array('tabHash' => ACTION_NAME));
        //实例化模型
        $this->orderAlbum = D('ZyOrderAlbum');
        $this->order = D('ZyOrderCourse');
    }
    /**
     * 课程订单列表
     */
    public function index() {
        //显示字段
        $this->pageKeyList = array(
            'id', 'uid','mhm_id','mhm_title', 'video_id', 'order_mhm_title' , 'old_price', 'discount',
            'discount_type', 'price', 'order_album_title','pay_status','learn_status','term','time_limit', 'ctime', 'DOACTION'
        );
        //页面按钮
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "删除", 'onclick' => "admin.delOrders('zy_order_course')");
        //搜索字段
        $this->searchKey = array('id', 'uid', 'video_id', 'order_album_id', 'mhm_id', 'startTime', 'endTime');
        $school = model('School')->getAllSchol('','id,title');
        $this->opt['mhm_id'] = array_merge(array('0'=>'请选择',$school));


        //where
        $map = array();
        $map['is_del'] = 0;
        if (!empty($_POST['id'])) {
            $map['id'] = $_POST['id'];
        } else {
            //根据用户查找
            if (!empty($_POST['uid'])) {
                $_POST['uid'] = t($_POST['uid']);
                $map['uid'] = array('in', $_POST['uid']);
            }
            //课程ID
            if (!empty($_POST['video_id'])) {
                $map['video_id'] = $_POST['video_id'];
            }
            //套餐订单ID
            if (!empty($_POST['order_album_id'])) {
                $map['order_album_id'] = $_POST['order_album_id'];
            }
            //机构ID
            if (!empty($_POST['mhm_id'])) {
                $map['mhm_id'] = $_POST['mhm_id'];
            }
            //开始时间
            if (!empty($_POST['startTime'])) {
                $map['ctime'][] = array('gt', strtotime($_POST['startTime']));
            }
            //结束时间
            if (!empty($_POST['endTime'])) {
                $map['ctime'][] = array('lt', strtotime($_POST['endTime']));
            }
        }
        $order = 'id desc';
        //取得数据列表
        $listData = M('zy_order_course')->where($map)->order($order)->findPage(20);
        //整理数据列表
        foreach ($listData['data'] as $key => $val) {
            $val = $this->formatData($val);
            if($val['order_album_id'] > 0){
                $val['term'] = "<span style='color: green;'>永久</span>";;
                $val['time_limit'] = "<span style='color: green;'>永久</span>";
            }else{
                $val['time_limit'] = date('Y-m-d H:i:s',$val['time_limit']);
            }
            $val['DOACTION'] = '<a href="' . U(APP_NAME . '/' . MODULE_NAME . '/viewOrder', array('id' => $val['id'],'type'=>'zy_order_course', 'tabHash' => 'viewOrder')) . '">查看详细</a>';
            $listData['data'][$key] = $val;
        }
        $this->displayList($listData);
    }

    /**
     * 套餐订单列表
     * @return void
     */
    public function album() {
        //显示字段
        $this->pageKeyList = array(
            'id', 'uid', 'mhm_title', 'album_id', 'album_title', 'old_price', 'discount',
            'discount_type', 'price',  'pay_status','learn_status', 'ctime', 'DOACTION'
        );
        //页面按钮
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "删除", 'onclick' => "admin.delOrders('zy_order_album')");
        //搜索字段
        $this->searchKey = array('id', 'uid', 'album_id', 'mhm_id', 'startTime', 'endTime');
        $school = model('School')->getAllSchol('','id,title');
        $this->opt['mhm_id'] = array_merge(array('0'=>'请选择',$school));


        //where
        $map = array();
        $map['is_del'] = 0;
        if (!empty($_POST['id'])) {
            $map['id'] = $_POST['id'];
        } else {
            //根据用户查找
            if (!empty($_POST['uid'])) {
                $_POST['uid'] = t($_POST['uid']);
                $map['uid'] = array('in', $_POST['uid']);
            }
            //套餐订单ID
            if (!empty($_POST['album_id'])) {
                $map['album_id'] = $_POST['album_id'];
            }
            //机构ID
            if (!empty($_POST['mhm_id'])) {
                $map['mhm_id'] = $_POST['mhm_id'];
            }
            //开始时间
            if (!empty($_POST['startTime'])) {
                $map['ctime'][] = array('gt', strtotime($_POST['startTime']));
            }
            //结束时间
            if (!empty($_POST['endTime'])) {
                $map['ctime'][] = array('lt', strtotime($_POST['endTime']));
            }
        }
        //取得数据列表
        $listData = M('zy_order_album')->where($map)->order('ctime DESC,id DESC')->findPage();
        //整理数据列表
        foreach ($listData['data'] as $key => $val) {
            $val = $this->formatData($val);
            $val['DOACTION'] = '<a href="' . U(APP_NAME . '/' . MODULE_NAME . '/viewOrder', array('id' => $val['id'],'type'=>'zy_order_album', 'tabHash' => 'viewOrder')) . '">查看详细</a>';
            $listData['data'][$key] = $val;
        }
        $this->displayList($listData);
    }

    /**
     * 套餐的课程订单列表
     * @return void
     */
    public function albumOrderList() {
        //显示字段
        $this->pageKeyList = array(
            'id', 'uid', 'muid', 'video_id', 'old_price', 'discount',
            'discount_type', 'price', 'album_title', 'percent',
            'user_num', 'master_num', 'learn_status', 'ctime'
        );

        $_GET['id'] = intval($_GET['id']);

        $this->pageTab[] = array('title' => '查看课程订单-套餐订单ID:' . $_GET['id'], 'tabHash' => 'albumOrderList', 'url' => U('classroom/AdminOrder/albumOrderList', array('id' => $_GET['id'])));
        //页面按钮
        $this->pageButton[] = array('title' => '&lt;&lt;&nbsp;返回来源页', 'onclick' => "admin.zyPageBack()");
        $this->pageTitle['albumOrderList'] = '套餐订单 - 查看课程订单';
        //取得套餐ID
        $albumId = $this->orderAlbum->getAlbumIdById($_GET['id']);
        $vl = D('Album')->getVideoId($albumId); //取得套餐的课程IDList
        $rows = $this->order->getAlbumOrderList($_GET['id'], $vl);
        foreach ($rows as $key => $val) {
            $val = $this->formatData($val);
            //$val['DOACTION'] = '<a href="' . U(APP_NAME . '/' . MODULE_NAME . '/viewOrder', array('id' => $val['id'], 'tabHash' => 'viewOrder')) . '">查看详细</a>';
            $rows[$key] = $val;
        }
        $data['count'] = intval(count($rows));
        $data['totalPages'] = 1;
        $data['totalRows'] = $data['count'];
        $data['nowPage'] = $data['nowPage'];
        $data['html'] = '';
        $data['data'] = $rows;
        $this->displayList($data);
    }

    /**
     * 直播课堂订单列表
     * @return void
     */
    public function live() {
        //显示字段
        $this->pageKeyList = array(
            'id', 'uid','mhm_id','mhm_title', 'live_id', 'live_title', 'old_price', 'discount',
            'discount_type', 'price', 'order_album_title', 'pay_status','learn_status', 'ctime', 'DOACTION'
        );
        //页面按钮
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "删除", 'onclick' => "admin.delOrders('zy_order_live')");
        //搜索字段
        $this->searchKey = array('id', 'uid', 'live_id', 'mhm_id', 'startTime', 'endTime');
        $school = model('School')->getAllSchol('','id,title');
        $this->opt['mhm_id'] = array_merge(array('0'=>'请选择',$school));


        //where
        $map = array();
        $map['is_del'] = 0;
        if (!empty($_POST['id'])) {
            $map['id'] = $_POST['id'];
        } else {
            //根据用户查找
            if (!empty($_POST['uid'])) {
                $_POST['uid'] = t($_POST['uid']);
                $map['uid'] = array('in', $_POST['uid']);
            }
            //直播间ID
            if (!empty($_POST['live_id'])) {
                $map['live_id'] = $_POST['live_id'];
            }
            //机构ID
            if (!empty($_POST['mhm_id'])) {
                $map['mhm_id'] = $_POST['mhm_id'];
            }
            //开始时间
            if (!empty($_POST['startTime'])) {
                $map['ctime'][] = array('gt', strtotime($_POST['startTime']));
            }
            //结束时间
            if (!empty($_POST['endTime'])) {
                $map['ctime'][] = array('lt', strtotime($_POST['endTime']));
            }
        }
        //取得数据列表
        $listData = M('zy_order_live')->where($map)->order('ctime DESC,id DESC')->findPage(20);
        //整理数据列表
        foreach ($listData['data'] as $key => $val) {
            $s_map['id']            = $val['mhm_id'];
            $val                    = $this->formatData($val);
            $val['DOACTION']        = '<a href="' . U(APP_NAME . '/' . MODULE_NAME . '/viewOrder', array('id' => $val['id'],'type'=>'zy_order_live', 'tabHash' => 'viewOrder')) . '">查看详细</a>';
            $listData['data'][$key] = $val;
        }
        $this->displayList($listData);
    }

    /**
     * 查看课程订单
     * @return void
     */
    public function viewOrder() {
        //不允许更改
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $url = U(APP_NAME . '/' . MODULE_NAME . '/index');
            $this->redirect($url);
            exit;
        }

        $id = intval($_GET['id']);
        $type = t($_GET['type']);

        if($type == 'zy_order_course'){
            $this->pageTab[] = array('title' => '查看课程订单-ID:' . $id, 'tabHash' => 'viewOrder', 'url' => U('classroom/AdminOrder/viewOrder', array('id' => $id,'type'=>$type)));
            //显示字段
            $this->pageKeyList = array(
                'id', 'ctime', 'uid','mhm_title', 'order_title', 'old_price', 'discount',
                'discount_type', 'price', 'pay_status', 'learn_status'
            );
            //点击按钮返回来源页面
            $this->submitAlias = '返 回';
            $this->onsubmit = 'admin.zyPageBack()';
            $this->pageTitle['viewOrder'] = '课程订单  - 查看详细';
            $this->savePostUrl = U(APP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME);

            $data = M('zy_order_course')->find($id);
        }else if($type == 'zy_order_live'){
            $this->pageTab[] = array('title' => '查看直播课堂订单-ID:' . $id, 'tabHash' => 'viewOrder', 'url' => U('classroom/AdminOrder/viewOrder', array('id' => $id,'type'=>$type)));
            //显示字段
            $this->pageKeyList = array(
                'id','ctime','uid','mhm_title', 'order_title', 'old_price', 'discount',
                'discount_type', 'price', 'pay_status','learn_status'
            );
            //点击按钮返回来源页面
            $this->submitAlias = '返 回';
            $this->onsubmit = 'admin.zyPageBack()';
            $this->pageTitle['viewOrder'] = '直播课堂订单  - 查看详细';
            $this->savePostUrl = U(APP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME);

            $data = M('zy_order_live')->find($id);
        }else if($type == 'zy_order_album'){
            $this->pageTab[] = array('title' => '查看套餐订单-ID:' . $id, 'tabHash' => 'viewOrder', 'url' => U('classroom/AdminOrder/viewOrder', array('id' => $id,'type'=>$type)));
            //显示字段
            $this->pageKeyList = array(
                'id','ctime','uid','mhm_title', 'order_title', 'old_price', 'discount',
                'discount_type', 'price', 'pay_status','learn_status'
            );
            //点击按钮返回来源页面
            $this->submitAlias = '返 回';
            $this->onsubmit = 'admin.zyPageBack()';
            $this->pageTitle['viewOrder'] = '套餐订单  - 查看详细';
            $this->savePostUrl = U(APP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME);

            $data = M('zy_order_album')->find($id);
        }else if($type == 'zy_order_teacher'){
            $this->pageTab[] = array('title' => '查看线下课订单-ID:' . $id, 'tabHash' => 'viewOrder', 'url' => U('classroom/AdminOrder/viewOrder', array('id' => $id,'type'=>$type)));
            //显示字段
            $this->pageKeyList = array('id', 'ctime', 'uid','mhm_title', 'order_title', 'price', 'pay_status', 'learn_status');
            //点击按钮返回来源页面
            $this->submitAlias = '返 回';
            $this->onsubmit = 'admin.zyPageBack()';
            $this->pageTitle['viewOrder'] = '线下课订单  - 查看详细';
            $this->savePostUrl = U(APP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME);

            $data = M('zy_order_teacher')->find($id);
        }

        if (!$data) $this->error('没有找到对应的订单记录');
        if($type == 'zy_order_teacher'){
            $data = $this->formatTeacherData($data);
        }else{
            $data = $this->formatData($data);
        }
        $this->displayConfig($data);
    }

    /**
     * 数据显示格式化
     * @param $val 一个结果集数组
     * @return array
     */
    protected function formatData($val) {
        //学习状态
        $learn_status = array('未开始', '学习中', '已完成');
        //折扣类型
        $discount_type = array('<span style="color:gray">无折扣</span>', '会员折扣', '限时优惠');
        //取得套餐订单的套餐ID
        if ($val['order_album_id'] > 0) {
            $url = U('classroom/Album/view', array('id' => $val['order_album_id']));
            $val['order_album_title'] = getAlbumNameForID($val['order_album_id']);
            $val['order_album_title'] = getQuickLink($url,$val['order_album_title'],"未知套餐");

        } else {
            $val['order_album_title'] = ACTION_NAME == 'albumOrderList' ? '<span style=color:gray>单独购买</span>' : '-';
        }
        $s_map['id'] = $val['mhm_id'];
        if($val['pay_status'] == 1){
            $val['pay_status'] = "<span style='color: red;'>未支付</span>";
        }else if($val['pay_status'] == 2){
            $val['pay_status'] = "<span style='color: #9c9c9c;'>已取消</span>";
        }else if($val['pay_status'] == 3){
            $val['pay_status'] = "<span style='color: green;'>已支付</span>";
        }else if($val['pay_status'] == 4){
            $val['pay_status'] = "<span style='color: darkmagenta;'>申请退款</span>";
        }else if($val['pay_status'] == 5){
            $val['pay_status'] = "<span style='color: green;'>退款成功</span>";
        }
        //课程所属机构信息
        $school = model('School')->getSchoolFindStrByMap($s_map,'title,doadmin');
        $val['mhm_title'] = getQuickLink(getDomain($school['doadmin']),$school['title'],"未知机构");

        //购买课程用户所属机构信息
        $o_map['id'] = $val['order_mhm_id'];
        $school = model('School')->getSchoolFindStrByMap($o_map,'title,doadmin');
        $val['order_mhm_title'] = getQuickLink(getDomain($school['doadmin']),$school['title'],"未知机构");

        //购买用户
        $val['uid'] = getUserSpace($val['uid'], null, '_blank');
        //课程学习状态
        $val['learn_status'] = $learn_status[$val['learn_status']];
        //取得课程名称
        if($val['video_id']){
            $url = U('classroom/Video/view', array('id' => $val['video_id']));
            $val['video_title'] = '<div style="width:300px;">' . getVideoNameForID($val['video_id']) . '</div>';
            $val['video_id'] = getQuickLink($url,$val['video_title'],"未知课程");
        }
        //取得直播课程名称
        if($val['live_id']){
            $url = U('live/Index/view', array('id' => $val['live_id']));
            $val['live_title'] = '<div style="width:300px;">' . getVideoNameForID($val['live_id']) . '</div>';
            $val['live_title'] = getQuickLink($url,$val['live_title'],"未知直播");
        }
        //取得套餐名称
        if($val['album_id']){
            $url = U('classroom/Album/view', array('id' => $val['album_id']));
            $val['album_title'] = '<div style="width:300px;">' . getAlbumNameForID($val['album_id']) . '</div>';
            $val['album_title'] = getQuickLink($url,$val['album_title'],"未知套餐");
        }

        if($val['video_id']){
            $val['order_title'] = $val['video_id'];
        }else if($val['live_title']){
            $val['order_title'] = $val['live_title'];
        }else if($val['album_title']){
            $val['order_title'] = $val['album_title'];
        }
        //价格和折扣
        $val['old_price'] = '<span style="text-decoration:line-through;">￥' . $val['old_price'] . '</span>';
        $val['price'] = '<span style="color:red">￥' . $val['price'] . '</span>';
        $val['discount_type'] = $discount_type[$val['discount_type']];
        if ($val['discount_type'] > 0) {
            $val['discount'] = $val['discount'] . '折';
        } else {
            $val['discount'] = '-';
        }
        //返佣分成
//        $val['percent'] = $val['percent'] ? $val['percent'] . '%' : '-';
//        $val['user_num'] = $val['user_num'] ? '￥' . $val['user_num'] : 0;
//        $val['master_num'] = $val['master_num'] ? '￥' . $val['master_num'] : 0;

        //购买时间
//        $val['ctime'] = ACTION_NAME == 'viewOrder' ? date('Y-m-d H:i:s') : friendlyDate($val['ctime']);
        $val['ctime'] = date('Y-m-d H:i:s',$val["ctime"]);

        return $val;
    }


    /**
     * 并发量订单列表
     */
    public function concurrent() {
        //显示字段
        $this->pageKeyList = array(
            'id', 'uid', 'mhm_id','school','connums','price', 'rel_id','pay_status','stime','etime','ctime','is_del'
        );
        //页面按钮
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "删除", 'onclick' => "admin.delOrders('zy_order_concurrent')");
        //搜索字段
        $this->searchKey = array( 'id', 'uid', 'mhm_id','connums','price', 'rel_id','pay_status',array('stime','stime2'),array('etime','etime2'),array('ctime','ctime2'));

        $this->opt['pay_status'] = array( '0' => '全部', '1' => '未支付','2' =>'已取消','3' =>'已支付','4' =>'申请退款','53' =>'退款成功','6' =>'申请退款驳回');
        $school = model('School')->getAllSchol('','id,title');
        $this->opt['mhm_id'] = array_merge(array('0'=>'请选择',$school));


        if( isset($_POST) ) {
            $map = array();
            $map['is_del'] = 0;
            if (!empty($_POST['id'])) {
                $map['id'] = $_POST['id'];
            } else {
                //根据用户查找
                if (!empty($_POST['uid'])) {
                    $_POST['uid'] = t($_POST['uid']);
                    $map['uid'] = array('in', $_POST['uid']);
                }

                //机构ID
                if (!empty($_POST['mhm_id'])) {
                    $mhm_id = $_POST['mhm_id'];
                    $uid = M('school') -> where('id ='.$mhm_id) ->getField('uid');
                    $map['uid'] = $uid;
                }

                //数量
                if (!empty($_POST['connums'])) {
                    $map['connums'] = $_POST['connums'];
                }

                //订单状态
                if (!empty($_POST['rel_id'])) {
                    $map['rel_id'] = $_POST['rel_id'];
                }


                //订单状态
                if (!empty($_POST['pay_status'])) {
                    $map['pay_status'] = $_POST['pay_status'];
                }

                //最终价格
                if (!empty($_POST['price'])) {
                    $map['price'] = $_POST['price'];
                }


                if (!empty ($_POST ['stime'] [0]) && !empty ($_POST ['stime'] [1])) { // 时间区间条件
                    $map ['stime'] = array('BETWEEN', array(strtotime($_POST ['stime'] [0]),
                        strtotime($_POST ['etime'] [1])));
                } else if (!empty ($_POST ['etime'] [0])) {// 时间大于条件
                    $map ['stime'] = array('GT', strtotime($_POST ['stime'] [0]));
                } elseif (!empty ($_POST ['listingtime'] [1])) {// 时间小于条件
                    $map ['stime'] = array('LT', strtotime($_POST ['stime'] [1]));
                }

                if (!empty ($_POST ['etime'] [0]) && !empty ($_POST ['etime'] [1])) { // 时间区间条件
                    $map ['etime'] = array('BETWEEN', array(strtotime($_POST ['etime'] [0]),
                        strtotime($_POST ['etime'] [1])));
                } else if (!empty ($_POST ['etime'] [0])) {// 时间大于条件
                    $map ['etime'] = array('GT', strtotime($_POST ['etime'] [0]));
                } elseif (!empty ($_POST ['listingtime'] [1])) {// 时间小于条件
                    $map ['etime'] = array('LT', strtotime($_POST ['etime'] [1]));
                }

                if (!empty ($_POST ['ctime'] [0]) && !empty ($_POST ['ctime'] [1])) { // 时间区间条件
                    $map ['ctime'] = array('BETWEEN', array(strtotime($_POST ['ctime'] [0]),
                        strtotime($_POST ['ctime'] [1])));
                } else if (!empty ($_POST ['ctime'] [0])) {// 时间大于条件
                    $map ['ctime'] = array('GT', strtotime($_POST ['ctime'] [0]));
                } elseif (!empty ($_POST ['listingtime'] [1])) {// 时间小于条件
                    $map ['ctime'] = array('LT', strtotime($_POST ['ctime'] [1]));
                }
            }
        }
//        $order = 'id desc';
        $map['is_del'] = 0;
        //取得数据列表
        $listData = M('zy_order_concurrent')->where($map)->order()->findPage(20);
        //整理数据列表
        foreach ($listData['data'] as $key => $val) {

            $listData['data'][$key]['uid']       = getUserSpace($val['uid'], null, '_blank');
            $listData['data'][$key]['mhm_id']       = M('school') -> where('uid ='.$val['uid']) ->getField('id');
//            $listData['data'][$key]['school']       = M('school') -> where('uid ='.$val['uid']) ->getField('title');
            $school = model('School')->getSchoolFindStrByMap(array('uid'=>$val['uid']),'title,doadmin');
            if(!$school['doadmin']){
                $url = U('school/School/index', array('id' => $val['mhm_id']));
            }else{
                $url = getDomain($school['doadmin']);
            }
            $listData['data'][$key]['school'] = getQuickLink($url,$school['title'],"未知机构");

            $listData['data'][$key]['stime'] = date('Y-m-d H:i:s',$val["stime"]);
            $listData['data'][$key]['etime'] = date('Y-m-d H:i:s',$val["etime"]);
            $listData['data'][$key]['ctime'] = date('Y-m-d H:i:s',$val["ctime"]);
            if($val['pay_status'] == 1){
                $listData['data'][$key]['pay_status'] = "<span style='color: red;'>未支付</span>";
            }else if($val['pay_status'] == 2){
                $listData['data'][$key]['pay_status'] = "<span style='color: #9c9c9c;'>已取消</span>";
            }else if($val['pay_status'] == 3){
                $listData['data'][$key]['pay_status'] = "<span style='color: green;'>已支付</span>";
            }else if($val['pay_status'] == 4){
                $listData['data'][$key]['pay_status'] = "<span style='color: darkmagenta;'>申请退款</span>";
            }else if($val['status'] == 5){
                $listData['data'][$key]['pay_status'] = "<span style='color: green;'>退款成功</span>";
            }else if($val['status'] == 6){
                $listData['data'][$key]['pay_status'] = "<span style='color: green;'>申请退款驳回</span>";
            }

            if($val['is_del'] == 0){
                $listData['data'][$key]['is_del'] = "<span style='color: green;'>未删除</span>";
            }else if($val['is_del'] == 1){
                $listData['data'][$key]['is_del'] = "<span style='color: red;'>删除</span>";
            }
        }
        $this->displayList($listData);
    }


    /**
     * 线下课管理
     */
    public function meetingcourse(){
        // 管理分页项目
        $this->pageKeyList = array('id', 'uid','mhm_title', 'video_title', 'teacher_name' , 'price', 'pay_status','learn_status', 'ctime', 'DOACTION');
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "删除", 'onclick' => "admin.delOrders('zy_order_teacher')");
        //搜索字段
        $this->searchKey = array( 'id','uid','video_id','teacher_name','mhm_id', 'startTime', 'endTime');
//        $this->opt['pay_status'] = array( '0' => '全部', '1' => '未支付','2' =>'已取消','3' =>'已支付');
        $school = model('School')->getAllSchol('','id,title');
        $school[0] = '请选择';
        $this->opt['mhm_id'] = $school;
        // 数据的格式化
        $order = 'id desc';
        $list = $this-> _getmeetList();
        $this->assign('pageTitle', '线下课管理');
        $this->_listpk = 'id';
        $this->allSelected = true;
        array_values($list);
        $this->displayList($list);
    }


    /***
     * @param $type
     * @param $limit
     * @param $order
     * @return mixed
     * 获取线下课列表
     */
    private function _getmeetList()
    {
        if (isset($_POST)) {
            $_POST['id'] && $map['id'] = intval(t($_POST['id']));
            $_POST['teacher_name'] && $map['tid'] = M("zy_teacher")->where("name like"."'%".$_POST["teacher_name"]."%'")->getField("id");
            $_POST['is_del'] && $map['is_del'] = intval(t($_POST['is_del']));
            $_POST ['uid'] && $map ['uid'] = array('in', t((string)$_POST ['uid']));
            $_POST['video_id'] && $map['video_id'] = intval(t($_POST['video_id']));
            $_POST['mhm_id'] && $map['mhm_id'] = intval(t($_POST['mhm_id']));
            //开始时间
            if (!empty($_POST['startTime'])) {
                $map['ctime'][] = array('gt', strtotime($_POST['startTime']));
            }
            //结束时间
            if (!empty($_POST['endTime'])) {
                $map['ctime'][] = array('lt', strtotime($_POST['endTime']));
            }
        }
        $map['is_del'] = 0;
        $list = M('zy_order_teacher')->where($map)->order("ctime DESC")->findPage(20);
        foreach ($list['data'] as $key => $val) {
            $list['data'][$key]['ctime'] = date('Y-m-d H:i:s', $val["ctime"]);
            $list['data'][$key]['uid'] = getUserSpace($val['uid'], null, '_blank');
            $list['data'][$key]['title'] = M('event')->where(array('id' => $val['row_id']))->getField('title');
            $list['data'][$key]['teacher_name'] = M('zy_teacher')->where('id='.$val['tid'])->getField('name');
            $list['data'][$key]['time_limit'] = date('Y-m-d H:i:s',$val['time_limit']);
            $course = M('zy_teacher_course')->where('course_id='.$val['video_id'])->field("course_name,course_price,mhm_id")->find();
            //课程所属机构信息
            $school = model('School')->getSchoolFindStrByMap(array('id'=>$course['mhm_id']),'title,doadmin');
            $list['data'][$key]['mhm_title'] = getQuickLink(getDomain($school['doadmin']),$school['title'],"未知机构");

            //课程信息
            $url = U('classroom/LineClass/view', array('id' => $val['video_id']));
            $list['data'][$key]['video_title'] = getQuickLink($url,$course['course_name'],"未知线下课程");

            //讲师信息
            $url = U('classroom/Teacher/view', array('id' => $val['tid']));
            $list['data'][$key]['teacher_name'] = getQuickLink($url,$list['data'][$key]['teacher_name'],"未知讲师");

            if($val['pay_status'] == 1){
                $list['data'][$key]['pay_status'] = "<span style='color: red;'>未支付</span>";
            }else if($val['pay_status'] == 2){
                $list['data'][$key]['pay_status'] = "<span style='color: #9c9c9c;'>已取消</span>";
            }else if($val['pay_status'] == 3){
                $list['data'][$key]['pay_status'] = "<span style='color: green;'>已支付</span>";
            }else if($val['pay_status'] == 4){
                $list['data'][$key]['pay_status'] = "<span style='color: darkmagenta;'>申请退款</span>";
            }else if($val['pay_status'] == 5){
                $list['data'][$key]['pay_status'] = "<span style='color: green;'>退款成功</span>";
            }else if($val['pay_status'] == 6){
                $list['data'][$key]['pay_status'] = "<span style='color: red;'>申请退款驳回</span>";
            }
            if ($val['learn_status'] == 1) {
                $list['data'][$key]['learn_status'] = "<span style='color: #3695ff;'>待完成</span>";
            }else if ($val['learn_status'] == 2) {
                $list['data'][$key]['learn_status'] = "<span style='color: green;'>已完成</span>";
            }else{
                $list['data'][$key]['learn_status'] = "<span style='color: #9c9c9c;'>未开始</span>";
            }
            /*if ($val['is_del'] == 1) {
                $list['data'][$key]['DOACTION'] = '<a href="javascript:admin.meetcourse(' . $val['id'] . ',\'cpmeeting\',\'显示\',\'约课\');">显示</a>';
            } else {
                $list['data'][$key]['DOACTION'] = '<a href="javascript:admin.meetcourse(' . $val['id'] . ',\'cpmeeting\',\'隐藏\',\'约课\');">隐藏</a>';
            }*/
            $list['data'][$key]['DOACTION'] = '<a href="' . U(APP_NAME . '/' . MODULE_NAME . '/viewOrder', array('id' => $val['id'],'type'=>'zy_order_teacher', 'tabHash' => 'viewOrder')) . '">查看详细</a>';

        }
        $this->assign('pageTitle', '线下课管理');
        $this->_listpk = 'id';
        $this->allSelected = true;
        return $list;
    }


    /**
     * 批量删除订单操作
     */
    public function delOrders(){
        $ids = implode(",",$_POST['ids']);
        $ids = trim(t($ids),",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
        $where = array(
            'id'=>array('in',$ids)
        );

        $type = t($_POST['type']);
        $data['is_del'] = 1;
        $res = M($type)->where($where)->save($data);
        if( $res !== false){
            $msg['data']   = '操作成功';
            $msg['status'] = 1;
            echo json_encode($msg);
        }else{
            $msg['data'] = "操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
    }


    /**
     * 数据显示格式化
     * @param $val 一个结果集数组
     * @return array
     */
    protected function formatTeacherData($val) {
        //学习状态
        $learn_status = array('未开始', '学习中', '已完成');
        if($val['pay_status'] == 1){
            $val['pay_status'] = "<span style='color: red;'>未支付</span>";
        }else if($val['pay_status'] == 2){
            $val['pay_status'] = "<span style='color: #9c9c9c;'>已取消</span>";
        }else if($val['pay_status'] == 3){
            $val['pay_status'] = "<span style='color: green;'>已支付</span>";
        }else if($val['pay_status'] == 4){
            $val['pay_status'] = "<span style='color: darkmagenta;'>申请退款</span>";
        }else if($val['pay_status'] == 5){
            $val['pay_status'] = "<span style='color: green;'>退款成功</span>";
        }else if($val['pay_status'] == 6){
            $val['pay_status'] = "<span style='color: red;'>申请退款驳回</span>";
        }
        //课程所属机构信息
        $s_map['id'] = $val['mhm_id'];
        $school = model('School')->getSchoolFindStrByMap($s_map,'title,doadmin');
        $val['mhm_title'] = getQuickLink(getDomain($school['doadmin']),$school['title'],"未知机构");
        //购买用户
        $val['uid'] = getUserSpace($val['uid'], null, '_blank');
        //课程学习状态
        $val['learn_status'] = $learn_status[$val['learn_status']];
        //取得课程名称
        $val['course_name'] = M('zy_teacher_course')->where('course_id='.$val['video_id'])->getField("course_name");
        $url = U('classroom/LineClass/view', array('id' => $val['video_id']));
        $val['order_title'] = getQuickLink($url,$val['course_name'],"未知课程");
        //价格和折扣
        $val['price'] = '<span style="color:red">￥' . $val['price'] . '</span>';
        //购买时间
        $val['ctime'] = date('Y-m-d H:i:s',$val["ctime"]);

        return $val;
    }















}
