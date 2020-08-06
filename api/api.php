<?php
$mysql = new PDO('mysql:dbname=;host=localhost','','',array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';"));
$fakadb = new PDO('mysql:dbname=;host=localhost','','',array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';"));
$T = empty($_GET['t'])?'':$_GET['t'];
switch ($T) {
    case 'cardvalid':
        if (strlen($_POST['card']) != 8) {
            $ret = array('status' => 110001,'ret' => '激活校验失败');
        } else {
            $a = $mysql->prepare('SELECT * FROM `card` WHERE `card` = ?');
            $a->execute(array($_POST['card']));
            $res = $a->fetchAll();
            if (empty($res)) {
                $ret = array('status' => 110002,'ret' => '激活校验失败');
            } else {
                $a = $fakadb->prepare('SELECT * FROM `cards` WHERE `card` = ?');
                $a->execute(array($_POST['card']));
                $res = $a->fetchAll();
                if (empty($res)) {
                    $ret = array('status' => 110003,'ret' => '激活校验失败');
                } else {
                    $cardid = $res[0]['id'];
                    $a = $fakadb->prepare('SELECT * FROM `card_order` WHERE `card_id` = ?');
                    $a->execute(array($cardid));
                    $res = $a->fetchAll();
                    if (empty($res)) {
                        $ret = array('status' => 110004,'ret' => '激活校验失败');
                    } else {
                        $orderid = $res[0]['order_id'];
                        $a = $fakadb->prepare('SELECT * FROM `orders` WHERE `id` = ?');
                        $a->execute(array($orderid));
                        $res = $a->fetchAll();
                        $phone = $res[0]['contact'];
                        $ret = array('status' => 0,'ret' => $phone);
                    }
                }
            }
        }
        break;
    case 'phonevalid':
        // code...
        break;

    case 'newcard':
        if ($_GET['admin'] != '963852741') {
            $ret = array('status' => 170002,'ret' => 'Request denied');
        } else {
            $sql = "INSERT INTO `card` (`card`,`time`) VALUES ";
            for ($i = 0; $i < intval($_GET['n'])-1;
                $i++) {
                $sql = $sql.'(\''.substr(md5(crypt(rand())),8,8).'\',now()),';
            }
            $sql = $sql.'(\''.substr(md5(crypt(rand())),8,8).'\',now());';
            $mysql->query($sql);
            $ret = array('status' => 0,'ret' => 'card insert Success');
        }
        break;

    case 'uservalid':
        if (strlen($_POST['card']) == 0) {
            $ret = array('status' => 120001,'ret' => '项目注册失败');
        } else {
            $a = $mysql->prepare('SELECT * FROM `card` WHERE `card` = ?');
            $a->execute(array($_POST['card']));
            $res = $a->fetchAll();
            if (empty($res)) {
                $ret = array('status' => 120002,'ret' => '项目注册失败');
            } else {
                $res = $res[0];
                if (!empty($res['user'])) {
                    if ($res['user'] == $_POST['user']) {
                        //鉴权(php)取已上传文件列表
                        $ret = array('status' => 120000,'ret' => '该功能暂未开放');
                    } else {
                        $ret = array('status' => 120003,'ret' => '项目注册失败');
                    }
                } else {
                    $a = $mysql->prepare('SELECT * FROM `card` WHERE `user` = ?');
                    $a->execute(array($_POST['user']));
                    $res = $a->fetchAll();
                    if (!empty($res)) {
                        $ret = array('status' => 120004,'ret' => '该项目已存在');
                    } else {
                        $a = $mysql->prepare('UPDATE `card` SET `user` = ?,`timeuse` = now() WHERE `card` = ?');
                        $a->execute(array($_POST['user'],$_POST['card']));
                        $ret = array('status' => 0,'ret' => 'project insert Success');
                    }
                }
            }
        }
        break;

    case 'getauth':
        if (strlen($_POST['card']) == 0) {
            $ret = array('status' => 130001,'ret' => '鉴权获取失败');
        } else {
            $a = $mysql->prepare('SELECT * FROM `card` WHERE `card` = ? AND `user` = ?');
            $a->execute(array($_POST['card'],$_POST['user']));
            $res = $a->fetchAll();
            if (empty($res)) {
                $ret = array('status' => 130002,'ret' => '鉴权获取失败');
            } else {
                include './qcloud-sts-sdk.php';
                $sts = new STS();
                $config = array(
                    'url' => 'https://sts.tencentcloudapi.com/',
                    'domain' => 'sts.tencentcloudapi.com',
                    'proxy' => '',
                    'secretId' => '', // 固定密钥
                    'secretKey' => '', // 固定密钥
                    'bucket' => '', // 换成你的 bucket
                    'region' => 'ap-guangzhou', // 换成 bucket 所在园区
                    'durationSeconds' => 1800, // 密钥有效期
                    'allowPrefix' => $_POST['user'].'/*',
                    'allowActions' => array (
                        'name/cos:PutObject',
                        'name/cos:PostObject',
                    )
                );
                $tempKeys = $sts->getTempKeys($config);
                $ret = array('status' => 0,'ret' => $tempKeys);
            }
        }
        break;

    default:
        $ret = array('status' => 170001,'ret' => 'Request denied');
        break;
}
$return = json_encode($ret,JSON_UNESCAPED_UNICODE);
header('Content-Type:application/json');
echo $return;
?>