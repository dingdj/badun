<?php
class TopicsModel extends Model {
	protected $tableName = 'zy_topic';

    public function getCate($is_del){
	    return M('zy_topic_category')->where(array('is_del'=>$is_del))->findAll();
	}
	 //+阅读
	public function addread($id){
		  return $this->setInc('readcount',array('id'=>$id),1);
	}

	public function getAdmincate($is_del){
		$cate =  M('zy_topic_category')->where(array('is_del'=>$is_del))->findAll();
		foreach($cate as $k =>$v){
		   $catelist[$v['zy_topic_category_id']] = $v['title'];	
		}
		return $catelist;
	 }
	 
	 public function getOnedata($id){
	    return $this->where(array('id'=>$id))->find();	 
	 }
	 
	 public function addNew($data){
		return $this->add($data);	 
	 }
	public function getTopic($type , $cate,$map){
		$map['is_del']=0; 
		if($cate){
			$map['cate']=$cate;
		}
		$orders='id DESC';
		$types = ($type==1) ? 1 : 2;//1:最新 2:热门 
		if($types=='1'){
			$orders = 'dateline DESC';	 
		} else{
			$orders = '`readcount` DESC';	 
		}
	    return $this->where($map)->order($orders)->findPage(10); 
	 }
	 
	 public function setTj($id){
		$data = $this->where(array('ids'=>$id))->find();	
		if($data['re']==1){
			$this->where(array('id'=>$id))->save(array('re'=>0));	 
			echo '设为推荐';
		}else{
			$this->where(array('id'=>$id))->save(array('re'=>1));	
			echo '取消推荐';
		}
	}
	
	public function savedata($data,$id){
		return $this->where(array('id'=>$id))->save($data);	 
	}
	
	public function reTopic(){
		$map['re']     = 1;
		$map['is_del'] = 0;
		$this->where($map)->order('recount DESC')->findPage(5); 
	 }
	 
	 public function getTjlist($limit=20){
		 $map['re']     = 1;
		 $map['is_del'] = 0;
		 return $this->where($map)->order('readcount DESC')->findPage($limit); 
	 }

	 
	 
	 public function upPage($id){
	     $upmap['id'] = array('lt',$id);
	     $upmap['is_del'] = 0;
		  return $this->where($upmap)->order('dateline DESC ')->find();
	 }
	 public function downPage($id){
         $downmap['id'] = array('gt',$id);
         $downmap['is_del'] = 0;

		  return $this->where($downmap)->order('dateline ASC')->find();
	 }
	 
}