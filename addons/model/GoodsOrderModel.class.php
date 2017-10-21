<?php
/**
 * @name 积分商品模型
 * @author martinsun 
 * @version v1.0
 */
class GoodsOrderModel extends Model
{
	public $tableName   = 'goods_order'; //映射到投票表
	
    /**
     * @name 获取列表
     */
    public function getList(array $map,$order,$limit = 10){
        $list = $this->order($order)->where($map)->findPage($limit);
            if($list['data']){
            $goods_model = model('Goods');
            foreach($list['data'] as &$v){
                $v['goods_order_id'] = (int)$v['id'];
                $v['goods_info'] = $goods_model->getInfoByGoodsId($v['goods_id']);
                unset($v['goods_id'],$v['id'],$v['is_del']);
            }
        }
        return $list;
    }
    
    /**
     * @name 添加兑换的商品
     */
    public function addExchangeGoods(array $info){
        if(!is_array($info)){
            return 0;
        }
        $info['ctime'] = time();
        return $this->add($info);
    }

    /**
     * @name 获取特定用户兑换商品记录
     * @uid 用户ID
     */
    public function getUserGoodsList($uid,$order,$limit = 10){
        $map = array('uid'=>$uid,'is_del'=>0);
        $list = $this->order($order)->where($map)->findPage($limit);
        if($list['data']) {
            $goods_model = model('Goods');
            $address_model = model('Address');
            foreach ($list['data'] as &$v) {
                $v['goods_order_id'] = (int)$v['id'];
                $v['goods_info'] = $goods_model->getInfoByGoodsId($v['goods_id']);
                $v['address_info'] = $address_model->getAddressById($v['address_id']);
                unset($v['goods_id'], $v['id'], $v['is_del'],$v['address_id']);
            }
        }
        return $list;
    }
}
?>