<?php
namespace app\index\controller;
use think\Db;
use think\Cookie;

class Index extends Base
{
    public function index()
    {
        if(!input('token')){
            $this->redirect('index/index?token='.$this->token);
        }
        //获取产品信息
        $pro = Db::name('productinfo')->alias('pi')->field('pi.pid,pi.ptitle,pd.Price,pd.UpdateTime,pd.Low,pd.Open,pd.Close,pd.Diff,pd.DiffRate,pd.High')
        		->join('__PRODUCTDATA__ pd','pd.pid=pi.pid')
        		->where('pi.isdelete',0)->order('pi.proorder asc')->select();
        $this->assign('pro',$pro);
        return $this->fetch();
    }

    public function shouye(){
  		$tjlist = db('newsinfo')->where("fid=5")->limit(4)->select();
  		$this->assign('tjlist',$tjlist);
  		// 7X24h快讯
  		$kxlist = db('newsinfo')->where("fid=6")->limit(4)->select();
  		$this->assign('kxlist',$kxlist);
  		// 市场数据
  		$sclist = db('newsinfo')->where("fid=7")->limit(9)->select();
  		$this->assign('sclist',$sclist);
  		// hdp
  		$map['ncover'] = array('EXP','is not NULL');
  		$hdplist = db('newsinfo')->where($map)->limit(3)->select();
  		$this->assign('hdplist',$hdplist);
    	return $this->fetch();
    }
}
