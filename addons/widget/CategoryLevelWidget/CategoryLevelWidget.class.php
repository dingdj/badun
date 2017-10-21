<?php
/**
 * 分类选择 widget
 * @example W('VideoLevel',array('type'=>1,'template'=>'admin_level','default'=>'1,5,7'))
 * @author MissZhou
 * @version TS3.0
 */
class CategoryLevelWidget extends Widget {
	/**
	 * @param integer type 分类类型
	 * @param string template 模板名称
	 * @param integer default 【1,5,7】
	 */
	protected $_table = '';

	public function render($data) {
		$var = array();
		$var['template']   = 'default';//模板名称
		$var['default']    = '';//默认选择
		
        is_array($data) && $var = array_merge($var,$data);
		
		$template = $var['template'].'.html';
		$this->_table = $data['table'];

		//渲染模版
        $content = $this->renderFile(dirname(__FILE__)."/".$template,$var);
        unset($var,$data);
        //输出数据
        return $content;
	}
	
	
	/**
	 * 获取所有的顶级分类
	 */
	public function getParentLevelAll(){
		//预先取第一级分类
		$map['pid']      = array('eq',0);
		$table = t( $_POST['table'] );
		$_parentLevelAll = M($table)->where($map)->order('`sort` DESC')->select();
		$parentLevelAll = array();
		foreach((array)$_parentLevelAll as $key=>$value){
			$parentLevelAll[$value[$table.'_id']] = $value;
		}
		exit( json_encode($parentLevelAll ? $parentLevelAll : null) );
	}
	
	/**
	 * 获取所有的子集
	 * @param integer type 分类类型 1:课程分类，2:点播分类
	 * @param integer pid 父级ID
	 */
	public function getChildrenAll($pid){
		$pid   = intval($pid) ? intval($pid) : intval($_POST['pid']);
		$table = t( $_POST['table'] );
		
		//预先取第一级分类
		$map['pid']  = array('eq',$pid);
		$_parentLevelAll = M($table)->where($map)->order('`sort` DESC')->select();
		
		$parentLevelAll = array();
		foreach((array)$_parentLevelAll as $key=>$value){
			$parentLevelAll[$value[$table.'_id']] = $value;
		}
		exit( json_encode($parentLevelAll ? $parentLevelAll : null) );
	}

	
	
	
	
}