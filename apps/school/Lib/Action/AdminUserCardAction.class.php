<?php
/**
 * 用户卡券管理
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminUserCardAction extends AdministratorAction{

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
            'title' => '用户卡券列表',
            'tabHash' => 'index',
            'url' => U ( 'school/AdminUserCard/index' )
        );
        if(!is_admin($this->mid)){
            $this->pageTab [] = array (
                'title' => '添加用户卡券',
                'tabHash' => 'addCard',
                'url' => U ( 'school/AdminUserCard/addCard' )
            );
        }
    }

    public function index(){
        $this->_initTabSpecial();
        $this->assign('pageTitle','用户卡券管理');
        //页面配置
        $id     =  intval($_POST['id']);
        $uid    =  intval($_POST['uid']);
        $cid    =  intval($_POST['cid']);
        $this->pageKeyList = array('id','uname','cid','type','exp_date','status','stime','etime','is_del','DOACTION');
        $this->searchKey = array('id','uid','cid');
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[] = array('title'=>'删除','onclick'=>"admin.delUserCardAll('delUserCard')");
        if(!empty($id))$map['id']=$id;
        if(!empty($uid))$map['uid']=$uid;
        if(!empty($cid))$map['cid']=$cid;
        //数据列表
        if(is_admin($this->mid)){
            $listData = M('coupon_user')->where($map)->order('stime DESC,id DESC')->findPage();
        }else{
            $map['mhm_id'] = is_school($this->mid);
            $listData = M('coupon_user')->where($map)->order('stime DESC,id DESC')->findPage();
        }
        $nowTime = strtotime(date('Y-m-d'));
        foreach($listData['data'] as $key=>$val){

            $val['uname'] = getUserSpace($val['uid'], null, '_blank');
            if($nowTime < $val['etime']){
                if($val['status'] == 0){
                    $val['status'] = "<span style='color: green;'>未使用</span>";
                }else if($val['status'] == 1){
                    $val['status'] = "<span style='color: blue;'>已使用</span>";
                }else if($val['status'] == 2){
                    $val['status'] = "<span style='color: red;'>已被使用到订单</span>";
                }
            }else{
                $val['status'] = "<span style='color: gray;'>已过期</span>";
            }
            $val['type'] = M('coupon')->where('id='.$val['cid'])->getField('type');
            if($val['type'] == 1){
                $val['type'] = "<span>课程优惠券</span>";
            }else if($val['type'] == 2){
                $val['type'] = "<span>打折卡</span>";
            }else if($val['type'] == 3){
                $val['type'] = "<span>会员卡</span>";
            }else if($val['type'] == 4){
                $val['type'] = "<span>充值卡</span>";
            }
            $val['exp_date'] = M('coupon')->where('id='.$val['cid'])->getField('exp_date');
            $val['stime'] = date("Y-m-d H:i:s", $val['stime']);
            $val['etime'] = date("Y-m-d H:i:s", $val['etime']);

            if($val['is_del'] == 1) {
                $val['is_del'] = "<span style='color: red;'>删除</span>";
            }else {
                $val['is_del'] = "<span style='color: green;'>正常</span>";
            }

            $mhm_id = model('User')->where('uid='.$val['uid'])->getField('mhm_id');

            if($val['is_del'] == 1) {
                $val['DOACTION'] ='<a href="javascript:admin.mzUserCardEdit('.$val['id'].',\'closeUserCard\',\'恢复\',\'用户卡券\');">恢复</a>';
            }else {
                $val['DOACTION'] ='<a href="javascript:admin.mzUserCardEdit('.$val['id'].',\'closeUserCard\',\'删除\',\'用户卡券\');">删除</a>';
            }
            if($mhm_id == $this->school_id){
                $val['DOACTION'].=" | <a href=".U('school/AdminUserCard/addCard',array('id'=>$val['id'],'tabHash'=>'revise')).";>编辑</a>";
            }
            $listData['data'][$key] = $val;
        }
        $this->displayList($listData);
    }
    /**
     * 发放卡券
     */
    public function addCard(){
        $id = intval($_GET['id']);
        $this->_initTabSpecial();
        if($id){
            $userCard = M('coupon_user')->where('id='.$id)->find();
            $userCard['uname'] = getUserName($userCard['uid']);
            $coupon =  M('coupon')->where('id='.$userCard['cid'])->find();
            $coupon['vip_grade'] = M('user_vip')->where('id='.$coupon['vip_grade'])->getField('title');
            $this->assign('pageTitle','编辑优惠券');
            $this->assign('userCard',$userCard);
            $this->assign('coupon',$coupon);
        }else{
            $this->assign('pageTitle','添加优惠券');
        }
        $this->display();
        /*$this->pageKeyList = array ( 'uid','type','cid');
        $this->notEmpty = array ( 'uid','type','cid');

        $time  = time();
        $map = "status=1 AND is_del=0 AND end_time>$time";
        $couponData = model( 'Coupon' )->where($map)->field('id,code')->select();
        foreach($couponData as $key=>$val){
            $userCard = M('coupon_user')->where('cid='.$val['id'])->find();
            if($userCard){
                unset($couponData[$key]);
            }
        }
        if($couponData){
            $this->opt['cid'] = array_column($couponData,'code','id');
        }else{
            $this->opt['cid'] = array('0'=>'暂时没有可用的卡券');
        }

        if($id){
            $this->savePostUrl = U ( 'school/AdminUserCard/doUserCard','type=save&id='.$id);
            $coupon = M('coupon_user')->where( 'id=' .$id )->find ();
            $this->assign('pageTitle','编辑优惠券');
            //说明是编辑
            $this->displayConfig($coupon);
        }else{
			$this->assign('pageTitle','添加优惠券');
            $this->savePostUrl = U ('school/AdminUserCard/doUserCard','type=add');
            //说明是添加
            $this->displayConfig($couponData);
        }*/

    }
    public function getSubCategory(){
        $type = $_POST['type'];
        $coupon = $_POST['coupon'];
        $time  = time();
        $sid = $this->school_id;
        $map = "status=1 AND is_del=0 AND end_time>$time AND sid=$sid AND coupon_type=0 ";
        if($type>0) {
            switch ($type) {
                case 1:
                    $field = "price";
                    break;
                case 2:
                    $field = "discount";
                    break;
                case 3:
                    $field = "vip_grade";
                    break;
                case 4:
                    $field = "recharge_price";
                    break;
                default;
            }
            $map .=" AND type=".$type ;
            if(!$coupon){
                $couponList = M('coupon')->where($map)->field($field)->select();
                if($couponList){
                    foreach($couponList as $v){
                        $v = implode(",",$v);
                        $temp[]=$v;
                    }
                    $list = array_unique($temp);
                    foreach($list as $v){
                        $v = M('user_vip')->where('id='.$v)->field('id,title')->find();
                        if($v){
                            $vip_title[]=$v;
                        }
                    }
                    if($vip_title){
                        $list = array_column($vip_title,'title','id');
                    }
                }
            }else{
                if($type == 3){
                    $vip_grade = M('user_vip')->where(array('title'=>$coupon))->getField('id');
                    $map .=" AND $field=".$vip_grade ;
                }else{
                    $map .=" AND $field=".$coupon ;
                }
                $list = M('coupon')->where($map)->field('id,code')->select();
            }
        }
        if($list){
            $res = [
                'status'=>1,
                'data' => $list
            ];
        }else{
            $res = [
                'status'=>0,
                'message' => '暂无子分类'
            ];
        }
        echo json_encode($res);exit;
    }
    /**
     * 处理添加/修改 用户卡券
     */
    public function doUserCard(){

        $id		=	intval($_POST['id']);
        //$type	= 	t($_GET['type']);

        $info = model('Coupon')->where('id='.$_POST['cid'])->getField('exp_date');

        $data = array(
            'uid'    => intval($_POST['uid']),
            'cid'    => intval($_POST['cid']),
            'stime'  => time(),
            'etime'  => time()+(int)$info * 86400,
        );
        if(!is_admin($this->mid)){
            $data['mhm_id'] = $this->school_id;
        }
        if(!$id){
            $res = M('coupon_user')->add($data);
            if(!$res){
                echo json_encode(array('status'=>0,'info'=>'对不起，添加失败！'));exit;
            }else{
                $map['status'] = 2;
                model('Coupon')->where('id='.$data['cid'])->save($map);
                echo json_encode(array('status'=>1,'info'=>'添加成功'));exit;
            }
        }else{
            $res = M('coupon_user')->where("id=$id")->save($data);
            if(!$res){
                echo json_encode(array('status'=>0,'info'=>'对不起，编辑失败！'));exit;
            }else{
                echo json_encode(array('status'=>1,'info'=>'编辑成功'));exit;
            }
        }
    }
    /**
     * 删除/恢复 用户卡券
     */
    public function closeUserCard()
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
        $is_del = M('coupon_user')->where($where)->getField('is_del');
        if ($is_del == 1) {
            $data['is_del'] = 0;
        } else {
            $data['is_del'] = 1;
        }
        $res = M('coupon_user')->where($where)->save($data);

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
     * 批量删除用户卡券
     * @return void
     */
    public function delUserCard(){
        $ids = implode(",", $_POST['ids']);
        $ids = trim(t($ids), ",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
        $msg = array();
        $where=array(
            'id'=>array('in',$ids)
        );
        $is_del = M('coupon_user')->where($where)->getField('is_del');
        if ($is_del == 1) {
            $data['is_del'] = 0;
        } else {
            $data['is_del'] = 1;
        }
        $res =  M('coupon_user')->where($where)->save($data);

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

}