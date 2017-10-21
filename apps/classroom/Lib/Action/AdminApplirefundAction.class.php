<?php

/**
 * 订单管理
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php');

class AdminApplirefundAction extends AdministratorAction {

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
        $this->pageTab[] = array('title' => '课程订单', 'tabHash' => 'index', 'url' => U('classroom/AdminApplirefund/index'));
        $this->pageTab[] = array('title' => '套餐订单', 'tabHash' => 'album', 'url' => U('classroom/AdminApplirefund/album'));
        $this->pageTab[] = array('title' => '直播课堂订单', 'tabHash' => 'live', 'url' => U('classroom/AdminApplirefund/live'));
//        $this->pageTab[] = array('title' => '约课订单', 'tabHash' => 'meetingcourse', 'url' => U('classroom/AdminApplirefund/meetingcourse'));
//        $this->pageTab[] = array('title' => '并发量订单', 'tabHash' => 'concurrent', 'url' => U('classroom/AdminApplirefund/concurrent'));
        $this->pageTitle['index'] = '课程订单 - 申请退款交易记录';
        $this->pageTitle['album'] = '套餐订单 - 申请退款交易记录';
        $this->pageTitle['live'] = '直播课堂订单 - 申请退款交易记录';
        $this->pageTitle['meetingcourse'] = '约课订单 - 交易记录';
        $this->pageTitle['concurrent'] = '并发量订单 - 交易记录';
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
            'discount_type', 'price', 'album_title','pay_status','learn_status','term','time_limit', 'ctime', 'DOACTION'
        );
        //页面按钮
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "删除", 'onclick' => "admin.delOrders('zy_order_course')");
        //搜索字段
        $this->searchKey = array('id', 'uid', 'video_id', 'order_album_id', 'mhm_id', 'startTime', 'endTime');
        $school = model('School')->getAllSchol('','id,title');
        $this->opt['mhm_id'] = $school;

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
        $map['pay_status'] = array('in',array(4,5,6));

        $order = 'id desc';
        //取得数据列表
        $listData = M('zy_order_course')->where($map)->order($order)->findPage(20);
        //整理数据列表
        foreach ($listData['data'] as $key => $val) {

            $val['DOACTION'] = '<a href="' . U(APP_NAME . '/' . MODULE_NAME . '/viewOrder', array('id' => $val['id'],'type'=>'zy_order_course', 'tabHash' => 'viewOrder')) . '">查看详细</a>';
            $type = "course";
            $val = $this->formatData($val,$type);
            $val['time_limit'] = date('Y-m-d H:i:s',$val['time_limit']);
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
        $this->opt['mhm_id'] = $school;

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
        $map['pay_status'] = array('in',array(4,5,6));
        //取得数据列表
        $listData = M('zy_order_album')->where($map)->order('ctime DESC,id DESC')->findPage();
        //整理数据列表
        foreach ($listData['data'] as $key => $val) {
            $s_map['id']            = $val['mhm_id'];;
            $listData['data'][$key]['ctime'] = date('Y-m-d H:i:s',$val["ctime"]);
            $val['DOACTION']        = '<a href="' . U(APP_NAME . '/' . MODULE_NAME . '/viewOrder', array('id' => $val['id'],'type'=>'zy_order_album', 'tabHash' => 'viewOrder')) . '">查看详细</a>';
            $type ="album";
            $val                    = $this->formatData($val,$type);

            $listData['data'][$key] = $val;
            $listData['data'][$key]['album_title'] = getAlbumNameForID($val['album_id']);
            $url = U('classroom/Album/view', array('id' => $val['album_id']));
            $listData['data'][$key]['album_title'] = getQuickLink($url,$listData['data'][$key]['album_title'],"未知套餐");
        }
        $this->displayList($listData);
    }

    /**
     * 直播课堂订单列表
     * @return void
     */
    public function live() {
        //显示字段
        $this->pageKeyList = array(
            'id', 'uid','mhm_id','mhm_title', 'live_id', 'live_title', 'old_price', 'discount',
            'discount_type', 'price', 'pay_status','learn_status', 'ctime', 'DOACTION'
        );
        //页面按钮
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "删除", 'onclick' => "admin.delOrders('zy_order_live')");
        //搜索字段
        $this->searchKey = array('id', 'uid', 'live_id', 'mhm_id', 'startTime', 'endTime');
        $school = model('School')->getAllSchol('','id,title');
        $this->opt['mhm_id'] = $school;

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
        $map['pay_status'] = array('in',array(4,5,6));
        //取得数据列表
        $listData = M('zy_order_live')->where($map)->order('ctime DESC,id DESC')->findPage(20);

        //整理数据列表
        foreach ($listData['data'] as $key => $val) {
            $s_map['id']            = $val['mhm_id'];;
            $listData['data'][$key]['ctime'] = date('Y-m-d H:i:s',$val["ctime"]);
            $val['DOACTION']        = '<a href="' . U(APP_NAME . '/' . MODULE_NAME . '/viewOrder', array('id' => $val['id'],'type'=>'zy_order_live', 'tabHash' => 'viewOrder')) . '">查看详细</a>';
            $type ="live";
            $val                    = $this->formatData($val,$type);

            $listData['data'][$key] = $val;
            $listData['data'][$key]['live_title']     = M('zy_video')->where(array('id' => $val['live_id']))->getField('video_title');
            $url = U('live/Index/view', array('id' => $val['live_id']));
            $listData['data'][$key]['live_title'] = getQuickLink($url,$listData['data'][$key]['live_title'],"未知直播");
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

        $this->pageTab[] = array('title' => '查看课程订单-套餐订单ID:' . $_GET['id'], 'tabHash' => 'albumOrderList', 'url' => U('classroom/AdminApplirefund/albumOrderList', array('id' => $_GET['id'])));
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
     * 查看课程订单
     * @return void
     */
    /*public function viewOrder() {
        //不允许更改
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $url = U(APP_NAME . '/' . MODULE_NAME . '/index');
            $this->redirect($url);
            exit;
        }


        $_GET['id'] = intval($_GET['id']);


        if($_GET['type']  == 'zy_order_course')
        {
            $data = M('zy_order_course')->find($_GET['id']);
            $type = "课程";

        }
        if($_GET['type']  == 'zy_order_album')
        {
            $data = M('zy_order_album')->find($_GET['id']);
            $type = "套餐";
            $data['album_title'] = getAlbumNameForID($data['album_id']);

        }

        if($_GET['type']  == 'zy_order_live')
        {
            $data = M('zy_order_live')->find($_GET['id']);
            $data['video_id'] = M('zy_video')->where('id='.$data['live_id']) ->getField('video_title');
            $type = "直播";
        }
        if (!$data) {
            $this->error('没有找到对应的订单记录');
        }



        $this->pageTab[] = array('title' => '查看'.$type.'订单-ID:' . $_GET['id'], 'tabHash' => 'viewOrder', 'url' => U('classroom/AdminApplirefund/viewOrder', array('id' => $_GET['id'])));
        //显示字段
        $this->pageKeyList = array(
            'id', 'ctime', 'uid', 'muid', 'video_id', 'old_price', 'discount',
            'discount_type', 'price', 'album_title', 'learn_status'
        );
        //点击按钮返回来源页面
        $this->submitAlias = '返 回';
        $this->onsubmit = 'admin.zyPageBack()';
        $this->pageTitle['viewOrder'] = '课程订单  - 查看详细';
        $this->savePostUrl = U(APP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME);


        $this->displayConfig($data);
    }*/
    public function viewOrder() {
        //不允许更改
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $url = U(APP_NAME . '/' . MODULE_NAME . '/index');
            $this->redirect($url);
            exit;
        }


        $id = intval($_GET['id']);
        switch($_GET['type']){
            case 'zy_order_course':
                $type = "课程";
                break;
            case 'zy_order_album':
                $type = "套餐";
                break;
            case 'zy_order_live':
                $type = "直播";
                break;;
            default;
        }

        $this->pageTab[] = array('title' => '查看'.$type.'退款-ID:' . $_GET['id'], 'tabHash' => 'viewOrder', 'url' => U('classroom/AdminApplirefund/viewOrder', array('id' => $id,'type'=>$_GET['type'])));
        //显示字段
        $this->pageKeyList = array('id', 'ordertype', 'refundtype', 'refundaccount', 'refundRemarks', 'banner', 'refundReason', 'ctime');
        $data = M('zy_order_refund')->where('order_id='.$id)->find();
        $data['ordertype'] = $type;

        if($data['refundtype'] == 1){
            $data['refundtype'] = '支付宝';
        }else if($data['refundtype'] == 2){
            $data['refundtype'] = '微信';
        }else if($data['refundtype'] == 3){
            $data['refundtype'] = '银联';
        }
        $data['ctime'] = date('Y-m-d H:i:s',$data["ctime"]);
        //点击按钮返回来源页面
        $this->submitAlias = '返 回';
        $this->onsubmit = 'admin.zyPageBack()';
        $this->pageTitle['viewOrder'] = '课程订单  - 查看详细';
        $this->savePostUrl = U(APP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME);


        $this->displayConfig($data);
    }

    /**
     * 数据显示格式化
     * @param $val 一个结果集数组
     * @return array
     */
    protected function formatData($val,$type) {
        //学习状态
        $learn_status = array('未开始', '学习中', '已完成');
        //折扣类型
        $discount_type = array('<span style="color:gray">无折扣</span>', '会员折扣', '限时优惠');
        //取得套餐订单的套餐ID
        if ($val['order_album_id'] > 0) {
            $url = U('classroom/Album/view', array('id' => $val['order_album_id']));
            $albumId = $this->orderAlbum->getAlbumIdById($val['order_album_id']);
            $val['album_title'] = getAlbumNameForID($val['order_album_id']);
            $val['album_title'] = getQuickLink($url,$val['album_title'],"未知套餐");
        } else {
            $val['album_title'] = ACTION_NAME == 'albumOrderList' ? '<span style=color:gray>单独购买</span>' : '-';
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

            if($type ==  'course') {
                $val['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.adoptorderverify(' . $val['id'] . ',\'course\')">通过</a> - ';
                $val['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.rejectorderBox(' . $val['id'] . ',\'course\')">驳回</a>  ';
            }
            if($type ==  'live') {
                $val['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.adoptorderverify(' . $val['id'] . ',\'live\')">通过</a> - ';
                $val['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.rejectorderBox(' . $val['id'] . ',\'live\')">驳回</a>  ';
            }
            if($type ==  'album') {
                $val['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.adoptorderverify(' . $val['id'] . ',\'album\')">通过</a> - ';
                $val['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.rejectorderBox(' . $val['id'] . ',\'album\')">驳回</a>  ';
            }

                 }else if($val['pay_status'] == 5){
            $val['pay_status'] = "<span style='color: green;'>退款成功</span>";
        } else if($val['pay_status'] == 6){
        $val['pay_status'] = "<span style='color: red;'>申请退款驳回</span>";
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
        $url = U('classroom/Video/view', array('id' => $val['video_id']));
        $val['video_title'] = '<div style="width:300px;">' . getVideoNameForID($val['video_id']) . '</div>';
        $val['video_id'] = getQuickLink($url,$val['video_title'],"未知课程");

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
        $this->pageButton[] = array('title' => '搜索记录', 'onclick' => "admin.fold('search_form')");
        //搜索字段
        $this->searchKey = array( 'id', 'uid','connums','price', 'rel_id','pay_status',array('stime','stime2'),array('etime','etime2'),array('ctime','ctime2'));


        if( isset($_POST) ) {
            $map = array();
            if (!empty($_POST['id'])) {
                $map['id'] = $_POST['id'];
            } else {
                //根据用户查找
                if (!empty($_POST['uid'])) {
                    $_POST['uid'] = t($_POST['uid']);
                    $map['uid'] = array('in', $_POST['uid']);
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
            $listData['data'][$key]['school']       = M('school') -> where('uid ='.$val['uid']) ->getField('title');
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
     * 约课管理
     */
    public function meetingcourse(){
        // 管理分页项目

        $this->pageKeyList = array( 'id','uid','teacher_name','rel_id','pay_status', 'type','words','ctime');
        $this->pageButton[] = array('title' => '搜索约课', 'onclick' => "admin.fold('search_form')");
        //搜索字段
        $this->searchKey = array( 'id','uid','teacher_name','rel_id','pay_status', 'type','words',array('ctime','ctime1'));
        $this->opt['pay_status'] = array( '0' => '全部', '1' => '未支付','2' =>'已取消','3' =>'已支付');
        $this->opt['type'] = array( '0' => '全部', '1' => '在线试听','2' =>'线下试听');
        // 数据的格式化
        $order = 'id desc';
        $list = $this-> _getmeetList();
        $this->assign('pageTitle', '约课管理');
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
     * 获取约课列表
     */
    private function _getmeetList()
    {
        if (isset($_POST)) {
            $_POST['id'] && $map['id'] = intval(t($_POST['id']));
            $map['tid'] = M("zy_teacher")->where("name like"."'%".$_POST["teacher_name"]."%'")->getField("id");
            $_POST['rel_id'] && $map['rel_id'] = intval(t($_POST['rel_id']));
            $_POST['is_del'] && $map['is_del'] = intval(t($_POST['is_del']));
            $_POST['words'] && $map['words'] = array('like', '%' . t($_POST['words']) . '%');
            $_POST ['uid'] && $map ['uid'] = array('in', t((string)$_POST ['uid']));
            if (!empty ($_POST ['ctime'] [0]) && !empty ($_POST ['ctime'] [1])) { // 时间区间条件
                $map ['ctime'] = array('BETWEEN', array(strtotime($_POST ['ctime'] [0]),
                    strtotime($_POST ['ctime'] [1])));
            } else if (!empty ($_POST ['ctime'] [0])) {// 时间大于条件
                $map ['ctime'] = array('GT', strtotime($_POST ['ctime'] [0]));
            } elseif (!empty ($_POST ['ctime'] [1])) {// 时间小于条件
                $map ['ctime'] = array('LT', strtotime($_POST ['ctime'] [1]));
            }
            $type = intval($_POST['type']);
            if (!empty($type)) {
                $map['type'] = $type;
            }
            $status = intval($_POST['pay_status']);
            if (!empty($status)) {
                $map['pay_status'] = $status;
            }
        }
        $list = M('zy_order_teacher')->where($map)->order("ctime DESC")->findPage(20);
        foreach ($list['data'] as $key => $val) {
            $list['data'][$key]['ctime'] = date('Y-m-d H:i:s', $val["ctime"]);
            $list['data'][$key]['content'] = $val['content'];
            $list['data'][$key]['uid'] = getUserSpace($val['uid'], null, '_blank');
            $list['data'][$key]['title'] = M('event')->where(array('id' => $val['row_id']))->getField('title');
            $list['data'][$key]['teacher_name'] = M('zy_teacher')->where('id='.$val['tid'])->getField('name');
            if ($val['type'] == 1) {
                $list['data'][$key]['type'] = "在线试听";
            }
            if ($val['type'] == 2) {
                $list['data'][$key]['type'] = "线下试听";
            }
            if ($val['pay_status'] == 1) {
                $list['data'][$key]['pay_status'] = "未支付";
            }
            if ($val['pay_status'] == 2) {
                $list['data'][$key]['pay_status'] = "已取消";
            }
            if ($val['pay_status'] == 3) {
                $list['data'][$key]['pay_status'] = "已支付";
            }
            if ($val['is_del'] == 1) {
                $list['data'][$key]['DOACTION'] = '<a href="javascript:admin.meetcourse(' . $val['id'] . ',\'cpmeeting\',\'显示\',\'约课\');">显示</a>';
            } else {
                $list['data'][$key]['DOACTION'] = '<a href="javascript:admin.meetcourse(' . $val['id'] . ',\'cpmeeting\',\'隐藏\',\'约课\');">隐藏</a>';
            }

        }
        $this->assign('pageTitle', '约课管理');
        $this->_listpk = 'id';
        $this->allSelected = true;
        return $list;
    }


}
