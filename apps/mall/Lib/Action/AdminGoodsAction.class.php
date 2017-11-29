<?php
/**
 * 后台商城管理
 * @author wangjun@chuyouyun.com
 * @version chuyouyun1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminGoodsAction extends AdministratorAction
{

    /**
     * 初始化，
     */
    public function _initialize(){
        $this->pageTitle['index']       = '列表';
        //$this->pageTitle['disable']     = '禁用商品列表';
        $this->pageTitle['close']       = '回收站';
        $this->pageTitle['addGoods']    = '添加';

        $this->pageTab[] = array('title'=>'列表','tabHash'=>'index','url'=>U('mall/AdminGoods/index'));
        //$this->pageTab[] = array('title'=>'禁用商品列表','tabHash'=>'disable','url'=>U('mall/AdminGoods/disable'));
        $this->pageTab[] = array('title'=>'回收站','tabHash'=>'close','url'=>U('mall/AdminGoods/close'));
        $this->pageTab[] = array('title'=>'添加','tabHash'=>'addGoods','url'=>U('mall/AdminGoods/addGoods'));

        parent::_initialize();
    }

    /**
     * 商品列表
     */
    public function index(){
        $_REQUEST['tabHash'] = 'index';

        $this->pageKeyList = array('id', 'uid', 'uname', 'title', 'cover', 'price', 'stock', 'fare', 'info','details', 'status', 'ctime', 'DOACTION');
        //搜索字段
        $this->searchKey = array('id', 'title', 'price', 'stock', 'fare', 'status', array('ctime','ctime1'));
        $this->pageButton[] = array('title' => "搜索", 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "禁用", 'onclick' => "admin.delGoodsAll('delGoodsAll',2)");
        $this->pageButton[] = array('title' => "删除", 'onclick' => "admin.delGoodsAll('delGoodsAll',0)");

        $this->opt['status'] = array('0'=>'不限','1'=>"删除",'2'=>"正常",'3'=>"禁用");
        // 数据的格式化
        $order = 'id desc';
        $list = $this->_getGoodsList('index',20,$order);

        $this->displayList($list);
    }

    /**
     * 禁用商品列表
     */
    public function disable(){
        $_REQUEST['tabHash'] = 'disable';

        $this->pageKeyList = array('id', 'uid', 'uname', 'title', 'cover', 'price', 'stock', 'fare', 'info','details', 'status', 'ctime', 'DOACTION');
        //搜索字段
        $this->searchKey = array('id', 'title', 'price', 'stock', 'fare', array('ctime','ctime1'));
        $this->pageButton[] = array('title' => "搜索", 'onclick' => "admin.fold('search_form')");

        // 数据的格式化
        $order = 'id desc';
        $map['status'] = 2;
        $list = $this->_getGoodsList('disable',20,$order,$map);

        $this->displayList($list);
    }

    /**
     * 已删除商品列表
     */
    public function close(){
        $_REQUEST['tabHash'] = 'close';

        $this->pageKeyList = array('id', 'uid', 'uname', 'title', 'cover', 'price', 'stock', 'fare', 'info','details', 'status', 'ctime', 'DOACTION');
        //搜索字段
        $this->searchKey = array('id', 'title', 'price', 'stock', 'fare', array('ctime','ctime1'));
        $this->pageButton[] = array('title' => "搜索", 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "彻底删除", 'onclick' => "admin.delGoodsAll('delGoodsAll',3)");

        // 数据的格式化
        $order = 'id desc';
        $map['status'] = 0;
        $list = $this->_getGoodsList('close',20,$order,$map);

        $this->displayList($list);
    }

    /**
     * 添加商品
     */
    public function addGoods(){
        if(isset($_POST)){
            $exp = "/^-[1-9]\d*\.\d*|-0\.\d*[1-9]\d*$/";
            if(empty($_POST['title'])){$this->error("商品名称不能为空");}
            if(empty($_POST['goods_levelhidden'])){$this->error("请选择分类");}
            if(empty($_POST['cover'])){$this->error("请上传商品封面");}
            if(empty($_POST['info'])){$this->error("商品简介不能为空");}
            if($_POST['price'] == ''){$this->error("商品价格不能为空");}
            if(!is_numeric($_POST['price'])){$this->error('价格必须为数字');}
            if(preg_match($exp,$_POST['price'])){$this->error('价格必须为正整数');}
            if($_POST['stock'] == ''){$this->error("商品库存不能为空");}
            if(!is_numeric($_POST['stock'])){$this->error('商品库存必须为数字');}
            if($_POST['fare'] == ''){$this->error("商品库存不能为空");}
            if(!is_numeric($_POST['fare'])){$this->error('商品运费必须为数字');}
            if(preg_match($exp,$_POST['fare'])){$this->error('商品运费必须为正整数');}

            $myAdminLevelhidden 		= getCsvInt(t($_POST['goods_levelhidden']),0,true,true,',');  //处理分类全路径
            $fullcategorypath 			= explode(',',$_POST['goods_levelhidden']);
            $category 					= array_pop($fullcategorypath);
            $category					= $category == '0' ? array_pop($fullcategorypath) : $category; //过滤路径最后为0的情况
            $data['fullcategorypath']	= $myAdminLevelhidden; //分类全路径
            $data['goods_category']		 = $category == '0' ? array_pop($fullcategorypath) : $category;
            $goods_category_list = array_filter(explode(',', $myAdminLevelhidden ));
            $data['title']  = t($_POST['title']);
            $data['cover']  = intval($_POST['cover']);
            $data['info']   = t($_POST['info']);
            $data['details']= $_POST['details'];
            $data['status'] = intval($_POST['status']);
            $data['price']  = floatval($_POST['price']);
            $data['stock']  = intval($_POST['stock']);
            $data['fare']   = floatval($_POST['fare']);
            $data['is_best']= intval($_POST['is_best']);
            $data['ctime']  = time();
            $data['uid']    = $this->mid;

            $res = model('Goods')->add($data);
            if($res){
                $this->assign('jumpUrl',U('mall/AdminGoods/index'));
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            $_REQUEST['tabHash'] = 'addGoods';

            $this->onsubmit = 'admin.checkGoods(this)';
            $this->pageKeyList   = array('title', 'goods_cate',  'cover','info','details', 'status', 'price', 'stock', 'fare','is_best');
            $this->notEmpty      = array('title', 'goods_cate', 'cover', 'info', 'status', 'price', 'stock', 'fare','is_best');

            $this->opt['status']  = array('0' => '删除', '1' => '正常', '2' => '禁用');
            $this->opt['is_best']  = array('0' => '否', '1' => '是');

            ob_start();
            echo W('CategoryLevel', array('table' => 'goods_category', 'id' => 'goods_level'));
            $output = ob_get_contents();
            ob_end_clean();
            $this->savePostUrl = U('mall/AdminGoods/addGoods');
            $this->displayConfig(array('goods_cate' => $output));
        }
    }

    /**
     * 修改商品信息
     */
    public function editGoods(){
        if(isset($_POST)){
            $id = intval($_POST['id']);
            if(!$id){
                $this->error("参数错误");
            }
            $exp = "/^-[1-9]\d*\.\d*|-0\.\d*[1-9]\d*$/";
            if(empty($_POST['title'])){$this->error("商品名称不能为空");}
            if(empty($_POST['goods_levelhidden'])){$this->error("请选择分类");}
            if(empty($_POST['cover'])){$this->error("请上传商品封面");}
            if(empty($_POST['info'])){$this->error("商品简介不能为空");}
            if($_POST['price'] == ''){$this->error("商品价格不能为空");}
            if(!is_numeric($_POST['price'])){$this->error('价格必须为数字');}
            if(preg_match($exp,$_POST['price'])){$this->error('价格必须为正整数');}
            if($_POST['stock'] == ''){$this->error("商品库存不能为空");}
            if(!is_numeric($_POST['stock'])){$this->error('商品库存必须为数字');}
            if($_POST['fare'] == ''){$this->error("商品库存不能为空");}
            if(!is_numeric($_POST['fare'])){$this->error('商品运费必须为数字');}
            if(preg_match($exp,$_POST['fare'])){$this->error('商品运费必须为正整数');}

            $myAdminLevelhidden 		= getCsvInt(t($_POST['goods_levelhidden']),0,true,true,',');  //处理分类全路径
            $fullcategorypath 			= explode(',',$_POST['goods_levelhidden']);
            $category 					= array_pop($fullcategorypath);
            $category					= $category == '0' ? array_pop($fullcategorypath) : $category; //过滤路径最后为0的情况
            $data['fullcategorypath']	= $myAdminLevelhidden; //分类全路径
            $data['goods_category']		 = $category == '0' ? array_pop($fullcategorypath) : $category;
            $goods_category_list = array_filter(explode(',', $myAdminLevelhidden ));
            $data['title']  = t($_POST['title']);
            $data['cover']  = intval($_POST['cover']);
            $data['info']   = t($_POST['info']);
            $data['details']= $_POST['details'];
            $data['status'] = intval($_POST['status']);
            $data['price']  = floatval($_POST['price']);
            $data['stock']  = intval($_POST['stock']);
            $data['fare']   = floatval($_POST['fare']);
            $data['is_best']= intval($_POST['is_best']);
            $data['ctime']  = time();
            $data['uid']    = $this->mid;


            $res = model('Goods')->where(array('id'=>$id))->save($data);
            if($res){
                $this->assign('jumpUrl',U('mall/AdminGoods/index'));
                $this->success("编辑成功");
            }else{
                $this->error("编辑失败");
            }
        }else{
            $_REQUEST['tabHash'] = 'editGoods';

            $this->onsubmit = 'admin.checkGoods(this)';
            $this->pageKeyList = array('id', 'title', 'goods_cate', 'cover', 'info','details', 'status', 'price', 'stock', 'fare','is_best');
            $this->notEmpty = array('title', 'goods_cate', 'cover', 'info', 'status', 'price', 'stock', 'fare','is_best');

            $this->opt['status'] = array('0' => '删除', '1' => '正常', '2' => '禁用');
            $this->opt['is_best']  = array('0' => '否', '1' => '是');

            $id = intval($_GET['id']);

            $good_info = model('Goods')->findGoodsInfo(array('id' => $id));

            $this->pageTitle['editGoods'] = '编辑商品-' . $good_info['title'];

            ob_start();
            echo W('CategoryLevel', array('table' => 'goods_category', 'id' => 'goods_level', 'default' => trim($good_info['fullcategorypath'], ',')));
            $output = ob_get_contents();
            ob_end_clean();
            $good_info['goods_cate'] = $output;

            $this->savePostUrl = U('mall/AdminGoods/editGoods');
            $this->displayConfig($good_info);
        }
    }

    /**
     * 解析商品列表数据
     * @param integer $type 状态
     * @param integer $limit 结果集数目，默认为20
     * @param integer $order 排序方式
     * @param $order 排序
     * @return array 解析后的商品列表数据
     */
    private function _getGoodsList($type,$limit,$order,$map){
        if(isset($_POST)){
            $_POST['id'] && $map['id'] = intval($_POST['id']);
            $_POST['title'] && $map['title'] = array('like', '%'.t($_POST['title']).'%');
            $_POST['price'] && $map['price'] = floatval($_POST['price']);
            $_POST['stock'] && $map['stock'] = intval($_POST['stock']);
            $_POST['fare'] && $map['fare']   = floatval($_POST['fare']);
            if($_POST['status'] == 1){
                $map['status'] = 0;
            }else if($_POST['status'] == 2){
                $map['status'] = 1;
            }else if($_POST['status'] == 3){
                $map['status'] = 2;
            }
            if (! empty ( $_POST ['ctime'] [0] ) && ! empty ( $_POST ['ctime'] [1] )) { // 时间区间条件
                $map ['ctime'] = array ('BETWEEN',array (strtotime ( $_POST ['ctime'] [0] ),
                    strtotime ( $_POST ['ctime'] [1] )));
            } else if (! empty ( $_POST ['ctime'] [0] )) {// 时间大于条件
                $map ['ctime'] = array ('GT',strtotime ( $_POST ['ctime'] [0] ));
            } elseif (! empty ( $_POST ['ctime'] [1] )) {// 时间小于条件
                $map ['ctime'] = array ('LT',strtotime ( $_POST ['ctime'] [1] ));
            }
        }
        $map['is_del'] = 0;
        $goods_info = model('Goods')->_getGoodsList($limit,$order,$map);

        foreach($goods_info['data'] as $key => $val){
            $url = U('mall/Goods/view', array('id' => $val['id']));
            $goods_info['data'][$key]['title'] = getQuickLink($url,$val['title'],"未知商品");
            $goods_info['data'][$key]['uname'] = getUserSpace($val['uid'], null, '_blank');
            $goods_info['data'][$key]['ctime'] = date('Y-m-d H:i:s',$val["ctime"]);
            $goods_info['data'][$key]['info']  = mb_substr($val['info'],0,20,'utf-8')."...";
            $goods_info['data'][$key]['details']  = mb_substr(t($val['details']),0,15,'utf-8')."...";
            $goods_info['data'][$key]['cover'] = "<img src='".getAttachUrlByAttachId($val['cover'])."' width='60' height='60'>";
            if($val['status'] == 0){
                $goods_info['data'][$key]['status'] = "<span style='color: red;'>删除</span>";
            }else if($val['status'] == 1){
                $goods_info['data'][$key]['status'] = "<span style='color: green;'>正常</span>";
            }else if($val['status'] == 2){
                $goods_info['data'][$key]['status'] = "<span style='color: #9c9c9c;'>禁用</span>";
            }
            switch(strtolower($type)){
                case 'index':
                    if($val['status'] != 2 && $val['status'] != 0 ) {
                        $goods_info['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.closeGoods(\'' . $val['id'] . '\')">禁用</a> | ';
                    }
                    if($val['status'] == 2) {
                        $goods_info['data'][$key]['DOACTION'] .= '<a href="javascript:;" onclick="admin.openGoods(\''.$val['id'].'\')">启用</a> | ';
                    }
                    break;
                case 'disable':
                    $goods_info['data'][$key]['DOACTION'] .= '<a href="javascript:;" onclick="admin.openGoods(\''.$val['id'].'\')">启用</a> | ';
                    break;
            }
            $goods_info['data'][$key]['DOACTION'] .= '<a href="'.U('mall/AdminGoods/editGoods',array('id'=>$val['id'])).'">编辑</a> | ';
            if($val['status'] != 0) {
                $goods_info['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.delGoods(\'' . $val['id'] . '\')">删除</a>  ';
            }else{
                $goods_info['data'][$key]['DOACTION'] .= '<a href="javascript:;" onclick="admin.openGoods(\''.$val['id'].'\')">启用</a>';
            }
            switch(strtolower($type)){
                case 'close':
                    $goods_info['data'][$key]['DOACTION'] .= ' | <a href="javascript:void(0)" onclick="admin.deleteGoods(\'' . $val['id'] . '\')">彻底删除</a>  ';
                    break;
            }
        }
        return $goods_info;
    }
    /**
     * 批量删除商品操作
     */
    public function delGoodsAll(){
        $ids = implode(",",$_POST['ids']);
        $ids = trim(t($ids),",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
        $where = array(
            'id'=>array('in',$ids)
        );

        if($_POST['status'] == 3){
            $data['is_del'] = 1;
        }else{
            $data['status'] = $_POST['status'];
        }
        $res = M('goods')->where($where)->save($data);
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
    /**
     * 彻底删除商品操作
     */
    public function deleteGoods(){
        $id = intval($_POST['goods_id']);
        if(!$id){
            $this->ajaxReturn(null,"参数错误",0);
        }
        $res = M('goods')->where(array('id'=>$id))->delete();
        if($res){
            $this->ajaxReturn(null,"彻底删除成功",1);
        }else{
            $this->ajaxReturn(null,"彻底删除失败",0);
        }
    }
    /**
     * 删除商品操作
     */
    public function delGoods(){
        $id = intval($_POST['goods_id']);
        if(!$id){
            $this->ajaxReturn(null,"参数错误",0);
        }
        $data['status'] = 0;
        $res = M('goods')->where(array('id'=>$id))->save($data);
        if($res){
            $this->ajaxReturn(null,"删除成功",1);
        }else{
            $this->ajaxReturn(null,"删除失败",0);
        }
    }
    /**
     * 启用商品操作
     */
    public function openGoods(){
        $id = intval($_POST['goods_id']);
        if(!$id){
            $this->ajaxReturn(null,"参数错误",0);
        }
        $data['status'] = 1;
        $res = M('goods')->where(array('id'=>$id))->save($data);
        if($res){
            $this->ajaxReturn(null,"启用成功",1);
        }else{
            $this->ajaxReturn(null,"启用失败",0);
        }
    }
    /**
     * 禁用商品操作
     */
    public function closeGoods(){
        $id = intval($_POST['goods_id']);
        if(!$id){
            $this->ajaxReturn(null,"参数错误",0);
        }
        $data['status'] = 2;
        $res = M('goods')->where(array('id'=>$id))->save($data);
        if($res){
            $this->ajaxReturn(null,"禁用成功",1);
        }else{
            $this->ajaxReturn(null,"禁用失败",0);
        }
    }
}