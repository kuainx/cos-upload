<?php
$mysql = new PDO('mysql:dbname=;host=localhost','','',array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';"));
$fakadb = new PDO('mysql:dbname=;host=localhost','','',array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';"));
$T = empty($_GET['t'])?'':$_GET['t'];
switch ($T) {
    case 'phonevalid':
        if (strlen($_POST['phone']) == 0) {
            $ret = array('status' => 110001,'ret' => '用户校验失败');
        } else {
            $a = $fakadb->prepare('SELECT `order_no`, `product_name`, `id` FROM `orders` WHERE `contact` = ? AND `status` =\'2\'');
            $a->execute(array($_POST['phone']));
            $res = $a->fetchAll();
            if (empty($res)) {
                $ret = array('status' => 110002,'ret' => '用户校验失败');
            } else {
                $ret = array('status' => 0,'ret' => $res);
            }
        }
        break;

    case 'newproj':
        if (strlen($_POST['phone']) == 0) {
            $ret = array('status' => 120001,'ret' => '项目注册失败');
        } else {
            $a = $fakadb->prepare('SELECT * FROM `orders` WHERE `id` = ? AND `contact` = ? AND `status` =\'2\'');
            $a->execute(array($_POST['id'],$_POST['phone']));
            $res = $a->fetchAll();
            if (empty($res)) {
                $ret = array('status' => 120002,'ret' => '项目注册失败');
            } else {
                $a = $mysql->prepare('SELECT * FROM `order` WHERE `phone` = ? AND `user` = ?');
                $a->execute(array($_POST['phone'],$_POST['proj']));
                $res1 = $a->fetchAll();
                if (!empty($res1)) {
                    $ret = array('status' => 120003,'ret' => '项目已存在');
                } else {
                    $a = $mysql->prepare('SELECT * FROM `order` WHERE `order` = ?');
                    $a->execute(array($res[0]['order_no']));
                    $res1 = $a->fetchAll();
                    if (!empty($res1)) {
                        $ret = array('status' => 120004,'ret' => '项目注册失败');
                    } else {
                        $a = $mysql->prepare('INSERT INTO `order` (`order`, `phone`, `user`, `time`) VALUES (?, ?, ?, now())');
                        $a->execute(array($res[0]['order_no'],$_POST['phone'],$_POST['proj']));
                        $a = $mysql->prepare('SELECT * FROM `order` WHERE `order` = ?');
                        $a->execute(array($res[0]['order_no']));
                        $res = $a->fetchAll();
                        $ret = array('status' => 0,'ret' => $res[0]['id']);
                    }
                }
            }
            break;
        }

    case 'getauth':
        if (strlen($_POST['phone']) == 0 || strlen($_POST['proj']) == 0 || strlen($_POST['id']) == 0) {
            $ret = array('status' => 130001,'ret' => '鉴权获取失败');
        } else {
            $a = $mysql->prepare('SELECT * FROM `order` WHERE `phone` = ? AND `user` = ? AND `id` = ?');
            $a->execute(array($_POST['phone'],$_POST['proj'],$_POST['id']));
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
                    'allowPrefix' => $_POST['phone'].'-'.$_POST['proj'].'/*',
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

    case 'getwechat':
        if (strlen($_POST['phone']) == 0) {
            $ret = array('status' => 140001,'ret' => '微信鉴权失败');
        } else {
            $a = $mysql->prepare('SELECT `user` FROM `wechat` WHERE `phone` = ?');
            $a->execute(array($_POST['phone']));
            $res = $a->fetchAll();
            if (empty($res)) {
                $ret = array('status' => 140002,'ret' => '微信鉴权失败');
            } else {
                $ret = array('status' => 0,'ret' => $res[0]['user']);
            }
        }
        break;

    case 'finish':
        if (strlen($_POST['phone']) == 0 || strlen($_POST['proj']) == 0 || strlen($_POST['wechat']) == 0) {
            $ret = array('status' => 150001,'ret' => '微信鉴权失败');
        } else {
            $a = $mysql->prepare('SELECT * FROM `order` WHERE `phone` = ? AND `user` = ?');
            $a->execute(array($_POST['phone'],$_POST['proj']));
            $res = $a->fetchAll();
            if (empty($res)) {
                $ret = array('status' => 150002,'ret' => '微信鉴权失败');
            } else {
                $a = $mysql->prepare('SELECT * FROM `wechat` WHERE `phone` = ? AND `user` = ?');
                $a->execute(array($_POST['phone'],$_POST['wechat']));
                $res1 = $a->fetchAll();
                if (empty($res1)) {
                    $ret = array('status' => 150003,'ret' => '微信鉴权失败');
                } else {
                    $a = $fakadb->prepare('SELECT * FROM `orders` WHERE `order_no` = ?');
                    $a->execute(array($res[0]['order']));
                    $res = $a->fetchAll();
                    include('./wx.php');
                    $ret = getAccessToken();
                    $accessToken = $ret['access_token'];
                    sendTemplateMessage(array('openid' => $res1[0]['openid'],'order' => $_POST['phone'].'-'.$_POST['proj'],'user' => $res[0]['product_name'],'msg' => '/'),$accessToken);
                    $ret = array('status' => 0,'ret' => 'success');
                }
            }
        }
        break;
	
	case 'getproj':
		if (strlen($_POST['phone']) == 0) {
			$ret = array('status' => 160001,'ret' => '项目校验失败');
		} else {
			$a = $mysql->prepare('SELECT * FROM `order` WHERE `phone` = ? ');
			$a->execute(array($_POST['phone']));
			$res = $a->fetchAll();
			if (empty($res)) {
				$ret = array('status' => 160002,'ret' => '项目校验失败，未查询到订单');
			} else {
				$ret = array('status' => 0,'ret' => $res);
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