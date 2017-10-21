<?php
/**
 * 积分商城分类模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class GoodsCategoryModel extends Model
{

    protected $tableName = 'goods_category';

    /**
     *
     * 取得顶级分类
     * @return array
     */
    public function getTopCategory()
    {
        return $this->getChildCategory(0);
    }

    /**
     * 取得下级分类
     * @param $pid 上级分类ID
     * @param $type 类型 1:课程分类，2:点播分类
     * @return array
     */
    public function getChildCategory($pid)
    {
        return $this->where(array('pid' => $pid))->order('sort DESC,goods_category_id')->select();
    }

    /**
     * @name 获取指定分类下的所有子分类
     * @param int $pid 父类ID
     * @return array 数据信息
     */
    public function getChildsCategory($pid = 0)
    {
        $list = $this->getChildCategory($pid);
        if (is_array($list) && !empty($list)) {

            foreach ($list as $k => &$v) {
                $v['goods_category_id'] = (int) $v['goods_category_id'];
                $child                  = $this->getChildsCategory($v['goods_category_id']);
                if ($child) {

                    $v['childs'] = $child;
                }
                unset($v['sort'], $v['pid']);
            }
            $allCate = array(
                'goods_category_id' => $pid,
                'title'             => '全部',
                'middle_ids'        => null,
            );
            array_unshift($list, $allCate);

            unset($v);
        }
        return $list ?: [];
    }
    /**
     * @name 根据分类ID获取分类名称
     */
    public function getCateTitleById($cate_id = 0)
    {
        return $this->where(['goods_category_id' => $cate_id])->getField('title');
    }
}
