<?php
/**
 * @name 证书管理模型
 * @author martinsun
 * @version 1.0
 */
class ExamCertModel extends Model{
	protected $tableName = 'ex_cert';
    
    /**
     * @name 添加证书
     */
    public function addCert(array $data){
        if(empty($data)){
            return false;
        }
        $data['create_time'] = time();
        return $this->add($data);
    }
    
    /**
     * @name 根据证书ID获取证书内容
     */
    public function getCertById($cert_id = 0){
        if($cert_id){
            return $this->where(['cert_id'=>$cert_id])->find();
        }
        return [];
    }
    
    /**
     * @name 锁定
     */
    public function rmCert($cert_id = 0){
        return $this->where(['cert_id'=>$cert_id])->setField('is_del',1);
        //return $this->where(['cert_id'=>$cert_id])->delete();
    }
    /**
     * @name 解锁
     */
    public function reCert($cert_id = 0){
        return $this->where(['cert_id'=>$cert_id])->setField('is_del',0);
    }
    /**
     * @name 获取指定证书解析后信息
     */
    public function getCertParseInfoById($cert_id = 0,$data = []){
        if(!$cert_id && empty($data)){
            return [];
        }
        $data = empty($data) ? $this->getCertById($cert_id) : $data;
        if($data['grade_list']){
            $grade_list = explode(',',$data['grade_list']);
            $parse_list = [];
            foreach($grade_list as $v){
                $value = $this->getGradeValue('/(\d+)-(\d+)?#(.*)/',$v);
                $parse_list[] = [
                    'min' => $value[1],
                    'max' => $value[2],
                    'desc'=> $value[3],
                ];
            }
            $data['grade_list'] = $parse_list;
        }
        return $data;
    }
    
    /**
     * @name 获取对应的值
     */
    public function getGradeValue($reg,$str){
        preg_match($reg,$str,$value);
        return isset($value) ? $value : '';
    }
    /**
     * @name 颁发证书
     */
    public function createUserCert($paper_id = 0,$user_id = 0,$user_exam_score = 0,$exam_id = 0){
        $data = $this->where(['paper_id'=>$paper_id])->find();
        $info = $this->getCertParseInfoById($data['cert_id'],$data);
        $grade = '';
        if($info['grade_list']){
            $grade = $this->getGrade($info['grade_list'],$user_exam_score);
        }
        if($grade === ''){
            $this->error = '没有获得相关证书';
            return false;
        }
        //没有证书颁发
        if(!$grade) return false;
        $add = [
            'cert_code' => date('YmdHi').mt_rand(1000,9999),
            'grade'     => $grade,
            'cert_start_time' => time(),
            'cert_end_time' => strtotime('+'.$info['cert_validity_time'].' days'),
            'u_create_time'   => time(),
            'u_update_time'   => time(),
            'user_id'       => $user_id,
            'paper_id'      => $paper_id,
            'exam_id'       => $exam_id
        ];
        if(M('ex_user_cert')->add($add)){
            return true;
        }
        $this->error = '证书颁发失败';
        return false;
    }
    /**
     * @name 计算并取得等级
     */
    private function getGrade($grade_list,$score){
        if(is_array($grade_list)){
            foreach($grade_list as $v){
                if($v['min'] <= $score && $score <=$v['max']){
                    return $v['desc'];
                }
            }
        }
        return '';
    }
    
