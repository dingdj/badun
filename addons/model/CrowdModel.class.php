<?php
/**
 * 众筹模型 - 数据对象模型
 * @author martinsun<syh@sunyonghong.com>
 * @version v1.0
 */
class CrowdModel extends Model {

	protected $tableName = 'crowdfunding';
    
    /**
     * @name 获取众筹列表
     */
    public function getList(array $map,$limit = 10){
        $data = $this->where($map)->findPage($limit);
        if($data['data']){
            $data['data'] = $this->haddleData($data['data']);
        }
        return $data;
    }
    /**
     * @name 添加众筹
     */
    public function addCrowd(array $data){
        if(!$data['uid'] || !$data['title']){
            $this->error = '请填写完整';
            return false;
        }
        $data['ctime'] = time();
        return $this->add($data);
    }
    
     /**
     * @name 数据解析
     */
    protected function haddleData($data = array()){
        if(!is_array($data) || empty($data)){
            return [];
        }
        foreach($data as &$v){
            //重置值
            $v['cover'] = getCover($v['cover']);
            $v['status'] = (int)$v['status'];
            $v['crowd_id'] = (int)$v['id'];
            $v['uid'] = (int)$v['uid'];
            $v['price'] = (int)$v['price'];
            $v['num'] = (int)$v['num'];
            $v['info'] = $v['info'] ?:'';
            $v['sid'] = $v['sid'] ?:0;
            $user = model('User')->findUserInfo($v['uid'],'uname');
            $v['uname'] = $user['uname'];
            unset($v['id']);
        }
        return $data;
    }
    /**
     * @name 获取单个众筹的详情
     */
    public function getCrowdInfoById($crowd_id = 0){
        $info = [];
        if($crowd_id){
            $item = $this->where(['id'=>$crowd_id])->find();
            if($item){
                $info = $this->haddleData([$item])[0];
            }
        }
        return $info;
    }
}