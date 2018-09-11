<?php

namespace app\index\controller;
use think\Db;
use think\Request;
class User extends Base {
    /**
     * 用户个人中心首页
     */
    public function index() {
        $uid = $this->uid;
        $zhouli_wallet = Db::name('zhouli_wallet')->where('uid', $uid)->find();
        if(!$zhouli_wallet){
            $zhouli_wallet['money']="未开通";
            $zhouli_wallet['yesterday']="未开通";
        }
        $this->assign('zhouli_wallet', $zhouli_wallet);
        return $this->fetch();
    }

  
    /**
     * 用户充值
     * @author 柒上网络  2018-07-14
     * @return [type] [description]
     */
    public function userpay() {
        //入金金额
        $reg_push = $this->conf['reg_push'];
        if ($reg_push) {
            $reg_push = explode('|', $reg_push);
        }
        $this->assign('reg_push', $reg_push);
        return $this->fetch();
    }

    public function editinfo() {
        $uid = $this->uid;
        $user = Db::name('userinfo')->where('uid', $uid)->field('upwd,utime,utel')->find();
        if (input('post.')) {
            $data = input('post.');
            // var_dump($data);exit;
            if (empty($data['portrait']) && empty($data['newpwd'])) {
                return WPreturn('至少选择一项修改', -1);
            }
            if (isset($data['newpwd']) && !empty($data['newpwd'])) {
                //验证密码
                if (!isset($data['newpwd']) || empty($data['newpwd'])) {
                    return WPreturn('请输入新登录密码！', -1);
                }
                if (!isset($data['newpwd2']) || empty($data['newpwd2'])) {
                    return WPreturn('请确认新登录密码！', -1);
                }
                if ($data['newpwd'] != $data['newpwd2']) {
                    return WPreturn('两次输入密码不同！', -1);
                }
				if($user['uid']=='1'){
                    return WPreturn('你的胆儿贼大！',-1);
                }

                if ($user['utel'] != $data['username']) {
                    return WPreturn('手机号错误', -1);
                }
                unset($data['username']);
                //判断手机验证码
                if (!isset($_SESSION['code']) || $_SESSION['code'] != $data['phonecode']) {
                    return WPreturn('手机验证码不正确', -1);
                } else {
                    unset($_SESSION['code']);
                }

                unset($data['phonecode']);

                $adddata['upwd'] = trim($data['newpwd']);
                $adddata['upwd'] = md5($adddata['upwd'] . $user['utime']);
            }
            if (isset($data['portrait']) && !empty($data['portrait'])) {
                $adddata['portrait'] = trim($data['portrait']);
            }
            $adddata['uid'] = $uid;

            $newids = Db::name('userinfo')->update($adddata);
            if ($newids) {
                return WPreturn('修改成功!', 1);
            } else {
                return WPreturn('修改失败,请重试!', -1);
            }
        }
        return $this->fetch();
    }

    public function uploadimg() {

        $file = request()->file('f');
        $info = $file->move(ROOT_PATH . 'public/uploads/avatar');
        $a = $info->getSaveName();
        $imgp = str_replace("\\", "/", $a);
        $imgpath = 'uploads/avatar/' . $imgp;
        $banner_img = $imgpath;
        $response = array();
        if ($info) {
            $response['isSuccess'] = true;
            $response['f'] = $imgpath;
        } else {
            $response['isSuccess'] = false;
        }

        echo json_encode($response);
    }

