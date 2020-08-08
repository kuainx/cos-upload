<?php
if(empty($_GET['code'])){
    header("Location:https://mp.weixin.qq.com/mp/profile_ext?action=home&__biz=MzUxNjM2NzM3Ng==#wechat_redirect");
    exit;
}
include('./util.php');
//https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx61b12b15f8cbb912&redirect_uri=https%3a%2f%2flifestudio.cn%2fup%2fapi%2fserver.php&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect
$mysql = new PDO('mysql:dbname=cosupload;host=localhost','cosupload','XCP8kcCrMA6jnFX8',array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';"));
$ret = httpRequest('https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx61b12b15f8cbb912&secret=&code='.$_GET['code'].'&grant_type=authorization_code');
$ret = httpRequest('https://api.weixin.qq.com/sns/userinfo?access_token='.$ret['access_token'].'&openid='.$ret['openid'].'&lang=zh_CN');
$a = $mysql->prepare('INSERT INTO `wechat` (`openid`, `user`, `phone`) VALUES (?, ?, ?)');
$a = $mysql->prepare('SELECT * FROM `wechat` WHERE `phone` = ?');
$a->execute(array($_GET['state']));
$res = $a->fetchAll();
if (empty($res)) {
    $a = $mysql->prepare('INSERT INTO `wechat` (`openid`, `user`, `phone`) VALUES (?, ?, ?)');
    $a->execute(array($ret['openid'],$ret['nickname'],$_GET['state']));
} else {
    $a = $mysql->prepare('UPDATE `wechat` SET `openid` = ?,`user` = ?, `phone` = ? WHERE `id` = ?');
    $a->execute(array($ret['openid'],$ret['nickname'],$_GET['state'],$res[0]['id']));
}
header("Location:https://mp.weixin.qq.com/mp/profile_ext?action=home&__biz=MzUxNjM2NzM3Ng==#wechat_redirect");