<?php
class SingleModel extends Model{
	
	/**
	 * 获取单页分类
	 * @return array
	 */
    public function getCate(){
		return M('single_category')->order('sort asc')->getField('single_category_id,title');
    }
    
    /**
     *获取单页分类及分类下的单页 
     */
    public function getList(){
    	$cate = M('single_category')->order('sort asc')->getField('single_category_id,title');
    	foreach($cate as $k => $val){
			$cate_list[$val]['url'] = M('single_category')->where('single_category_id = '.$k)->getField('url');
			$cate_list[$val]['child'] = M('single')->where('cate_id = '.$k.' and is_del=0')->findAll();
		}
    	return $cate_list;
    }

	/**
	 * 根据分类获取底部导航数据
	 * @param $cate_id  int 分类id
     * @param $field  string 所需字段
	 * @return void
	 */
	public function getNavById($cate_id ,$field){
		$map['cate_id'] = $cate_id;
		$map['is_del']  = 0;
		$order = 'sort ASC';
		$list = $this->where($map)->order($order)->field($field)->findAll();

		return $list;
	}
	 
}