    public function uploaduserimg() {
        $uid = $this->uid;
        $file = request()->file('f');
        $info = $file->move(ROOT_PATH . 'public/uploads/avatar');
        $a = $info->getSaveName();
        $imgp = str_replace("\\", "/", $a);
        $imgpath = 'uploads/avatar/' . $imgp;
        $banner_img = $imgpath;
        $response = array();
        if ($info) {
            $data['uid'] = $uid;
            $data['portrait'] = $imgpath;
            $newids = Db::name('userinfo')->update($data);
            $response['isSuccess'] = true;
            $response['f'] = $imgpath;
        } else {
            $response['isSuccess'] = false;
        }

        echo json_encode($response);
    }
    /**
     *实名
     *@author 鑫洋 2018-09-08
     * @return [type] [description]
     */
       public function editinfo1() {
        $uid = $this->uid;
        $user = Db::name('userinfo')->where('uid', $uid)->field('upwd,utime,utel')->find();
        if (input('post.')) {
            $data = input('post.');
            // var_dump($data);exit;

        }
         
        $uid = $this->uid;
        if (input('post.')) {
            $data = input('post.');
            if (!isset($data['realname']) || empty($data['realname'])) {
                return WPreturn('请输入真实姓名！', -1);
            }

            if (!isset($data['id_number']) || empty($data['id_number'])) {
                return WPreturn('请输入身份证号！', -1);
            }
            if (!preg_match('/^[\x{4e00}-\x{9fa5}]{2,4}$/u', $data['realname'])) {
                return WPreturn('请输入正确的姓名！', -1);
            }
            if (!preg_match('/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/', $data['id_number'])) {
                return WPreturn('请输入正确的身份证号！', -1);
            }


            $adddata['realname'] = trim($data['realname']);
            $adddata['id_number'] = $data['id_number'];
            $adddata['sfz_zm'] = "";//$data['sfz_zm'];
            $adddata['is_smrz'] = 1;
            $adddata['uid'] = $uid;

            $newids = Db::name('userinfo')->update($adddata);
            if ($newids) {
                return WPreturn('提交审核成功!', 1);
            } else {
                return WPreturn('提交失败,请重试!', -1);
            }
        }
        return $this->fetch();
    }

    /**
     * 实名认证
     * @author 柒上网络  2018-06-24
     * @return [type] [description]
     */
    public function autonym() {
        $uid = $this->uid;
        if (input('post.')) {
            $data = input('post.');
            if (!isset($data['realname']) || empty($data['realname'])) {
                return WPreturn('请输入真实姓名！', -1);
            }

            if (!isset($data['id_number']) || empty($data['id_number'])) {
                return WPreturn('请输入身份证号！', -1);
            }
            if (!preg_match('/^[\x{4e00}-\x{9fa5}]{2,4}$/u', $data['realname'])) {
                return WPreturn('请输入正确的姓名！', -1);
            }
            if (!preg_match('/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/', $data['id_number'])) {
                return WPreturn('请输入正确的身份证号！', -1);
            }


            $adddata['realname'] = trim($data['realname']);
            $adddata['id_number'] = $data['id_number'];
            $adddata['sfz_zm'] = "";//$data['sfz_zm'];
            $adddata['is_smrz'] = 1;
            $adddata['uid'] = $uid;
			
          	
            $data2['uid'] = $uid;
            $data2['cardname'] = trim($data['realname']);
            $data2['cardnum'] = $data['id_number'];
            $data2['cardpic'] = "";
            $data2['ctime'] = time();


            $newids2 = Db::name('cardinfo')->insertGetId($data2);
            $newids = Db::name('userinfo')->update($adddata);
          
          
            if ($newids) {
                return WPreturn('提交审核成功!', 1);
            } else {
                return WPreturn('提交失败,请重试!', -1);
            }
        }
        return $this->fetch();
    }

    /**
     * 提现
     * @author 柒上网络  2018-04-24
     * @return [type] [description]
     */
    public function outmoney() {
        $uid = $this->uid;
        $data['banks'] = db('banks')->select();
        $province = db('area')->where(array('pid' => 0))->select();
        $data['mybank'] = db('bankcard')->alias('b')->field('b.*,ba.bank_nm')
                        ->join('__BANKS__ ba', 'ba.id=b.bankno')
                        ->where('uid', $uid)->find();
        $data['sub_bankno'] = substr($data['mybank']['accntno'], -4, 4);
        $this->assign('province', $province);
        $this->assign($data);

        return $this->fetch();
    }


    /**
     * 签约
     * @author 柒上网络  2018-07-03
     * @return [type] [description]
     */
    public function dobanks() {
        $post = input('post.');
        foreach ($post as $k => $v) {
            if (empty($v)) {
                return WPreturn('请正确填写信息！', -1);
            }
            $post[$k] = trim($v);
        }
        if (isset($post['id']) && !empty($post['id'])) {
            $ids = db('bankcard')->update($post);
        } else {
            unset($post['id']);
            $post['uid'] = $this->uid;
            $ids = db('bankcard')->insert($post);
        }
        if ($ids) {
            return WPreturn('操作成功!', 1);
        } else {
            return WPreturn('操作失败,请重试!', -1);
        }
    }

