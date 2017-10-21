<?php
/**
 * 文库
 * @author martinsun<syh@sunyonghong.com>
 * @version v1.0
 */
class DocModel extends Model{
	protected $tableName = 'doc';
	public $mid = 0;
    /**
     * @name 获取文库列表
     */
    public function getList(array $map,$order,$limit = 10){
        $data = $this->where($map)->order($order)->findPage($limit);
        if($data['data']){
            $data['data'] = $this->haddleData($data['data']);
        }
        return $data;
    }
    /**
     * @name 数据解析
     */
    protected function haddleData($data = array(),$doc_id_key = 'id'){
        if(!is_array($data) || empty($data)){
            return [];
        }
        foreach($data as &$v){
            //重置值
            $v['attach']    = getAttachUrlByAttachId($v['attach_id']);
            $v['aid']       = $v['attach_id'];
            $v['category']  = model('DocCategory')->getCateNameById($v['category']);
            $v['status']    = (int)$v['status'];
            $v['doc_id']    = (int)$v[$doc_id_key];
            $v['uid']       = (int)$v['uid'];
            $v['price']     = (int)$v['price'];
            $v['is_buy']    = $this->mid ? (int)$this->docIsBuy($this->mid,$v[$doc_id_key]) : 0;
            $v['down_nums'] = (int)$v['down_nums'];
            $v['attach_info'] = model('Attach')->getAttachById($v['attach_id']);
            $v['attach_info']['size'] = byte_format($v['attach_info']['size']);
            $v['axchange_num'] = (int)M('doc_user')->where('cid ='.$v['doc_id'])->count();
            $v['cover_id'] = (int)$v['cover'];
            $v['cover'] = $v['cover_id'] ? getCover($v['cover']) : '';
            unset($v['id'],$v[$doc_id_key],$v['attach_id']);
        }
        return $data;
    }
    /**
     * @name 检测文件是否已经购买
     */
    public function docIsBuy($uid = 0 , $doc_id = 0){
        if($this->where(['uid' => $uid,'id'=>$doc_id])->count() > 0){
            //自己发布的
            return true;
        }
        if(M('doc_user')->where(['uid' => $uid,'cid'=>$doc_id])->count() > 0){
            //已经购买的
            return true;
        }
        return false;
    }
    /**
     * @name 根据文库ID获取文库状态
     */
    public function getStatusByDocId($doc_id = 0){
        return (int)$this->where(['id'=>$doc_id])->getField('status');
    }
    /**
     * @name 根据文库ID获取文库价格
     */
    public function getPriceByDocId($doc_id = 0){
        return (int)$this->where(['id'=>$doc_id])->getField('price');
    }
    /**
     * @name 根据ID获取单个文库信息
     */
    public function getDocById($doc_id = 0){
        $data = $this->where(['id'=>$doc_id])->select();
        if($data){
            $data = $this->haddleData($data);
            return $data[0];
        }
        return [];
    }
    /**
     * @name 添加兑换的文库
     */
    public function addExchange(array $data){
        if(!$data['uid'] || !$data['cid']){
            return false;
        }
        $data['ctime'] = time();
        return M('doc_user')->add($data);
    }
    /**
     * @name 检测是否已经兑换文库
     */
    public function isExchange($uid = 0,$doc_id = 0){
        return M('doc_user')->where(['uid'=>$uid,'cid'=>$doc_id])->count() > 0 ? true : false;
    }
    /**
     * @name 获取指定用户的文库
     */
    public function getUserDoc($uid = 0,array $map){
        $data = $this->where($map)->join("as d INNER JOIN `".C('DB_PREFIX')."doc_user` u ON u.cid = d.id AND u.uid = ".$uid)->findPage();
		if($data['data']){
            $data['data'] = $this->haddleData($data['data'],'cid');
        }
        return $data;
        
    }
}