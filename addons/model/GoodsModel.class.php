<?php
/**
 * 直播模型 - 业务逻辑模型
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class GoodsModel extends Model{
	protected $tableName = 'goods';
    public $mid = 0;

	/**
	 * 根据条件获取所有的商品信息
	 * @param $limit
	 *        	结果集数目，默认为20
	 */
	public function _getGoodsList($limit,$order,$map){
		$data = $this->order($order)->where($map)->findPage($limit);
		return($data);
	}

	/**
	 * 根据条件获取某条商品信息
	 * @param $limit
	 *        	结果集数目，默认为20
	 */
	public function findGoodsInfo($map){
		$data = $this->where($map)->find();
		return($data);
	}

    /**
     * 根据条件获取所有的商品信息
     * @param $limit
     *        	结果集数目，默认为20
     */
    public function _getGoodsOrderList($limit,$order,$map){
        $data = M('goods_order')->order($order)->where($map)->findPage($limit);
        return($data);
    }
    
    /**
     * @name 获取列表
     * @param array $map 查询列表条件
     */
    public function getList($map = array(),$order = 'ctime desc',$limit = 10){
        $data = $this->where($map)->order($order)->findPage($limit);
        if($data['data']){
            $goods_category = model('GoodsCategory');
            foreach($data['data'] as &$v){
                $v['cover_ids'] = $v['cover'];
                $v['cover'] = cutImg($v['cover'] ,  160 , 160);
                $v['status'] = (int)$v['status'];
                $v['uid'] = (int)$v['uid'];
                $v['price'] = (int)$v['price'];
                $v['stock'] = (int)$v['stock'];
                $v['fare'] = (int)$v['fare'];
                $v['goods_id'] = (int)$v['id'];
                $v['num'] = (int)M('goods_order')->where('goods_id='.$v['goods_id'])->count() ?:0;
                $v['goods_category_id'] = intval($v['goods_category']);
                $v['goods_category'] = $goods_category->getCateTitleById($v['goods_category']);
                    //获取商品数量
                $v['goods_count'] = (int)model('GoodsOrder')->where('goods_id='.$v['goods_id'])->sum('count') ? : 0;
                unset($v['id']);
            }
        }
        return $data;
    }
    /**
     * @name 根据积分商品ID 获取信息
     */
    public function getInfoByGoodsId(int $goods_id){
        $info = [];
        if($goods_id){
            $info = $this->where(array('id'=>$goods_id,'status'=>1,'is_del'=>0))->find();
            $goods_category = model('GoodsCategory');
            $info['cover_id'] = (int)$info['cover'];
            $info['cover'] = cutImg($info['cover'] ,  280 , 160);
            $info['status'] = (int)$info['status'];
            $info['price'] = (int)$info['price'];
            $info['stock'] = (int)$info['stock'];
            $info['fare'] = (int)$info['fare'];
            $info['goods_id'] = (int)$info['id'];
            $info['num'] = (int)M('goods_order')->where('goods_id='.$info['goods_id'])->count() ?:0;
            $info['goods_category_id'] = intval($info['goods_category']);
            $info['goods_category'] = $goods_category->getCateTitleById($info['goods_category']);
            $info['goods_count'] = (int)model('GoodsOrder')->where('goods_id='.$info['goods_id'])->sum('count') ? : 0;
            unset($info['id']);
        }
        return empty($info) ? [] : $info;
    }
    /**
     * @name 根据积分商品ID获取商品状态
     */
    public function getStatusByGoodsId(int $goods_id){
        if($goods_id){
            $status = $this->where(['id' => $goods_id])->getField('status');
            if($status !== null){
                return (int)$status;
            }
        }
        return false;
    }
    /**
     * @name 检测某商品的库存量是否足够
     * @param int $goods_id 积分商品ID
     * @param int $get_num 需求量
     */
    public function checkGoodsNum(int $goods_id , int $get_num){
        $n_count = $this->where(['id' => $goods_id])->getField('stock');
        if($n_count - $get_num < 0){
            return false;
        }
        return true;
    }
    /**
     * @name 获取某个积分商品兑换所需要的积分数量
     */
    public function getPriceByGoodsId(int $goods_id){
        if($goods_id){
            $price = $this->where(['id' => $goods_id])->getField('price');
            if($price !== null){
                return (int)$price;
            }
        }
        return false;
    }
    
    /**
     * @name 获取积分商品兑换排行榜
     */
    public function getRankGoods($limit = 10){
        $subSql = '(SELECT goods_id,COUNT(id) AS num FROM '.C('DB_PREFIX').'goods_order GROUP BY goods_id) AS o';
        $data =$this->join('g INNER JOIN '.$subSql.' ON o.goods_id = g.id')->where('g.status=1 AND is_del=0')->order('o.num DESC')->findPage($limit);
        if($data['data']){
            $goods_category = model('GoodsCategory');
            foreach($data['data'] as &$v){
                $v['cover_ids'] = $v['cover'];
                $v['cover'] = cutImg($v['cover'] ,  160 , 160);
                $v['status'] = (int)$v['status'];
                $v['uid'] = (int)$v['uid'];
                $v['price'] = (int)$v['price'];
                $v['stock'] = (int)$v['stock'];
                $v['fare'] = (int)$v['fare'];
                $v['goods_id'] = (int)$v['id'];
                $v['num'] = (int)$v['num'];
                $v['goods_category'] = $goods_category->getCateTitleById($v['goods_category']);
                $v['goods_count'] = (int)model('GoodsOrder')->where('goods_id='.$v['goods_id'])->sum('count') ? : 0;
                unset($v['id']);
            }
        }
        return $data;
    }
    /**
     * @name 获取分类数据
     * @param int $topLimit 表示获取几个顶级分类的数据
	 * @param int $limit 每个分类下的数据条数
	 * @param int $cate_id 作为顶级分类的父ID
	 * @param int $cate_limit 分类的条数
	 * @return array 数据数组
     */
    public function getListForCate($map = array() ,$topLimit = -1,$limit = 6,$cate_id = 0,$cate_limit = 20){
        $cate_id = intval($cate_id) ?:0; 
        $cate_mod = model('CategoryTree')->setTable('goods_category');
        if($cate_id == 0){
            $top_cate = $cate_mod->getAllCategory(array('pid'=>$cate_id),$cate_limit);
            //$top_cate = M('goods_category')->where(array('pid'=>$cate_id))->select();
        }else{
            $top_cate = array($cate_mod->getCategoryById($cate_id));
        }
        $list = [];
        foreach($top_cate as $v){
            if($topLimit >0 && (count($list) >= $topLimit)){
                return $list;
            }
            $sub_cate = $cate_mod->getSubCateIdByPid($v['id']);
			$where = array( 'goods_category'=>array('in',$sub_cate) ,'status'=>1,'is_del'=>0);
			$where = $map ? array_merge($where , $map) : $where;
            $data = $this->getList($where ,'ctime desc',$limit);
            if($data['data'] && $data['gtLastPage'] !== true){
                $item = [
                    'cate_id' => (int)$v['id'],
                    'cate_name' => $v['title'],
                    'middle_ids' => $v['middle_ids'],
                    'cate_cover'=> getCover($v['middle_ids']),
                    'data_list'    => $data['data']
                ];
                array_push($list,$item);
            }
        }
        return $list;
    }
    
    public function getGoodsById($good_id){
        $info = $this->getInfoByGoodsId($good_id);
        if($info){
            $info['user_info'] = model('User')->formatForApi(array(),$info['uid'],$this->mid);
        }
        return $info;
    }
    
    /**
     * 库存减少
     */
    public function setStockDesc($good_id,$count = 1){
        $map['id'] = $good_id;
        $this->startTrans();
        $id = $this->where($map)->save(array('stock'=>array('exp','stock-'.$count)));
        $res = $this->where($map)->save(array('goods_count'=>array('exp','goods_count+'.$count)));
        if($res && $id){
            $this->commit();
            return true;
        }
        $this->rollback();
        return false;
    }

}