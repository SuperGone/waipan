<?php
namespace app\index\controller;
use think\Db;
use think\Request;


class Zhouli extends Base {
    //周周利首页
    public function zhouli() {
        
        $lilvlinearr= Db::name('interest_table')->order('id','desc')->limit(10)->select();
        $lilvline_date=array();
        $lilvline_value=array();
        foreach($lilvlinearr as $list)
        {
            array_push($lilvline_date,date('m/d',$list['datetime']));
            array_push($lilvline_value, $list['end']);
        }
       
        $this->assign('lilvline_date', json_encode(array_reverse($lilvline_date)));
        $this->assign('lilvline_value',json_encode(array_reverse($lilvline_value)));
        
        $turn_lilvline_value=array_reverse($lilvline_value);
        $wallet=Db::name('zhouli_wallet')->where('uid',$this->uid )->find();
        $this->assign('wallet', $wallet);
      	$sevenwhere=array(
        	'uid'=>$this->uid,
         	'type'=>'0',
          	'time'=>['between',[(time()-604800),time()]],
        );
      	$sevencount=Db::name('zhouli_log')->where($sevenwhere)->sum('money');
      	if(!$sevencount){$sevencount=0;}
      	$this->assign('sevencount', $sevencount);
      
        $this->assign('dayll',end($turn_lilvline_value));
        return $this->fetch();
    }
    
    public function get_log_ajax()
    {
        $post = Request::instance()->post();
        $where['type']=($post['type']>0)?array('gt',0):$post['type'];
        $starttime=($post['starttime']==null)?"651674460":strtotime($post['starttime']);
        $endtime=($post['endtime']==null)?time():strtotime($post['endtime']);
        $where['time']=['between',[$starttime,$endtime]];
        $where['uid']=$this->uid;
        $log_list_num=Db::name('zhouli_log')->where($where)->count();
        $pagecount=intval($log_list_num/10)+1;
        if($post['page']<= 0){$post['page']=1;}
        if($post['page']>$pagecount){$post['page']=$pagecount;}
        $log_list_arr=Db::name('zhouli_log')->where($where)->order('time','desc')->page($post['page'],10)->select();
        $value=array(
            'state'=>'200',
            'nowpage'=>$post['page'],
            'page'=>$pagecount,
            'data'=>$log_list_arr,
            'msg'=>'正常获取'
        );
        echo json_encode($value);
    }
    public function fymx(){
        
        $data=Db::name('zhouli_log')->where(array('uid'=>$this->uid,'type'=>'0','time'=>['between',[(time()-604800),time()]],))->order('time','desc')->paginate(10);
        $this->assign('wallet_log', $data);
        return $this->fetch();
    }
    
    public function account_wallet(){
        $post = Request::instance()->post();
        $userinfo=$this->user;
        $user_wallet=Db::name('zhouli_wallet')->where('uid',$this->uid)->find();
        
        if(md5($post['paypass'].$userinfo['utime'])!=$userinfo['upwd']){
             exit(json_encode(array('state'=>"-1",'msg'=>"密码错误！")));
        }
        if($post['type']==1){//转入  减用户列表，加钱包列表
          
           if($userinfo['usermoney']<$post['money']){
                exit(json_encode(array('state'=>"-1",'msg'=>"超额支出！")));
            }
          
            Db::name('userinfo')->where('uid',$this->uid)->update(array('usermoney'=>$userinfo['usermoney']-$post['money']));
            change_zl_wallet($this->uid,'1', $post['money'],"");
            exit(json_encode(array('state'=>"200",'msg'=>"转入成功！")));
        }elseif($post['type']==2){//转出
            if($user_wallet['money']<$post['money']){
                exit(json_encode(array('state'=>"-1",'msg'=>"超额支出！")));
            }
            
            Db::name('userinfo')->where('uid',$this->uid)->update(array('usermoney'=>$userinfo['usermoney']+$post['money']));
            change_zl_wallet($this->uid,'-1', $post['money'],"");
            exit(json_encode(array('state'=>"200",'msg'=>"转出成功！")));
        }else{
            exit(json_encode(array('state'=>"-1",'msg'=>"配置错误！")));
        }
    }

    public function creat() {
        if(db('zhouli_wallet')->where('uid', $this->uid)->find()){
        }else{
            $data=array(
                'uid'=>$this->uid,
                'money'=>0,
                'accumulation'=>0,
                'yesterday'=>0,
                'fy_plus'=>0
            );
            if(db('zhouli_wallet')->insert($data)){
                $this->redirect('index/zhouli/zhouli?token=' . $this->token);
            }   
        }
        
    }
    //明细
    public function mingxi() {
        return $this->fetch();
    }
    //转入
    public function cashroot() {
        return $this->fetch();
    }
    //转出
     public function cashout() {
        return $this->fetch();
    }
    //年化列表
     public function nianhua() {
        return $this->fetch();
    }
    //累计收益
     public function leiji() {
        return $this->fetch();
    }
    
}
