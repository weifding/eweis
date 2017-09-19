<?php
global $_W, $_GPC;
$openid   = m("user")->getOpenid();
$shop_set = m("common")->getSysset("shop");
$set      = set_medias($this->set, "regbg");
$member   = m("member")->getMember($openid);
//if ($member["isagent"] == 1 && $member["status"] == 1) {
 //   header("location: " . $this->createPluginMobileUrl("commission"));
 //   exit;
//}
if (empty($set["become"])) {
}
$template_flag  = 0;
$diyform_plugin = p("diyform");
if ($diyform_plugin) {
    $set_config              = $diyform_plugin->getSet();
    $commission_diyform_open = $set_config["ç_diyform_open"];
    if ($commission_diyform_open == 1) {
        $template_flag = 1;
        $diyform_id    = $set_config["commission_diyform"];
        if (!empty($diyform_id)) {
            $formInfo     = $diyform_plugin->getDiyformInfo($diyform_id);
            $fields       = $formInfo["fields"];
            $diyform_data = iunserializer($member["diycommissiondata"]);
            $f_data       = $diyform_plugin->getDiyformData($diyform_data, $fields, $member);
        }
    }
}
$mid = intval($_GPC["mid"]);

$shop = set_medias(
    $this->model->getShop($mid),
    array(
    'img',
    'logo'
    )
);

//LOG::INFO('REG'.$shop['logo']);

$avatar_dir = IA_ROOT . '/attachment/images/'.$_W["uniacid"].'/'.'avatar/';
if (!is_dir($avatar_dir)){
    load() -> func('file');
    mkdirs($avatar_dir);
}
$avatar_file = $member['id'].'.jpg';
$logo_url = $shop['logo'];
if(!is_file($avatar_dir.$avatar_file)){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $shop['logo']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $output = curl_exec($ch);
    curl_close($ch);
    // 保存文件到制定路径
    file_put_contents($avatar_dir.$avatar_file, $output);
    unset($output);
    //LOG::INFO('REG:Save'.$avatar_dir.$avatar_file);
    $logo_url = 'http://www.bellmu.com/attachment/images/'.$_W["uniacid"].'/'.'avatar/'.$avatar_file;
}
else{
    $logo_url = 'http://www.bellmu.com/attachment/images/'.$_W["uniacid"].'/'.'avatar/'.$avatar_file;
    //需要修正
}
//LOG::INFO('REG'.$logo_url);


$my_name = $member['nickname'];
if(empty($member['nickname'])){
    $my_name = $member['realname'];
}



$_W['shopshare'] = array(
    'title' => '邀请成为分销商',
    'imgUrl' => $logo_url,
    'desc' => $my_name.'真诚邀请您加入!',
    'link' => $this->createPluginMobileUrl('commission/register', array('mid' => $member['id']))
);

