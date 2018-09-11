<?php

namespace app\ccenter\controller;

use think\Controller;
use think\Db;

class Base extends Controller {

    public function __construct() {
        parent::__construct();
        
        //session_unset();
        //验证登录
        $login = cookie('htlogin');
      	//print_r($login);//exit;//echo "////  ///";echo sha1($login['username']);exit;
        if (!isset($login['userid'])) {
            $this->error('请先登录！', 'login/login', 1, 1);
        }
	
        if (!isset($login['token']) || $login['token'] != sha1($login['username'])) {
            $this->redirect('login/logout');
        }

        $request = \think\Request::instance();

        $contrname = $request->controller();
        $actionname = $request->action();

        $this->assign('contrname', $contrname);
        $this->assign('actionname', $actionname);
        $this->assign('is_admin', $login['is_admin']);


        $this->otype = $login['otype'];
        $this->uid = $login['userid'];
        // $this->is_admin = $login['is_admin'];

        $this->assign('otype', $this->otype);
    }

    protected function fetch($template = '', $vars = [], $replace = [], $config = []) {
        $replace['__ADMIN__'] = str_replace('/index.php', '', \think\Request::instance()->root()) . '/static/admin';
        return $this->view->fetch($template, $vars, $replace, $config);
    }

}
