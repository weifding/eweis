<?php
//http://www.bellmu.com/app/index.php?i=48&c=entry&method=myshop&p=commission&mid=2276&m=ewei_shop&do=plugin
global $_W, $_GPC;
$mid     = intval($_GPC['mid']);
$openid  = m('user')->getOpenid();
$member  = m('member')->getMember($openid);
$set     = $this->set;
$stop     = intval($_GPC['s']);
$uniacid = $_W['uniacid'];
$shop_url = '';
$log = 'MyShop:';



    //可能性
    //mid是代理商，member是普通用户，显示代理商的店
    //mid是普通用户，显示总店
    //mid是代理商，member也是代理商，显示member的店。

/* if (empty($mid)) {
    $shopurl = $this->createMobileUrl('shop', array('mid' => $member['id']));
    header('location: ' .$shop_url);
    exit;
}

    //LOG::INFO($log.'s='.$stop);

if (!empty($mid) && $stop==0) {
    //LOG::INFO($log.$mid.':'.$member['id']);
    //member是代理商
    if ($this->model->isAgent($member)) {
        $agent_info = $member;
        $agent_link_lv = $this->model->getAgentLinkLV($agent_info);
        //LOG::INFO($log.$agent_link_lv);
        if ($agent_link_lv>=1) {
            //二级代理商已经按专属用户处理，永不显示个人小店
            $agent_info = null;
        }
    }
            
    //mid是代理商
    if ($this->model->isAgent($mid)) {
        $agent_info = m('member')->getMember($mid);
        $agent_link_lv = $this->model->getAgentLinkLV($agent_info);
        //LOG::INFO($log.$agent_link_lv);
        if ($agent_link_lv>=1) {
            //二级代理商已经按专属用户处理，永不显示个人小店
            $agent_info = null;
        }
    }

    //mid和member都不是一级代理商
    if (empty($agent_info)) {
        header('location: '. $this->createMobileUrl('shop', array('mid' => $member['id'])));
        exit;
    } else {
        if (!empty($set['closemyshop'])) {
            //关闭个人小店功能
            $shopurl = $this->createMobileUrl('shop', array('mid' => $agent_info['id']));
        } else {
            //显示个人小店
            $shopurl = $this->createPluginMobileUrl('commission/myshop', array('mid' => $agent_info['id'],'s'=>1));
        }
        header('location: ' . $shopurl);
        //LOG::INFO($log.'代理商');
        exit;
    }
} */


