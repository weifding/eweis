<?php
global $_W, $_GPC;
$openid   = m('user')->getOpenid();
$tabwidth = "50";
if ($this->set['level'] >= 1) {
    $tabwidth = 100;
}
if ($this->set['level'] >= 2) {
    $tabwidth = 50;
}
if ($this->set['level'] >= 3) {
    $tabwidth = 33.3;
}
$member = $this->model->getInfo($openid);
$total  = $member['agentcount'];
$level  = intval($_GPC['level']);
($level > 3 || $level <= 0) && $level = 1;
$condition = '';
$level1    = $member['level1'];
$level2    = $member['level2'];
$level3    = $member['level3'];
$my_users  = $member['my_users'];
$hasangent = false;
if ($level == 1) {
    if ($level1 > 0) {
        $condition = " and agentid={$member['id']}";
        $hasangent = true;
    }
} elseif ($level == 2) {
    //二级代理商代码改成专属用户代码
    if ($my_users > 0) {
        //$condition = " and agentid in( " . implode(',', array_keys($member['level1_agentids'])) . ")";
        $condition = " and inviter={$member['id']}";
        $hasangent = true;
    }
} elseif ($level == 3) {
    //废弃掉三级代理商代码
    //if ($level3 > 0) {
    //    $condition = " and agentid in( " . implode(',', array_keys($member['level2_agentids'])) . ")";
    //    $hasangent = true;
    //}
}


if ($_W['isajax']) {
    $pindex = max(1, intval($_GPC['page']));
    $psize  = 20;
    $list   = array();
    if ($hasangent) {
        if ($level == 1)
        {
            //一级代理商代码
            $list = pdo_fetchall("select * from " . tablename('ewei_shop_member') . " where isagent =1 and status=1 and uniacid = " . $_W['uniacid'] . " {$condition}  ORDER BY agenttime desc limit " . ($pindex - 1) * $psize . ',' . $psize);
            foreach ($list as &$row) {
                $info                    = $this->model->getInfo($row['openid'], array(
                    'total'
                ));
                $row['commission_total'] = $info['commission_total'];
                $row['agentcount']       = $info['agentcount'];
                $row['agenttime']        = date('Y-m-d H:i', $row['agenttime']);
            }
        }
        else
        {
           //专属用户代码  status=0 isagent =0
           $list = pdo_fetchall("select * from " . tablename('ewei_shop_member') . " where isagent =0  and uniacid = " . $_W['uniacid'] . " {$condition}  ORDER BY agenttime desc limit " . ($pindex - 1) * $psize . ',' . $psize);
           foreach ($list as &$row) {
            $info                    = $this->model->getInfo($row['openid'], array(
                'total'
            ));
            $row['commission_total'] = $info['commission_total'];
            $row['agentcount']       = $info['agentcount'];
            $row['agenttime']        = '';
        }
        }
       
    }
    unset($row);
    show_json(1, array(
        'list' => $list,
        'pagesize' => $psize
    ));
}
include $this->template('team');
