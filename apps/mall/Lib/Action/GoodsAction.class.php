<?php

/**
 * Eduline积分商城控制器
 * @author Ashang <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/classroom/Lib/Action/CommonAction.class.php');
class GoodsAction extends Action 
{
    /**
     *积分商城 商城列表方法
     */
    public function index() {
        if($_GET['type']){
            $ranking_list =  model('Goods')->getRankGoods();
            //dump($ranking_list);
            $this->assign('ranking_list',$ranking_list['data']);
        }else{
            $map="status=1 AND is_del=0 ";
            $orders = array (
                'default' => 'ctime DESC',
                'price_up' => 'price ASC',
                'price_down' => 'price DESC',
                'hot' => 'stock DESC'
            );
            $limit = 20;

            $sort_type= t($_GET['sort_type']);
            //获取分类列表
            $cate_id = t($_GET ['cateId']);
            if($cate_id > 0){
                $cateId = explode(",", $cate_id );
            }
            $good_category = model('GoodsCategory')->getTopCategory();
            $this->assign ( 'good_category', $good_category);

            if($cateId){
                $title=model('GoodsCategory')->where('goods_category_id='.end($cateId))->getField("title");
                $this->assign('title',$title);
                $this->assign('cate_id_str',$cateId);
                $selCate = M("goods_category")->where(array('pid'=>$cateId[0]))->field("goods_category_id,title")->findALL();
                $this->assign('category_two',$selCate);
            }
            if($cateId[1]){
                $selChildCate = M("goods_category")->where(array('pid'=>$cateId[1]))->field("goods_category_id,title")->findALL();
                $this->assign('category_three',$selChildCate);
            }
            $this->assign('cateId', $cateId[0]);
            $this->assign('cate_id', $cateId[1]);
            $this->assign('cate_ids', $cateId[2]);

            if($cateId>0){
                $good_category = implode(',',$cateId);
                $map .="AND fullcategorypath like '%,$good_category,%'";
            }
            if($sort_type){
                switch ($sort_type) {
                    case 1:
                        $map .=" AND is_best = $sort_type";
                        break;
                    case hot:
                        $order = $orders [$sort_type];
                        break;
                    case price_up:
                        $order = $orders [$sort_type];
                        break;
                    case price_down:
                        $order = $orders [$sort_type];
                        break;
                    default;
                }
            }
            if($_GET['lower']){
                list($lower,$toper) = explode(',',$_GET['lower']);
                if($toper &&  $lower >= 1){
                    $map .=" AND (t_price >= $lower AND t_price <= $toper)";
                }
            }
            $data = model('Goods')->getList($map,$order,$limit);
            if ($data['data']) {
                $this->assign('data', $data);
                $this->assign('listData', $data['data']);
            }
        }

        //精选课程
        $video  = D('ZyVideo')->where('is_del=0')->order('video_comment_count desc','video_collect_count desc','video_score desc','video_order_count desc')->findAll();
        foreach ($video as $key=>$val) {
            $video[$key]['school_title'] = model('School')->getSchooldStrByMap('id='.$val['mhm_id'],'title');
        }
        $bestVideo  = D('ZyVideo')->where(array('is_del'=>0,'is_best'=>1))->order('video_comment_count desc','video_collect_count desc','video_score desc','video_order_count desc')->limit(3)->findAll();
        foreach ($bestVideo as $key=>$val) {
            $bestVideo[$key]['school_title'] = model('School')->getSchooldStrByMap('id='.$val['mhm_id'],'title');
        }
        //猜你喜欢
        $guess_you_like = D('ZyGuessYouLike')->getGYLData(0,$this->mid,7);
        foreach ($guess_you_like as $key=> $val){
            $section = M('zy_video_section')->where(['pid'=>['neq',0],'vid'=>$val['id']])->field('is_free,vid')->findAll();
            foreach ($section as $k => $v){
                if($v['is_free'] == 1){
                    $datas[$key]['free_status'] = '可试听';
                }
            }
            $mhmName = model('School')->getSchoolInfoById($val['mhm_id']);
            $datas[$key]['mhmName'] = $mhmName['title'];
            //教师头像和简介
            $teacher = M('zy_teacher')->where(array('id'=>$val['teacher_id']))->find();
            $guess_you_like[$key]['teacherInfo']['name'] = $teacher['name'];
            $guess_you_like[$key]['teacherInfo']['inro'] = $teacher['inro'];
            $guess_you_like[$key]['teacherInfo']['head_id'] = $teacher['head_id'];
            //直播课时
            if($val['type'] == 2){
                $live_data = $this->live_data($val['live_type'],$val['id']);
                $guess_you_like[$key]['live']['count'] = $live_data['count'];
                $guess_you_like[$key]['live']['now'] = $live_data['now'];
            }
        }
        $this->assign("video",$video);
        $this->assign("guess_you_like",$guess_you_like);
        $this->assign("bestVideo",$bestVideo);
        $this->assign('sort_type', $sort_type);
        $this->display();
    }
    //直播数据处理
    protected function live_data($live_type,$id)
    {
        $count = 0;
        //第三方直播类型
        if($live_type == 1){
            $live_data = M('zy_live_zshd')->where(array('live_id'=>$id,'is_del'=>0))->order('invalidDate asc')->select();
            if($live_data){
                foreach ($live_data as $item=>$value){
                    if($value['invalidDate'] < time()){
                        $count = $count + 1 ;
                    }
                }
            }else {
                $live_data = array(1);
                $count = 1;
            }
        }elseif ($live_type == 3){
            $live_data = M('zy_live_gh')->where(array('live_id'=>$id,'is_del'=>0))->order('endTime asc')->select();
            if($live_data){
                foreach ($live_data as $item=>$value){
                    if($value['endTime'] < time()){
                        $count = $count + 1 ;
                    }
                }
            }else{
                $live_data = array(1);
                $count = 1;
            }
        }
        $live_data['count'] = count($live_data);
        $live_data['now'] = $count;

        return $live_data;
    }
    /**
      * @name 获取子分类
      */
    /*public function getSubCategory(){
        $pid = $_POST['pid'];
        $list = model('GoodsCategory')->getChildCategory($pid);
        if($list){
            $res = [
                'status'=>1,
                'data' => $list
            ];
        }else{
            $res = [
                'status'=>0,
                'message' => '暂无子分类'
            ];
        }
        echo json_encode($res);exit;
    }*/

    /**
      * @name 获取商品列表数据
      */
    /*public function getList(){
        $good_category= intval($_GET['good_category']);
        $sort_type= intval($_GET['sort_type']);
        $order = "id desc";
        $limit = 20;
        if($good_category>0){
            $map .="fullcategorypath like '%,$good_category,%'";
        }
        if($sort_type > 0){
            switch ($sort_type) {
                case 1:
                    $map .="AND is_best = $sort_type";
                    break;
                case 2:
                default:
                    $order="ctime desc";
                    break;
            }
        }
        $data = model('Goods')->getList($map,$order,$limit);
        if ($data['data']) {
            $this->assign('listData', $data['data']);
            $this->assign('good_category', $good_category);
            $this->assign('sort_type', $sort_type);
            $html = $this->fetch('index_list');
        }else{
            $html="<div style=\"margin-top:20px;\">对不起，没有找到符合条件的商品T_T</div>";
        }
        $data['data'] = $html;
        echo json_encode($data);
        exit;
    }*/

    /**
      * @name 获取商品详情
      */
    public function view(){
        $uid = intval($this->mid);
        $goods_id = intval($_GET['id']);
        //获取商品详情
        $data = model('Goods')->getInfoByGoodsId($goods_id);
        if(!$data['goods_id']){
            $this->assign('jumpUrl',U('mall/Index/index'));
            $this->error('该商品不存在!');
        }
        //获取商品兑换记录
        $order = "ctime DESC";
        $goodsOrder = model('GoodsOrder')->getList(array('goods_id'=>$goods_id),$order);
        foreach ($goodsOrder['data'] as $key => $val) {
            $goodsOrder['data'][$key]['uname'] = getUserName($val['uid']);
            $goodsOrder['data'][$key]['ctime'] = date("Y-m-d H:i",$val['ctime']);
        }
        //获取用户收货地址
        $address = M('Address')->where(array('uid'=>$uid,'is_default'=>1,'is_del'=>0))->field('id,location,address')->find();
        $address = $address ?: M('Address')->where(array('uid'=>$uid,'is_del'=>0))->field('id,location,address')->find();
        $url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $this->assign('data',$data);
        $this->assign('address',$address);
        $this->assign('goodsOrder',$goodsOrder['data']);
        $this->assign('url',$url);
        $this->display();
    }
    
    public function getAddress(){
        $uid = intval($this->mid);
        $address_id = intval($_POST['address_id']);
        if($address_id){
            $address = model('Address')->where(array('uid'=>$this->mid,'id'=>$address_id,'is_del'=>0))->field('id,location,address')->find();
            if(!$address)exit(json_encode(array('status'=>'0','info'=>'更换收货地址失败')));
            exit(json_encode(array('status'=>'1','info'=>'更换收货地址成功','data'=>$address)));
        }else{
            $address_list = model('Address')->where(array('uid'=>$uid,'is_del'=>0))->field('id,location,address')->findALL();
            $this->assign('address_list',$address_list);
            $this->display('address_list');
        }
    }
    /**
      * @name 添加商品订单
      */
    public function doSaveGoods(){
        $data = array(
            'uid' => intval($this->mid),
            'goods_id' => intval($_POST['goods_id']),
            'price' => intval($_POST['total']),
            'fare' => intval($_POST['fare']),
            'count' => intval($_POST['count']),
            'address_id' => intval($_POST['address_id']),
            'lastPrice' =>intval($_POST['total'])+intval($_POST['fare']),
        );
        $stock = model('Goods')->where(array('id'=>$data['goods_id']))->getField('stock');

        if($data['count'] > $stock){
            exit(json_encode(array('status'=>'0','message'=>'对不起，仓库剩余商品数量不足！')));
        }else{
			$credit = model('Credit')->getUserCredit($this->mid);
			foreach ($credit['credit'] as $key => $val) {
				$credit = $val['value'];
			}
			if($credit >= $data['lastPrice']){
				//$credit = model('Credit')->setUserCredit($data['uid'],array('score'=>"-".$data['price']));
                $credit = model('Credit')->rmfreeze($data['uid'],$data['lastPrice']);
                if($credit){
                    $res = model('GoodsOrder')->addExchangeGoods($data);
                    $id = model('GoodsOrder')->getLastInsID();
                    $title = model('Goods')->where(array('id'=>$data['goods_id']))->getField('title');
                    $note = '购买商品'.$title;
                    model('Credit')->addCreditFlow($data['uid'],0,$data['lastPrice'],$id,'goods',$note);
                }
                $datas['goods_count'] = model('GoodsOrder')->where('goods_id='.$data['goods_id'])->sum('count');
                $datas['stock'] = $stock - $data['count'] ;
                model('Goods')->where(array('id'=>$data['goods_id']))->save($datas);
			}else{
				 exit(json_encode(array('status'=>'0','message'=>'对不起，你的积分不足！')));
			}
            if(!$res)exit(json_encode(array('status'=>'0','message'=>'兑换失败')));
            exit(json_encode(array('status'=>'1','message'=>'兑换成功')));
        }
        
    }
    /**
      *@name 修改用户收货地址
      */
    public function chargeAddress(){
        $uid = intval($this->mid);

        $address = model('Address')->getList(array('uid'=>$uid));
        //dump($address);exit;
        $this->assign('address',$address['data']);
        $this->display('address');
    }
}

