<?php
/**
 * 后台商城收货地址管理
 * @author wangjun@chuyouyun.com
 * @version chuyouyun1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminGoodsAddressAction extends AdministratorAction
{
    
    /**
     * 初始化，
     */
    public function _initialize(){
        $this->pageTitle['index']       = '列表';
        $this->pageTitle['addAddress']       = '添加';

        $this->pageTab[] = array('title'=>'列表','tabHash'=>'index','url'=>U('mall/AdminGoodsAddress/index'));
        $this->pageTab[] = array('title'=>'添加','tabHash'=>'addAddress','url'=>U('mall/AdminGoodsAddress/addAddress'));
        parent::_initialize();
    }

    /**
     * 递归取得多维数组的所有值
     * @param array $content 数据数组
     * @return str 数组的值
     */
    public function getreturnContent($content){
        foreach ($content as $v){
            if(is_array($v)){
                $str.=$this->getreturnContent($v);
            }else{
                $str.=$v;
            }
        }
        return $str;
    }

    /**
     * 收货地址列表
     */
    public function index(){
        $_REQUEST['tabHash'] = 'index';
        $this->pageKeyList = array('id','uid','location','address','name','phone','is_del','is_default','DOACTION');

        $this->searchKey = array('uid', 'name', 'phone',);
        $this->pageButton[] = array('title' => "搜索", 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => '删除', 'onclick' => "admin.delAddressAll('delAddress')");
        $map = array();
        $map['is_del'] = ['neq',2];
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            empty($_POST['uid']) || $map['uid'] = $_POST['uid'];
            empty($_POST['name']) || $map['name'] = array('LIKE',"%{$_POST['name']}%");
            empty($_POST['phone']) || $map['phone'] = array('LIKE',"%{$_POST['phone']}%");
        }
        $listData = M('Address')->where($map)->order('uid DESC,ctime DESC')->findPage(20);
        foreach($listData['data'] as $key=>$val){
            $data['id'] = array('IN',array($val['province'],$val['city'],$val['area']));
            $location = model('Area')->where(array('area_id'=> $data['id']))->field('title')->findAll();
            $val['location'] = $this->getreturnContent($location);
            $val['is_default'] = $val['is_default'] ? '是' : '否';
            if($val['is_del'] == '0'){
                $val['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.closeAddress(\'' . $val['id'] . '\')">禁用</a> | ';
            }else{
                $val['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.openAddress(\'' . $val['id'] . '\')">启用</a> | ';
            }
            if($val['is_del'] == 0){
                $val['is_del'] = "<span style='color: green;'>正常</span>";
            }else if($val['is_del'] == 1){
                $val['is_del'] = "<span style='color: red;'>禁用</span>";
            }
            $val['DOACTION'] .= '<a href="' .U('mall/AdminGoodsAddress/editAddress',array('id'=>$val['id'])) . '">编辑</a> | <a href="javascript:admin.delAddress('.$val['id'].');">彻底删除</a>';
            $val['content']  = '<div style="width:500px">'.$val['content'].'</div>';
            $listData['data'][$key] = $val;
        }
        $this->displayList($listData);
    }

    /**
     * 添加/修改收货地址
     */
    public function addAddress(){
        $_REQUEST['tabHash'] = 'addAddress';
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            if (!preg_match('/^1[3,4,5,7,8,9]\d{9}$/', $_POST['phone'])) {
                $this->error("手机号码格式不正确");
            }
            if(empty($_POST['id'])) {
                if (empty($_POST['uid']) || empty($_POST['city']) || empty($_POST['address']) || empty($_POST['name']) || empty($_POST['phone'])) {
                    $this->error("请填写完整信息");
                }

                $data['uid'] = $_POST['uid'];
                if (!M('User')->find($data['uid'])) {
                    $this->error("用户不存在");
                }
                $data['ctime'] = time();
                $count = M('Address')->where(['uid' => $uid])->count();
                $data['province']= $_POST['province'];
                $data['city']    = $_POST['city'];
                $data['area']    = $_POST['area'];
                $data['address'] = $_POST['address'];
                $data['name']    = $_POST['name'];
                $data['phone']   = $_POST['phone'];
                $data['is_default'] = $_POST['is_default'];
                $data['location'] = $_POST['city_names'];
                if(!empty($data['is_default']) && $data['is_default'] == '1'){
                    M('Address')->where('uid='.$data['uid'])->setField('is_default',0);
                }
                if (M('Address')->add($data)) {
                    $this->assign('jumpUrl', U('mall/AdminGoodsAddress/index'));

                    $this->success('添加成功');
                } else {
                    $this->error('添加失败');
                }
                exit;
            }else{
                if(empty($_POST['city']) || empty($_POST['address']) || empty($_POST['name']) || empty($_POST['phone'])) {
                    $this->error("请填写完整信息");
                }
                $data['uid'] = $_POST['uid'];
                if (!M('User')->find($data['uid'])) {
                    $this->error("用户不存在");
                }
                $data['ctime'] = time();
                $data['province']= $_POST['province'];
                $data['city']    = $_POST['city'];
                $data['area']    = $_POST['area'];
                $data['address'] = $_POST['address'];
                $data['name']    = $_POST['name'];
                $data['phone']   = $_POST['phone'];
                $data['location'] = $_POST['city_names'];
                $data['is_default'] = $_POST['is_default'];
                if(!empty($data['is_default']) && $data['is_default'] == '1'){
                    M('Address')->where('uid='.$data['uid'])->setField('is_default',0);
                }
                if(M('Address')->where(array('id'=>$_POST['id']))->save($data)){
                    $this->assign ( 'jumpUrl', U ( 'mall/AdminGoodsAddress/index' ) );
                    $this->success('修改成功');
                }else{
                    $this->error('修改失败');
                }
            }
        }
        $this->display();
    }

    /**
     * 编辑收货地址
     */
    public function editAddress(){
        $_REQUEST ['tabHash'] = 'editAddress';
        $this->pageTitle ['editAddress'] = '编辑';

        $id = intval($_REQUEST ['id']);
        $data = M('Address')->find($id);
        if (! $id || !$data) {
            $this->assign ( 'jumpUrl', U ( 'mall/AdminGoodsAddress/index' ) );
            $this->error ( '参数错误' );
            exit;
        }
        $this->pageTab [] = array ('title' => '编辑', 'tabHash' => 'editAddress','url' => U ( 'mall/AdminGoodsAddress/editAddress' ));

        $this->savePostUrl = U ( 'mall/AdminGoodsAddress/addAddress', array ('id' => $id) );
        $this->assign('data',$data);
        $this->display('addAddress');
    }
    /*
    *删除收货地址
    */
    public function delAddress(){
        $ids = implode(",",$_POST['ids']);
        $ids = trim(t($ids),",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
        $msg = array();
        $where = array(
            'id'=>array('in',$ids)
        );
        $defaults = M('address')->where($where)->field('is_default')->select();
        foreach ($defaults as $val) {
           if($val['is_default'] == '1'){
                $msg['data']   = '不能删除默认地址，请重新选择';
                $msg['status'] = 0;
               echo json_encode($msg);
               exit;
           }
        }
//        $res = M('address')->where($where)->delete();
        $data['is_del'] = 2;
        $res = M('address')->where($where)->save($data);
        if( $res !== false){
            $msg['data']   = '操作成功';
            $msg['status'] = 1;
            echo json_encode($msg);
            exit;
        }else{
            $msg['data'] = "操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
            exit;
        }
    }

    /**
     * 异步请求启用收货地址
     */
    public function openAddress(){
        $id = intval($_POST['address_id']);
        if(!$id){
            $this->ajaxReturn(null,"参数错误",0);
        }
        $data['is_del'] = 0;
        $res = M('Address')->where(array('id'=>$id))->save($data);
        if($res){
            $this->ajaxReturn(null,"启用成功",1);
        }else{
            $this->ajaxReturn(null,"启用失败",0);
        }
    }
    /**
     * 异步请求禁用收货地址
     */
    public function closeAddress(){
        $id = intval($_POST['address_id']);
        if(!$id){
            $this->ajaxReturn(null,"参数错误",0);
        }
        if(M('Address')->where(['id'=>$id,'is_default'=>1])->find()){
            $this->ajaxReturn(null,"不能禁用默认收货地址",0);
        }
        $data['is_del'] = 1;
        $res = M('Address')->where(array('id'=>$id))->save($data);
        if($res){
            $this->ajaxReturn(null,"禁用成功",1);
        }else{
            $this->ajaxReturn(null,"禁用失败",0);
        }
    }

}