if ($_W["isajax"]) {
    $agent = false;

    //旧的邀请代码
    if (!empty($member["fixagentid"])) {
        $mid = $member["agentid"];
        if (!empty($mid)) {
            $agent = m("member")->getMember($member["agentid"]);
        }
    } else {
        if (!empty($member["agentid"])) {
            $mid   = $member["agentid"];
            $agent = m("member")->getMember($member["agentid"]);
        } else if (!empty($member["inviter"])) {
            $mid   = $member["inviter"];
            $agent = m("member")->getMember($member["inviter"]);
        } else if (!empty($mid)) {
            $agent = m("member")->getMember($mid);
        }
    }

    //邀请链接谁发出的，就锁定邀请者
    $agent = m("member")->getMember($mid);

    $ret           = array(
        "shop_set" => $shop_set,
        "set" => $set,
        "member" => $member,
        "agent" => $agent
    );
    $ret["status"] = 0;
    $status        = intval($set["become_order"]) == 0 ? 1 : 3;
    if (empty($set["become"])) {
        $become_reg = intval($set["become_reg"]);
        if (empty($become_reg)) {
            $become_check  = intval($set["become_check"]);
            $ret["status"] = $become_check;
            $data          = array(
                "isagent" => 1,
                "agentid" => $mid,
                "status" => $become_check,
                "realname" => $_GPC["realname"],
                "mobile" => $_GPC["mobile"],
                "weixin" => $_GPC["weixin"],
                "agenttime" => $become_check == 1 ? time() : 0
            );
            pdo_update("ewei_shop_member", $data, array(
                "id" => $member["id"]
            ));
            if ($become_check == 1) {
                $this->model->sendMessage($member["openid"], array(
                    "nickname" => $member["nickname"],
                    "agenttime" => $data["agenttime"]
                ), TM_COMMISSION_BECOME);
                $this->model->upgradeLevelByAgent($member["id"]);
            }
            if (!empty($member["uid"])) {
                load()->model("mc");
                mc_update($member["uid"], array(
                    "realname" => $data["realname"],
                    "mobile" => $data["mobile"]
                ));
            }
        }
    } else if ($set["become"] == "2") {
        $ordercount = pdo_fetchcolumn("select count(*) from " . tablename("ewei_shop_order") . " where uniacid=:uniacid and openid=:openid and status>={$status} limit 1", array(
            ":uniacid" => $_W["uniacid"],
            ":openid" => $openid
        ));
        if ($ordercount < intval($set["become_ordercount"])) {
            $ret["status"]     = 1;
            $ret["order"]      = number_format($ordercount, 0);
            $ret["ordercount"] = number_format($set["become_ordercount"], 0);
        }
    } else if ($set["become"] == "3") {
        $moneycount = pdo_fetchcolumn("select sum(goodsprice) from " . tablename("ewei_shop_order") . " where uniacid=:uniacid and openid=:openid and status>={$status} limit 1", array(
            ":uniacid" => $_W["uniacid"],
            ":openid" => $openid
        ));
        if ($moneycount < floatval($set["become_moneycount"])) {
            $ret["status"]     = 2;
            $ret["money"]      = number_format($moneycount, 2);
            $ret["moneycount"] = number_format($set["become_moneycount"], 2);
        }
    } else if ($set["become"] == 4) {
        $goods      = pdo_fetch("select id,title from" . tablename("ewei_shop_goods") . " where id=:id and uniacid=:uniacid limit 1", array(
            ":id" => $set["become_goodsid"],
            ":uniacid" => $_W["uniacid"]
        ));
        $goodscount = pdo_fetchcolumn("select count(*) from " . tablename("ewei_shop_order_goods") . " og " . "  left join " . tablename("ewei_shop_order") . " o on o.id = og.orderid" . " where og.goodsid=:goodsid and o.openid=:openid and o.status>=1  limit 1", array(
            ":goodsid" => $set["become_goodsid"],
            ":openid" => $openid
        ));
        if ($goodscount <= 0) {
            $ret["status"] = 3;
            $ret["buyurl"] = $this->createMobileUrl("shop/detail", array(
                "id" => $goods["id"]
            ));
            $ret["goods"]  = $goods;
        } else {
            $ret["status"]    = 4;
            $data             = array(
                "isagent" => 1,
                "agentid" => $mid,
                "status" => 1,
                "agenttime" => time()
            );
            $member["status"] = 1;
            $ret["member"]    = $member;
            pdo_update("ewei_shop_member", $data, array(
                "id" => $member["id"]
            ));
            $this->model->sendMessage($member["openid"], array(
                "nickname" => $member["nickname"],
                "agenttime" => $data["agenttime"]
            ), TM_COMMISSION_BECOME);
            $this->model->upgradeLevelByAgent($member["id"]);
        }
    }

    //提交数据判断
    if ($_W["ispost"]) {
        if ($member["isagent"] == 1 && $member["status"] == 1) {
            show_json(0, "您已经是" . $set["texts"]["become"] . "，请把当前页面发给被邀请者，邀请成为分销商!");
        }
        if ($ret["status"] == 1 || $ret["status"] == 2) {
            show_json(0, "您消费的还不够哦，无法申请" . $set["texts"]["become"] . "!");
        } else {
            $become_check  = intval($set["become_check"]);
            $ret["status"] = $become_check;
            if ($template_flag == 1) {
                $memberdata                    = $_GPC["memberdata"];
                $insert_data                   = $diyform_plugin->getInsertData($fields, $memberdata);
                $data                          = $insert_data["data"];
                $m_data                        = $insert_data["m_data"];
                $mc_data                       = $insert_data["mc_data"];
                $m_data["diycommissionid"]     = $diyform_id;
                $m_data["diycommissionfields"] = iserializer($fields);
                $m_data["diycommissiondata"]   = $data;
                $m_data["isagent"]             = 1;
                $m_data["agentid"]             = $mid;
                $m_data["status"]              = $become_check;
                $m_data["agenttime"]           = $become_check == 1 ? time() : 0;
                pdo_update("ewei_shop_member", $m_data, array(
                    "id" => $member["id"]
                ));
                if ($become_check == 1) {
                    $this->model->sendMessage($member["openid"], array(
                        "nickname" => $member["nickname"],
                        "agenttime" => $m_data["agenttime"]
                    ), TM_COMMISSION_BECOME);
                }
                if (!empty($member["uid"])) {
                    load()->model("mc");
                    if (!empty($mc_data)) {
                        mc_update($member["uid"], $mc_data);
                        show_json(1, $ret);
                    }
                }
            } else {
                $data = array(
                    "isagent" => 1,
                    "agentid" => $mid,
                    "status" => $become_check,
                    "realname" => $_GPC["realname"],
                    "mobile" => $_GPC["mobile"],
                    "weixin" => $_GPC["weixin"],
                    "agenttime" => $become_check == 1 ? time() : 0
                );

                pdo_update("ewei_shop_member", $data, array(
                    "id" => $member["id"]
                ));
                if ($become_check == 1) {
                    $this->model->sendMessage($member["openid"], array(
                        "nickname" => $member["nickname"],
                        "agenttime" => $data["agenttime"]
                    ), TM_COMMISSION_BECOME);
                    if (!empty($mid)) {
                        $this->model->upgradeLevelByAgent($mid);
                    }
                }
                if (!empty($member["uid"])) {
                    load()->model("mc");
                    mc_update($member["uid"], array(
                        "realname" => $data["realname"],
                        "mobile" => $data["mobile"]
                    ));
                    show_json(1, $ret);
                }
            }
        }
    }
    show_json(1, $ret);
}
$this->setHeader();
if ($template_flag == 1) {
    include $this->template("diyform/register");
} else {
    include $this->template("register");
}
?>