if (!empty($mid)) {
    //mid有值
    //这代码似乎没用啊。
    if (!$this->model->isAgent($mid)) {
        //普通用户，跳转到总店
        header('location: ' . $this->createMobileUrl('shop'));
        exit;
    }
    if ($mid != $member['id']) {
        if ($member['isagent'] == 1 && $member['status'] == 1) {
            if (!empty($set['closemyshop'])) {
                //关闭个人小店功能
                $shopurl = $this->createMobileUrl('shop', array('mid' => $member['id']));
            } else {
                $agent_link_lv = $this->model->getAgentLinkLV($member);
                if ($agent_link_lv<1) {
                    //显示个人小店
                    $shopurl = $this->createPluginMobileUrl('commission/myshop', array('mid' => $member['id']));
                }
                else{
                    $shopurl = $this->createMobileUrl('shop', array('mid' => $member['id']));
                }

            }
            header('location: ' . $shopurl);
            exit;
        } else {
            if (!empty($set['closemyshop'])) {
                $shopurl = $this->createMobileUrl('shop', array('mid' => $mid));
                header('location: ' . $shopurl);
                exit;
            }
        }
    } else {
        if (!empty($set['closemyshop'])) {
            $shopurl = $this->createMobileUrl('shop', array('mid' => $member['id']));
            header('location: ' . $shopurl);
            exit;
        }
    }
} else {
    if ($member['isagent'] == 1 && $member['status'] == 1) {
        $mid = $member['id'];
        if (!empty($set['closemyshop'])) {
            $shopurl = $this->createMobileUrl('shop');
            header('location: ' . $shopurl);
            exit;
        }
    } else {
        header('location: ' . $this->createMobileUrl('shop'));
        exit;
    }
}

    $shop = set_medias(
        $this->model->getShop($mid),
        array(
        'img',
        'logo'
        )
    );


    //LOG::INFO($log.'op='.$_GPC['op']);
    $op   = empty($_GPC['op']) ? 'display' : $_GPC['op'];
    if ($op == 'display') {
        if ($_W['isajax']) {
            if (empty($shop['selectgoods'])) {
                $goodscount = pdo_fetchcolumn(
                'select count(*) from ' . tablename('ewei_shop_goods') . ' where uniacid=:uniacid and status=1 and deleted=0',
                array(
                ':uniacid' => $_W['uniacid']
                )
                );
            } else {
                $goodscount = count(explode(",", $shop['goodsids']));
            }
            $advs = pdo_fetchall(
            "select id,advname,link,thumb from " . tablename('ewei_shop_adv') . ' where uniacid=:uniacid and enabled=1 order by displayorder desc',
            array(
            ':uniacid' => $uniacid
            )
            );
            $advs        = set_medias($advs, 'thumb');
            $ret         = array(
                'shop' => $shop,
                'goodscount' => number_format($goodscount, 0),
                'set' => m('common')->getSysset('shop'),
                'advs' => $advs
            );
            $ret['isme'] = $mid == $member['id'];
            show_json(1, $ret);
        }
        $_W['shopshare'] = array(
        'title' => $shop['name'],
        'imgUrl' => $shop['logo'],
        'desc' => $shop['desc'],
        'link' => $this->createMobileUrl('shop')
            );
        if ($member['isagent'] == 1 && $member['status'] == 1) {
            $_W['shopshare']['link'] = $this->createPluginMobileUrl('commission/myshop', array(
                'mid' => $member['id']
            ));
            if (empty($this->set['become_reg']) && (empty($member['realname']) || empty($member['mobile']))) {
                    $trigger = true;
            }
        } elseif (!empty($mid)) {
            $_W['shopshare']['link'] = $this->createPluginMobileUrl('commission/myshop', array(
                'mid' => $_GPC['mid']
            ));
        }
            $this->setHeader();
            include $this->template('myshop');
    } elseif ($op == 'goods') {
        if ($_W['isajax']) {
            $args = array(
            'page' => $_GPC['page'],
            'pagesize' => 6,
            'nocommission' => 0,
            'order' => 'displayorder desc,createtime desc',
            'by' => ''
            );
            //LOG::INFO($log.'select goods');
            if (!empty($shop['selectgoods'])) {
                    $goodsids = explode(',', $shop['goodsids']);
                    //LOG::INFO($log.$goodsids);
                if (!empty($goodsids)) {
                    $args['ids'] = trim($shop['goodsids']);
                }
            }
            $goods = m('goods')->getList($args);
            show_json(1, array(
                'goods' => $goods,
                'pagesize' => $args['pagesize']
            ));
        }
    } elseif ($op == 'set') {
        if ($_W['isajax']) {
            if ($_W['ispost']) {
                $shopdata            = is_array($_GPC['shopdata']) ? $_GPC['shopdata'] : array();
                $shopdata['uniacid'] = $_W['uniacid'];
                $shopdata['mid']     = $member['id'];
                if (empty($shop['id'])) {
                    pdo_insert('ewei_shop_commission_shop', $shopdata);
                } else {
                    pdo_update('ewei_shop_commission_shop', $shopdata, array(
                    'id' => $shop['id']
                    ));
                }
                show_json(1);
            }
            $shop       = pdo_fetch('select * from ' . tablename('ewei_shop_commission_shop') . ' where uniacid=:uniacid and mid=:mid limit 1', array(
            ':uniacid' => $_W['uniacid'],
            ':mid' => $member['id']
            ));
            $shop       = set_medias($shop, array(
                'img',
                'logo'
            ));
            $openselect = false;
            if ($this->set['select_goods'] == '1') {
                if (empty($member['agentselectgoods']) || $member['agentselectgoods'] == 2) {
                    $openselect = true;
                }
            } else {
                if ($member['agentselectgoods'] == 2) {
                    $openselect = true;
                }
            }
            $shop['openselect'] = $openselect;
            show_json(1, array(
                'shop' => $shop
            ));
        }
        include $this->template('myshop_set');
    } elseif ($op == 'select') {
        if ($_W['isajax']) {
            if ($member['agentselectgoods'] == 1) {
                show_json(-1, '您无权自选商品，请和运营商联系!');
            }
            if (empty($this->set['select_goods'])) {
                if ($member['agentselectgoods'] != 2) {
                    show_json(-1, '系统未开启自选商品!');
                }
            }
            $shop = pdo_fetch('select * from ' . tablename('ewei_shop_commission_shop') . ' where uniacid=:uniacid and mid=:mid limit 1', array(
            ':uniacid' => $_W['uniacid'],
            ':mid' => $member['id']
            ));
            if ($_W['ispost']) {
                    $shopdata['selectgoods']    = intval($_GPC['selectgoods']);
                    $shopdata['selectcategory'] = intval($_GPC['selectcategory']);
                    $shopdata['uniacid']        = $_W['uniacid'];
                    $shopdata['mid']            = $member['id'];
                if (is_array($_GPC['goodsids'])) {
                    $shopdata['goodsids'] = implode(",", $_GPC['goodsids']);
                }
                if (!empty($shopdata['selectgoods']) && !is_array($_GPC['goodsids'])) {
                    show_json(0, '请选择商品!');
                }
                if (empty($shop['id'])) {
                    pdo_insert('ewei_shop_commission_shop', $shopdata);
                } else {
                    pdo_update('ewei_shop_commission_shop', $shopdata, array(
                        'id' => $shop['id']
                    ));
                }
                    show_json(1);
            }
            $goods = array();
            if (!empty($shop['selectgoods'])) {
                    $goodsids = explode(',', $shop['goodsids']);
                if (!empty($goodsids)) {
                    $goods = pdo_fetchall('select id,title,marketprice,thumb from ' . tablename('ewei_shop_goods') . ' where uniacid=:uniacid and id in ( ' . trim($shop['goodsids']) . ')', array(
                        ':uniacid' => $_W['uniacid']
                    ));
                    $goods = set_medias($goods, 'thumb');
                }
            }
            show_json(1, array(
                'shop' => $shop,
                'goods' => $goods
            ));
        }
        $set = m('common')->getSysset('shop');
        include $this->template('myshop_select');
    }