    /**
     * @name 获取某用户的证书列表
     */
    public function getUserCertList($user_id = 0,$map = []){
        $list = [];
        if($user_id){
            $tp = C('DB_PREFIX');
            $map['user_id'] = $user_id;
            $list = M("ex_user_cert e")->join("`{$tp}ex_cert` c ON e.paper_id = c.paper_id")->where($map)->order('e.u_update_time desc')->select();
            if($list){
                foreach($list as $k=>&$v){
                    $paper = M('ex_exam')->where(['paper_id'=>$v['paper_id'],'exam_id'=>$v['exam_id']])->find();
                    
                    if($paper['exam_publish_result_tm_mode'] != 0 && time() < $paper['exam_publish_result_tm']){
                        unset($list[$k]);
                        continue;
                    }
                    $v['exam_name'] = $paper['exam_name'];
                    $v['cert_content'] = $this->parseContent($v['cert_content'],$v);
                }
            }else{
                $list = [];
            }
        }
        return $list;
    }
    /**
     * @name 解析替换证书内容变量
     */
    private function parseContent($content,$value){
        $value['yyyy'] = date('Y',$value['cert_start_time']);
        $value['mm'] = date('m',$value['cert_start_time']);
        $value['dd'] = date('d',$value['cert_start_time']);
        $value['exam_year'] = date('Y',$value['cert_start_time']);
        $value['exam_month'] = date('m',$value['cert_start_time']);
        $value['exam_day'] = date('d',$value['cert_start_time']);
        $value['user_name'] = getUserName($value['user_id']);
        $value['cert_start_time'] = date('Y.m.d',$value['cert_start_time']);
        $value['cert_end_time'] = date('Y.m.d',$value['cert_end_time']);
        $keys = array_keys($value);
        $keys = array_map(create_function('$v','return "[".$v."]";'),$keys);
        return str_replace($keys,$value,$content);
    }
    
    /**
     * @name 通过查询获取用户证书
     */
    public function getUserCertBySearch($type = 0,$value = ''){
        if(!in_array($type,[0,1,2,3])){
            $this->error = '请选择正确的查询方式';
            return false;
        }
        $map = [];
        switch($type){
            //手机号
            case 1:
                $uid = M('user_verified')->where(['phone'=>$value])->getField('uid');
                break;
            //身份证
            case 2:
                $uid = M('user_verified')->where(['idcard'=>$value])->getField('uid');
                break;
            //真实姓名
            case 3:
                $uid = M('user_verified')->where(['realname'=>$value])->getField('uid');
                break;
            //证书编号
            default:
                $uid = M('ex_user_cert')->where(['cert_code'=>$value])->getField('user_id');
                $map['cert_code'] = $value;
                break;
        }
        if(!$uid && $type != 0){
            $this->error = '未能查询到你的相关信息,请先完成认证';
            return false;
        }elseif(!$uid){
            $this->error = '未能查询到该证书信息';
            return false;
        }
        return $this->getUserCertList($uid,$map);
    }
    /**
     * 获取导入excel的字段名称
     */
    public function getImportFields(){
        return array(
            0 => 'user_name',
            1 => 'user_sex',
            2 => 'user_datebirth',
            3 => 'user_native_place',
            4 => 'user_education',
            5 => 'user_occupation',
            6 => 'user_id_card',
            7 => 'user_phone',
            8 => 'user_photo',
            9 => 'cert_name',
            10 => 'cert_code',
            11 => 'cert_type',
            12 => 'cert_verify_unit',
            13 => 'cert_verify_time',
            14 => 'cert_unit',
            15 => 'cert_create_time',
            16 => 'cert_start_time',
            17 => 'cert_end_time',
            18 => 'cert_desc',
            19 => 'cert_review',
            20 => 'cert_review_time',
            21 => 'cert_next_review_time',
            22 => 'cert_is_revocation',
            23 => 'cert_revocation_reason',
            24 => 'cert_revocation_time',
            25 => 'exam_knowledge_type',
            26 => 'exam_knowledge_time',
            27 => 'exam_knowledge_place',
            28 => 'exam_knowledge_score',
            29 => 'exam_skill_type',
            30 => 'exam_skill_time',
            31 => 'exam_skill_score',
            32 => 'exam_skill_place',
            33 => 'exam_skill_place_code',
            34 => 'evaluation_result',
            35 => 'evaluation_result_desc'
        );
    }
}