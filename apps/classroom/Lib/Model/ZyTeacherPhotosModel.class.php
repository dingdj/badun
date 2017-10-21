<?php
/**
 * @name 教师相册模型
 * @author martinsun<syh@sunyonghong.com>
 * @date 2016-12-7
 */

class ZyTeacherPhotosModel extends Model {
    protected $tableName = 'zy_teacher_photos_data';
    
    protected $photosAlbum = '';
    
    protected function _initialize(){
        $this->photosAlbum = M('zy_teacher_photos');
    }
    /**
     * @name 保存相册
     * @param array $data 需要的数据
     * $data 中至少包含以下字段
     *      tid:教师ID
     *      title:相册名称
     * @desc 如果成功,返回相册ID,失败或有错误,返回false
     * @return int
     */
    public function savePhotoAlbum(array $data){
        if(empty($data)){
            $this->error = '相册信息不能为空';
            return false;
        }else if(!isset($data['title']) || $data['title'] == ''){
            $this->error = '请填写相册名称';
            return false;
        }
        $save = array(
            'tid'       => intval($data['tid']),
            'title'     => t($data['title']),
			'ctime'		=> t($data['ctime']),
            'is_del'    =>0
        );
        if(isset($data['id']) && intval($data['id'])){
            $this->photosAlbum->where(array('id'=>intval($data['id'])))->save($save) && $photo_id = $data['id'];
        }else{
            $photo_id = $this->photosAlbum->add($save);
        }
        if($photo_id){
            return (int)$photo_id;
        }else{
            $this->error = '保存相册失败,请重新尝试';
            return false;
        }
    }
    /**
     * @name 添加相册资源
     */
    public function saveSource(array $data){
        if(empty($data)){
            $this->error = '添加的相册资源不能为空';
            return false;
        }
        $save = array(
            'tid'       => intval($data['tid']),
            'title'     => t($data['title']),
            'photo_id'  => intval($data['photo_id']),
            'type'      => intval($data['type']),
            'resource'  => $data['resource'],
            'cover'     => (intval($data['type']) == 1) ? 0 : $data['cover'],
            'videokey'  => $data['videokey'] ?:'',
            'filesize'  => $data['filesize'],
            'video_type'=> $data['video_type'] ?: 0,
            'is_syn'    => $data['is_syn'] ?: 0,
            'ctime'     => time(),
            'is_del'    => 0
        );
        if(!$this->checkSourceType($save)){
            return false;
        }
        if($data['pic_id']){
            $this->where(array('pic_id'=>intval($data['pic_id'])))->save($save) && $pic_id = $data['pic_id'];
        }else{
            $pic_id = $this->add($save);
        }
        if($pic_id){
            if($data['filesize']>0){
                $tid = $this->where('pic_id='.$pic_id)->getField('tid');
                $school_id = D('ZyTeacher')->where('id='.$tid)->getField('mhm_id');
                $video_space = M('zy_video_space')->where('mhm_id='.$school_id)->find();
                if($video_space){
                    $data['used_video_space'] = $video_space['used_video_space'] + $data['filesize'];
                    M('zy_video_space')->where('mhm_id='.$school_id)->save($data);
                }else{
                    $data['mhm_id'] = $school_id;
                    $data['used_video_space'] = $data['filesize'];
                    M('zy_video_space')->add($data);
                }
            }
            return (int)$pic_id;
        }else{
            $this->error = '保存相册资源失败,请重新尝试';
            return false;
        }
        
    }
	/**
	  * @name 批量添加相册内资源
	  * @param array $data 数据资源数组
	  * @return int 成功添加的记录条数
	  */
	public function addAllSource(array $data){
		$count = 0;
		if(is_array($data) && !empty($data)){
			$save = array(
				'tid'       => intval($data['tid']),
				'title'     => t($data['title']),
				'photo_id'  => intval($data['photo_id']),
				'type'      => intval($data['type']),
				'cover'     => (intval($data['type']) == 1) ? 0 : $data['cover'],
				'videokey'  => $data['videokey'] ?:'',
                'filesize'  => $data['filesize'],
                'video_type'=> $data['video_type'] ?: 0,
                'is_syn'    => $data['is_syn'] ?: 0,
                'ctime'     => time(),
			);
			$resources = !is_array($data['resource']) ? explode('|',$data['resource']) : $data['resource'];
			$resources = array_unique(array_filter($resources));

			if(!empty($resources)){
				foreach($resources as $val){
					$save['resource'] = $val;
					$this->saveSource($save) && $count++;
				}
			}
		}
		return $count;
	}
    /**
     * @name 检测添加的相册资源类型是否有效
     */
    private function checkSourceType(array $data){
        if(!$data || !in_array($data['type'],array(1,2))){
            $this->error = '请选择有效的资源类型';
            return false;
        }
        return true;
    }
    /**
     * @name 根据教师ID获取教师相册目录
     * @param int $tid 教师ID
     * @param int $limit 分页显示数量 default:20
     * @return array 
     */
    public function getPhotosAlbumByTid($tid = 0,$limit = 20){
        $data = array();
        if(is_numeric($tid)){
            $data = $this->photosAlbum->where(array('tid'=>$tid,'is_del'=>0))->findPage($limit);
            $data['data'] = $this->haddleDataForAlbum($data['data']);
        }
        return $data;
    }
    /**
     * @name 根据相册ID获取当前相册详细信息
     */
    public function getPhotosAlbumInfo($photos_id = 0,$tid = 0){
        $map['id'] = $photos_id;
        $tid && $map['tid'] = $tid;
        $data = $this->photosAlbum->where($map)->find();
        if($data){
            $data = $this->haddleDataForAlbum([$data]);
        }
        return $data ? $data[0] : [];
    }
    /**
     * @name 处理相册
     * @param array $data 相册数据信息
     * @return array
     */
    final private function haddleDataForAlbum(array $data){
        if(is_array($data) && !empty($data)){
            foreach($data as &$v){
                $v['tid'] = (int)$v['tid'];
                $v['cover'] = $this->getAlbumCover($v['id']);
                $v['cover_id'] = $this->getAlbumCover($v['id'],1);
				$v['picture_count'] = (int)$this->where(array('type'=>1,'photo_id'=>$v['id'],'is_del'=>0))->count();
				$v['video_count'] = (int)$this->where(array('type'=>2,'photo_id'=>$v['id'],'is_del'=>0))->count();
                unset($v['is_del']);
            }
        }
        return $data;
    }
    
