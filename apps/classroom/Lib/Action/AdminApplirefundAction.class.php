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
    //班级订单模型对象
    protected $orderAlbum = null;
    //约课订单模型对象
    protected $orderCourse = null;
    /**
     * 初始化，配置页面标题；创建模型对象
     * @return void
     */
    public function _initialize() {
        parent::_initialize();
        $this->pageTab[] = array('title' => '点播订单', 'tabHash' => 'index', 'url' => U('classroom/AdminApplirefund/index'));
        $this->pageTab[] = array('title' => '班级订单', 'tabHash' => 'album', 'url' => U('classroom/AdminApplirefund/album'));
        $this->pageTab[] = array('title' => '直播课堂订单', 'tabHash' => 'live', 'url' => U('classroom/AdminApplirefund/live'));
//        $this->pageTab[] = array('title' => '约课订单', 'tabHash' => 'meetingcourse', 'url' => U('classroom/AdminApplirefund/meetingcourse'));
//        $this->pageTab[] = array('title' => '并发量订单', 'tabHash' => 'concurrent', 'url' => U('classroom/AdminApplirefund/concurrent'));
        $this->pageTitle['index'] = '点播订单 - 申请退款交易记录';
        $this->pageTitle['album'] = '班级订单 - 申请退款交易记录';
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
            //班级订单ID
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
            $val['time_limit'] = $val['term'] ? date('Y-m-d H:i:s',$val['time_limit']) : "-";
            $val['term'] ? : $val['term'] = "永久有效";
            $listData['data'][$key] = $val;
        }
        $this->displayList($listData);
    }

    /**
     * 班级订单列表
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
            //班级订单ID
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
            $listData['data'][$key]['album_title'] = getQuickLink($url,$listData['data'][$key]['album_title'],"未知班级");
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
     * 班级的课程订单列表
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

        $this->pageTab[] = array('title' => '查看课程订单-班级订单ID:' . $_GET['id'], 'tabHash' => 'albumOrderList', 'url' => U('classroom/AdminApplirefund/albumOrderList', array('id' => $_GET['id'])));
        //页面按钮
        $this->pageButton[] = array('title' => '&lt;&lt;&nbsp;返回来源页', 'onclick' => "admin.zyPageBack()");
        $this->pageTitle['albumOrderList'] = '班级订单 - 查看课程订单';
        //取得班级ID
        $albumId = $this->orderAlbum->getAlbumIdById($_GET['id']);
        $vl = D('Album')->getVideoId($albumId); //取得班级的课程IDList
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
            $type = "班级";
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
                $type = "点播";
                $refund_type = 0;
                $table = "zy_order_course";
                break;
            case 'zy_order_album':
                $type = "班级";
                $refund_type = 1;
                $table = "zy_order_album";
                break;
            case 'zy_order_live':
                $type = "直播课程";
                $refund_type = 2;
                $table = "zy_order_live";
                break;;
            default;
        }

        $this->pageTab[] = array('title' => '查看'.$type.'退款-ID:' . $_GET['id'], 'tabHash' => 'viewOrder', 'url' => U('classroom/AdminApplirefund/viewOrder', array('id' => $id,'type'=>$_GET['type'])));
        //显示字段
        $this->pageKeyList = array('id', 'refund_type','refund_reason','refund_note','voucher','refund_status','ctime','htime');

        $order_info = M($table)->where(['id'=>$id])->field('price,rel_id')->find();
        $pay_type = M('zy_recharge')->where(['pay_pass_num'=>$order_info['rel_id']])->getField('pay_type');
        $data = M('zy_order_refund')->where(['refund_type'=>$refund_type,'order_id'=>$id])->find();
        $refundConfig = model('Xdata')->get('admin_Config:refundConfig');

        $data['refund_type'] = $type;

        $refund_reason_arr = ['讲师不专业','课程不是想学习的',"{$refundConfig['refund_numday']}天无理由退款",'其他原因'];
        $data['refund_reason'] = $refund_reason_arr[$data['refund_reason']-1];

        $pay_arr = ['alipay'=>'支付宝支付','wxpay'=>'微信支付','app_wxpay'=>'微信app支付','unionpay'=>'银联支付','lcnpay'=>'余额支付'];
        $data['pay_type'] = $pay_arr[$pay_type];
        $refund_status_arr = ['0'=>'等待审核','1'=>'审核通过','2'=>'审核驳回'];
        $data['refund_status'] = $refund_status_arr[$data['refund_status']];

        $data['ctime'] = date('Y-m-d H:i:s',$data["ctime"]);
        $data['htime'] = $data["htime"] ? date('Y-m-d H:i:s',$data["htime"]) : "暂未处理";

        $data['voucher'] = array_filter(explode('|',$data['voucher']));
        $voucher = '';
        foreach($data['voucher'] as $key => $value){
            $voucher .= "<a href='".U('widget/Upload/down',['attach_id'=>$value])."'><img src=".getCover($value)." width='100px' height='100px'></a><br/><br/>";
        }
        $data['voucher'] = $voucher;

        //点击按钮返回来源页面
        $this->submitAlias = '返 回';
        $this->onsubmit = 'admin.zyPageBack()';
        $this->pageTitle['viewOrder'] = $type.'订单  - 查看详细';
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
        //取得班级订单的班级ID
        if ($val['order_album_id'] > 0) {
            $url = U('classroom/Album/view', array('id' => $val['order_album_id']));
            $albumId = $this->orderAlbum->getAlbumIdById($val['order_album_id']);
            $val['album_title'] = getAlbumNameForID($val['order_album_id']);
            $val['album_title'] = getQuickLink($url,$val['album_title'],"未知班级");
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
                $val['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.doThroughAudit(' . $val['id'] . ',\'course\')">通过</a> - ';
                $val['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.doOverruleAudit(' . $val['id'] . ',\'course\')">驳回</a>  ';
            }
            if($type ==  'live') {
                $val['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.doThroughAudit(' . $val['id'] . ',\'live\')">通过</a> - ';
                $val['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.doOverruleAudit(' . $val['id'] . ',\'live\')">驳回</a>  ';
            }
            if($type ==  'album') {
                $val['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.doThroughAudit(' . $val['id'] . ',\'album\')">通过</a> - ';
                $val['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.doOverruleAudit(' . $val['id'] . ',\'album\')">驳回</a>  ';
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
        $val['video_title'] = getVideoNameForID($val['video_id']);
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
     * 订单通过窗口
     */
    public function doThroughAudit(){
        $refundConfig = model('Xdata')->get('admin_Config:refundConfig');

        $id = $_GET['id'];
        $order_type = $_GET['type'];
        if ($order_type == 'course') {
            $data['refund_type'] = "0";
            $table = "zy_order_course";
        } elseif ($order_type == 'album') {
            $data['refund_type'] = "1";
            $table = "zy_order_album";
        } elseif ($order_type == 'live') {
            $data['refund_type'] = "2";
            $table = "zy_order_live";
        }
        $order_info = M($table)->where(['id'=>$id])->field('price,rel_id')->find();
        $pay_type = M('zy_recharge')->where(['pay_pass_num'=>$order_info['rel_id']])->getField('pay_type');

        if($pay_type == 'alipay'){
            $pay_type = '支付宝支付';
        }else if($pay_type == 'wxpay'){
            $pay_type = '微信支付';
        }else if($pay_type == 'wap_wxpay'){
            $pay_type = '微信app支付';
        }else if($pay_type == 'unionpay'){
            $pay_type = '银联支付';
        }else if($pay_type == 'lcnpay'){
            $pay_type = '余额支付';
        }

        $data = M('zy_order_refund')->where(['order_id'=>$id,'refund_type'=>$data['refund_type']]) -> find() ;
        $data['price'] = $order_info['price'];
        $data['voucher'] = array_filter(explode('|',$data['voucher']));

        $this -> assign($data);
        $this->assign('pay_type', $pay_type);
        $this -> assign('orderid',$id);
        $this -> assign('refundConfig',$refundConfig);
        $this -> assign('type',$order_type);
        $this ->display();
    }

    /****
     * 订单驳回窗口
     */
    public function doOverruleAudit()
    {
        $id = $_GET['id'];
        $type = $_GET['type'];
        $this -> assign('id',$id);
        $this -> assign('type',$type);
        $this -> display();
    }

    /****
     * 申请退款通过-订单
     */
    public function doRefundOrderThrough()
    {
        $id = $_POST['orderid'];
        $order_type = $_POST['type'];

        //根据类型判断订单相关信息
        if ($order_type == 'course') {
            $data['refund_type'] = "0";
            $table = "zy_order_course";
            $field = 'video_id';
            $note = "课程：";
        } elseif ($order_type == 'album') {
            $data['refund_type'] = "1";
            $table = "zy_order_album";
            $field = 'album_id';
            $note = "班级：";
        } elseif ($order_type == 'live') {
            $data['refund_type'] = "2";
            $table = "zy_order_live";
            $field = 'live_id';
            $note = "直播课程：";
        }
        $order_info =  M($table) ->where('id ='.$id) ->field('id,uid,rel_id,pay_status,'.$field)->find();
        $recharge_info = M('zy_recharge')->where(['pay_pass_num'=>$order_info['rel_id']])->find();

        if($recharge_info['pay_type'] == 'lcnpay') {
            $pay_order = $recharge_info['id'];
        }else{
            $pay_order = $recharge_info['pay_order'];
        }
        if (!$pay_order) {
            $this->mzError("查询退款订单记录失败");
        }
        if($order_info['pay_status'] == 5){
            $this -> mzError("订单已经退款");
        }

        if($recharge_info['pay_type'] == 'alipay'){
            //设置支付的Data信息
            $bizcontent  = array(
                "refund_amount" => "{$recharge_info['money']}",
                "trade_no"      => "{$recharge_info['pay_order']}",
                "out_trade_no"  => "{$recharge_info['pay_pass_num']}",
            );
            if(!$bizcontent['trade_no']){
                unset($bizcontent['trade_no']);
            }
            if(!$bizcontent['out_trade_no']){
                unset($bizcontent['out_trade_no']);
            }
            $result = model('AliPay')->aliPayArouse($bizcontent,'refund');

            $responseNode = str_replace(".", "_", $result[0]->getApiMethodName()) . "_response";
            $resultCode = $result[1]->$responseNode->code;
            $htime = strtotime($result[1]->$responseNode->gmt_refund_pay);
            if(!empty($resultCode)&&$resultCode == 10000){
                $refund_status = true;
            }else{
                $refund_status = false;
            }
        }elseif($recharge_info['pay_type'] == 'wxpay' || $recharge_info['pay_type'] == 'app_wxpay'){
            $this->mzError("暂不支持微信在线退款");
            $htime = time();
            //设置支付的Data信息
            $refund = [
                'refund_amount' => $recharge_info['money'],
                "transaction_id"=> "{$recharge_info['pay_order']}",
                "out_trade_no"  => "{$recharge_info['pay_pass_num']}",
                "out_refund_no" => $htime,
            ];
            if(!$refund['transaction_id']){
                unset($refund['out_trade_no']);
            }
            if(!$refund['out_trade_no']){
                unset($refund['transaction_id']);
            }
            if($recharge_info['pay_type'] == 'app_wxpay'){
                $from = 'jsapi';
            }
            $response = model('WxPay')->wxRefund($refund,$from);
            dump($response);exit;
        }elseif($recharge_info['pay_type'] == 'lcnpay'){
            //添加余额并加相关流水
            $learnc = D('ZyLearnc')->recharge($recharge_info['uid'],$recharge_info['money']);

            if($learnc){
                if($table == 'zy_order_course' || $table == 'zy_order_live'){
                    $note_title = M('zy_video')->where(array('id' => $order_info[$field]))->getField('video_title');
                }else{
                    $note_title = M('album')->where(array('id' => $order_info[$field]))->getField('album_title');
                }
                D('ZyLearnc')->addFlow($recharge_info['uid'],1,$recharge_info['money'],$note.$note_title.'退款成功,退款余额：'.$recharge_info['money'],$order_info['id'],$table);

                $refund_status = true;
            }else{
                $refund_status = false;
            }
            $htime = time();
        }

        if($refund_status){
            M($table) ->where('id ='.$id) ->save(['pay_status'=>5]);
            M('zy_order_refund')->where(['order_id'=>$id]) -> save(['refund_status'=>1,'htime'=>$htime]) ;

            $map['uid'] = intval($order_info['uid']);//购买用户ID
            $map['vid']  = intval($order_info[$field]);
            $map['status'] = 1;

            //添加多条流水记录 并给扣除用户分成 通知购买用户
            D('ZySplit')->addVideoFlows($map, 0, $table);

            if ($order_type == 'course') {
                $info = "课程";
                $video_info = M('zy_video')->where(array('id' => $order_info[$field]))->getField('video_title');
            } elseif ($order_type == 'album') {
                $info = "班级";
                $video_info = M('album')->where(array('id' => $order_info[$field]))->getField('album_title');

                //操作班级下的课程、直播
                $video_ids      = trim(D("Album",'classroom')->getVideoId($order_info[$field]), ',');
                $v_map['id']        = array('in', array($video_ids));
                $v_map["is_del"]    = 0;
                $album_info         = M("zy_video")->where($v_map)->field("id,uid,video_title,mhm_id,teacher_id,
                                          v_price,t_price,discount,vip_level,endtime,starttime,limit_discount,type")
                    ->select();

                $video_pay_id = '';
                $live_pay_id = '';
                foreach ($album_info as $key => $video) {
                    if($video['type'] == 1) {
                        $video_pay_status = D("ZyOrderCourse",'classroom')->where(array('uid'=>$order_info['uid'], 'video_id'=>$video['id'],'order_album_id'=>$order_info[$field]))->field('id,pay_status')->find();
                        if($video_pay_status['pay_status'] == 3 || $video_pay_status['pay_status'] == 6){
                            $video_pay_id .= $video_pay_status['id'].',';
                        }
                    }
                    if($video['type'] == 2) {
                        $live_pay_status = D("ZyOrderLive",'classroom')->where(array('uid'=>$order_info['uid'], 'live_id'=>$video['id'],'order_album_id'=>$order_info[$field]))->field('id,pay_status')->find();
                        if($live_pay_status['pay_status'] == 3 || $live_pay_status['pay_status'] == 6){
                            $live_pay_id .= $live_pay_status['id'].',';
                        }
                    }
                }
                D("ZyOrderCourse",'classroom')->where(['id'=>['in',trim($video_pay_id,',')]])->save(['pay_status'=>5]);
                D("ZyOrderLive",'classroom')->where(['id'=>['in',trim($live_pay_id,',')]])->save(['pay_status'=>5]);
            } elseif ($order_type == 'live') {
                $info = "直播课程";
                $video_info = M('zy_video')->where(array('id' => $order_info[$field]))->getField('video_title');
            }
            $s['uid']= $order_info['uid'];
            $s['title'] = "{$info}：{$video_info} 退款成功";
            $s['body'] = "您购买的{$info}：{$video_info} 退款成功。届时，您将无法继续学习该{$info}，欢迎您再次购买";
            $s['ctime'] = time();
            model('Notify')->sendMessage($s);

            //积分操作
            if($order_type == 'course')
            {
                $credit = M('credit_setting')->where(array('id'=>41,'is_open'=>1))->field('id,name,score,count')->find();
                if($credit['score'] < 0){
                    $otype = 7;
                    $note = '课程退款扣除的积分';
                    $uid = M('zy_order_course') ->where('id ='.$id) ->getField('uid');
                }
            }
            if($order_type == 'album')
            {
                $credit = M('credit_setting')->where(array('id'=>43,'is_open'=>1))->field('id,name,score,count')->find();
                if($credit['score'] < 0){
                    $otype = 7;
                    $note = '班级退款扣除的积分';
                    $uid = M('zy_order_album') ->where('id ='.$id) ->getField('uid');
                }
            }
            if($order_type == 'live')
            {
                $credit = M('credit_setting')->where(array('id'=>42,'is_open'=>1))->field('id,name,score,count')->find();
                if($credit['score'] < 0){
                    $otype = 7;
                    $note = '直播退款扣除的积分';
                    $uid = M('zy_order_live') ->where('id ='.$id) ->getField('uid');
                }
            }

            model('Credit')->addUserCreditRule($uid,$otype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);

            $this -> mzSuccess("退款成功");
        } else {
            $this -> mzError("退款失败");
        }
    }

    /****
     * 申请退款驳回-订单
     */
    public function doRefundOrderOverrule() {

        $id = $_POST['id'];
        $data['reject_info'] = $_POST['reason'];
        $data['pay_status'] = 6;
        $order_type = t($_POST['type']);

        if ($order_type == 'course') {
            $refund_type = "0";
            $table       = "zy_order_course";
        } elseif ($order_type == 'album') {
            $refund_type = "1";
            $table       = "zy_order_album";
        } elseif ($order_type == 'live') {
            $refund_type = "2";
            $table       = "zy_order_live";
        }
        $res =  M($table) ->where('id ='.$id) ->save($data);

        if($res){
            M('zy_order_refund')->where(['order_id'=>$id,'refund_type'=>$refund_type])->save(['refund_status'=>2]);
            $this -> success('驳回成功');
        }else{
            $this -> error('驳回成功');
        }
    }

    public function test (){
        $vid = 1;
        $this_mid = 1127;
        //添加多条流水记录 并给分成用户加钱 通知购买用户
        $album = D("Album",'classroom')->getAlbumOneInfoById($vid,'id,price,mhm_id,album_title');
        $video_ids      = trim(D("Album",'classroom')->getVideoId($vid), ',');
        $v_map['id']        = array('in', array($video_ids));
        $v_map["is_del"]    = 0;
        $album_info         = M("zy_video")->where($v_map)->field("id,uid,video_title,mhm_id,teacher_id,
                                          v_price,t_price,discount,vip_level,endtime,starttime,limit_discount,type")
            ->select();

        $insert_live_value = "";
        $insert_course_value = "";
        $time = time();
        $pay_data =['pay_status'=>3,'order_album_id'=>$vid,'rel_id'=>$data['rel_id'],'ptime'=>$time];
        foreach ($album_info as $key => $video) {
            //如果已经购买 则销毁，已有订单则改为支付
            if($video['type'] == 1) {
                $video_pay_status = D("ZyOrderCourse",'classroom')->where(array('uid'=>$this_mid, 'video_id'=>$video['id']))->field('id,pay_status')->find();
                if($video_pay_status['pay_status'] == 3 || $video_pay_status['pay_status'] == 6){
                    unset($video);
                }elseif($video_pay_status['pay_status'] == 1 || $video_pay_status['pay_status'] == 5){
                    D("ZyOrderCourse",'classroom')->where(array('uid'=>$this_mid, 'id'=>$video_pay_status['id']))->save($pay_data);
                    unset($video);
                }
            }
            if($video['type'] == 2) {
                $video_pay_status = D("ZyOrderLive",'classroom')->where(array('uid'=>$this_mid, 'live_id'=>$video['id']))->field('id,pay_status')->find();
                if($video_pay_status['pay_status'] == 3 || $video_pay_status['pay_status'] == 6){
                    unset($video);
                }elseif($video_pay_status['pay_status'] == 1){
                    D("ZyOrderLive",'classroom')->where(array('uid'=>$this_mid, 'id'=>$video_pay_status['id']))->save($pay_data);
                    unset($video);
                }
            }

            $album_info[$key] = $video;
        }
            dump($album_info);
        $album_info = array_filter($album_info);
        $order_mhm_id = model('User')->where('uid='.$this_mid)->getField('mhm_id');
        foreach ($album_info as $key => $video) {
            if($video['type'] == 2){
                $insert_live_value .= "('" . $this_mid . "','" . $video['id'] . "','" . $video['t_price'] . "','0.00','0','" . $video['t_price'] . "','" . $vid . "','0','3','". time()."','" .$album['mhm_id']."',". time() . ",'0','".$data['rel_id']."','" .$order_mhm_id."'),";
            }else{
                $insert_course_value .= "('" . $this_mid . "','" . $video['uid'] . "','" . $video['id'] . "','" . $video['v_price'] . "','" . ($video['price']['discount'] / 10) . "','" . $video['price']['dis_type'] . "','" . $video['price']['price'] . "','" . $vid . "','0','3','". time()."','" .$album['mhm_id']."',". time() . ",'0','".$data['rel_id']."','".$order_mhm_id."'),";
            }
        }
        if($insert_live_value){
            $live_order_sql = "INSERT INTO " . C("DB_PREFIX") . "zy_order_live (`uid`,`live_id`,`old_price`,`discount`,`discount_type`,`price`,`order_album_id`,`learn_status`,`pay_status`,`ptime`,`mhm_id`,`ctime`,`is_del`,`rel_id`,`order_mhm_id`) VALUE " . trim($insert_live_value, ',');
            M('zy_order_live')->execute($live_order_sql)? true : false;
        }
        if($insert_course_value){
            $course_order_sql = "INSERT INTO " . C("DB_PREFIX") . "zy_order_course (`uid`,`muid`,`video_id`,`old_price`,`discount`,`discount_type`,`price`,`order_album_id`,`learn_status`,`pay_status`,`ptime`,`mhm_id`,`ctime`,`is_del`,`rel_id`,`order_mhm_id`) VALUE " . trim($insert_course_value, ',');
            M('zy_order_course')->execute($course_order_sql)? true : false;
        }
    }
}
