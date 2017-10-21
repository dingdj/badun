<?php
/**
 * 文库分类
 * @author martinsun<syh@sunyonghong.com>
 * @version v1.0
 */
class DocCategoryModel extends Model{
	protected $tableName = 'doc_category';
    /**
     * @name 获取文库分类列表
     */
    public function getList(){
        $data = $this->select();
        return $data ?:[];
    }
    /**
     * @name 根据分类ID获取分类名称
     */
    public function getCateNameById($id = 0){
        return $this->where('doc_category_id ='.$id)->getField("title");
    }
    /**
     * @name 获取文件分类
     */
    public function getCategoryList($pid = 0){
        $data = $this->where('pid='.$pid)->field(['title','doc_category_id'])->order('sort ASC')->select();
        if($data){
            foreach($data as &$v){
                $v['doc_category_id'] = (int)$v['doc_category_id'];
                $childs = $this->getCategoryList($v['doc_category_id']);
                if($childs){
                    $v['childs'] = $childs;
                }
            }
        }
        return $data ?:[];
    }
    /**
     * @name 获取顶级分类
     */
    public function getDocList(){
        return $this->where('pid=0')->order('sort ASC')->select();
    }
    /**
     * @name 取得下级分类
     * @param $pid 上级分类ID
     */
    public function getChildCategory($pid){
        return $this->where(array('pid'=>$pid))->order('sort DESC,doc_category_id')->select();
    }
}