    /**
     * @name 获取相册的封面图
     * @param int $photo_id 相册ID
     * @return $type 封面类型(0 路径 ，1 id)
     * @return string 封面地址
     */
    public function getAlbumCover($photo_id = 0,$type = 0){
        if($photo_id){
            $photo_data = $this->where(array('photo_id'=>$photo_id,'is_del'=>0))->order('pic_id ASC')->find();
            if($photo_data){
                switch($photo_data['type']){
                    case '1':
                        $cover = intval($photo_data['resource']);
                        break;
                    default:
                        $cover = intval($photo_data['cover']);
                        break; 
                }
                if($type == 0){
                    return getCover($cover,222,144);
                }else{
                    return $cover;
                }
            }
        }
        return '';
    }
    /**
     * @name 根据相册ID获取相册资源
     * @param int $photo_id 相册ID
     * @param int $limit int 分页显示数量 default:20
     * @return array
     */
    public function getPhotoDataByPhotoId($photo_id = 0,$limit = 20){
        $data = array();
        if($photo_id){
            $data = $this->where(array('photo_id'=>$photo_id,'is_del'=>0))->findPage($limit);
            $data['data'] = $this->haddleDataForPhotos($data['data']);
        }
        return $data;
    }
    
    /**
     * @name 处理相册资源数据
     */
    final private function haddleDataForPhotos(array $data){
        if(is_array($data) && !empty($data)){
            foreach($data as &$v){
                if($v['type'] == 1){
                    $v['cover'] = $v['resource'];
                }
            }
        }
        return $data;
    }
    
}

?>