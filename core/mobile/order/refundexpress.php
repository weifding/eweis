<?php
if (!defined("IN_IA")) {
    print("Access Denied");
}
global $_W, $_GPC;
function sortByTime($zym_var_7, $zym_var_8)
{
    if ($zym_var_7["ts"] == $zym_var_8["ts"]) {
        return 0;
    } else {
        return $zym_var_7["ts"] > $zym_var_8["ts"] ? 1 : -1;
    }
}
function getList($companyid, $posterid)
{
    $str = file_get_contents(EWEI_SHOP_PATH.'/data/sf.json');
    //LOG::INFO('EX:data'.EWEI_SHOP_PATH.'/data/sf.json');
    $json = json_decode($str, true);
    //LOG::INFO('EX:1'.$companyid);
    //参数设置
    $post_data = array();
    $post_data["customer"] = $json['customer'] ;
    $key= $json['key'];
    $post_data["param"] = '{"com":"'.$companyid.'","num":"'.$posterid.'"}';

    $url='http://poll.kuaidi100.com/poll/query.do';
    $post_data["sign"] = md5($post_data["param"].$key.$post_data["customer"]);
    $post_data["sign"] = strtoupper($post_data["sign"]);
    $o=""; 
    foreach ($post_data as $k=>$v)
    {
        $o.= "$k=".urlencode($v)."&";		//默认UTF-8编码格式
    }
    $post_data=substr($o,0,-1);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

    $result = curl_exec($ch);
    curl_close($ch);
    $data = str_replace("\&quot;",'"',$result);
    $data = json_decode($data,true);
    $printr = print_r($data, true);
    //LOG::INFO('EX:1'.$printr);

    return $data['data'];
}
}

$operation = !empty($_GPC["op"]) ? $_GPC["op"] : "display";
$openid    = m("user")->getOpenid();
$uniacid   = $_W["uniacid"];
$orderid   = intval($_GPC["id"]);
if ($_W["isajax"]) {
    if ($operation == "display") {
        $order = pdo_fetch("select refundid from " . tablename("ewei_shop_order") . " where id=:id and uniacid=:uniacid and openid=:openid limit 1", array(
            ":id" => $orderid,
            ":uniacid" => $uniacid,
            ":openid" => $openid
        ));
        if (empty($order)) {
            show_json(0);
        }
        $refundid = $order["refundid"];
        $refund   = pdo_fetch("select * from " . tablename("ewei_shop_order_refund") . " where id=:id and uniacid=:uniacid  limit 1", array(
            ":id" => $refundid,
            ":uniacid" => $uniacid
        ));
        $set      = set_medias(m("common")->getSysset("shop"), "logo");
        show_json(1, array(
            "order" => $order,
            "refund" => $refund,
            "set" => $set
        ));
    } else if ($operation == "step") {
        $express   = trim($_GPC["express"]);
        $expresssn = trim($_GPC["expresssn"]);

        $arr = getList($express, $expresssn);
        if (!$arr) {
            show_json(1, array(
                "list" => array()
            ));
        }
        
        $len   = count($arr);
        $step1 = explode("<br />", str_replace("&middot;", "", $arr[0]));
        $step2 = explode("<br />", str_replace("&middot;", "", $arr[$len - 1]));
        for ($i = 0; $i < $len; $i++) {
            $row = $arr[$i];
            $step   = explode("<br />", str_replace("&middot;", "", $row));
            
            $list[] = array(
                "time" => $row['time'],
                "step" => $row['context'],
                "ts" => $row['context']
            );
        }
        show_json(1, array(
            "list" => $list
        ));
    }
}
include $this->template("order/refundexpress");
?>