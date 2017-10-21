<?php
/**
 * @name 优惠券API
 * @author martinsun<syh@sunyonghong.com>
 * @version v1.0
 */

class CouponApi extends Api{
    protected $mod = '';//当前操作的模型
    

    /**
     * 初始化模型
     */
    public function _initialize() {
        $this->mod 	 = model('Coupon');
        $this->mod->mid = $this->mid;
    }
    /**
     * @name 获取我的优惠券列表
     */
    public function getMyCouponList(){
        $status = $this->status ?:-1;
        if($this->type && in_array($this->type,[1,2,3,4])){
            $map['type'] = (int)$this->type;
        }
        $map['uid'] = $this->mid;
        $data = $this->mod->getUserCouponList($map,$status,$this->count);
        if($data['gtLastPage'] === true){
            $this->exitJson((object)[],1,'暂时没有获得更多优惠券');
        }
        $data['data'] ? $this->exitJson($data['data'],1) : $this->exitJson((object)[],1,'暂时没有优惠券');
    }
    
    /**
     * @name 领取优惠券
     */
    public function grantCoupon(){
        $this->code = t($this->code);
        if($this->code){
            //检测优惠券并发放,返回发放的结果状态
            $status = $this->mod->grantCouponByCode($this->code);
            ($status === true) ? $this->exitJson(['code'=>$this->code],1,'成功领取优惠券') : $this->exitJson((object)[],0,$this->mod->getError());
        }else{
            $this->exitJson((object)[],0,'请输入合法的优惠券');
        }
    }
}