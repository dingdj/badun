<?php
//+----------------------------------------------------------------------
// | Sociax [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2012 http://www.thinksns.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: jason <yangjs17@yeah.net>
// +----------------------------------------------------------------------
// 

/**
 +------------------------------------------------------------------------------
 * 搜索关键字管理
 +------------------------------------------------------------------------------
 *
 * @author    jason <yangjs17@yeah.net>
 * @version   1.0
 +------------------------------------------------------------------------------
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');

class SearchKeywordsAction extends AdministratorAction
{
	/**
	 * 初始化，
	 */
	public function _initialize(){
		$this->pageTitle['index']       = '关键字列表';

		$this->pageTab[] = array('title'=>'关键字列表','tabHash'=>'index','url'=>U('admin/SearchKeywords/index'));
		$this->pageTab[] = array('title'=>'添加关键字','tabHash'=>'opSK','url'=>U('admin/SearchKeywords/opSK'));

		parent::_initialize();
	}

	/**
	 * 关键字列表
	 */
	public function index(){
		$_REQUEST['tabHash'] = 'index';

		$this->pageKeyList = array('id','uid','uname','sk_name','sk_url','sort','is_color','is_del', 'ctime', 'DOACTION');
		//搜索字段
		$this->searchKey = array('id', 'uid', 'sk_name', );
		$this->pageButton[] = array('title' => "搜索", 'onclick' => "admin.fold('search_form')");
        $this->pageButton[]  = array('title'=>'禁用','onclick'=>"admin.delOptSK('delOptSk')");
		// 数据的格式化
		$order = 'id desc';
		$list = $this->_getSKList('index',20,$order);

		$this->displayList($list);
	}

    public function _getSKList($type,$limit,$order){

        if(isset($_POST)){
            $_POST['id'] && $map['id'] = intval($_POST['id']);
            $_POST ['uid'] && $map ['uid']      = array('in', (string)$_POST ['uid']);
            $_POST['sk_name'] && $map['sk_name'] = array('like', '%'.t($_POST['sk_name']).'%');
        }
        $sk_info = M('search_keywords')->where($map)->order($order)->findPage($limit);

        foreach($sk_info['data'] as $key => $val){
            $sk_info['data'][$key]['uname'] = getUserSpace($val['uid'], null, '_blank');
            $sk_info['data'][$key]['ctime'] = date('Y-m-d H:i:s',$val['ctime']);
//            $sk_info['data'][$key]['sk_name']  = mb_substr($val['sk_name'],0,20,'utf-8')."...";
            if($val['is_del'] == 1){
                $sk_info['data'][$key]['is_del'] = "<p style='color: red;'>禁用</p>";
            }else if($val['status'] == 0){
                $sk_info['data'][$key]['is_del'] = "<p style='color: green;'>正常</p>";
            }
            if($val['is_color'] == 1){
                $sk_info['data'][$key]['is_color'] = "<p style='color: red;'>着重</p>";
            }else if($val['status'] == 0){
                $sk_info['data'][$key]['is_color'] = "<p style='color: green;'>正常</p>";
            }

            $sk_info['data'][$key]['DOACTION'] .= '<a href="'.U('admin/SearchKeywords/opSK',array('id'=>$val['id'])).'">编辑</a> | ';
            if($val['is_color'] == 0) {
                $sk_info['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.optSK(' . $val['id'] . ',\'yes_color\')">着重</a> | ';
            }else{
                $sk_info['data'][$key]['DOACTION'] .= '<a href="javascript:;" onclick="admin.optSK('.$val['id'].',\'no_color\')">正常</a> | ';
            }
            if($val['is_del'] == 0) {
                $sk_info['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.optSK(' . $val['id'] . ',\'close\')">禁用</a>  ';
            }else{
                $sk_info['data'][$key]['DOACTION'] .= '<a href="javascript:;" onclick="admin.optSK('.$val['id'].',\'open\')">启用</a>';
                $sk_info['data'][$key]['DOACTION'] .= ' | <a href="javascript:void(0)" onclick="admin.optSK(' . $val['id'] . ',\'delete\')">彻底删除</a>  ';
            }
        }
        return $sk_info;
    }

    /**
     * 添加关键字
     */
    public function opSK(){
        $this->pageKeyList   = array('sk_name', 'sk_url',  'sk_text','sort');
        $this->notEmpty      = array('sk_name');



        if(intval($_GET['id'])){
            $this->savePostUrl = U('admin/SearchKeywords/doSK',array('id'=>$_GET['id']));
            $sk_data = M('search_keywords')->where('id = '.intval($_GET['id']))->find();
            $this->pageTitle['opSK'] = '编辑关键字：'.$sk_data['sk_name'];
            $_REQUEST['tabHash'] = 'editSK';

            $this->displayConfig($sk_data);
        }else{
            $this->savePostUrl = U('admin/SearchKeywords/doSK');
            $this->pageTitle['opSK'] = '添加关键字';
            $_REQUEST['tabHash'] = 'opSK';

            $this->displayConfig();
        }
    }

    //编辑/添加搜索字
    public function doSK(){
        if(empty($_POST['sk_name'])){$this->error("关键字名称不能为空");}

        $data['sk_name']    = t($_POST['sk_name']);
        $data['sk_url']     = $_POST['sk_url'];
        $data['sk_text']    = t($_POST['sk_text']);
        $data['sort']       = t($_POST['sort']) ? : 999;
        $data['ctime']      = time();
        $data['uid']        = $this->mid;
        if($_GET['id']){
            $res = M('search_keywords')->where(['id'=>$_GET['id']])->save($data);
            if($res){
                $this->assign('jumpUrl',U('admin/SearchKeywords/index'));
                $this->success("编辑成功");
            }else{
                $this->error("编辑失败");
            }
        }else{
            $res = M('search_keywords')->add($data);
            if($res){
                $this->assign('jumpUrl',U('admin/SearchKeywords/index'));
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }
    }

    //操作搜索词
    public function delOptSk(){
        $ids = implode(",",$_POST['ids']);
        $ids = trim(t($ids),",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
        $msg = array();
        $where = array(
            'id'=>array('in',$ids)
        );
        $is_del = M('search_keywords')->where($where)->getField('is_del');
        if($is_del == 1){
            $data['is_del'] = 0;
        }else{
            $data['is_del'] = 1;
        }
        $res = M('search_keywords')->where($where)->save($data);
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
    //操作搜索词
    public function operationSk(){
        if(!intval($_POST['id'])){
            $this->ajaxReturn('参数错误',0);
        }
        if(t($_POST['type']) == 'yes_color'){
            $data['is_color'] = 1;
            $res = M('search_keywords')->where(['id'=>intval($_POST['id'])])->save($data);
        }else if(t($_POST['type']) == 'no_color'){
            $data['is_color'] = 0;
            $res = M('search_keywords')->where(['id'=>intval($_POST['id'])])->save($data);
        }else if(t($_POST['type']) == 'open'){
            $data['is_del'] = 0;
            $res = M('search_keywords')->where(['id'=>intval($_POST['id'])])->save($data);
        }else if(t($_POST['type']) == 'close'){
            $data['is_del'] = 1;
            $res = M('search_keywords')->where(['id'=>intval($_POST['id'])])->save($data);
        }else if(t($_POST['type']) == 'delete'){
            $res = M('search_keywords')->where(['id'=>intval($_POST['id'])])->delete();
        }else{
            $this->ajaxReturn('参数错误',0);
        }
        if($res){
            $this->ajaxReturn('操作成功',1);
        }else{
            $this->ajaxReturn('操作失败',0);
        }

    }
}
