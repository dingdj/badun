<?php

/**
 * 订单管理
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php');

class AdminExpendOrderAction extends AdministratorAction {

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
        $this->pageTab[] = array('title' => '课程订单', 'tabHash' => 'index', 'url' => U('school/AdminExpendOrder/index'));
        $this->pageTab[] = array('title' => '直播课堂订单', 'tabHash' => 'live', 'url' => U('school/AdminExpendOrder/live'));
        $this->pageTab[] = array('title' => '班级订单', 'tabHash' => 'album', 'url' => U('school/AdminExpendOrder/album'));
        $this->pageTitle['index'] = '课程订单 - 交易记录';
        $this->pageTitle['live'] = '直播订单 - 交易记录';
        $this->pageTitle['album'] = '班级订单 - 交易记录';
        //默认搜索提交地址
        $this->searchPostUrl = U(APP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME, array('tabHash' => ACTION_NAME));
        //实例化模型
        $this->orderAlbum = D('ZyOrderAlbum','classroom');;
        $this->order = D('ZyOrder','classroom');
        $this->orderCourse = D('ZyOrderCourse','classroom');
    }
    /**
     * 课程订单列表
     */
    public function index() {
        //显示字段
        if(is_admin($this->mid)){
            $this->pageKeyList = array(
                'id', 'uid','mhm_title', 'video_id', 'old_price', 'discount',
                'discount_type', 'price', 'order_album_title','pay_status','learn_status','term','time_limit', 'ctime', 'DOACTION'
            );
        }else{
            $this->pageKeyList = array(
                'id', 'uid', 'video_id', 'old_price', 'discount',
                'discount_type', 'price','rel_id', 'pay_status','learn_status','term','time_limit', 'ctime', 'DOACTION'
            );
        }
        //页面按钮
        $this->pageButton[] = array('title' => '搜索记录', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "删除记录", 'onclick' => "admin.delOrders('zy_order_course')");
        //搜索字段
        $this->searchKey = array('id', 'uid' , 'video_id', 'startTime', 'endTime');

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
        if(!is_admin($this->mid)){
            $order_mhm_id = $this->school_id;
            if($this->mid == 1){
                $map['mhm_id'] = " (`mhm_id` = {$order_mhm_id} ) or (`mhm_id` = '0') ";
            }else{
                $map['order_mhm_id'] = $order_mhm_id;
            }
        }
        $listData = M('zy_order_course')->where($map)->order('ctime DESC,id DESC')->findPage(20);
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
     * 直播课堂订单列表
     */
    public function live() {
        //显示字段
        if(is_admin($this->mid)){
            $this->pageKeyList = array(
                'id', 'uid','mhm_id','mhm_title', 'live_id', 'live_title', 'old_price', 'discount',
                'discount_type', 'price', 'pay_status','learn_status', 'ctime', 'DOACTION'
            );
        }else{
            $this->pageKeyList = array(
                'id', 'uid', 'live_id', 'live_title', 'old_price', 'discount',
                'discount_type', 'price','rel_id', 'pay_status','learn_status', 'ctime', 'DOACTION'
            );
        }
        //页面按钮
        $this->pageButton[] = array('title' => '搜索记录', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "删除记录", 'onclick' => "admin.delOrders('zy_order_live')");
        //搜索字段
        $this->searchKey = array('id', 'uid',  'live_id', 'startTime', 'endTime');

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
        if(!is_admin($this->mid)){
            $map['order_mhm_id'] = $this->school_id;
        }
        $listData = M('zy_order_live')->where($map)->order('ctime DESC,id DESC')->findPage(20);
        //整理数据列表
        foreach ($listData['data'] as $key => $val) {
            $s_map['id']            = $val['mhm_id'];
            $val                    = $this->formatData($val);
            $val['DOACTION']        = '<a href="' . U(APP_NAME . '/' . MODULE_NAME . '/viewOrder', array('id' => $val['id'],'type'=>'zy_order_live', 'tabHash' => 'viewOrder')) . '">查看详细</a>';
            $val['live_title']     = M('zy_video')->where(array('id' => $val['live_id']))->getField('video_title');
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
        /*$this->pageKeyList = array(
            'id', 'uid', 'cuid', 'album_id', 'price', 'ctime'
        );*/
        if(is_admin($this->mid)){
            $this->pageKeyList = array(
                'id', 'uid', 'mhm_title', 'album_id', 'album_title', 'old_price', 'discount',
                'discount_type', 'price', 'pay_status','learn_status', 'ctime', 'DOACTION'
            );
        }else{
            $this->pageKeyList = array(
                'id', 'uid', 'album_id', 'album_title', 'old_price', 'discount',
                'discount_type', 'price','rel_id', 'pay_status','learn_status', 'ctime', 'DOACTION'
            );
        }

        //页面按钮
        $this->pageButton[] = array('title' => '搜索记录', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "删除记录", 'onclick' => "admin.delOrders('zy_order_album')");
        //搜索字段
        $this->searchKey = array('id', 'uid', 'album_id', 'startTime', 'endTime');

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
            //班级ID
            if (!empty($_POST['album_id'])) {
                $map['album_id'] = $_POST['album_id'];
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

        //查询数据列表
        if(!is_admin($this->mid)){
            $map['order_mhm_id'] = $this->school_id;
        }
        $listData = $this->orderAlbum->where($map)->order('ctime DESC,id DESC')->findPage();
        //整理数据列表
        foreach ($listData['data'] as $key => $val) {
            $val = $this->formatData($val);
            $val['DOACTION'] = '<a href="' . U(APP_NAME . '/' . MODULE_NAME . '/viewOrder', array('id' => $val['id'],'type'=>'zy_order_album', 'tabHash' => 'viewOrder')) . '">查看详细</a>';
            $listData['data'][$key] = $val;
        }
        $this->displayList($listData);
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
        //取得班级订单的班级ID
        if ($val['order_album_id'] > 0) {
            $val['order_album_title'] = getAlbumNameForID($val['order_album_id']);
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
        }else if($val['status'] == 5){
            $val['pay_status'] = "<span style='color: green;'>退款成功</span>";
        }
        //课程所属机构信息
        $school = model('School')->getSchoolFindStrByMap($s_map,'title,doadmin');
        if($school){
            if(!$school['doadmin']){
                $url = U('school/School/index', array('id' => $val['mhm_id']));
            }else{
                $url = getDomain($school['doadmin']);
            }
            $val['mhm_title'] = getQuickLink($url,$school['title'],"平台所有");
        }else{
            $val['mhm_title'] = "<span style='color: red;'>平台所有</span>";
        }

        //购买用户
        $val['uid'] = getUserSpace($val['uid'], null, '_blank');
        //课程学习状态
        $val['learn_status'] = $learn_status[$val['learn_status']];
        //取得课程名称
        if($val['video_id']){
            $val['video_id'] = '<div style="width:300px;">' . getVideoNameForID($val['video_id']) . '</div>';
        }
        //取得直播课程名称
        if($val['live_id']){
            $val['live_title'] = '<div style="width:300px;">' . getVideoNameForID($val['live_id']) . '</div>';
        }
        //取得班级名称
        if($val['album_id']){
            $val['album_title'] = '<div style="width:300px;">' . getAlbumNameForID($val['album_id']) . '</div>';
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
        /*$val['percent'] = $val['percent'] ? $val['percent'] . '%' : '-';
        $val['user_num'] = $val['user_num'] ? '￥' . $val['user_num'] : 0;
        $val['master_num'] = $val['master_num'] ? '￥' . $val['master_num'] : 0;*/

        //购买时间
        //$val['ctime'] = ACTION_NAME == 'viewOrder' ? date('Y-m-d H:i:s') : friendlyDate($val['ctime']);
        $val['ctime'] = date('Y-m-d H:i:s',$val["ctime"]);

        return $val;
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
            $this->pageTab[] = array('title' => '查看班级订单-ID:' . $id, 'tabHash' => 'viewOrder', 'url' => U('classroom/AdminOrder/viewOrder', array('id' => $id,'type'=>$type)));
            //显示字段
            $this->pageKeyList = array(
                'id','ctime','uid','mhm_title', 'order_title', 'old_price', 'discount',
                'discount_type', 'price', 'pay_status','learn_status'
            );
            //点击按钮返回来源页面
            $this->submitAlias = '返 回';
            $this->onsubmit = 'admin.zyPageBack()';
            $this->pageTitle['viewOrder'] = '班级订单  - 查看详细';
            $this->savePostUrl = U(APP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME);

            $data = M('zy_order_album')->find($id);
        }
        if (!$data)
            $this->error('没有找到对应的订单记录');

        $data = $this->formatData($data);
        $this->displayConfig($data);
    }
}
