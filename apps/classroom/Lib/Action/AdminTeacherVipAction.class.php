<?php
/**
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminTeacherVipAction extends AdministratorAction{
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
				'title' => '讲师头衔列表',
				'tabHash' => 'index',
				'url' => U ( 'classroom/AdminTeacherVip/index' )
		);
		$this->pageTab [] = array (
				'title' => '添加讲师头衔',
				'tabHash' => 'addVip',
				'url' => U ( 'classroom/AdminTeacherVip/addVip' )
		);
       
	}

    public function index(){
        $this->pageKeyList = array( 'zy_teacher_title_category_id','title','sort','cover','is_del','DOACTION' );
		$this->_listpk = 'zy_teacher_title_category_id';
        $this->_initTabSpecial();
        $this->pageButton[] = array('title'=>'删除','onclick'=>"admin.delVipAll('讲师头衔','AdminTeacherVip')");
        $this->assign('pageTitle','讲师头衔管理');
        $data = M('zy_teacher_title_category')->order("sort DESC")->findPage(20);
        foreach($data['data'] as &$val){
        	$val['ctime']  = date('Y-m-d H:i', $val['ctime']);
			$val['cover']  = "<img src=".getAttachUrlByAttachId($val['cover'])." width='19px' height='19px'>&nbsp;";
          	$val['DOACTION'] .= "<a href=".U('classroom/AdminTeacherVip/addVip',array('id'=>$val['zy_teacher_title_category_id'],'tabHash'=>'revise')).">编辑</a>";
			if($val['is_del'] == 0) {
				$val['DOACTION'] .= " | <a href=javascript:admin.closeVip(" . $val['zy_teacher_title_category_id'] . ",'讲师头衔','AdminTeacherVip');>禁用</a>";
				$val['is_del'] = "<span style='color: green;'>正常</span>";
			}else{
				$val['DOACTION'] .= " | <a href=javascript:admin.openVip(" . $val['zy_teacher_title_category_id'] . ",'讲师头衔','AdminTeacherVip');>启用</a>";
				$val['is_del'] = "<span style='color: red;'>禁用</span>";
			}
//			$val['DOACTION'] .= ' | <a onclick="admin.delVip('.$val['id'].',\'delVip\');" href="javascript:;" title="此操作会彻底删除数据">彻底删除</a>';
		}
        $this->displayList($data);
    }
    
    /**
     * 添加讲师头衔
     * Enter description here ...
     */
    public function addVip(){
    	$id = $_GET['id'];
    	$this->_initTabSpecial();
    	$this->pageKeyList = array ( 'title','sort','cover');
    	$this->notEmpty = array ( 'title','sort','cover' );
    	if($id){
    		$this->pageKeyList = array ('title','sort','cover');
    		$res = M('zy_teacher_title_category')->where( 'zy_teacher_title_category_id=' .$id )->find();
			$this->savePostUrl = U ( 'classroom/AdminTeacherVip/doAddVip',array('id'=>$id));
    		$this->assign('pageTitle','编辑讲师头衔-'.$res['title']);
    		//说明是编辑
    		$this->displayConfig($res);
    	}else{
    		$this->savePostUrl = U ('classroom/AdminTeacherVip/doAddVip');
    		$this->assign('pageTitle','添加讲师头衔');
    		//说明是添加
    		$this->displayConfig();
    	}
    
    }
    
    /**
     * 处理添加讲师头衔
     */
    public function doAddVip(){
    	$id = intval($_GET['id']);
    	//要添加的数据
    	$data['title']     = t($_POST['title']);
    	$data['sort']      = $_POST['sort'];
		$data['cover']      = $_POST['cover'];
    	$data['ctime']     = time();
    	//数据验证
    	if(!$data ['title']){
    		$this->error('请输入名称');
    	}
    	if(!$data ['sort']){
    		$this->error('请输入讲师头衔等级');
    	}
		if(!is_numeric($data ['sort'])){
			$this->error('讲师头衔等级必须为数字');
		}
		if(!$data ['cover']){
			$this->error('请上传vip图标');
		}
    	if( $id ){ //修改
    		$res = M('zy_teacher_title_category')->where('zy_teacher_title_category_id=' . $id)->save($data);
    		if( $res !== false) {
    			$this->success('修改成功');
    		} else {
    			$this->error('修改失败');
    		}
    	}else {
    		$res = M('zy_teacher_title_category')->add($data);
    		if($res ) {
    			$this->success('添加成功');
    		} else {
    			$this->error('添加失败');
    		}
    	}
    }

    /**
     * 禁用/启用 讲师头衔
     */
    public function closeVip(){
        $ids = implode(",",$_POST['ids']);
        $ids = trim(t($ids),",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
        $msg = array();
        $where = array(
            'zy_teacher_title_category_id'=>array('in',$ids)
        );
		$is_del = M('zy_teacher_title_category')->where($where)->getField('is_del');
		if($is_del == 1){
			$data['is_del'] = 0;
		}else{
			$data['is_del'] = 1;
		}
        $res = M('zy_teacher_title_category')->where($where)->save($data);

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
     * 彻底删除 讲师头衔
     */
    public function delVip(){
        $ids = implode(",",$_POST['ids']);
        $ids = trim(t($ids),",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
        $msg = array();
        $where = array(
            'zy_teacher_title_category_id'=>array('in',$ids)
        );
        $res = M('zy_teacher_title_category')->where($where)->delete();

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