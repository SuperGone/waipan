<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<head>
	<title><?php echo $conf['web_name']?> - 在线测试</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="//cdn.bootcss.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet"/>
	<link rel="stylesheet" href="https://css.letvcdn.com/lc04_yinyue/201612/19/20/00/bootstrap.min.css">
	<script src="//cdn.bootcss.com/jquery/1.11.3/jquery.min.js"></script>
  	<link rel="icon" href="//www.71idc.cn/favicon.ico"  type="image/x-icon">
  <script src="//cdn.bootcss.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
  <div class="container" style="padding-top:2px;">
    <div class="col-xs-12 col-sm-10 col-lg-8 center-block" style="float: none;">
      <div class="panel panel-primary">
        <div class="panel-body">
        <form name=alipayment action=epayapi.php method=post target="_blank">
         <td colspan='3' style='background-color:#cccccc;'>
          <div id='marguee' style='text-align:center;font-size:35px;color:red'>游戏充值</div>
           </td>
			<div class="input-group">
            <div class="FL">充值帐号：
            <input type="text" class="floatLeft wbk" id="username" name="username" maxlength="12" value=""></div>
            <div class="FL"></div><div id='marguee' style='font-size:10px;color:red'>请填写游戏ID，非注册账号（点击头像可以查看ID）<br class="clear">		   
            </div>
			<br/>
			<div class="input-group">
            <div class="FL">金币数量：
            <input type="text" class="FL" id="yuanbao" name="yuanbao" maxlength="12" value=""></div>
             <div class="FL"></div>充值比例：1元=1000金币<br class="clear">		        
            </div>        			
<br/> 
<center>
<div class="btn-group btn-group-justified" role="group" aria-label="...">
  <div class="btn-group" role="group">
    <button type="radio" name="type" value="alipay" class="btn btn-primary">支付宝</button>
  </div>
  <div class="btn-group" role="group">
    <button type="radio" name="type" value="qqpay" class="btn btn-success">QQ</button>
  </div>
  <div class="btn-group" role="group">
    <button type="radio" name="type" value="wxpay" class="btn btn-info">微信</button>
  </div>
</div>

</script>

  </div>