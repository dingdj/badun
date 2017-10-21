<?php
/**
 * @name 收货地址API
 * @author martinsun<syh@sunyonghong.com>
 * @version v1.0
 */

class AddressApi extends Api{
    protected $mod = '';//当前操作的模型
    

    /**
     * 初始化模型
     */
    public function _initialize() {
        $this->mod 	 = model('Address');
    }
    /**
     * @name 获取我的收货地址列表
     */
    public function getAddressList(){
        $map['uid'] = $this->mid;
        if(isset($this->data['is_default'])){
            $map['is_default'] = 1;
        }
        $data = $this->mod->getList($map,$this->count);
        if($data['gtLastPage'] === true){
            $this->exitJson((object)[],1,'暂时没有填写更多收货地址');
        }
        $data['data'] ? $this->exitJson($data['data'],1) : $this->exitJson((object)[],1,'暂时没有填写收货地址');
    }
    /**
     * @name 添加收货地址
     */
    public function addAdress(){
        $this->checkData();

        $province = model('Area')->where(array('title'=>['like','%'.t($this->province).'%']))->getField('area_id');
        $city = model('Area')->where(array('title'=>['like','%'.t($this->city).'%'],'pid'=>$province))->getField('area_id');
        $area = model('Area')->where(array('title'=>['like','%'.t($this->area).'%'],'pid'=>$city))->getField('area_id');
        $address = [
            'uid' => $this->mid,
            'province' => $province,
            'city' => $city,
            'area' => $area,
            'name' => $this->name,
            'phone' => $this->phone,
            'is_default' => $this->is_default == 1 ? 1 : 0,
            'address' => $this->address,
            'location' => $this->location
        ];
        $res = $this->mod->addAddress($address);
        if(false !== $res){
            $this->exitJson($res,1,'新增收货地址成功'); 
        }
        $this->exitJson((object)[],0,$this->mod->getError()); 
    }
    /**
     * @name 修改收货地址
     */
    public function setAdress(){
        if(!is_numeric($this->address_id)){
            $this->exitJson((object)[],0,'请选择修改的地址');
        }
        $this->checkData();
        $address = [
            'province' => $this->province,
            'city' => $this->city,
            'area' => $this->area,
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,
        ];
        if(isset($this->data['is_default'])){
            $address['is_default'] = $this->is_default == 1 ? 1 : 0;
        }
        $res = $this->mod->updateAddress(['uid'=>$this->mid,'id'=>$this->address_id],$address);
        if(false !== $res){
            $this->exitJson($res,1,'修改收货地址成功'); 
        }
        $this->exitJson((object)[],0,$this->mod->getError()); 
    }
    protected function checkData(){
        if(!$this->province || !$this->city || !$this->area){
            $this->exitJson((object)[],0,'请选择所在城市');
        }else if(!$this->address){
            $this->exitJson((object)[],0,'请填写详细地址');
        }else if(!$this->name){
            $this->exitJson((object)[],0,'请填写姓名');
        }else if(!$this->phone){
            $this->exitJson((object)[],0,'请填写联系方式');
        }elseif(!$this->isTel($this->phone)){
            $this->exitJson((object)[],0,'请输入正确的手机号');
        }
    }
    /**
     * @name 验证是否为电话号码
     */
    public function isTel($tel,$type = ''){  
        $regxArr = array(  
        'sj'  =>  '/^(\+86)?1[34578]{1}\d{9}$/',  
        'tel' =>  '/^(010|02\d{1}|0[3-9]\d{2})-\d{7,9}(-\d+)?$/',  
        '400' =>  '/^400(-\d{3,4}){2}$/',  
        );  
        if($type && isset($regxArr[$type])){  
            return preg_match($regxArr[$type], $tel) ? true:false;  
        }  
        foreach($regxArr as $regx)  {  
            if(preg_match($regx, $tel )){  
                return true;  
            }  
        }  
        return false;  
    }
    /**
     * @name 设置我的默认收货地址
     */
    public function setDefault(){
        if(!is_numeric($this->address_id)){
            $this->exitJson((object)[],0,'请选择设置的地址');
        }
        $map['uid'] = $this->mid;
        $map['id'] = $this->address_id;
        if($this->mod->setAddress($map,1)){
            $this->mod->setAddress(['uid'=>$this->mid,'id'=>['neq',$this->address_id]],0);
            $this->exitJson(['address_id' => (int)$this->address_id],1,'设置默认收货地址成功');  
        }
        $this->exitJson((object)[],0,'设置失败,请重新尝试');
    }
    /**
     * @name 删除收货地址
     */
    public function rmoveAddress(){
        if(!is_numeric($this->address_id)){
            $this->exitJson((object)[],0,'请选择删除的地址');
        }
        if($this->mod->where(['uid'=>$this->mid,'id'=>$this->address_id])->delete()){
            $this->exitJson(['address_id' => (int)$this->address_id],1,'删除收货地址成功');  
        }
        $this->exitJson((object)[],0,'删除收货地址失败,请重新尝试');
    }
}