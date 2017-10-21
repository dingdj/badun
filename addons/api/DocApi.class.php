<?php
/**
 * @name 文库API
 * @author martinsun<syh@sunyonghong.com>
 * @version v1.0
 */

class DocApi extends Api{
    protected $mod = '';//当前操作的模型
    

    /**
     * 初始化模型
     */
    public function _initialize() {
        $this->mod 	 = model('Doc');
		$this->mod->mid = $this->mid;
    }
    /**
     * @name 获取文库列表
     */
    public function getDocList(){
        $map['status'] = 1;
        (int)$this->doc_category_id && $map['category'] = (int)$this->doc_category_id;
        $this->keyword && $map['title'] = array('like','%'.h($this->keyword).'%');
        $data = $this->mod->getList($map,'ctime desc',$this->count);
        if($data['gtLastPage'] === true){
            $this->exitJson((object)[],1,'暂时没有更多文库');
        }
        $data['data'] ? $this->exitJson($data['data'],1) : $this->exitJson((object)[],1,'暂时没有更多文库');
    }
    /**
     * @name 获取文库分类
     */
    public function getDocCategory(){
        $cate_id = (int)$this->cate_id ?:0;
        $list = model('DocCategory')->getCategoryList($cate_id);
        $list ? $this->exitJson($list,1) : $this->exitJson([],0,'暂时没有文库分类');
    }
    /**
     * @name 积分兑换文库
     */
    public function exchange(){
        if(!is_numeric($this->doc_id)){
            $this->exitJson((object)[],0,'该文库不存在或已被删除');
        }
        //检测是否已经兑换
        if($this->mod->isExchange($this->mid,$this->doc_id)){
            $this->exitJson((object)[],0,'你已兑换该文库');
        }
        $info = $this->mod->getDocById($this->doc_id);
        if($info['status'] !== 1){
            $this->exitJson((object)[],0,'该文库不存在或已被删除');
        }
        
        //检测用户积分是否足够
        $creditModel = model('Credit');
        $account = $creditModel->getUserCredit($this->mid);
        $score = $account['credit']['score']['value'];
        $price = $info['price'];
        if($score - $price < 0){
            $this->exitJson((object)[],0,'你的积分不足');
        }
        //兑换--扣除兑换者积分
        $creditModel->setUserCredit($this->mid,['score'=> -$price]);
        //兑换--添加文库作者积分
        $creditModel->setUserCredit($info['uid'],['score'=> $price]);
        $data = [
            'uid' => $this->mid,
            'cid' => $this->doc_id,
            'price' => $price,
        ];
        if($this->mod->addExchange($data)){
			$info['is_buy'] = 1;
            $this->exitJson($info,1,'兑换成功');
        }
        $this->exitJson((object)[],0,'兑换失败,请重新尝试');
    }
    /**
     * @name 获取我的文库
     */
    public function getMyDocList(){
        $map = [];
        (int)$this->doc_category_id && $map['category'] = (int)$this->doc_category_id;
        $this->keyword && $map['title'] = array('like','%'.h($this->keyword).'%');
        $data = $this->mod->getUserDoc($this->mid,$map);
        if($data['gtLastPage'] === true){
            $this->exitJson((object)[],1,'暂时没有更多文库');
        }
        $data['data'] ? $this->exitJson($data['data'],1) : $this->exitJson((object)[],1,'暂时没有更多文库');
    }
}