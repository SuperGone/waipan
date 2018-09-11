<?php
namespace app\ccenter\controller;
use think\Controller;
use think\Db;
use think\Request;

class Zhouli extends Base {
    

    public function interest() {
        $interest= Db::name('interest_table')->order('datetime','desc')->paginate(10);

        $this->assign('interest', $interest);
        return $this->fetch();
    }
    public function change_interest()
    {
         $post = Request::instance()->post();
         if(Db::name('interest_table')->where('id',$post['id'])->update(array('end'=>$post['lilv']))){
             echo json_encode(array('state'=>"200",'msg'=>'修改成功'));
         }else{
             echo json_encode(array('state'=>"0",'msg'=>'修改失败'));
         }
         
    }
      public function change_wallet()
    {
         $post = Request::instance()->post();
         if(Db::name('zhouli_wallet')->where('uid',$post['uid'])->update(array('close'=>$post['close']))){
             echo json_encode(array('state'=>"200",'msg'=>'修改成功'));
         }else{
             echo json_encode(array('state'=>"0",'msg'=>'修改失败'));
         }
         
    }

    public function wallet() {
        $pagenum = cache('page');
		$getdata = $where = array();
		$data = input('param.');
		//用户名称、id、手机、昵称
		if(isset($data['username']) && !empty($data['username'])){
			$where['wa.uid'] = array('like','%'.$data['username'].'%');
			$getdata['username'] = $data['username'];
		}
                
                
        $wallet = Db::name('zhouli_wallet')->alias('wa')->field('wa.*,ui.nickname')
                        ->join('__USERINFO__ ui', 'ui.uid = wa.uid')
                        ->where($where)->order('wa.uid desc')->paginate($pagenum,false,['query'=> $getdata]);
        
        $this->assign('getdata',$getdata);
        $this->assign('wallet', $wallet);
        return $this->fetch();
    }

    public function log() {
        
		$pagenum = cache('page');
		$map = $getdata = array();
		$uid = $this->uid;

		//循环条件
		$price_log = db('zhouli_log');
		$tody_starttime = strtotime(date("Y").'-'.date("m").'-'.date("d").' 00:00:00');
		$tody_endtime = strtotime(date("Y").'-'.date("m").'-'.date("d").' 24:00:00');

		//搜索条件
		$data = input('param.');
		if(isset($data['username']) && !empty($data['username'])){
			$map['uid'] = array('like','%'.$data['username'].'%');
			$getdata['username'] = $data['username'];
		}
		if(isset($data['starttime']) && !empty($data['starttime'])){
			if(!isset($data['endtime']) || empty($data['endtime'])){
				$data['endtime'] = date('Y-m-d H:i:s',time());
			}
			$getdata['starttime'] = $data['starttime'];
			$getdata['endtime'] = $data['endtime'];
		}
		
		$list = db('userinfo')->field('uid,nickname')
				->where($map)->order('uid desc')->paginate($pagenum,false,['query'=> $getdata]);

		$_list = array();
		foreach ($list as $key => $v) {
			$_list[$key] = $v;
			
			$all_res_map['uid'] = $v['uid'];
                        $all_res_map['type'] = '1';
			if(isset($getdata['starttime']) && !empty($getdata['starttime'])){
				$all_res_map['time'] = array('between time',array($getdata['starttime'],$getdata['endtime']));
			}
			$_list[$key]['all_res'] = db('zhouli_log')->where($all_res_map)->sum('money');//入金总额
			$_list[$key]['all_res_count'] = db('zhouli_log')->where($all_res_map)->count();//入金次数 
                        
			$all_cash_map['uid'] = $v['uid'];
                        $all_cash_map['type'] = '-1';
			if(isset($getdata['starttime']) && !empty($getdata['starttime'])){
				$all_cash_map['time'] = array('between time',array($getdata['starttime'],$getdata['endtime']));
			}
			$_list[$key]['all_cash'] = db('zhouli_log')->where($all_cash_map)->sum('money');	//出金总额
			$_list[$key]['all_cash_count'] = db('zhouli_log')->where($all_cash_map)->count();//出金次数
                        
                        
                        $all_fy_map['uid'] = $v['uid'];
                        $all_fy_map['type'] = '2';
			if(isset($getdata['starttime']) && !empty($getdata['starttime'])){
				$all_fy_map['time'] = array('between time',array($getdata['starttime'],$getdata['endtime']));
			}
			$_list[$key]['all_fenyong'] = db('zhouli_log')->where($all_fy_map)->sum('money');	//分佣总额
			$_list[$key]['all_fenyong_count'] = db('zhouli_log')->where($all_fy_map)->count();//分佣次数
             
          
            $all_yl_map['uid'] = $v['uid'];
                         $all_yl_map['type'] = '0';
                        $_list[$key]['all_yingli'] = db('zhouli_log')->where($all_yl_map)->sum('money');
		}
		
		
		
		$this->assign('getdata',$getdata);
		$this->assign('_list',$_list);
		$this->assign('list',$list);
                $tongji['wallet']=db('zhouli_wallet')->sum('money');
                $tongji['res']=db('zhouli_log')->where(array('type'=>'1'))->sum('money');
                $tongji['cash']=db('zhouli_log')->where(array('type'=>'-1'))->sum('money');
                $tongji['yongjin']=db('zhouli_log')->where(array('type'=>'2'))->sum('money');
                $tongji['yingli']=db('zhouli_log')->where(array('type'=>'0'))->sum('money');
                $this->assign('tongji',$tongji);
		return $this->fetch();
    }

}
