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
        if($this->type && in_array($this->type,[1,2,3,4,5])){
            $map['type'] = (int)$this->type;
        }
        $map['uid'] = $this->mid;
        $map['coupon_type'] = 0;
        $data = $this->mod->getUserCouponList($map,$status,$this->count,1);
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

    /*
     * @name 使用课程卡
     */
    public function useCourseCard(){
        $this->coupon_id = (int) $this->coupon_id ?: 0;
        $coupon = model('Coupon')->canUse($this->coupon_id, $this->mid);
        if (!$coupon) {
            $this->exitJson((object)[],0,'该课程卡已经无法使用');
            return false;
        }
        $video_id = $coupon['video_id'];
        if($coupon['video_type'] == 1){
            $vtype = 'zy_video';
            $pay_status = M('zy_order_course')->where(array('uid'=>$this->mid,'video_id'=>$video_id))->getField('pay_status');
        }else if($coupon['video_type'] == 2){
            $vtype = 'zy_live';
            $pay_status = D('ZyOrderLive')->where(array('uid'=>$this->mid,'live_id'=>$video_id))->getField('pay_status');
        }
        if(!$pay_status || $pay_status == 1){
            $res = D('ZyVideo','classroom')->addOrder($coupon['video_id'],$vtype,$coupon['coupon_id']);
        }else{
            $this->exitJson((object)[],0,'该课程已经购买');
        }
        if($res == true){
            $this->exitJson(['coupon'=>$coupon],1,'课程卡使用成功');
        }else{
            $this->exitJson((object)[],0,'课程卡使用失败');
        }
    }
}