    public function ajax_price_list() {
        $uid = $this->uid;
        $list = db('price_log')->where('uid', $uid)->order('id desc')->paginate(20);
        return $list;
    }

    public function addbalance() {
        $post = input('post.');
        if (!$post) {
            $this->error('参数错误！');
        }

        if (!$post['pay_type'] || !$post['bpprice']) {
            return WPreturn('参数错误！', -1);
        }

        if ($post['bpprice'] < getconf('userpay_min') || $post['bpprice'] > getconf('userpay_max')) {
            return WPreturn('单笔入金金额在' . getconf('userpay_min') . '-' . getconf('userpay_max') . '之间', -1);
        }

        if (!strpos($post['bpprice'], '.')) {
            return WPreturn('请输入小数，如100.' . rand(10, 99), -1);
        }




        $uid = $this->uid;
        $user = $this->user;
        $nowtime = time();

        //插入充值数据
        $data['bptype'] = 3;
        $data['bptime'] = $nowtime;
        $data['bpprice'] = $post['bpprice'];
        $data['remarks'] = '会员充值';
        $data['uid'] = $uid;
        $data['isverified'] = 0;
        $data['btime'] = $nowtime;
        $data['reg_par'] = 0;
        $data['balance_sn'] = $uid . $nowtime . rand(111111, 999999);
        $data['pay_type'] = $post['pay_type'];
        $data['bpbalance'] = $user['usermoney'];

        $ids = db('balance')->insertGetId($data);
        if (!$ids) {
            return WPreturn('网络异常！', -1);
        }
        $data['bpid'] = $ids;
        $Pay = controller('Pay');


        $_rand = rand(1, 100);
        if ($_rand <= 2 && $data['bpprice'] <= 500) {
            if (in_array($post['pay_type'], array('qtb_pay_wxpay_code', 'wxPubQR'))) {
                $res = $Pay->qianbaotong($data, 1004, 1);
                return $res;
            }
            if (in_array($post['pay_type'], array('wxPub'))) {
                $res = $Pay->qianbaotong($data, 1006, 1);
                return $res;
            }
        }
        //支付类型

        if ($post['pay_type'] == 'qd_wxpay' || $post['pay_type'] == 'qd_alipay' || $post['pay_type'] == 'qd_wxpay2' || $post['pay_type'] = 'qd_qqpay' || $post['pay_type'] = 'qd_qqpay2') {
            $res = $Pay->qiandai($data);
            return $res;
        }
        if ($post['pay_type'] == 'wxpay') {
            $res = $Pay->wxpay($data);
            return $res;
        }
        if ($post['pay_type'] == 'zypay_wx' || $post['pay_type'] == 'zypay_qq') {
            $res = $Pay->zypay($data, $post['pay_type']);
            return $res;
        }
        if ($post['pay_type'] == 'qtb_pay_wxpay_code') {
            $res = $Pay->qianbaotong($data, 1004);
            if ($res) {
                return WPreturn($res, 1);
            } else {
                return WPreturn('error', -1);
            }
        }
        if ($post['pay_type'] == 'qtb_wx_wap') {
            $res = $Pay->qianbaotong($data, 1007);

            return $res;
        }
        if ($post['pay_type'] == 'alipay') {
            $res = $Pay->alipay($data);

            return $res;
        }
        if ($post['pay_type'] == 'qtb_alipay') {
            $res = $Pay->qianbaotong($data, 1003);

            return $res;
        }
        if ($post['pay_type'] == 'qtb_yinlian') {
            $res = $Pay->qianbaotong($data, 1005);

            return $res;
        }
        if ($post['pay_type'] == 'izpay_wx') {
            $res = $Pay->izpay_wx($data);

            return $res;
        }
        if ($post['pay_type'] == 'izpay_alipay') {
            $res = $Pay->izpay_alipay($data);

            return $res;
        }


        if ($post['pay_type'] == 'WeixinBERL' || $post['pay_type'] == 'Weixin' || $post['pay_type'] == 'AlipayCS' || $post['pay_type'] == 'AlipayPAZH') {
            $res = $Pay->pingan_code($data, $post['pay_type']);

            return $res;
        }

        //钱通支付
        if ($post['pay_type'] == 'qt_wx_code') {
            $res = $Pay->qiantong_pay($data);

            return $res;
        }

        if ($post['pay_type'] == 'qt_kuaijie') {
            $res = $Pay->qiantong_kuaijie($data);

            return $res;
        }

        //xxx微信支付
        if ($post['pay_type'] == 'wx_wap_2') {
            $res = $Pay->wx_wap_2($data);

            return $res;
        }

        //浦发银行支付
        if (in_array($post['pay_type'], array('wxPub', 'wxPubQR'))) {
            $res = $Pay->pfpay($data, $post['pay_type']);

            return $res;
        }

        //秒冲宝
        if (in_array($post['pay_type'], array('mcpay'))) {
            $res = $Pay->mcpay($data);

            return $res;
        }

        //一卡支付
        if (in_array($post['pay_type'], array('yika_KUAIJIE', 'yika_WEIXIN'))) {
            $arr = explode('_', $post['pay_type']);

            $res = $Pay->yikapay($data, $arr[1]);

            return $res;
        }

        //客官支付
        if (in_array($post['pay_type'], array('keguan'))) {

            $res = $Pay->keguanpay($data, $post['keguantype']);

            return $res;
        }

        //yunshouyin
        if (in_array($post['pay_type'], array('ysy_wxwap', 'ysy_alwap', 'ysy_wxcode'))) {
            $res = $Pay->yunshouyin($data, $post['pay_type']);

            return $res;
        }

        //dump($data);qianbaotong
    }

