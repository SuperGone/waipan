<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\Cookie;

class Pay extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }
    function qswl_pyf_notify(){
        require_once("./qswlpay/epay.config.php");
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();
        if($verify_result) {//验证成功
            $out_trade_no = $_GET['out_trade_no'];
            //拼云付交易号
            $trade_no = $_GET['trade_no'];
            //交易状态
            $trade_status = $_GET['trade_status'];
            //支付方式
            $type = $_GET['type'];
            if ($_GET['trade_status'] == 'TRADE_SUCCESS') {
                $balance = db('balance')->where('balance_sn',$out_trade_no)->find();
                //余额
                $user_money = db('userinfo')->where('uid',$balance['uid'])->value('usermoney');
                $_edit['bptype'] = 1;
                $_edit['bptype'] = 1;
                $_edit['isverified'] = 1;
                $_edit['cltime'] = time();
                $_edit['bpbalance'] = $balance['bpprice']+$user_money;
                db('balance')->where('balance_sn',$out_trade_no)->update($_edit);
                $_ids=db('userinfo')->where('uid',$balance['uid'])->setInc('usermoney',$balance['bpprice']);
                set_price_log($balance['uid'],1,$balance['bpprice'],'充值','用户充值',$balance['bpid'],$_edit['bpbalance']);
              
            }  
            echo "success";     //请不要修改或删除
        }
        else {
            echo "fail";
        }
    }

}


?>