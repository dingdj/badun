<?php

/**
 * Eduline积分商城控制器
 * @author Ashang <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/classroom/Lib/Action/CommonAction.class.php');
class IndexAction extends Action 
{
    /**
     * Eduline积分商城首页方法
     * @return void
     */
    public function index() {
        //加载首页头部轮播广告位
        $ad_map = array('is_active' => 1,'display_type' => 3,'place' => 5);
        $ad_list = M('ad')->where($ad_map)->order('display_order DESC')->find();

        //序列化广告内容
        $ad_list = unserialize($ad_list['content']);

        //最新公告
        $new_suggest = M('suggest')->where(['type'=>1])->order('ctime DESC,id DESC')->getField('content');

        $credit = model('Credit')->getUserCredit($this->mid);
        foreach ($credit['credit'] as $key => $val) {
            $credit = $val['value'];
        }
        $vip = D('ZyLearnc')->getUserVip($this->mid);

        //获取顶部分类下的所有数据
        $good_category = model('Goods')->getListForCate( array('is_best'=>1) , -1,6,0);

        //获取兑换排行榜数据
        $ranking_list =  model('Goods')->getRankGoods(12);

        //获取最新商品
        $new_goods = model('Goods')->where(array('status'=>1,'is_del'=>0))->order('ctime desc')->limit(8)->select();

        $this->assign('ad_list',$ad_list);
        $this->assign('new_suggest',$new_suggest);
        $this->assign('credit',$credit);
        $this->assign('vip',$vip);
        $this->assign('good_category',$good_category);
        $this->assign('ranking_list',$ranking_list['data']);
        $this->assign('ranking',$ranking_list);
        $this->assign('new_goods',$new_goods);
        $this->display();
    }
    //首页查看更多兑换排行榜
    public function getRankingList(){
        $data =  model('Goods')->getRankGoods();

        $this->assign("data", $data['data']);
        $data['data'] = $this->fetch('ranking_list');
        echo json_encode($data);
        exit;
    }
    /*
    *获取商品列表
    */
    public function getGoodsList() {

        $data = model('Goods')->getListForCate(array() ,-1,8);
        if ($data['data']) {
            $html = $this->fetch('index_list');
        }else{
            $html="<div style=\"margin-top:20px;\">对不起,暂时没有数据T_T</div>";
        }
        $data['data'] = $html;
        echo json_encode($data);
        exit;
    }
}

