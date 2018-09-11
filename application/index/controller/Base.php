<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\Loader;
use think\Request;
use think\Session;

class Base extends Controller {
    public function __construct() {
        if (isMobile()) {
            config('template.view_path', './template/mobile/');
        } else {
            config('template.view_path', './template/default/');
        }
        $get = Request::instance()->get();
        $session_uid=Session::get('uid');
        $request = Request::instance();
        
        parent::__construct();
        cookie(['prefix' => '', 'expire' => 60 * 60 * 24]);
        $this->token = md5(time());
        $this->assign('token', $this->token);
        //推荐
        $fid = isset($get['fid'])?$get['fid']:null;
        if ($fid) {
            Session::set('fid',$fid);
            if (!$session_uid) {
                $this->redirect('login/register?token=' . $this->token);
            }
        }
        
        $contrname = $request->controller();
        $actionname = $request->action();
        $this->assign('contrname', $contrname);
        $this->assign('actionname', $actionname);

        if (!isset($session_uid)) {
            $this->redirect('login/login?token=' . $this->token);
        }
        $this->uid = $session_uid;
            $this->user = db('userinfo')->where('uid', $this->uid)->find();
            if (!$this->user) {
                Session::delete('uid');
                $this->redirect('login/login?token=' . $this->token);
            }
        $this->assign('userinfo', $this->user);   
        
        if(db('zhouli_wallet')->where('uid', $this->uid)->find())
        {
            $is_zl_wallet=1;
        }else{$is_zl_wallet=0;}
        $this->assign('is_zl_wallet', $is_zl_wallet);  
        
        //网站配置信息
        $this->conf = getconf('');
        if ($this->conf['is_close'] != 1) {header('Location:/error.html');exit;}
        $this->assign('conf', $this->conf);
    }

    protected function fetch($template = '', $vars = [], $replace = [], $config = []) {
        $replace['__HOME__'] = str_replace('/index.php', '', Request::instance()->root()) . '/static/index';
        return $this->view->fetch($template, $vars, $replace, $config);
    }

}
