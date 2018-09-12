<?php

use think\Db;

function get_client_ip($type = 0) {
    $type = $type ? 1 : 0;
    static $ip = NULL;
    if ($ip !== NULL)
        return $ip[$type];
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {//客户端的ip
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {//浏览当前页面的用户计算机的网关
        $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos = array_search('unknown', $arr);
        if (false !== $pos)
            unset($arr[$pos]);
        $ip = trim($arr[0]);
    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR']; //浏览当前页面的用户计算机的ip地址
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

function viewcode($str) {
    if (isset($_SESSION['viewauth']) && $_SESSION['viewauth'] == "ok") {
        return $str;
    } else {
        $resstr = substr_replace($str, '****', 3, 4);
        return $resstr;
    }
}

//二维数组最大值
function getArrayMax($arr, $field) {
    $temp = array();
    foreach ($arr as $k => $v) {
        array_push($temp, $v[$field]);
    }
    return max(array_unique($temp));
}

//二维数组最小值
function getArrayMin($arr, $field) {
    foreach ($arr as $k => $v) {
        $temp[] = $v[$field];
    }
    return min($temp);
}

//2位小数的随机数
function randomFloat($min, $max) {
    $num = $min + mt_rand() / mt_getrandmax() * ($max - $min);
    return sprintf("%.2f", $num);
}

function json_arr($json) {
    return json_decode($json, true);
}

function interest_line($bili, $count_wallet) {//随机正负区间波动
    if ($count_wallet == 0) {
        return 0;
        exit;
    }
    $k = rand(1, 100);
    if ($k % 4 == 0) {
        $a = "-";
    } else {
        $a = "";
    }
    return $a . $bili * ($count_wallet / 10000) / 100;
}

function shouxufanyong($yingli, $uid, $orderid) {

    $level1_fl = getconf('level1_fl') / 100;
    $level2_fl = getconf('level2_fl') / 100;
    $level3_fl = getconf('level3_fl') / 100;

    $db_userinfo = db('userinfo');

    //C 级分佣 $level1_fl
    $c_oid = $db_userinfo->where('uid', $uid)->value('fid');
    if ($c_oid && $c_oid != '1') {
        $c_money = $level1_fl * $yingli;
        change_zl_wallet($c_oid, '2', $c_money, $orderid);
    }
    //B 级分佣 $level2_fl
    $b_oid = $db_userinfo->where('uid', $c_oid)->value('fid');
    if ($b_oid && $b_oid != '1') {
        $b_money = $level2_fl * $yingli;
        change_zl_wallet($b_oid, '2', $b_money, $orderid);
    }
    //A 级分佣 $level3_fl
    $a_oid = $db_userinfo->where('uid', $b_oid)->value('fid');
    if ($a_oid && $a_oid != '1') {
        $a_money = $level3_fl * $yingli;
        change_zl_wallet($a_oid, '2', $a_money, $orderid);
    }
}

function change_zl_wallet($uid, $type, $money, $orderid) {
    //type:1、转入  -1、转出 0、收益 2、充值返佣
    if ($type < 0) { //转出
        Db::name('zhouli_wallet')->where('uid', $uid)->setDec('money', abs($money));
        Db::name('zhouli_log')->insert(array(
            'uid' => $uid,
            'type' => $type,
            'money' => $money,
            'time' => time()
        ));
        $jian_money = $money;
        while (true) {
            $log_zhuanru = db('zhouli_log')->where(array('uid' => $uid, 'type' => '1', 'state' => '1'))->order('time', 'asc')->limit(1)->find();
            $jian_money = $jian_money - $log_zhuanru['money'];
            if ($jian_money >= 0) {
                db('zhouli_log')->where(array('id' => $log_zhuanru['id']))->update(array('state' => '0'));
                if ($jian_money == 0) {
                    break;
                }
                continue;
            } else {
                db('zhouli_log')->where(array('id' => $log_zhuanru['id']))->update(array('money' => abs($jian_money)));
                break;
            }
        }
    } elseif ($type == "1") {//转入
        Db::name('zhouli_wallet')->where('uid', $uid)->setInc('money', abs($money));
        $data = array(
            'uid' => $uid,
            'type' => $type,
            'money' => $money,
            'time' => time(),
            'state' => '1'
        );
        Db::name('zhouli_log')->insert($data);
    } elseif ($type == "0") {//收益
        Db::name('zhouli_wallet')->where('uid', $uid)->setInc('accumulation', abs($money));
        $data = array(
            'uid' => $uid,
            'type' => $type,
            'money' => $money,
            'time' => time()
        );
        Db::name('zhouli_log')->insert($data);
    } elseif ($type == "2") {//分佣转入账户
        db('userinfo')->where('uid', $uid)->setInc('usermoney', $money);
        $o_log['user_money'] = db('userinfo')->where('uid', $uid)->value('usermoney');
        set_fanli_log($uid, 1, $money, '返佣', '手续费返佣', $orderid, $o_log['user_money']);
    }
}

function isMobile() {
    global $_G;
    $mobile = array();
//各个触控浏览器中$_SERVER['HTTP_USER_AGENT']所包含的字符串数组
    static $touchbrowser_list = array('iphone', 'android', 'phone', 'mobile', 'wap', 'netfront', 'java', 'opera mobi', 'opera mini',
        'ucweb', 'windows ce', 'symbian', 'series', 'webos', 'sony', 'blackberry', 'dopod', 'nokia', 'samsung',
        'palmsource', 'xda', 'pieplus', 'meizu', 'midp', 'cldc', 'motorola', 'foma', 'docomo', 'up.browser',
        'up.link', 'blazer', 'helio', 'hosin', 'huawei', 'novarra', 'coolpad', 'webos', 'techfaith', 'palmsource',
        'alcatel', 'amoi', 'ktouch', 'nexian', 'ericsson', 'philips', 'sagem', 'wellcom', 'bunjalloo', 'maui', 'smartphone',
        'iemobile', 'spice', 'bird', 'zte-', 'longcos', 'pantech', 'gionee', 'portalmmm', 'jig browser', 'hiptop',
        'benq', 'haier', '^lct', '320x320', '240x320', '176x220');
//window手机浏览器数组【猜的】
    static $mobilebrowser_list = array('windows phone');
//wap浏览器中$_SERVER['HTTP_USER_AGENT']所包含的字符串数组
    static $wmlbrowser_list = array('cect', 'compal', 'ctl', 'lg', 'nec', 'tcl', 'alcatel', 'ericsson', 'bird', 'daxian', 'dbtel', 'eastcom',
        'pantech', 'dopod', 'philips', 'haier', 'konka', 'kejian', 'lenovo', 'benq', 'mot', 'soutec', 'nokia', 'sagem', 'sgh',
        'sed', 'capitel', 'panasonic', 'sonyericsson', 'sharp', 'amoi', 'panda', 'zte');
    $pad_list = array('pad', 'gt-p1000');
    $useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
    if (dstrpos($useragent, $pad_list)) {
        return false;
    }
    if (($v = dstrpos($useragent, $mobilebrowser_list, true))) {
        $_G['mobile'] = $v;
        return '1';
    }
    if (($v = dstrpos($useragent, $touchbrowser_list, true))) {
        $_G['mobile'] = $v;
        return '2';
    }
    if (($v = dstrpos($useragent, $wmlbrowser_list))) {
        $_G['mobile'] = $v;
        return '3'; //wml版
    }
    $brower = array('mozilla', 'chrome', 'safari', 'opera', 'm3gate', 'winwap', 'openwave', 'myop');
    if (dstrpos($useragent, $brower))
        return false;
    $_G['mobile'] = 'unknown';
//对于未知类型的浏览器，通过$_GET['mobile']参数来决定是否是手机浏览器
    if (isset($_G['mobiletpl'][$_GET['mobile']])) {
        return true;
    } else {
        return false;
    }
}

function dstrpos($string, $arr, $returnvalue = false) {
    if (empty($string))
        return false;
    foreach ((array) $arr as $v) {
        if (strpos($string, $v) !== false) {
            $return = $returnvalue ? $v : true;
            return $return;
        }
    }
    return false;
}

/**
 * 自定义返回提示信息
 * @author 柒上网络  2018-07-14
 * @param  [type] $data [description]
 * @param  [type] $type [description]
 */
function WPreturn($data, $type, $url = null) {
    $res = array('data' => $data, 'type' => $type);
    if ($url) {
        $res['url'] = $url;
    }
    return $res;
}

function gettimehs($data) {
    $times = strtotime($data);
    $sj = date("H:i", $times);
    return $sj;
}

function getfname($fid) {
    $tjlist = db('newsclass')->where("fid={$fid}")->find();
    return $tjlist['fclass'];
}

/**
 * 验证用户
 * @author 柒上网络  2018-07-17
 * @param  [type] $upwd 密码（未加密）
 * @param  [type] $uid  用户id
 * @return [type]       true or false
 */
function checkuser($upwd, $uid) {
    if (!isset($upwd) || empty($upwd)) {
        return false;
    }
    if (isset($uid) && !empty($uid)) {  //user
        $where['uid'] = $uid;
    } else {  //admin
        $where['uid'] = $_SESSION['userid'];
    }

    $admin = Db::name('userinfo')->field('uid,utime,upwd')->where($where)->find();
    if (md5($upwd . $admin['utime']) == $admin['upwd']) {
        return true;
    } else {
        return false;
    }
}

/**
 * 验证邀请码是否存在
 * @author 柒上网络  2018-07-17
 * @param  [type] $code 邀请码
 * @return [type]       code id
 */
function checkcode($code) {
    if (!isset($code) || empty($code)) {
        return false;
    }
    $codeid = Db::name('userinfo')->where(array('uid' => $code))->value('uid');
    if ($codeid) {
        return $codeid;
    } else {
        return false;
    }
}

function GetUserOidInfo($uid, $field) {
    if (!isset($uid) || empty($uid)) {
        return false;
    }
    if (!isset($field) || empty($field)) {
        $field = '*';
    }
    $res = array();
    //验证用户,获取oid
    $useroid = Db::name('userinfo')->where('uid', $uid)->value('oid');
    if (!$useroid) {
        return false;
    }
    //邀请码信息
    $oid_info = Db::name('usercode')->where('usercode', $useroid)->find();

    //通过邀请码的uid查询所属员工信息
    $res['yuangong'] = Db::name('userinfo')->field($field)->where('uid', $oid_info['uid'])->find();

    //通过员工oid查找经理信息
    $res['jingli'] = Db::name('userinfo')->field($field)->where('uid', $res['yuangong']['oid'])->find();

    //通过邀请码的mannerid查询所属员工信息
    $res['qudao'] = Db::name('userinfo')->field($field)->where('uid', $oid_info['mannerid'])->find();

    if ($res) {
        return $res;
    } else {
        return false;
    }
}

/**
 * 获取员工的所有客户
 * @author 柒上网络  2018-07-17
 * @param  [type] $uid 员工id
 */
function YuangongUser($uid) {

    if (!isset($uid) || empty($uid)) {
        return false;
    }
    $oid_info = $user = array();
    //获取员工的所有邀请码
    $oid_info = Db::name('usercode')->where('uid', $uid)->column('usercode');
    if ($oid_info) {
        //通过邀请码获取客户
        $user = Db::name('userinfo')->where('oid', 'IN', $oid_info)->column('uid');
    }
    return $user;
}

/**
 * 获取经理的所有客户
 * @author 柒上网络  2018-07-17
 * @param  [type] $uid [description]
 */
function JingliUser($uid) {
    if (!isset($uid) || empty($uid)) {
        return false;
    }
    $yg_user = $user = array();

    //获取经理下的所有员工
    $yg_user = Db::name('userinfo')->where('oid', $uid)->column('uid');
    foreach ($yg_user as $value) {
        $user += YuangongUser($value);
    }

    return $user;
}

/**
 * 获取渠道的所有客户
 * @author 柒上网络  2018-07-17
 * @param  [type] $uid [description]
 */
function QudaoUser($uid) {
    if (!isset($uid) || empty($uid)) {
        return false;
    }
    $oid_info = $user = array();
    //获取渠道的所有邀请码
    $oid_info = Db::name('usercode')->where('mannerid', $uid)->column('usercode');

    if ($oid_info) {
        //通过邀请码获取客户
        $user = Db::name('userinfo')->where('oid', 'IN', $oid_info)->column('uid');
    }

    return $user;
}

/**
 * 根据任意会员查询所属所有客户
 * @author 柒上网络  2018-07-18
 * @param  [type] $uid 会员id
 */
function UserCodeForUser($uid) {
    if (!isset($uid) || empty($uid)) {
        return false;
    }
    //查询uid的身份
    $otype = Db::name('userinfo')->where('uid', $uid)->value('otype');
    $u_uid = array();
    //获取会员的客户id
    if ($otype == 2) {  //经理
        $u_uid = JingliUser($uid);
    } elseif ($otype == 3) {  //渠道
        $u_uid = QudaoUser($uid);
    } elseif ($otype == 4) {  //员工
        $u_uid = YuangongUser($uid);
    } else {
        return false;
    }
    return($u_uid);
}

function GetProData($pid, $field = null) {
    if (!isset($pid) || empty($pid)) {
        return false;
    }
    if (!$field) {
        $field = 'pi.*,pd.*';
    }
    $data = Db::name('productinfo')->alias('pi')->field($field)
                    ->join('__PRODUCTDATA__ pd', 'pd.pid=pi.pid')
                    ->where('pi.pid', $pid)->find();
    return $data;
}

function getconf($field) {
    $conf = array();
    $res = '';
    $conf_cache = cache('conf');
    if (!$conf_cache) {
        $conf = Db::name('config')->select();
        foreach ($conf as $k => $v) {
            $conf_value[$v['name']] = $v['value'];
        }
        cache('conf', $conf_value);
        $conf_cache = cache('conf');
    }

    if (isset($conf_cache[$field]) && $field) {
        $res = $conf_cache[$field];
    } else {
        $res = $conf_cache;
    }
    return $res;
}

function getarea($id) {
    $name = db('area')->where('id', $id)->value('name');
    return $name;
}

function set_price_log($uid, $type, $account, $title, $content, $oid = 0, $nowmoney) {
    $data['uid'] = $uid;
    $data['type'] = $type;
    $data['account'] = $account;
    $data['title'] = $title;
    $data['content'] = $content;
    $data['oid'] = $oid;
    $data['time'] = time();
    $data['nowmoney'] = $nowmoney;
    db('price_log')->insert($data);
}

function set_fanli_log($uid, $type, $account, $title, $content, $oid = 0, $nowmoney) {
    $data['uid'] = $uid;
    $data['type'] = $type;
    $data['account'] = $account;
    $data['title'] = $title;
    $data['content'] = $content;
    $data['oid'] = $oid;
    $data['time'] = time();
    $data['nowmoney'] = $nowmoney;
    db('fanli_log')->insert($data);
}

//删除空格和回车
function trimall($str) {
    $qian = array(" ", "　", "\t", "\n", "\r");
    return str_replace($qian, '', $str);
}

//计算小数点后位数
function getFloatLength($num) {
    $count = 0;
    $temp = explode('.', $num);
    if (sizeof($temp) > 1) {
        $decimal = end($temp);
        $count = strlen($decimal);
    }
    return $count;
}

//PHP的两个科学计数法转换为字符串的方法
function NumToStr($num) {
    if (stripos($num, 'e') === false)
        return $num;
    $num = trim(preg_replace('/[=\'"]/', '', $num, 1), '"'); //出现科学计数法，还原成字符串
    $result = "";
    while ($num > 0) {
        $v = $num - floor($num / 10) * 10;
        $num = floor($num / 10);
        $result = $v . $result;
    }
    return $result;
}

/**
 * 我的代理商下级类别
 * @return array uids
 */
function myoids($uid) {
    if (cookie('oids')) {
        //return cookie('oids');
    }

    if (!$uid) {
        return false;
    }
    $map['oid'] = $uid;
    $map['otype'] = 101;

    $list = db('userinfo')->field('uid')->where($map)->select();

    if (empty($list)) {
        return false;
    }

    $uids = array();
    foreach ($list as $key => $v) {
        $user = myoids($v["uid"]);
        $uids[] = $v["uid"];
        if (is_array($user) && !empty($user)) {
            $uids = array_merge($uids, $user);
        }
    }

    cookie('oids', $uids, 60 * 20);
    return $uids;
}

/**
 * 获取次代理商的所有用户下级
 * @param  [type] $uid [description]
 * @return [type]      [description]
 */
function myuids($uid) {

    if (cookie('uids')) {
        //return cookie('uids');
    }
    $oids = myoids($uid);
    $oids[] = $uid;

    $map['oid'] = array('in', $oids);
    $map['otype'] = array('IN', array(0, 101));

    $user = db('userinfo')->field('uid')->where($map)->select();
    $_me = array(0 => array('uid' => $uid));
    if ($user) {
        $user = array_merge($_me, $user);
    } else {

        $uids = array($uid);
        return $uids;
    }


    $uids = array();
    if (empty($user)) {
        return $uids;
    }

    foreach ($user as $k => $v) {
        $uids[] = $v['uid'];
    }
    cookie('uids', $uids, 60 * 20);
    return $uids;
}

/**
 * 我的所有上级用户id
 * @param  [type] $uid [description]
 * @return [type]      [description]
 */
function myupoid($uid) {
    if (!$uid) {
        return false;
    }
    $map['uid'] = $uid;
    $map['otype'] = 101;
    $user = db('userinfo')->field('uid,oid,rebate,usermoney,feerebate,minprice')->where($map)->find();
    if ($user['uid'] == $user['oid']) {
        return false;
    }

    $list = array();
    if ($user) {
        $list[] = $user;
        $user = myupoid($user["oid"]);
        if (is_array($user) && !empty($user)) {
            $list = array_merge($list, $user);
        }
    }
    return $list;
}

/**
 * 我的代理商下级类别
 * @return array uids
 */
function mytime_oids($uid) {

    if (!$uid) {
        return false;
    }
    $map['oid'] = $uid;
    $map['otype'] = 101;

    $list = db('userinfo')->field('uid,oid,username,utel,nickname,usermoney')->where($map)->select();
    $uids = array();
    foreach ($list as $key => $v) {
        $user = mytime_oids($v["uid"]);
        $uids[$key] = $v;
        if (is_array($user) && !empty($user)) {
            //$uids += $user;
            $uids[$key]['mysons'] = $user;
        }
    }
    return $uids;
}

/**
 * 我的团队树状图
 * @author 柒上网络  2018-07-18
 * @param  [type]  $array [description]
 * @param  integer $type  [description]
 */
function set_my_team_html($array, $type = 1) {

    if (!$array) {
        return false;
    }

    $margin_left = 25 + 25 * $type;

    $html = '<div  class="foid_' . $array[0]['oid'] . '">';
    foreach ($array as $k => $vo) {
        //dump($v);
        $html .= '<div style="display:none" class="oid_list oid_' . $vo['oid'] . '">
	                  <div class="vo_son" style="margin-left: ' . $margin_left . 'px;"><p>|——' . $type . '级代理</p></div>
	                    <div class="div_my_son">
	                      <ul class="my_sons">
	                        <li>代理名：' . $vo['username'] . ' 余额：' . $vo['usermoney'] . '</li>
	                        <li>手机：' . $vo['utel'] . ' <a href="/ccenter/user/userlist.html?uid=' . $vo['uid'] . '"><button class="btn btn-primary btn-xs">详情</button></a></li>
	                      </ul>
	                      <a href="javascript:;"><p class="showdiv show_uid_' . $vo['uid'] . '" onclick="showoid(' . $vo['uid'] . ',1)" >+</p></a>
	                      </div>
	                </div>
	                ';

        if (isset($vo['mysons']) && is_array($vo['mysons']) && !empty($vo['mysons'])) {
            $html .= set_my_team_html($vo['mysons'], $type + 1);
        }
    }

    $html .= '</div>';
    return $html;
}

function ChickIsOpen($pid) {
    $isopen = 0;
    if (!cache('panzi_pro')) {
        cache('panzi_pro', db('productinfo')->where(array('pid' => $pid))->find());
    }
    $pro = cache('panzi_pro');
    //此时时间
    $_time = time();
    $_zhou = (int) date("w");
    if ($_zhou == 0) {
        $_zhou = 7;
    }
    $_shi = (int) date("H");
    $_fen = (int) date("i");
    if ($pro['isopen']) {
        $opentime = db('opentime')->where('pid=' . $pid)->find();
        if ($opentime) {
            $otime_arr = explode('-', $opentime["opentime"]);
        } else {
            $otime_arr = array('', '', '', '', '', '', '');
        }
        foreach ($otime_arr as $k => $v) {
            if ($k == $_zhou - 1) {
                $_check = explode('|', $v);
                if (!$_check) {
                    continue;
                }
                foreach ($_check as $key => $value) {
                    $_check_shi = explode('~', $value);
                    if (count($_check_shi) != 2) {
                        continue;
                    }
                    $_check_shi_1 = explode(':', $_check_shi[0]);
                    $_check_shi_2 = explode(':', $_check_shi[1]);
                    //开市时间在1与2之间
                    if ($isopen == 1) {
                        continue;
                    }
                    if (($_check_shi_1[0] == $_shi && $_check_shi_1[1] < $_fen) ||
                            ($_check_shi_1[0] < $_shi && $_check_shi_2[0] > $_shi) ||
                            ($_check_shi_2[0] == $_shi && $_check_shi_2[1] > $_fen)
                    ) {
                        $isopen = 1;
                    } else {
                        $isopen = 0;
                    }
                }
            }
        }
    }
    if ($pro['isopen']) {
        return $isopen;
    } else {
        return 0;
    }
}

function cash_oid($uid) {

    if (!$uid) {
        return '<td></td><td></td>';
    }

    $user = db('userinfo')->where('uid', $uid)->field('uid,usermoney,minprice')->find();
    if (!$user['minprice'])
        $user['minprice'] = 0;

    if ($user['usermoney'] >= $user['minprice']) {
        $minprice = $user['minprice'];
        $class = '';
    } else {
        $minprice = $user['usermoney'] - $user['minprice'];
        $class = 'style="color:red";';
    }

    return '<td> <a title="点击查看" href="/ccenter/user/userlist.html?uid=' . $uid . '"> ' . $uid . ' </a> </td><td ' . $class . '>' . $minprice . '</td>';
}

function check_user($field, $value) {
    if (!$value) {
        return false;
    }

    $isset = db('userinfo')->where($field, $value)->value('uid');
    if ($isset) {
        return true;
    } else {
        return false;
    }
}

function getuser($uid, $field) {
    $value = db('userinfo')->where('uid', $uid)->value($field);
    return $value;
}

function getusers($uid, $field) {
    $value = db('userinfo')->where('uid', $uid)->value($field);
    if ($value == '') {
        $value = db('userinfo')->where('uid', $uid)->value('managername');
        echo $value;
    }
    return $value;
}

function ordernum($uid) {

    if (!$uid) {
        return false;
    }
    $num = db('order')->where('uid', $uid)->count();
    if (!$num)
        $num = 0;
    return $num;
}

function xml_to_array($xml) {
    return json_decode(json_encode((array) simplexml_load_string($xml)), true);
}
