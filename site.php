<?php
/*
 * 大数据商城
 * 
 * @author 微赞
 */
if(!defined('IN_IA')) {
     exit('Access Denied');
}
require_once IA_ROOT. '/addons/ewei_shop/version.php';
require_once IA_ROOT. '/addons/ewei_shop/defines.php';
require_once EWEI_SHOP_INC.'functions.php'; 
require_once EWEI_SHOP_INC.'core.php';
require_once EWEI_SHOP_INC.'plugin/plugin.php';
require_once EWEI_SHOP_INC.'plugin/plugin_model.php';

//插件的入口函数
class Ewei_shopModuleSite extends Core { 
      
    //商城管理 
    public function doWebShop(){ $this->_exec(__FUNCTION__ ,'goods'); }
    //订单管理  
    public function doWebOrder(){ $this->_exec(__FUNCTION__,'list'); }

    //快递管理  
    public function doWebExpress(){ $this->_exec(__FUNCTION__,'batchadd'); }

    //会员管理
    public function doWebMember(){ $this->_exec(__FUNCTION__,'list'); }
    //财务管理
    public function doWebFinance(){ $this->_exec(__FUNCTION__,'log'); }
    //统计分析
    public function doWebStatistics(){ $this->_exec(__FUNCTION__,'sale'); }
    //插件管理
    public function doWebPlugins(){ $this->_exec(__FUNCTION__,'list'); }
    //系统设置 
    public function doWebSysset(){ $this->_exec(__FUNCTION__,'sysset'); } 
    //插件web入口  
    public function doWebPlugin(){   
        global $_W,$_GPC;
        require_once EWEI_SHOP_INC."plugin/plugin.php";
        $plugins = m('plugin')->getAll(); 
        $p = $_GPC['p']; 
        $file = EWEI_SHOP_PLUGIN.$p."/web.php";
        if(!is_file($file)){ 
            message('未找到插件 '.$plugins[$p].' 入口方法');
        }
        require $file;
        $pluginClass = ucfirst($p)."Web";
        $plug = new $pluginClass($p);
        $method =  strtolower($_GPC['method']);
        if(empty($method)){
           $plug->index();    
           exit;
        }
        if(method_exists($plug,$method)){
            $plug->$method();
            exit;
        }
        trigger_error('Plugin Web Method '.$method.' not Found!');
    }
    //插件app入口
    public function doMobilePlugin(){ 
        global $_W,$_GPC;
        require_once EWEI_SHOP_INC."plugin/plugin.php";
        $plugins = m('plugin')->getAll();
        $p = $_GPC['p'];
        $file = EWEI_SHOP_PLUGIN.$p."/mobile.php";
 
        if(!is_file($file)){
            message('未找到插件 '.$plugins[$p].' 入口方法');
        }
        require $file;
        $pluginClass = ucfirst($p)."Mobile";
        $plug = new $pluginClass($p);
        $method =  strtolower($_GPC['method']);
        if(empty($method)){
           $plug->index();    
           exit;
        }
        if(method_exists($plug,$method)){
            $plug->$method();
            exit;
        }
        trigger_error('Plugin Mobile Method '.$method.' not Found!');
    }
    //购物车入口
    public function doMobileCart(){ $this->_exec('doMobileShop','cart',false); }
    //我的收藏入口
    public function doMobileFavorite(){ $this->_exec('doMobileShop','favorite',false); }
    //工具
    public function doMobileUtil(){ $this->_exec(__FUNCTION__,'',false); }
    //会员
    public function doMobileMember(){ $this->_exec(__FUNCTION__,'center',false); }
    //商城
    public function doMobileShop(){ $this->_exec(__FUNCTION__,'index',false); }
    //订单
    public function doMobileOrder(){ $this->_exec(__FUNCTION__,'list',false); }
    //支付成功
    public function payResult($params){  return m('order')->payResult($params); }
    public function getAuthSet() {
        global $_W;
        $set = pdo_fetch('select sets from ' . tablename('ewei_shop_sysset') . ' order by id asc  limit 1');
        $sets = iunserializer($set['sets']);
        if (is_array($sets)) {
            return is_array($sets['auth']) ? $sets['auth'] : array();
        }
        return array();
    }
    public function doWebAuth() {$this->_exec('doWebSysset','auth',true);  }
    public function doWebUpgrade() {$this->_exec('doWebSysset','upgrade',true);   }
    public function doWebRunTasks() { $this->runTasks();  }
    public function doMobileRunTasks() { $this->runTasks(); }
}