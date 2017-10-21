<?php
/**
 * @name 积分商城API
 */

class GoodsApi extends Api
{
    protected $mod = ''; //当前操作的模型

    /**
     * 初始化模型
     */
    public function _initialize()
    {
        //$this->mod      = D('ZyGoods','classroom');
        $this->mod      = model('Goods');
        $this->mod->mid = $this->mid;
    }
    /**
     * @name 获取积分商品列表
     */
    public function getGoodsList()
    {
        $map['status']                  = 1;
        $this->keyword && $map['title'] = array('like', '%' . h($this->keyword) . '%');
        if ($this->goods_category) {
            $cate                  = model('GoodsCategory')->getChildsCategory($this->goods_category);
            $cate                  = $this->getValues($cate, 'goods_category_id');
            $map['goods_category'] = ['in', $cate . $this->goods_category];
        }
        $data = $this->mod->getList($map, 'ctime desc', $this->count);
        if ($data['gtLastPage'] === true) {
            $this->exitJson((object) [], 1, '暂时没有更多积分商品啦');
        }
        $data['data'] ? $this->exitJson($data['data'], 1) : $this->exitJson((object) [], 1, '暂时没有积分商品啦');
    }
    /**
     * @name 递归取的多维数组的值
     */
    public function getValues($data = [], $field = [], $symbol = ',')
    {
        $field = is_array($field) ? $field : explode(',', $field);
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    $resStr .= $this->getValues($v, $field, $symbol);
                } else {
                    if ($field) {
                        in_array($k, $field) && $resStr .= $v . $symbol;
                    } else {
                        $resStr .= $v . $symbol;
                    }
                }
            }
        }
        return $resStr;
    }
    /**
     * @name 积分兑换商品
     */
    public function exchange()
    {
        if (!is_numeric($this->goods_id) || !$g_status = $this->mod->getStatusByGoodsId($this->goods_id)) {
            $this->exitJson((object) [], 0, '该积分商品已下架');
        }
        //检测库存
        $exchange_num = (int) $this->num ?: 1;
        if (true !== $this->mod->checkGoodsNum($this->goods_id, $this->num)) {
            $this->exitJson((object) [], 0, '该积分商品库存不足');
        }
        //检测用户积分是否足够
        $creditModel = model('Credit');
        $account     = $creditModel->getUserCredit($this->mid);
        $score       = $account['credit']['score']['value'];
        $goods_price = $this->mod->getPriceByGoodsId($this->goods_id);
        $total_price = $exchange_num * $goods_price;
        if ($score - $total_price < 0) {
            $this->exitJson((object) [], 0, '你的积分不足');
        }
        if (!$this->address_id) {
            $this->exitJson((object) [], 0, '请选择收获地址');
        }
        $fare      = $this->mod->where('id=' . $this->goods_id)->getField('fare');
        $lastPrice = $total_price + $fare;
        //兑换商品商品
        $creditModel->setUserCredit($this->mid, ['score' => -$lastPrice]);
        $data = [
            'uid'        => $this->mid,
            'sid'        => 0, //待设置
            'goods_id'   => $this->goods_id,
            'price'      => $total_price,
            'fare'       => $fare,
            'count'      => $exchange_num,
            'address_id' => $this->address_id,
        ];
        if ($id = model('GoodsOrder')->addExchangeGoods($data)) {
            $map['id'] = $id;
            // 更新库存
            $this->mod->setStockDesc($this->goods_id, $exchange_num);
            $data = model('GoodsOrder')->getList($map, 1);

            $this->exitJson((object) $data['data'][0], 1, '兑换成功');
        }
        $this->exitJson((object) [], 0, '兑换失败,请重新尝试');
    }
    /**
     * @name 获取兑换的商品列表
     */
    public function getMyGoodsList()
    {
        $map['uid']                     = $this->mid;
        $map['is_del']                  = 0;
        $this->keyword && $map['title'] = array('like', '%' . h($this->keyword) . '%');
        $data                           = model('GoodsOrder')->getList($map, $this->count);
        if ($data['gtLastPage'] === true) {
            $this->exitJson((object) [], 1, '暂时没有更多积分商品');
        }
        $data['data'] ? $this->exitJson($data['data'], 1) : $this->exitJson((object) [], 1, '暂时没有积分商品');
    }
    /**
     * @name 获取积分商品分类
     */
    public function getGoodsCate()
    {
        $cate_id = (int) $this->goods_category_id ?: 0;
        $data    = model('GoodsCategory')->getChildsCategory($cate_id);
        $data ? $this->exitJson($data, 1) : $this->exitJson((object) [], 1, '暂时没有分类');
    }

    /**
     * @name 获取首页
     */
    public function index()
    {
        $type = ['rank', 'list'];
        if (in_array($this->type, $type)) {
            $type = [$this->type];
        }
        $data = [];
        foreach ($type as $v) {
            $list = [];
            switch ($v) {
                case 'rank':
                    $rank = $this->mod->getRankGoods($this->count);
                    if ($rank['data'] && $rank['gtLastPage'] !== true) {
                        $list = $rank['data'];
                    }
                    break;
                case 'list':
                default:
                    //$list = $this->mod->getListForCate($this->floor_count, $this->count, $this->cate_id);
                    $map['status']                  = 1;
                    $this->keyword && $map['title'] = array('like', '%' . h($this->keyword) . '%');
                    if ($this->goods_category) {
                        $cate                  = model('GoodsCategory')->getChildsCategory($this->goods_category);
                        $cate                  = $this->getValues($cate, 'goods_category_id');
                        $map['goods_category'] = ['in', $cate . $this->goods_category];
                    }
                    $datas = $this->mod->getList($map, 'ctime desc', $this->count);
                    if ($datas['gtLastPage'] === true) {
                        $list = [];
                    } else {
                        $list = $datas['data'] ?: [];
                    }
            }
            $data[$v] = $list;
        }
        $this->exitJson($data, 1);

    }

    /**
     * @name 获取积分详情
     */
    public function getDetail()
    {
        $good_id = intval($this->goods_id);
        if ($good_id) {
            $info = $this->mod->getGoodsById($good_id);
        }
        $info ? $this->exitJson($info, 1) : $this->exitJson((object) array(), 0, '未能查询到积分商品详情');
    }
}
