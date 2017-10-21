<?php
/**
 * 意见反馈模型 - 数据对象模型
 * @version 1.0
 */
class FeedbackModel extends Model {

	protected $tableName = 'feedback';
	protected $fields = array(0=>'id',1=>'content',2=>'uid',3=>'uid',4=>'ctime',5=>'mtime',6=>'contact_way','type'=>true,'_pk'=>'id');

	/**
     * @name 添加一条反馈记录
     */
    public function addFeedback(array $data){
        return $this->add($data);
    }
}