<?php
/**
 * @name 众筹API
 */

class CrowdApi extends Api{
    protected $mod = '';//当前操作的模型
    

    /**
     * 初始化模型
     */
    public function _initialize() {
        $this->mod 	 = model('Crowd');
    }
    /**
     * @name 获取众筹列表
     */
    public function getCrowdList(){
        $this->keyword && $map['title'] = array('like','%'.h($this->keyword).'%');
        $data = $this->mod->getList($map,$this->count);
        if($data['gtLastPage'] === true){
            $this->exitJson((object)[],1,'暂时没有更多众筹');
        }
        $data['data'] ? $this->exitJson($data['data'],1) : $this->exitJson((object)[],1,'暂时没有众筹');
    }
    /**
     * @name 申请众筹
     */
    public function createCrowd(){
        if(!$this->title){
            $this->exitJson((object)[],1,'请填写众筹名称');
        }elseif(!$this->category){
            $this->exitJson((object)[],1,'请填写众筹分类');
        }elseif(!$this->stime || !$this->etime){
            $this->exitJson((object)[],1,'请完善众筹的起止时间');
        }elseif(!$this->vstime || !$this->vetime){
            $this->exitJson((object)[],1,'请完善众筹课程的起止时间');
        }
        $data = [
            'uid' => $this->mid,
            'title' => h($this->title),
            'category' => h($this->category),
            'stime' => $this->stime,
            'etime' => $this->etime,
            'vstime' => $this->vstime,
            'vetime' => $this->vetime,
            'num' => (int)$this->num ?:0,
            'price' => (int)$this->price?:0,
            'cover' => (int)$this->cover ?: '',
            'info' => h($this->info) ?:'',
        ];
        
        $id = $this->mod->addCrowd($data);
        if($id){
            $this->exitJson(['crowd_id'=>(int)$id],1,'申请众筹成功');
        }
        $this->exitJson((object)[],0,'申请众筹失败,请重新尝试');
        
    }
    /**
     * @name 众筹详情
     */
    public function getCrowdInfo(){
        if(!is_numeric($this->crowd_id)){
            $this->exitJson((object)[],0,'该众筹不存在'); 
        }
        $data = $this->mod->getCrowdInfoById($this->crowd_id);
        if($data){
            $this->exitJson($data,1); 
        }
        $this->exitJson((object)[],0,'该众筹不存在或已被删除');
    }
}