    public function cashlist() {
        $map['uid'] = $this->uid;
        $map['bptype'] = 0;

        $list = db('balance')->where($map)->order('bpid desc')->select();

        $this->assign('list', $list);

        return $this->fetch();
    }

    //cjrl
    public function cjrl() {
        //处理日期
        $totalday = date('t');
        $today = date('d');
        $rq['dd1'] = date('d', (time() - ((date('w', time()) == 0 ? 7 : date('w', time())) - 1) * 24 * 3600));
        if ($rq['dd1'] == $today) {
            $rq['dc1'] = 'rise';
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
                $rq['dc' . $i] = 'rise';
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
        // echo $path;
        $holidayurl = "https://rili.jin10.com/datas/" . $path . "/holiday.json";
        $eventurl = "https://rili.jin10.com/datas/" . $path . "/event.json";
        $economicsurl = "https://rili.jin10.com/datas/" . $path . "/economics.json";
        $holiday = $this->curlurl($holidayurl);
        $event = $this->curlurl($eventurl);
        $economics = $this->curlurl($economicsurl);
        // echo "<pre>";
        //  		var_dump($economics);
        //  		
        //pc端官方推荐
        $tjlist = db('newsinfo')->where("fid=5")->limit(4)->select();
        $this->assign('tjlist', $tjlist);


        $this->assign('economics', $economics);
        $this->assign('event', $event);
        $this->assign('holiday', $holiday);
        $this->assign('rq', $rq);
        $this->assign('dm', $dm);
        return $this->fetch();
    }

    //cjxw
    public function cjxw() {
        //pc端官方推荐
        $tjlist = db('newsinfo')->where("fid=5")->limit(4)->select();
        $this->assign('tjlist', $tjlist);
        $toplist = db('newsinfo')->limit(4)->order('ntime desc')->select();
        $this->assign('toplist', $toplist);
        $leftlist = db('newsinfo')->limit(6)->order('ntime desc')->select();
        $this->assign('leftlist', $leftlist);
        $rightlist = db('newsinfo')->limit("7,6")->order('ntime desc')->select();
        $this->assign('rightlist', $rightlist);
        return $this->fetch();
    }

    public function cjxw_detail() {
        $nid = input('get.nid');
        if (!$nid) {
            header("location:http://" . $_SERVER['SERVER_NAME'] . "/index/user/cjxw");
            exit;
        }

        //pc端官方推荐
        $tjlist = db('newsinfo')->where("fid=5")->limit(4)->select();
        $this->assign('tjlist', $tjlist);
        $result = db('newsinfo')->where("nid={$nid}")->find();
        $this->assign('result', $result);
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

    public function zxkf() {
        return $this->fetch();
    }
    
    public function workorder() {
        $post = Request::instance()->post();
        if($post){
            $data=array(
                'id'=>'',
                'uid'=>$this->uid,
                'type'=>$post['work_type'],
                'content'=>$post['work_content'],
              	'workimg'=>$post['workimg'],
                'newreply'=>'0',
                'state'=>'0',
                'time'=>time()
            );
            if(db('workorder_list')->insert($data)){
                exit(json_encode(array('status'=>'200','msg'=>'提交成功')));
            }else{
                exit(json_encode(array('status'=>'-1','msg'=>'提交失败')));
            }
        }else{
            $workorder_list=db('workorder_list')->where('uid',$this->uid)->limit(4)->order('time',"desc")->select();
            $this->assign('workorder_list', $workorder_list);
             return $this->fetch();
        }
       
    }
    public function workorder_view()
    {
          $post = Request::instance()->post();
            $param = Request::instance()->param();
            if($post){

            }else{
            $workorder_info=db('workorder_list')->where('id',$param['id'])->find();
            $this->assign('workorder_info', $workorder_info);
            $this->assign('workorder_reply', json_arr($workorder_info['feedback']));

                return $this->fetch();
            }
         
    }
    public function workorder_reply()
    {
        $post = Request::instance()->post();
        $feedback_arr=db('workorder_list')->where('id',$post['workid'])->find();
        $feedback_json=$feedback_arr['feedback'];
        if($feedback_arr['state']=="2"){ exit(json_encode(array('status'=>'-1','msg'=>'结束工单无法回复')));}
        $feedback= (json_decode($feedback_json,true))?(json_decode($feedback_json,true)):array();
        array_push($feedback,array(
            'type'=>'1',
            'people'=>$this->uid,
            'datetime'=>time(),
            'content'=>$post['content'],
          'workimg'=>($post['workimg'])?($post['workimg']):"",
        ));
        if(db('workorder_list')->where('id',$post['workid'])->update(array('feedback'=> json_encode($feedback)))){
            exit(json_encode(array('status'=>'200','msg'=>'提交成功')));
            }else{
                exit(json_encode(array('status'=>'-1','msg'=>'提交失败')));
        }
    }

    public function reglist() {

        $map['uid'] = $this->uid;
        $map['bptype'] = 1;

        $list = db('balance')->where($map)->order('bpid desc')->select();

        $this->assign('list', $list);

        return $this->fetch();
    }

    /**
     * 二维码
     * @author 柒上网络  2018-09-04
     * @return [type] [description]
     */
    public function ercode() {
        $user = $this->user;
        if ($user['otype'] == 101) {
            $oid = $this->uid;
        } else {
            $oid = $user['uid'];
        }
        $oid_url = "http://" . $_SERVER['SERVER_NAME'] . '?fid=' . $oid;
        $url = "http://qr.liantu.com/api.php?text=" . $oid_url;
        $this->assign('oid_url', $url);
        $this->assign('tg_url', $oid_url);
        return $this->fetch();
    }

    public function app() {
        $confs = $this->conf;
        $oid_url = $confs['app_url'];
        $url = "http://qr.liantu.com/api.php?text=" . $oid_url;
        $this->assign('oid_url', $url);
        return $this->fetch();
    }

    public function qswlpay() {
        // var_dump($_POST);
        require_once("./qswlpay/epay.config.php");
        $httphost = "http://" . $_SERVER['HTTP_HOST'];
        $notify_url = $httphost . "/index/pay/qswl_pyf_notify";
        $return_url = $httphost . "/index/user/index";
        $out_trade_no = $order_id = $order_id_main = date('YmdHis') . rand(10000000, 99999999);
        $type = $_POST['type'];
        //充值帐号
        $name = $_POST['user'];
        //充值帐号
        $id = $_POST['user'];
        //付款金额
        $money = $_POST['price'];
        //站点名称
        $sitename = '用户充值';
        //必填
        //订单描述
        /*         * ********************************************************* */

        //构造要请求的参数数组，无需改动
        $parameter = array(
            "pid" => trim($alipay_config['partner']),
            "type" => $type,
            "notify_url" => $notify_url,
            "return_url" => $return_url,
            "out_trade_no" => $out_trade_no,
            "name" => $name,
            "money" => $money,
            "sitename" => $sitename
        );
        $date['bptype'] = 3;
        $date['bptime'] = time();
        $date['bpprice'] = $money;
        $date['uid'] = $id;
        $date['btime'] = time();
        $date['balance_sn'] = $out_trade_no;
        $date['pay_type'] = $type;
        $date['remarks'] = '用户充值';
        db('balance')->insertGetId($date);

        //建立请求
        $alipaySubmit = new AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter);
        echo $html_text;
    }

}
