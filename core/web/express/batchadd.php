<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}
global $_W, $_GPC;

$op      = $operation = $_GPC['op'] ? $_GPC['op'] : 'display';
$groups  = m('member')->getGroups();
$levels  = m('member')->getLevels();
$uniacid = $_W['uniacid'];



if ($_W['ispost']) {
    $order_list_text = $_GPC['order_lists'];
    $order_list_text=trim($order_list_text);
    $order_lists = multiexplode(array(",","\r\n","\r","\n"), $order_list_text);

    $post_list_text = $_GPC['post_lists'];
    $post_list_text=trim($post_list_text);
    $post_lists = multiexplode(array(",","\r\n","\r","\n"), $post_list_text);


    $_GPC['result']  = '';
    if (count($order_lists)!=count($post_lists)) {
        $_GPC['result'] ='订单和快递单号数量不一致，请重新输入'.count($order_lists).'!='.count($post_lists);

        $_GPC['result'] = $_GPC['result'].implode(",", $post_lists);

        /* for ($i=0; $i < count($order_lists); $i++) {
            $_GPC['result'] = $_GPC['result'].$i.$order_lists[$i].'=='.$post_lists[$i];
        } */

        $orders = pdo_fetchall("select * from " . tablename("ewei_shop_order") . " where  ordersn in (". implode(',', $order_lists).") and uniacid=:uniaci", array(":uniacid" => $_W["uniacid"]));
        $_GPC['result'] = print_r($orders, true);

        for ($i=0; $i < count($order_lists); $i++) {
            $ordersn = $order_lists[$i];
            $expresssn = $post_lists[$i];
            $express = '';

            pdo_update("ewei_shop_order", array(
                "express" => $express,
                "expresssn"=>$expresssn
            ), array(
                "uniacid" => $_W["uniacid"],
                "ordersn" => $order_lists[$i]
            ));
        }
    } else {
        $orders = pdo_fetchall("select * from " . tablename("ewei_shop_order") . " where  ordersn in (". implode(',', $order_lists).") and uniacid=:uniacid", array(":uniacid" => $_W["uniacid"]));
    

        $num = count($order_lists);
        for ($i=0; $i < $num; $i++) {
            $_GPC['result'] = $_GPC['result']. $order_lists[$i].'='.$post_lists[$i].'\r\n';
        }
    }
    $_GPC['order_lists'] = $order_list_text;
    $_GPC['post_lists'] = $post_list_text;
}



load()->func('tpl');
include $this->template('web/express/batchadd');

function multiexplode($delimiters, $string)
{
    
    $ready = str_replace($delimiters, $delimiters[0], $string);

    $launch = explode($delimiters[0], $ready);
    return  $launch;
}
