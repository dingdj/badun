<?php
/**
 * 课程优惠券管理
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminVideoCouponAction extends AdministratorAction{

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
                'title' => '列表',
                'tabHash' => 'index',
                'url' => U ( 'school/AdminVideoCoupon/index' )
        );
        if(!is_admin($this->mid)) {
            $this->pageTab [] = array(
                'title' => '添加',
                'tabHash' => 'addTeacher',
                'url' => U('school/AdminVideoCoupon/addCoupon')
            );
        }
    }

    public function index(){
        $this->_initTabSpecial();
        $this->assign('pageTitle','列表');
        //页面配置
        $id     =  intval($_POST['id']);
        $code   =  intval($_POST['code']);
        $this->pageKeyList = array('id','school_title','code','maxprice','price','exp_date','status','count','end_time','ctime','is_del','DOACTION');
        $this->searchKey = array('id','code');
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[] = array('title'=>'禁用','onclick'=>"admin.delCouponAll('delCoupon')");
        $map = array('type'=>1,'coupon_type'=>0);
        if(!is_admin($this->mid)){
            $map['sid'] = $this -> school_id;
        }
        if(!empty($id))$map['id']=$id;
        if(!empty($code))$map['code']=$code;
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
            $url = getDomain($school['doadmin'],$school['id']);
            $val['school_title'] = getQuickLink($url,$school['title'],"未知机构");

            $val['ctime']    = date("Y-m-d H:i:s", $val['ctime']);
			$val['end_time'] = date("Y-m-d H:i:s", $val['end_time']);

            if($val['status'] == 3){
                $val['status'] = "<span style='color: grey;'>已作废</span>";
            }else if($val['status'] == 1){
                $val['status'] = "<span style='color: green;'>未领取</span>";
            }else if($val['status'] == 2){
                $val['status'] = "<span style='color: blue;'>已领取</span>";
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
            if(!is_admin($this->mid)){
                $val['DOACTION'].=" | <a href=".U('school/AdminVideoCoupon/addCoupon',array('id'=>$val['id'],'tabHash'=>'revise')).";>修改</a>";
            }
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
        $this->pageKeyList = array ( 'maxprice','price','exp_date','end_time','count');
        $this->notEmpty = array ( 'maxprice','price','exp_date','end_time', 'count');
        if($id){
            $this->savePostUrl = U ( 'school/AdminVideoCoupon/doAddCoupon','type=save&id='.$id);
            $coupon = model('Coupon')->where( 'id=' .$id )->find ();
			$coupon['end_time'] = date('Y-m-d H:i:s',$coupon['end_time']);
            $this->assign('pageTitle','修改');
            //说明是编辑
            $this->displayConfig($coupon); 
        }else{
			$this->assign('pageTitle','添加');
            $this->savePostUrl = U ('school/AdminVideoCoupon/doAddCoupon','type=add');
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
		//$max_price_sys = model('School')->where('id='.$this-> school_id)->getField('max_price_sys');
        //要添加的数据
        //数据验证
        if(empty($_POST['maxprice'])){$this->error("优惠条件不能为空");}
        if(!is_numeric($_POST['price'])){$this->error('优惠条件必须为数字');}
        //if(intval($_POST['maxprice']) < $max_price_sys){$this->error('优惠条件必须大于'.$max_price_sys);}
        if($_POST['price'] == ''){$this->error("价格不能为空");}
        if(!is_numeric($_POST['price'])){$this->error('价格必须为数字');}
        if($_POST['price'] >= $_POST['maxprice']){$this->error("立减价格必须小于优惠条件");}
        if($_POST['exp_date'] == ''){$this->error("有效期不能为空");}
        if(!is_numeric($_POST['exp_date'])){$this->error('有效期必须为数字');}
		if($_POST['end_time'] == ''){$this->error("终止时间不能为空");}
        //if(!is_numeric($count)) $this->error('生成数量必须为数字');
        if(!$_POST['count']) $this->error('兑换次数不能为空');
        if(preg_match("/^[0-9]+$/",$_POST['count'] ) == 0){$this->error('兑换次数必须为正整数');}

        $params = array(
			'type'=> 1,
			'sid'=>$this-> school_id,
			'maxprice'=>t($_POST['maxprice']),
			'price'=>t($_POST['price']),
			'exp_date'=>intval($_POST['exp_date']),
			'end_time'=>strtotime($_POST['end_time']),
            'count'=>intval($_POST['count']),
            'ctime'=>time(),
		);
        if($type == 'add'){
			/*$num = 0;
			$couponModel = model('Coupon');
            for ($i = 0; $i < $count; $i++) {
				$params['code'] = $this->create_code();
				$params['ctime'] = time();
				$couponModel->add($params);
				$num++;
            }
            if(!$num)$this->error("对不起，添加失败！");
            $this->success("成功添加".$num."个优惠券！");*/
            $params['code'] = $this->create_code();
            $res=model('Coupon')->add($params);
            if(!$res)$this->error("对不起，添加失败！");
            $this->success("添加优惠券成功!");
        }else if($type=='save' && $id){
            $res=model('Coupon')->where("id=$id")->save($params);
            if(!$res)$this->error("对不起，修改失败！");
            $this->success("修改优惠券成功!");
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
}