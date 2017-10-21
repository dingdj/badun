<?php
/**
 * 收货地址
 * @author martinsun<syh@sunyonghong.com>
 * @version v1.0
 */
class AddressModel extends Model{
	protected $tableName = 'address';
    /**
     * @name 获取地址列表
     */
    public function getList(array $map,$limit = 10){
        $data = $this->where($map)->findPage($limit); 
        if($data['data']){
            $data['data'] = $this->haddleData($data['data']);
        }
        return $data;
    }
    /**
     * @name 数据解析
     */
    protected function haddleData($data = array()){
        if(!is_array($data) || empty($data)){
            return [];
        }
        $area_model = model('Area');
        foreach($data as &$v){
            //重置值
            is_numeric($v['province']) && $v['province'] = $area_model->getAreaById($v['province'])['title'];
            is_numeric($v['city']) && $v['city'] = $area_model->getAreaById($v['city'])['title'];
            is_numeric($v['area']) && $v['area'] = $area_model->getAreaById($v['area'])['title'];
            $v['address_id'] = (int)$v['id'];
            $v['uid'] = (int)$v['uid'];
            unset($v['id'],$v['location']);
            
        }
        return $data;
    }
    /**
     * @name 添加收货地址
     */
    public function addAddress(array $data){
        if(!is_array($data)){
            $this->error = '请填写完整';
            return false;
        }
        $data['ctime'] = (string)time();
        
        $id = $this->add($data);
        if($id){
            //设置了默认收货
            if($data['is_default'] == 1){
                $this->setAddress(['uid' => $data['uid'],'id'=>['neq',$id]],0);
            }
            $area_model = model('Area');
            is_numeric($data['province']) && $data['province'] = $area_model->getAreaById($data['province'])['title'];
            is_numeric($data['city']) && $data['city'] = $area_model->getAreaById($data['city'])['title'];
            is_numeric($data['area']) && $data['area'] = $area_model->getAreaById($data['area'])['title'];
            $data['address_id'] = $id;
            return $data;
        }else{
            $this->error = '新增收货地址失败,请重新尝试';
            return false;
        }
    }
    /**
     * @name 修改收货地址
     */
    public function updateAddress(array $map ,array $data){
        if(!is_array($data)){
            $this->error = '请填写完整';
            return false;
        }
        if($this->where($map)->save($data)){
            //设置了默认收货
            if($data['is_default'] == 1){
                $this->setAddress(['uid' => $map['uid'],'id'=>['neq',$map['id']]],0);
            }
            $area_model = model('Area');
            $data['province'] = $area_model->getAreaById($data['province'])['title'];
            $data['city'] = $area_model->getAreaById($data['city'])['title'];
            $data['area'] = $area_model->getAreaById($data['area'])['title'];
            $data['address_id'] = (int)$map['id'];
            $data['uid'] = (int)$map['uid'];
            $data['is_default'] = isset($data['is_default']) ? (int)$data['is_default'] : $this->getIsDefault($data['address_id']);
            return $data;
        }else{
            $this->error = '修改收货地址失败,请重新尝试';
            return false;
        }
    }
    /**
     * @name 设置默认收货地址
     */
    public function setAddress(array $map,$status = 0){
        if($map){
            return $this->where($map)->setField('is_default',$status);
        }
        return false;
    }
    /**
     * @name 获取是否被设置为默认地址
     */
    protected function getIsDefault($address_id){
        return $this->where(['id'=>$address_id])->getField('is_default') ? 1 : 0;
    }
    /**
     * @name 根据地址ID获取地址
     * @ID   地址ID
     */
    public function getAddressById($id){
        $data = $this->where(['id'=>$id])->find();
        return $data;
    }
}