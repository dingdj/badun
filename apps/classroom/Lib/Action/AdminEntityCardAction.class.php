<?php
/**
 * 线下实体卡管理
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminEntityCardAction extends AdministratorAction{

    /**
     * 初始化，配置内容标题
     * @return void
     */
    public function _initialize()
    {
        parent::_initialize();
    }

/**
     * 初始化专题配置
     * 
     * @return void
     */
    private function _initTabSpecial() {
        // Tab选项
        $this->pageTab [] = array (
                'title' => '优惠券',
                'tabHash' => 'index',
                'url' => U ( 'classroom/AdminEntityCard/index' )
        );
        $this->pageTab [] = array (
            'title' => '打折卡',
            'tabHash' => 'discount',
            'url' => U ( 'classroom/AdminEntityCard/discount' )
        );
        $this->pageTab [] = array (
            'title' => '课程卡',
            'tabHash' => 'course',
            'url' => U ( 'classroom/AdminEntityCard/course' )
        );
        $this->pageTab [] = array (
            'title' => '充值卡',
            'tabHash' => 'recharge',
            'url' => U ( 'classroom/AdminEntityCard/recharge' )
        );
    }
    /**
     * 优惠券列表
     */
    public function index(){
        $this->_initTabSpecial();
        $this->assign('pageTitle','优惠券');
        //页面配置
        $id     =  intval($_POST['id']);
        $sid    =  intval($_POST['sid']);
        $code   =  intval($_POST['code']);
        $this->pageKeyList = array('id','school_title','code','maxprice','price','exp_date','status','end_time','ctime','is_del','DOACTION');
        $this->searchKey = array('id','sid','code');
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[] = array('title'=>'禁用','onclick'=>"admin.delCouponAll('delCoupon')");
        $this->pageButton[] = array("title"=>"添加","onclick"=>"admin.addCoupon('addCoupon')");

        $school = model('School')->getAllSchol('','id,title');
        $this->opt['video_type'] = array('1'=>'点播','2'=>'直播','3'=>'班级');
        $this->opt['sid'] = $school;
        $map = array('type'=>1,'coupon_type'=>1);
        if(!empty($id))$map['id']=$id;
        if(!empty($sid))$map['sid']=$sid;
        if(!empty($code))$map['code']=$code;
        $explod['map'] = $map;
        $explod['title'] = "优惠券数据列表";
        $this->pageButton[] = array('title' => '导出', 'onclick' => "admin.exportCoupon('".urlencode(sunjiami(json_encode($explod),"hll"))."')");

        $coupon = model('Coupon');
        //数据列表
        $listData = $coupon->where($map)->order('ctime DESC,id DESC')->findPage();
        $time = time();
        foreach($listData['data'] as $key=>$val){
            if($val['is_del'] == 1) {
                $val['status'] = 3;
            }
            if($val['status'] != 3 && $val['end_time'] < $time){
                $val['status'] = 0;
				$coupon->setStatus($val['id'],0);
            }
            $school = model('School')->where(array('id'=>$val['sid']))->field('id,doadmin,title')->find();
            if(!$school['doadmin']){
                $url = U('school/School/index', array('id' => $val['mhm_id']));
            }else{
                $url = getDomain($school['doadmin']);
            }
            $val['school_title'] = getQuickLink($url,$school['title'],"未知机构");

            $val['ctime']    = date("Y-m-d H:i:s", $val['ctime']);
			$val['end_time'] = date("Y-m-d H:i:s", $val['end_time']);
			
            if($val['status'] == 3){
                $val['status'] = "<span style='color: grey;'>已作废</span>";
            }else if($val['status'] == 1){
                $val['status'] = "<span style='color: green;'>未使用</span>";
            }else if($val['status'] == 2){
                $val['status'] = "<span style='color: blue;'>已使用</span>";
            }else if($val['status'] == 0){
                $val['status'] = "<span style='color: red;'>已过期</span>";
            }

            if($val['is_del'] == 1) {
               $val['DOACTION'] ='<a href="javascript:admin.mzCouponCardEdit('.$val['id'].',\'closeCoupon\',\'启用\',\'优惠券\');">启用</a>';
            }else {
               $val['DOACTION'] ='<a href="javascript:admin.mzCouponCardEdit('.$val['id'].',\'closeCoupon\',\'禁用\',\'优惠券\');">禁用</a>';
            }

            if($val['is_del'] == 0){
                $val['is_del'] = "<span style='color: green;'>正常</span>";
            }else if($val['is_del'] == 1){
                $val['is_del'] = "<span style='color: red;'>禁用</span>";
            }
            $val['DOACTION'].=" | <a href=".U('classroom/AdminEntityCard/addCoupon',array('id'=>$val['id'],'tabHash'=>'revise')).";>修改</a>";
            $listData['data'][$key] = $val;
        }
        $this->displayList($listData);
    }

    /**
     * 添加优惠券
     * Enter description here ...
     */
    public function addCoupon(){
        $id   = intval($_GET['id']);
        
        $this->_initTabSpecial();
        $_REQUEST['tabHash'] = 'addCoupon';
        $this->pageKeyList = array ( 'sid','maxprice','price','exp_date','end_time','counts');
        $this->notEmpty = array ( 'sid','maxprice','price','exp_date','end_time', 'counts');
		//$schoolData = model ( 'School' )->findAll();
		//$this->opt['sid'] = array_column($schoolData,'title','id');
        $school = model('School')->getAllSchol('','id,title');
        $this->opt['sid'] = $school;
        if($id){
            $this->savePostUrl = U ( 'classroom/AdminEntityCard/doAddCoupon','type=save&id='.$id);
            $coupon = model('Coupon')->where( 'id=' .$id )->find ();
			$coupon['end_time'] = date('Y-m-d H:i:s',$coupon['end_time']);
            $this->assign('pageTitle','修改');
            //说明是编辑
            $this->displayConfig($coupon); 
        }else{
			$this->assign('pageTitle','添加');
            $this->savePostUrl = U ('classroom/AdminEntityCard/doAddCoupon','type=add');
            //说明是添加
            $this->displayConfig();
        }
    }

    /**
     * 处理添加优惠券
     */
    public function doAddCoupon(){
        $id		=	intval($_GET['id']);
        $type	= 	t($_GET['type']);
        $count  = 	intval($_POST['counts']);
        if($count == 0){
            $count = 1;
        }
		
        //要添加的数据
        //数据验证
        if(empty($_POST['sid'])){$this->error("请选择机构");}
        //if(empty($_POST['maxprice'])){$this->error("优惠条件不能为空");}
        if($_POST['maxprice'] == ''){$this->error("优惠条件不能为空");}
        if(!is_numeric($_POST['price'])){$this->error('优惠条件必须为数字');}
        if($_POST['price'] == ''){$this->error("价格不能为空");}
        if(!is_numeric($_POST['price'])){$this->error('价格必须为数字');}
        if($_POST['price'] >= $_POST['maxprice']){$this->error("立减价格必须小于优惠条件");}
        if($_POST['exp_date'] == ''){$this->error("有效期不能为空");}
        if(!is_numeric($_POST['exp_date'])){$this->error('有效期必须为数字');}
		if($_POST['end_time'] == ''){$this->error("终止时间不能为空");}
        if(!is_numeric($count)) $this->error('生成数量必须为数字');
        $params = array(
			'type'=> 1,
			'sid'=>intval($_POST['sid']),
			'maxprice'=>t($_POST['maxprice']),
			'price'=>t($_POST['price']),
			'exp_date'=>intval($_POST['exp_date']),
			'end_time'=>strtotime($_POST['end_time']),
            'coupon_type'=>1,
            'ctime'=>time(),
        );
        if($type == 'add'){
			$num = 0;
			$couponModel = model('Coupon');
            for ($i = 0; $i < $count; $i++) {
				$params['code'] = $this->create_code();
				$params['ctime'] = time();
				$couponModel->add($params);
				$num++;
            }
            if(!$num)$this->error("对不起，添加失败！");
            $this->success("成功添加".$num."个优惠券！");
        }else if($type=='save' && $id){
            $res=model('Coupon')->where("id=$id")->save($params);
            if(!$res)$this->error("对不起，修改失败！");
            $this->success("修改优惠券成功!");
        }
    }

    /**
     * 打折卡列表
     */
    public function discount(){
        $this->_initTabSpecial();
        $this->assign('pageTitle','打折卡');
        //页面配置
        $id     =  intval($_POST['id']);
        $sid    =  intval($_POST['sid']);
        $code   =  intval($_POST['code']);
        $this->pageKeyList = array('id','school_title','code','discount','exp_date','status','end_time','ctime','is_del','DOACTION');
        $this->searchKey = array('id','sid','code');
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[] = array('title'=>'禁用','onclick'=>"admin.delCouponAll('delcoupon')");
        $this->pageButton[] = array("title"=>"添加","onclick"=>"admin.addCoupon('addDiscount')");

        $school = model('School')->getAllSchol('','id,title');
        $this->opt['sid'] = $school;
        $map = array('type'=>2,'coupon_type'=>1);
        if(!empty($id))$map['id']=$id;
        if(!empty($sid))$map['sid']=$sid;
        if(!empty($code))$map['code']=$code;
        $explod['map'] = $map;
        $explod['title'] = "打折卡数据列表";
        $this->pageButton[] = array('title' => '导出', 'onclick' => "admin.exportCoupon('".urlencode(sunjiami(json_encode($explod),"hll"))."')");

        $coupon = model('Coupon');
        //数据列表
        $listData = $coupon->where($map)->order('ctime DESC,id DESC')->findPage();
        $time = time();
        foreach($listData['data'] as $key=>$val){
            if($val['is_del'] == 1) {
                $val['status'] = 3;
            }
            if($val['status'] != 3 && $val['end_time'] < $time){
                $val['status'] = 0;
                $coupon->setStatus($val['id'],0);
            }
            $school = model('School')->where(array('id'=>$val['sid']))->field('id,doadmin,title')->find();
            if(!$school['doadmin']){
                $url = U('school/School/index', array('id' => $val['mhm_id']));
            }else{
                $url = getDomain($school['doadmin']);
            }
            $val['school_title'] = getQuickLink($url,$school['title'],"未知机构");

            $val['ctime']    = date("Y-m-d H:i:s", $val['ctime']);
            $val['end_time'] = date("Y-m-d H:i:s", $val['end_time']);
            if($val['status'] == 3){
                $val['status'] = "<span style='color: grey;'>已作废</span>";
            }else if($val['status'] == 1){
                $val['status'] = "<span style='color: green;'>未使用</span>";
            }else if($val['status'] == 2){
                $val['status'] = "<span style='color: blue;'>已使用</span>";
            }else if($val['status'] == 0){
                $val['status'] = "<span style='color: red;'>已过期</span>";
            }

            if($val['is_del'] == 1) {
                $val['DOACTION'] ='<a href="javascript:admin.mzCouponCardEdit('.$val['id'].',\'closeCoupon\',\'启用\',\'打折卡\');">启用</a>';
            }else {
                $val['DOACTION'] ='<a href="javascript:admin.mzCouponCardEdit('.$val['id'].',\'closeCoupon\',\'禁用\',\'打折卡\');">禁用</a>';
            }

            if($val['is_del'] == 0){
                $val['is_del'] = "<span style='color: green;'>正常</span>";
            }else if($val['is_del'] == 1){
                $val['is_del'] = "<span style='color: red;'>禁用</span>";
            }
            $val['DOACTION'].=" | <a href=".U('classroom/AdminEntityCard/addDiscount',array('id'=>$val['id'],'tabHash'=>'revise')).";>修改</a>";;
            $listData['data'][$key] = $val;
        }

        $this->displayList($listData);
    }

    /**
     * 添加打折卡
     * Enter description here ...
     */
    public function addDiscount(){
        $id   = intval($_GET['id']);

        $this->_initTabSpecial();
        $this->pageKeyList = array ('sid','discount','exp_date','end_time','counts');
        $this->notEmpty = array ( 'sid','discount','exp_date','end_time','counts');
        //$schoolData = model ( 'School' )->findAll();
        //$this->opt['sid'] = array_column($schoolData,'title','id');
        $school = model('School')->getAllSchol('','id,title');
        $this->opt['sid'] = $school;
        if($id){
            $this->savePostUrl = U ( 'classroom/AdminEntityCard/doAddDiscount','type=save&id='.$id);
            $discount = model('Coupon')->where( 'id=' .$id )->find ();
            $discount['end_time'] = date('Y-m-d H:i:s',$discount['end_time']);
            $this->assign('pageTitle','修改');
            //说明是编辑
            $this->displayConfig($discount);
        }else{
            $this->savePostUrl = U ('classroom/AdminEntityCard/doAddDiscount','type=add');
            $this->assign('pageTitle','添加');
            //说明是添加
            $this->displayConfig();
        }

    }

    /**
     * 处理添加打折卡
     * Enter description here ...
     */
    public function doAddDiscount(){
        $id=intval($_GET['id']);
        $type= t($_GET['type']);
        $count = intval($_POST['counts']);
        if($count == 0){
            $count = 1;
        }
        //要添加的数据
        //数据验证
        if(empty($_POST['sid'])){$this->error("请选择机构");}
        if($_POST['discount'] == ''){$this->error("折扣不能为空");}
        if($_POST['discount'] == 0){$this->error('折扣数不能为0');}
        $reg = '/^(([\d])(\.\d{1,2}|\.{0}))$/';
        if(!preg_match($reg,$_POST['discount'])){
            $this->error('折扣数必须小于10');
        }
        if($_POST['exp_date'] == ''){$this->error("有效期不能为空");}
        if(!is_numeric($_POST['exp_date'])){$this->error('有效期必须为数字');}
        if($_POST['end_time'] == ''){$this->error("终止时间不能为空");}
        if(!is_numeric($count)){$this->error('生成数量必须为数字');}

        $params = array(
            'type'=> 2,
            'sid'=>intval($_POST['sid']),
            'discount'=>number_format($_POST['discount'], 2, '.', ''),
            'exp_date'=>intval($_POST['exp_date']),
            'end_time'=>strtotime($_POST['end_time']),
            'coupon_type'=>1,
            'ctime'=>time(),
        );
        if($type == 'add'){
            $num = 0;
            $couponModel = model('Coupon');
            for ($i = 0; $i < $count; $i++) {
                $params['code'] = $this->create_code();
                $params['ctime'] = time();
                $couponModel->add($params);
                $num++;
            }
            if(!$num)$this->error("对不起，添加失败！");
            $this->success("成功添加".$num."个打折卡！");
        }else if($type=='save' && $id){
            $res=model('Coupon')->where("id=$id")->save($params);
            if(!$res)$this->error("对不起，修改失败！");
            $this->success("修改打折卡成功!");
        }
    }

    /**
     * 课程卡列表
     */
    public function course(){
        $this->_initTabSpecial();
        $this->assign('pageTitle','课程卡');
        //页面配置
        $id     =  intval($_POST['id']);
        $sid    =  intval($_POST['sid']);
        $code   =  intval($_POST['code']);
        $video_type = intval($_POST['video_type']);
        $this->pageKeyList = array('id','school_title','code','video_type','video_title','exp_date','status','end_time','ctime','is_del','DOACTION');
        $this->searchKey = array('id','sid','code','video_type');
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[] = array('title'=>'禁用','onclick'=>"admin.delCouponAll('delCoupon')");
        $this->pageButton[] = array("title"=>"添加","onclick"=>"admin.addCoupon('addCourse')");

        $school = model('School')->getAllSchol('','id,title');
        $this->opt['sid'] = $school;
        $this->opt['video_type'] = array('1'=>'点播','2'=>'直播','3'=>'班级');
        $map = array('type'=>5,'coupon_type'=>1);
        if(!empty($id))$map['id']=$id;
        if(!empty($sid))$map['sid']=$sid;
        if(!empty($code))$map['code']=$code;
        if(!empty($video_type))$map['video_type']=$video_type;
        $explod['map'] = $map;
        $explod['title'] = "课程卡数据列表";
        $this->pageButton[] = array('title' => '导出', 'onclick' => "admin.exportCoupon('".urlencode(sunjiami(json_encode($explod),"hll"))."')");

        $coupon = model('Coupon');
        //数据列表
        $listData = $coupon->where($map)->order('ctime DESC,id DESC')->findPage();
        $time = time();
        foreach($listData['data'] as $key=>$val){
            if($val['is_del'] == 1) {
                $val['status'] = 3;
            }
            if($val['status'] != 3 && $val['end_time'] < $time){
                $val['status'] = 0;
                $coupon->setStatus($val['id'],0);
            }
            $school = model('School')->where(array('id'=>$val['sid']))->field('id,doadmin,title')->find();
            if(!$school['doadmin']){
                $url = U('school/School/index', array('id' => $val['mhm_id']));
            }else{
                $url = getDomain($school['doadmin']);
            }
            $val['school_title'] = getQuickLink($url,$school['title'],"未知机构");

            if($val['video_type'] == 1){
                $val['video_title'] = M('zy_video')->where(array('id'=>$val['video_id']))->getField('video_title');
                $url = U('classroom/Video/view', array('id' => $val['video_id']));
            }else if($val['video_type'] == 2){
                $val['video_title'] = M('zy_video')->where(array('id'=>$val['video_id']))->getField('video_title');
                $url = U('live/Index/view', array('id' => $val['video_id']));
            }else{
                $val['video_title'] = M('album')->where(array('id'=>$val['video_id']))->getField('album_title');
                $url = U('classroom/Album/view', array('id' => $val['video_id']));
            }
            $val['video_title'] = getQuickLink($url,$val['video_title'],"未知课程");

            $val['ctime']    = date("Y-m-d H:i:s", $val['ctime']);
            $val['end_time'] = date("Y-m-d H:i:s", $val['end_time']);

            if($val['video_type'] == 1){
                $val['video_type'] = "点播";
            }else if($val['video_type'] == 2){
                $val['video_type'] = "直播";
            }else{
                $val['video_type'] = "班级";
            }

            if($val['status'] == 3){
                $val['status'] = "<span style='color: grey;'>已作废</span>";
            }else if($val['status'] == 1){
                $val['status'] = "<span style='color: green;'>未使用</span>";
            }else if($val['status'] == 2){
                $val['status'] = "<span style='color: blue;'>已使用</span>";
            }else if($val['status'] == 0){
                $val['status'] = "<span style='color: red;'>已过期</span>";
            }

            if($val['is_del'] == 1) {
                $val['DOACTION'] ='<a href="javascript:admin.mzCouponCardEdit('.$val['id'].',\'closeCoupon\',\'启用\',\'课程卡\');">启用</a>';
            }else {
                $val['DOACTION'] ='<a href="javascript:admin.mzCouponCardEdit('.$val['id'].',\'closeCoupon\',\'禁用\',\'课程卡\');">禁用</a>';
            }

            if($val['is_del'] == 0){
                $val['is_del'] = "<span style='color: green;'>正常</span>";
            }else if($val['is_del'] == 1){
                $val['is_del'] = "<span style='color: red;'>禁用</span>";
            }
            $val['DOACTION'].=" | <a href=".U('classroom/AdminEntityCard/addCourse',array('id'=>$val['id'],'tabHash'=>'revise')).";>修改</a>";
            $listData['data'][$key] = $val;
        }
        $this->displayList($listData);
    }

    /**
     * 添加课程卡
     * Enter description here ...
     */
    public function addCourse(){
        $id   = intval($_GET['id']);

        $this->_initTabSpecial();
        $map['is_del'] = 0;
        $map['status'] = 1;
        $field = 'id,title';
        $school = model('School')->getAllSchol($map,$field);
        $this->assign('school',$school);
        if($id){
            $coupon = model('Coupon')->where( 'id=' .$id )->find ();
            if($coupon['video_type'] == 3){
                $video = M('album')->where(array('id'=>$coupon['video_id']))->field('id,album_title')->find();
                $video['title'] = $video['album_title'];
            }else{
                $video = M('zy_video')->where(array('id'=>$coupon['video_id']))->field('id,video_title')->find();
                $video['title'] = $video['video_title'];
            }
            $coupon['end_time'] = date("Y-m-d H:i:s",$coupon['end_time']);
            $this->assign('id',$id);
            $this->assign('pageTitle','修改');
            $this->assign('coupon',$coupon);
            $this->assign('video',$video);
        }else{
            $this->assign('pageTitle','添加');
        }
        $this->display();
    }

    /**
     * 加载点播/直播/班级数据
     */
    public function getVideoInfoList(){
        $video_type = intval($_POST['video_type']);
        $mhm_id = intval($_POST['mhm_id']);

        if($video_type == 3){
            $where = "status=1 AND is_mount=1 AND mhm_id=$mhm_id";
            $table = 'album';
        }else{
            $time = time();
            $where = "is_del=0 AND is_mount = 1 AND is_activity=1 AND uctime>$time AND listingtime<$time AND type=$video_type AND mhm_id=$mhm_id";
            $table = 'zy_video';
        }
        if($table == 'zy_video'){
            $video = M($table)->where($where)->field('id,video_title')->findALL();
            $data['data'] = array_column($video, 'video_title', 'id');
        }else{
            $album = M($table)->where($where)->field('id,album_title')->findALL();
            $data['data'] = array_column($album, 'album_title', 'id');
        }
        if($data['data']){
            $data['status'] = 1;
        }else{
            $data['status'] = 0;
        }
        echo json_encode($data);
        exit;
    }

    /**
     * 处理添加课程卡
     */
    public function doAddCourse(){
        $id		=	intval($_POST['id']);

        $count  = 	intval($_POST['counts']);
        if($count == 0){
            $count = 1;
        }
        $params = array(
            'type'=> 5,
            'sid'=>intval($_POST['mhm_id']),
            'video_type'=>intval($_POST['video_type']),
            'video_id'=>intval($_POST['video_id']),
            'exp_date'=>intval($_POST['exp_date']),
            'end_time'=>strtotime($_POST['end_time']),
            'coupon_type'=>1,
            'ctime'=>time(),
        );
//        if($params['type'] == 1){
//            $params['maxprice'] = t($_POST['maxprice']);
//            $params['price'] = t($_POST['price']);
//            $params['discount'] = '0.00';
//        }else{
//            $params['discount'] = t($_POST['discount']);
//            $params['maxprice'] = '0.00';
//            $params['price'] = '0.00';
//        }

        //要添加的数据

        if(!$id){
            $num = 0;
            $couponModel = model('Coupon');
            for ($i = 0; $i < $count; $i++) {
                $params['code'] = $this->create_code();
                $params['ctime'] = time();
                $couponModel->add($params);
                $num++;
            }
            if(!$num)$this->error("对不起，添加失败！");
            $this->success("成功添加".$num."个课程卡！");
        }else{
            $res=model('Coupon')->where("id=$id")->save($params);
            if(!$res)$this->error("对不起，修改失败！");
            $this->success("修改课程卡成功!");
        }
    }

    /**
     * 充值卡列表
     */
    public function recharge(){
        $this->_initTabSpecial();
        $this->assign('pageTitle','充值卡');
        //页面配置
        $id     =  intval($_POST['id']);
        $code   =  intval($_POST['code']);
        $recharge_price   =  intval($_POST['recharge_price']);
        $this->pageKeyList = array('id','code','recharge_price','exp_date','status','end_time','ctime','is_del','DOACTION');
        $this->searchKey = array('id','code','recharge_price');
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[] = array('title'=>'禁用','onclick'=>"admin.delCouponAll('delcoupon')");
        $this->pageButton[] = array("title"=>"添加","onclick"=>"admin.addCoupon('addRecharge')");

        $map = array('type'=>4,'coupon_type'=>1);
        if(!empty($id))$map['id']=$id;
        if(!empty($code))$map['code']=$code;
        if(!empty($recharge_price))$map['recharge_price']=$recharge_price;
        $explod['map'] = $map;
        $explod['title'] = "充值卡数据列表";
        $this->pageButton[] = array('title' => '导出', 'onclick' => "admin.exportCoupon('".urlencode(sunjiami(json_encode($explod),"hll"))."')");

        $coupon = model('Coupon');
        //数据列表
        $listData = $coupon->where($map)->order('ctime DESC,id DESC')->findPage();
        $time = time();
        foreach($listData['data'] as $key=>$val){
            if($val['is_del'] == 1) {
                $val['status'] = 3;
            }
            if($val['status'] != 3 && $val['end_time'] < $time){
                $val['status'] = 0;
                $coupon->setStatus($val['id'],0);
            }

            $val['ctime']    = date("Y-m-d H:i:s", $val['ctime']);
            $val['end_time'] = date("Y-m-d H:i:s", $val['end_time']);
            if($val['status'] == 3){
                $val['status'] = "<span style='color: grey;'>已作废</span>";
            }else if($val['status'] == 1){
                $val['status'] = "<span style='color: green;'>未使用</span>";
            }else if($val['status'] == 2){
                $val['status'] = "<span style='color: blue;'>已使用</span>";
            }else if($val['status'] == 0){
                $val['status'] = "<span style='color: red;'>已过期</span>";
            }

            if($val['is_del'] == 1) {
                $coupon->setStatus($val['id'],3);
                $val['DOACTION'] ='<a href="javascript:admin.mzCouponCardEdit('.$val['id'].',\'closeCoupon\',\'启用\',\'充值卡\');">启用</a>';
            }else {
                $val['DOACTION'] ='<a href="javascript:admin.mzCouponCardEdit('.$val['id'].',\'closeCoupon\',\'禁用\',\'充值卡\');">禁用</a>';
            }

            if($val['is_del'] == 0){
                $val['is_del'] = "<span style='color: green;'>正常</span>";
            }else if($val['is_del'] == 1){
                $val['is_del'] = "<span style='color: red;'>禁用</span>";
            }
            $val['DOACTION'].=" | <a href=".U('classroom/AdminEntityCard/addRecharge',array('id'=>$val['id'],'tabHash'=>'revise')).";>修改</a>";
            $listData['data'][$key] = $val;
        }

        $this->displayList($listData);
    }

    /**
     * 添加充值卡
     * Enter description here ...
     */
    public function addRecharge(){
        $id   = intval($_GET['id']);

        $this->_initTabSpecial();
        $this->pageKeyList = array ('recharge_price','exp_date','end_time','counts');
        $this->notEmpty = array ('recharge_price','exp_date','end_time','counts');
        if($id){
            $this->savePostUrl = U ( 'classroom/AdminEntityCard/doAddRecharge','type=save&id='.$id);
            $recharge = model('Coupon')->where( 'id=' .$id )->find ();
            $recharge['end_time'] = date('Y-m-d H:i:s',$recharge['end_time']);
            $this->assign('pageTitle','修改');
            //说明是编辑
            $this->displayConfig($recharge);
        }else{
            $this->savePostUrl = U ('classroom/AdminEntityCard/doAddRecharge','type=add');
            $this->assign('pageTitle','添加');
            //说明是添加
            $this->displayConfig();
        }
    }

    /**
     * 处理添加充值卡
     * Enter description here ...
     */
    public function doAddRecharge(){
        $id=intval($_GET['id']);
        $type= t($_GET['type']);
        $count = intval($_POST['counts']);
        if($count == 0){
            $count = 1;
        }
        //要添加的数据
        //数据验证
        if($_POST['recharge_price'] == ''){$this->error("充值面额不能为空");}
        if(!is_numeric($_POST['recharge_price'])){$this->error('充值面额必须为数字');}
        if($_POST['exp_date'] == ''){$this->error("有效期不能为空");}
        if(!is_numeric($_POST['exp_date'])){$this->error('有效期必须为数字');}
        if($_POST['end_time'] == ''){$this->error("终止时间不能为空");}
        if(!is_numeric($count)){$this->error('生成数量必须为数字');}

        $params = array(
            'sid'=>1,
            'type'=> 4,
            'recharge_price'=>t($_POST['recharge_price']),
            'exp_date'=>intval($_POST['exp_date']),
            'end_time'=>strtotime($_POST['end_time']),
            'coupon_type'=>1,
            'ctime'=>time(),
        );
        if($type == 'add'){
            $num = 0;
            $rechargeModel = model('Coupon');
            for ($i = 0; $i < $count; $i++) {
                $params['code'] = $this->create_code();
                $params['ctime'] = time();
                $rechargeModel->add($params);
                $num++;
            }
            if(!$num)$this->error("对不起，添加失败！");
            $this->success("成功添加".$num."个充值卡！");
        }else if($type=='save' && $id){
            $res=model('Coupon')->where("id=$id")->save($params);
            if(!$res)$this->error("对不起，修改失败！");
            $this->success("修改充值卡成功!");
        }
    }
	/**
	  * @name 生成code方法
	  * 
	  */
	private function create_code(){
		static $codeList;
		$code = date('ydis',time()).substr(time(),-3).mt_rand(10000,99999);
		//检测code是否已经存在
		if(in_array($code,$codeList)){
			return $this->create_code();
		}
		$codeList[] = $code;
		return $code;
	}
     /**
     * 禁用/启用 课程卡
     */
    public function closeCoupon()
    {
        $id = implode(",", $_POST['id']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['id']);
        }
        $msg = array();
        $where = array(
            'id' => array('in', $id)
        );
        $is_del = M('coupon')->where($where)->getField('is_del');
        if ($is_del == 1) {
            $data['is_del'] = 0;
        } else {
            $data['is_del'] = 1;
        }
        $res = M('coupon')->where($where)->save($data);

        if ($res !== false) {
            $msg['data'] = '操作成功';
            $msg['status'] = 1;
            echo json_encode($msg);
        } else {
            $msg['data'] = "操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
    }

    /**
     * 批量禁用优惠券
     * @return void
     */
    public function delCoupon(){
        $ids = implode(",", $_POST['ids']);
        $ids = trim(t($ids), ",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
        $msg = array();
        $where=array(
            'id'=>array('in',$ids)
        );
        $is_del = M('coupon')->where($where)->getField('is_del');
        if ($is_del == 1) {
            $data['is_del'] = 0;
        } else {
            $data['is_del'] = 1;
        }
        $res =  M('coupon')->where($where)->save($data);
        
        if($res!==false){
            $msg['data']="操作成功";
            $msg['status']=1;
            echo json_encode($msg);
        }else{
            $msg['data']="操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
    }
    /*
    *
    *彻底删除优惠券
    */
    /*public function delCouponCard(){
        $ids = implode(",",$_POST['ids']);
        $ids = trim(t($ids),",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
        $msg = array();
        $where = array(
            'id'=>array('in',$ids)
        );
        $res = M('coupon')->where($where)->delete();
        
        if( $res !== false){
            $msg['data']   = '操作成功';
            $msg['status'] = 1;
            echo json_encode($msg);
        }else{
            $msg['data'] = "操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
    }*/

    /**
     * @name 导出日志
     */
    public function exportCoupon(){
        $xlsCell  = [
            ['id','ID'],
            ['code','卡券编码'],
            ['coupon_type','卡券类型'],
            ['exp_date','有效期（单位天）'],
            ['end_time','终止时间'],
        ];
        $xlsData =  $this->xlsData($_GET['explod']);
        $xlsCell = array_merge($xlsCell,$xlsData['xlsCell']);
        model('Excel')->export($xlsData['explod_title'],$xlsCell,$xlsData['data']);

        header('HTTP/1.1 401 Unauthorized');
        header('status: 401 Unauthorized');
        exit;
    }

    protected function xlsData($explod){
        $explod = json_decode(sunjiemi(urldecode($explod),'hll'));
        $listData['explod_title'] = $explod->title;

        $field = 'id,code,type,exp_date,end_time,';
        switch ($explod->map->type)
        {
            case 1:
                $field .= 'maxprice,price';
                $type = '优惠券';
                $listData['xlsCell']  = [
                    ['maxprice','优惠条件（优惠券满减）'],
                    ['price','优惠价格（优惠券立减）'],
                ];
                break;
            case 2:
                $field .= 'discount';
                $type = '打折卡';
                $listData['xlsCell']  = [
                    ['discount','折扣'],
                ];
                break;
            case 4:
                $field .= 'recharge_price';
                $type = '充值卡';
                $listData['xlsCell']  = [
                    ['recharge_price','充值卡面额'],
                ];
                break;
            case 5:
                $field .= 'video_type,video_id';
                $type = '课程卡';
                $listData['xlsCell']  = [
                    ['video_type','课程类型'],
                    ['video_title','关联课程'],
                ];
                break;
            default:
        }

        $listData['data'] = model('Coupon')->where($explod->map)->field($field)->order('ctime DESC,id DESC')->select();
        //整理数据列表
        foreach ($listData['data'] as $key => $val) {
            $val['coupon_type'] = $type;
            $val['end_time'] = date("Y-m-d H:i",$val['end_time']);
            if($val['type'] == 3){
                $val['vip_grade'] = M('user_vip')->where(array('id'=>$val['vip_grade']))->getField('title');
            }
            if($val['type'] == 5){
                switch ($val['video_type'])
                {
                    case 1:
                        $val['video_type'] = '点播';
                        $val['video_title'] = D('ZyVideo')->getVideoTitleById($val['video_id']);
                        break;
                    case 2:
                        $val['video_type'] = '直播';
                        $val['video_title'] = D('ZyVideo')->getVideoTitleById($val['video_id']);
                        break;
                    case 3:
                        $val['video_type'] = '班级';
                        $val['video_title'] = D('Album')->getAlbumTitleById($val['video_id']);
                        break;
                    default:
                }
            }
            $listData['data'][$key] = $val;
        }
        return $listData;
    }
}