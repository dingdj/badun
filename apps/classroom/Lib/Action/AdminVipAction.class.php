<?php
/**
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminVipAction extends AdministratorAction{
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
	 * @return void
	 */
	private function _initTabSpecial() {
		// Tab选项
		$this->pageTab [] = array (
				'title' => '列表',
				'tabHash' => 'index',
				'url' => U ( 'classroom/AdminVip/index' ) 
		);
		$this->pageTab [] = array (
				'title' => '添加',
				'tabHash' => 'addVip',
				'url' => U ( 'classroom/AdminVip/addVip' ) 
		);
       
	}

    public function index(){
        $this->pageKeyList = array( 'id','title','sort','vip_month','vip_year','is_del','DOACTION' );
        $this->_initTabSpecial();
        $this->pageButton[] = array('title'=>'删除','onclick'=>"admin.delVipAll('会员等级','AdminVip')");
        $this->assign('pageTitle','列表');
        $data = M('user_vip')->order("sort asc")->findPage(20);
        foreach($data['data'] as &$val){
        	$val['ctime']  = date('Y-m-d H:i', $val['ctime']);
			//$val['cover']  = "<img src=".getAttachUrlByAttachId($val['cover'])." width='19px' height='19px'>&nbsp;";
          	$val['DOACTION'] .= "<a href=".U('classroom/AdminVip/addVip',array('id'=>$val['id'],'tabHash'=>'revise')).">编辑</a>";
			if($val['is_del'] == 0) {
				$val['DOACTION'] .= " | <a href=javascript:admin.closeVip(" . $val['id'] . ",'会员等级','AdminVip');>禁用</a>";
				$val['is_del'] = "<span style='color: green;'>正常</span>";
			}else{
				$val['DOACTION'] .= " | <a href=javascript:admin.openVip(" . $val['id'] . ",'会员等级','AdminVip');>启用</a>";
				$val['is_del'] = "<span style='color: red;'>禁用</span>";
			}
//			$val['DOACTION'] .= ' | <a onclick="admin.delVip('.$val['id'].',\'delVip\');" href="javascript:;" title="此操作会彻底删除数据">彻底删除</a>';
		}
        $this->displayList($data);
    }
    
    /**
     * 添加会员等级
     * Enter description here ...
     */
    public function addVip(){
    	$id = $_GET['id'];
    	$this->_initTabSpecial();
    	$this->pageKeyList = array ( 'title','sort','vip_month','vip_year','cover');
    	$this->notEmpty = array ( 'title','sort' );
    	if($id){
    		$this->pageKeyList = array ('title','sort','vip_month','vip_year','cover');
    		$res = M('user_vip')->where( 'id=' .$id )->find();
			$this->savePostUrl = U ( 'classroom/AdminVip/doAddVip',array('id'=>$id));
    		$this->assign('pageTitle','编辑会员等级-'.$res['title']);
    		//说明是编辑
    		$this->displayConfig($res);
    	}else{
    		$this->savePostUrl = U ('classroom/AdminVip/doAddVip');
    		$this->assign('pageTitle','添加');
    		//说明是添加
    		$this->displayConfig();
    	}
    
    }
    
    /**
     * 处理添加会员等级
     */
    public function doAddVip(){
    	$id = intval($_GET['id']);
    	//要添加的数据
    	$data['title']     = t($_POST['title']);
        $data['vip_month'] = $_POST['vip_month'];
    	$data['vip_year']  = $_POST['vip_year'];
    	$data['sort']      = $_POST['sort'];
		$data['cover']     = $_POST['cover'] ?: 0;
    	$data['ctime']     = time();
    	//数据验证
    	if(!$data ['title']){
    		$this->error('请输入名称');
    	}
    	if(!$data ['sort']){
    		$this->error('请输入会员等级');
    	}
		if(!is_numeric($data ['sort'])){
			$this->error('会员等级必须为数字');
		}
        /*
    	if(!$data ['vip_year']){
			$this->error('请输入vip年费');
		}
		if(!$data ['cover']){
			$this->error('请上传vip图标');
		}
        */
    	if( $id ){ //修改
    		$res = M('user_vip')->where('id=' . $id)->save($data);
    		if( $res !== false) {
    			$this->success('修改成功');
    		} else {
    			$this->error('修改失败');
    		}
    	}else {
    		$res = M('user_vip')->add($data);
    		if($res ) {
    			$this->success('添加成功');
    		} else {
    			$this->error('添加失败');
    		}
    	}
    }

    /**
     * 禁用/启用会员等级
     */
    public function closeVip(){
        $ids = implode(",",$_POST['ids']);
        $ids = trim(t($ids),",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
        $msg = array();
        $where = array(
            'id'=>array('in',$ids)
        );
		$is_del = M('user_vip')->where($where)->getField('is_del');
		if($is_del == 1){
			$data['is_del'] = 0;
		}else{
			$data['is_del'] = 1;
		}
        $res = M('user_vip')->where($where)->save($data);

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
     * 彻底删除会员等级
     */
    public function delVip(){
        $ids = implode(",",$_POST['ids']);
        $ids = trim(t($ids),",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
        $msg = array();
        $where = array(
            'id'=>array('in',$ids)
        );
        $res = M('user_vip')->where($where)->delete();

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

}
?>