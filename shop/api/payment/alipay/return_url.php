<?php
/**
 * 支付宝返回地址
 *
 * 
 * by 33hao 好商城V3  www.33hao.com 开发
 */
$_GET['act']	= 'payment';
$_GET['op']		= 'return';
$_GET['payment_code'] = 'alipay';
require_once(dirname(__FILE__).'/../../../index.php');
?>