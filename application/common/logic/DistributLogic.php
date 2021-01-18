<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 *
 * Date: 2019-03-1
 * 
 * TPshop 公共逻辑类  将放到Application\Common\Logic\   由于很多模块公用 将不在放到某个单独模下面
 */

namespace app\common\logic;

use think\Db;
use think\Page;
use think\Model;
use app\common\logic\wechat\WechatUtil;
use app\common\model\Users;
use app\common\model\RebateLog;

/**
 * 分销逻辑层
 * Class CatsLogic
 * @package common\Logic
 */
class DistributLogic extends Model
{
    /**
     * 生成分销记录
     * @param $order
     * @return bool
     */
     public function rebateLog($order)
     {      
         // 看看分销规则由谁设置
         $distribut = tpCache('distribut');
         if(empty($distribut['switch']) || $distribut['switch']== 0) return false;
         $store_distribut = Db::name('store_distribut')->where(['store_id' =>$order['store_id']])->find();
         if($distribut['distribut_set_by'] == 1)// 商家设置
         {
            if(empty($store_distribut) || $store_distribut['switch'] == 0) return false;
            $pattern = $store_distribut['pattern']; // 分销模式  
            $first_rate = $store_distribut['first_rate']; // 一级比例
            $second_rate = $store_distribut['second_rate']; // 二级比例  
            $third_rate = $store_distribut['third_rate']; // 三级比例  
            $regrade = empty($store_distribut['regrade']) ? 0 : $store_distribut['regrade'];//返佣级数
         } else {
            $pattern = $distribut['pattern'];
            $first_rate =$distribut['first_rate']; // 一级比例
            $second_rate = $distribut['second_rate']; // 二级比例  
            $third_rate = $distribut['third_rate']; // 三级比例
            $regrade = empty($distribut['regrade']) ? 0 : $distribut['regrade'];//返佣级数
         }
         $user = Db::name('users')->where(['user_id' => $order['user_id']])->find();
         //按照商品分成 每件商品的佣金拿出来
         if($pattern  == 0) {
             // 获取所有商品分类
             $order_goods = Db::name('order_goods')->master()->where("order_id = {$order['order_id']}")->select(); // 订单所有商品
             $commission = 0;
             foreach($order_goods as $k => $v) {
                    $goods = Db::name('goods')->where(['goods_id' => $v['goods_id']])->find(); // 单个商品的佣金
                    $tmp_commission = $goods['distribut']; // 多商家版 已改名 distribut  为了 不跟平台抽成字段冲突                                                      
                    $tmp_commission = $tmp_commission  * $v['goods_num']; // 单个商品的分佣乘以购买数量                    
                    $commission += $tmp_commission; // 所有商品的累积佣金
             }                        
         }else{
             $order_rate = $store_distribut['order_rate']; // 订单分成比例  
             $commission = $order['goods_price'] * ($order_rate / 100); // 订单的商品总额 乘以 订单分成比例
         }
                  
         // 如果这笔订单没有分销金额
         if($commission == 0) return false;
         $first_money = $commission * ($first_rate / 100); // 一级赚到的钱
         $second_money = $commission * ($second_rate / 100); // 二级赚到的钱
         $third_money = $commission * ($third_rate / 100); // 三级赚到的钱
         
         //微信消息推送
         $wechat = new WechatUtil;
         $commission_rule = Db::name('distribut_level')->cache(true)->getField('level_id,rate1,rate2,rate3');
         $data = array(
         		'user_id' =>$user['first_leader'],
         		'buy_user_id'=>$user['user_id'],
         		'nickname'=>$user['nickname'],
         		'order_sn' => $order['order_sn'],
         		'order_id' => $order['order_id'],
         		'goods_price' => $order['goods_price'],
         		'money' => $first_money,
         		'level' => 1,
         		'create_time' => time(),
         		'store_id' =>$order['store_id'],
         );
         
         // 一级 分销商赚 的钱. 小于一分钱的 不存储
         if($user['first_leader'] > 0 && $first_money > 0.01)
         {
         	$first_leader = get_user_info($user['first_leader']);
         	if($first_leader['distribut_level']>0){
         		$data['money'] = $first_money = $commission*$commission_rule[$first_leader['distribut_level']]['rate1']/100;
         	}                 
            Db::name('rebate_log')->add($data);
            // 微信推送消息 
            $tmp_user = Db::name('OauthUsers')->where(['user_id'=>$user['first_leader'] , 'oauth'=>'weixin' , 'oauth_child'=>'mp'])->find();
            if ($tmp_user) {
                $wx_content = "你的一级下线:{$user['nickname']},刚刚下了一笔订单:{$order['order_sn']} 如果交易成功你将获得 ￥".$first_money."奖励 !";
                $wechat->sendMsg($tmp_user['openid'], 'text', $wx_content);
            }                       
         }
          // 二级 分销商赚 的钱.
         if($user['second_leader'] > 0 && $second_money > 0.01 && $regrade>=1)
         {      
         	$seconde_leader = get_user_info($user['second_leader']);
         	if($seconde_leader['distribut_level']>0){
         		$second_money = $commission*$commission_rule[$seconde_leader['distribut_level']]['rate2']/100;
         	}   
         	$data['user_id'] =  $user['second_leader'];
            $data['money'] = $second_money;
            $data['level'] = 2;                
            Db::name('rebate_log')->add($data);
            // 微信推送消息
            $tmp_user = Db::name('OauthUsers')->where(['user_id'=>$user['second_leader'] , 'oauth'=>'weixin' , 'oauth_child'=>'mp'])->find();
            if ($tmp_user['oauth']== 'weixin') {
                $wx_content = "你的二级下线:{$user['nickname']},刚刚下了一笔订单:{$order['order_sn']} 如果交易成功你将获得 ￥".$second_money."奖励 !";
                $wechat->sendMsg($tmp_user['openid'], 'text', $wx_content);
            }              
         }
          // 三级 分销商赚 的钱.
         if($user['third_leader'] > 0 && $third_money > 0.01 && $regrade>=2)
         {       
         	$third_leader = get_user_info($user['third_leader']);
         	if($third_leader['distribut_level']>0){
         		$third_money = $commission*$commission_rule[$third_leader['distribut_level']]['rate3']/100;
         	}           
            $data['user_id'] = $user['third_leader'];
            $data['money'] = $third_money;
            $data['level'] = 3;
            Db::name('rebate_log')->add($data);
            // 微信推送消息
            $tmp_user = Db::name('OauthUsers')->where(['user_id'=>$user['third_leader'] , 'oauth'=>'weixin' , 'oauth_child'=>'mp'])->find();
            if ($tmp_user['oauth']== 'weixin') {
                $wx_content = "你的三级下线,刚刚下了一笔订单:{$order['order_sn']} 如果交易成功你将获得 ￥".$third_money."奖励 !";
                $wechat->sendMsg($tmp_user['openid'], 'text', $wx_content);
            }
         }
         Db::name('order')->where("order_id = {$order['order_id']}")->save(array("is_distribut"=>1));  //修改订单为已经分成
     }

    /**
     * 自动分成 符合条件的 分成记录
     * @param $store_id
     * @return bool
     */
     function autoConfirm($store_id){
         
         // 看看分销规则由谁设置
         $distribut_set_by = Db::name('config')->where("name = 'distribut_set_by'")->getField('value');
         if($distribut_set_by == 1)// 商家设置
         {                      
            $store_distribut = Db::name('store_distribut')->where("store_id = {$store_id}")->find();
            if(empty($store_distribut) || $store_distribut['switch'] == 0) return false;         
         }
         else
         {
             $switch = Db::name('config')->where("name = 'switch'")->getField('value');
             if(empty($switch) || $switch == 0) return false;             
         }
         $today_time = time();
         $distribut_date = tpCache('shopping.auto_service_date'); //申请售后时间段 商家结算时间拿来 跟分销结算一起同一时间
         $distribut_time = strtotime("-$distribut_date day");  // 计算N天以前的时间戳
         $rebate_log_arr = Db::name('rebate_log')->where("store_id = $store_id and status = 2 and confirm < $distribut_time")->select();
         $level_info = Db::name('distribut_level')->cache(true)->order('level_id')->select();
         foreach ($rebate_log_arr as $key => $val)
         {
             accountLog($val['user_id'], $val['money'], 0,"订单:{$val['order_sn']}分佣",$val['money']);             
             $val['status'] = 3;
             $val['confirm_time'] = $today_time;
             $val['remark'] = $val['remark']."满{$distribut_date}天,程序自动分成.";
             M("rebate_log")->where("id = {$val[id]}")->save($val);
             
             if($distribut_set_by == 0){
             	$user = get_user_info($val['user_id']);//查看分销累计金额是否满足升级条件
             	foreach ($level_info as $lv){
             		if($user['distribut_money'] > $lv['order_money']) $new_level[$val['user_id']] = $lv['level_id'];
             	}
             	if(isset($new_level[$val['user_id']]) && $new_level[$val['user_id']] != $user['distribut_level']){
             		Db::name('users')->where(array('user_id'=>$val['user_id']))->save(array('distribut_level'=>$new_level[$val['user_id']]));
             	}
             }
         }
     }
     
    public function lowerList($user_id, $level, $q)
    {
        $condition = [1 => 'first_leader', 2 => 'second_leader', 3 => 'third_leader'];
        $where =[
            $condition["$level"] => $user_id,
        ];
        if ($q) {
            $where['nickname'] =['exp'," like %$q% or user_id = :%$q% or mobile = :%$q%)"];
        }
        $UserModel  = new Users();
        $count = $UserModel->where($where)->count();
        $page = new \think\Page($count, 10);
        $listsObj = $UserModel->where($where)->field('nickname,user_id,distribut_money,reg_time,head_pic')->limit($page->firstRow,$page->listRows)->order('user_id desc')->select();
        if ($listsObj){
            $lists = collection($listsObj)->append(['rebate_money'])->toArray();
        }
        return ['count' => $count, 'page' => $page->show(), 'lists' => $lists];
    }

    /**
     * 用户未上架商品数量
     */
    public function getUserNotAddGoodsNum($user_id)
    {
        $where = 'is_on_sale =1 and distribut > 0 ';
        $ids1 = Db::name('user_distribution')->field('goods_id')->where('user_id', $user_id)->select();
        $ids1 = array_multi2single($ids1);    
        $ids  = implode(',', $ids1);
        if ($ids) {
            $where .= " and goods_id not in($ids)";
        }
        
        $count = Db::name('goods')->where($where)->count();
        return $count;
    }
    
    public function getGoodsListById($ids)
    {
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }
        $where = [
            'is_on_sale' => 1,
            'distribut'  => ['>', 0],
            'goods_id' => ['IN', $ids]
        ];
        $goodsList = Db::name('goods')->field('goods_name,goods_id,shop_price,brand_id,store_id,cat_id3')
                ->where($where)
                ->select();
        return $goodsList;
    }
    
    public function addGoods($user, $goods_ids)
    {
        $goodsList = $this->getGoodsListById($goods_ids);
        foreach ($goodsList as $goods) {
            $data[] = [
                'user_id'   => $user['user_id'],
                'user_name' => $user['nickname'],
                'addtime'   => time(),
                'goods_id'  => $goods['goods_id'],
                'goods_name' => $goods['goods_name'],
                'cat_id'    => $goods['cat_id3'],
                'brand_id'  => $goods['brand_id'],
                'store_id'  => $goods['store_id'],
            ];
        }
        
        return Db::name('user_distribution')->insertAll($data);
    }
    
    /**
     * 订单列表
     * @param type $user_id
     * @param type $status 0未付款,1已付款, 2等待分成(已收货) 3已分成, 4已取消
     * @return type
     */
    public function orderList($user_id, $status)
    {
        $where = ['user_id' => $user_id, 'status' => ['in', $status]];
        $count = Db::name('rebate_log')->where($where)->count();
        $page  = new Page($count,10);
        $list = Db::name('rebate_log')->where($where)->order("id desc")->limit($page->firstRow.','.$page->listRows)->select(); //分成订单记录
        foreach ($list as $k => $v) {
            $list[$k]['goods_list'] = Db::name('order_goods')->where(['order_id'=>$v['order_id']])->select();
        }
       return [
           'list' => $list,
           'page' => $page
       ];
    }
    
    /**
     * 获取用户分销店的商品列表
     * @param type $user_id
     * @return type
     */
    public function getStoreGoods($user_id)
    {
        $where = ['is_on_sale'=>1,'distribut'=>['gt',0]];
        $distribution_ids = Db::name('user_distribution')->where('user_id', $user_id)->column('goods_id');
        $totalRows=0;
        $goodsList =[];
        if ($distribution_ids) {
            $where['goods_id'] = ['in', $distribution_ids];
            $count = Db::name('goods')->where($where)->count();
            $page = new Page($count, 10);
            $goodsList = Db::name('goods')->field('goods_name,goods_id,distribut,shop_price,brand_id,store_id,cat_id3')
                ->where($where)
                ->page($page->firstRow, $page->listRows)
                ->select();
            $totalRows = $page->totalRows;
        }
        return [
            'totalRows'=>$totalRows,
            'list'=>$goodsList
        ];
    }
    
    /**
     * 获取商店销售情况
     */
    public function getStoreSales()
    {
        $countWhere = 'is_on_sale = 1 and distribut > 0'; //公共统计条件
        $prom_num = Db::name('goods')->where("prom_type=1 and $countWhere")->count();  //促销
        $new_num = Db::name('goods')->where("is_new=1 and $countWhere")->count();  //新品
        return [
            'prom_num' => $prom_num,
            'new_num' => $new_num
        ];
    }
    
    /**
     * 分成记录
     * @param type $user_id 
     * @param type $status 日志状态
     * @param type $sort 排序
     * @param type $order 排序条件
     */
    public function getRebateLog($user_id, $status, $sort, $order)
    {
        $where['user_id'] = $user_id;
        
        if ($status != '') {
            $where['status'] = $status;
        }
        
        $count = Db::name('rebate_log')->where($where)->count();
        $page = new \think\Page($count,10);
        $list = Db::name('rebate_log')->where($where)->order($sort, $order)
                ->limit($page->firstRow, $page->listRows)->cache(true)
                ->select();
        
        return ['page' => $page, 'list' => $list];
    }
    
    public function setStoreInfo($user_id, $storeName, $trueName, $mobile, $qq)
    {
        // 上传图片
        if (!empty($_FILES['store_img']['tmp_name'])) {
            $files = request()->file('store_img');
            $save_url = UPLOAD_PATH.'user_store';
            // 移动到框架应用根目录/public/uploads/ 目录下
            $image_upload_limit_size = config('image_upload_limit_size');
            $info = $files->rule('uniqid')->validate(['size' => $image_upload_limit_size, 'ext' => 'jpg,png,gif,jpeg'])->move($save_url);
            if ($info) {
                // 成功上传后 获取上传信息
                $return_imgs[] = '/'.$save_url . '/' . $info->getFilename();
            } else {
                // 上传失败获取错误信息
                return ['status' => -1, 'msg' => $files->getError()];
            }
        }
        
        if (!empty($return_imgs)) {
            $data['store_img'] = implode(',', $return_imgs);
        }
        $data['store_name'] = $storeName;
        $data['true_name'] = $trueName;
        $data['mobile'] = $mobile;
        $data['qq'] = $qq;
        
        $user_store = Db::name('user_store')->where("user_id", $user_id)->find();
        if ($user_store == null) { 
            //添加
            $data['user_id'] = $user_id;
            $data['store_time'] = time();
            $addres = Db::name('user_store')->add($data);
            if (!$addres) {
                return ['status' => -1, 'msg' => '添加店铺信息失败'];
            }
        } else {
            //修改
            $upres = Db::name('user_store')->where(['user_id' => $user_id])->update($data);
            if ($upres===false) {
                return ['status' => -1, 'msg' => '修改店铺信息失败'];
            }
        }
        
        return ['status' => 1, 'msg' => '添加店铺信息成功'];
    }
    
    public function getStoreInfo($user_id)
    {
        $store = Db::name('user_store')->where(['user_id' => $user_id])->find();
        if (!$store) {
            return ['status' => -1, 'msg' => '店铺不存在'];
        }
        
        return ['status' => 1, 'msg' => '获取成功', 'result' => $store];
    }
    
    public function rankings($user, $sort, $p = 1)
    {
        if ($p < 1) {
            $p = 1;
        }
        if (!in_array($sort, ['distribut_money', 'underling_number'])) {
            $sort = 'distribut_money';
        }

        $where['is_distribut'] = 1;
        $lists = Db::name('users')->field('head_pic,nickname,distribut_money,underling_number')
                ->where($where)->order($sort, "desc")->page($p, 10)->select(); //获排行列表
        $where["$sort"] = ['gt', $user["$sort"]];
        $place = Db::name('users')->where($where)->count($sort); //用户排行名
        
        return [
            'lists' => $lists,
            'firstRow' => ($p - 1) * 10, //当前分页开始数
            'place' => $place + 1   //当前分页开始数
        ];
    }

    /**
     * 设置店铺上传图片
     */
    public function uploadStoreImg()
    {
        $return_imgs = '';
        if (!empty($_FILES['store_img']['tmp_name'])) {
            $files = request()->file('store_img');
            $save_url = UPLOAD_PATH.'user_store';
            // 移动到框架应用根目录/public/uploads/ 目录下
            $image_upload_limit_size = config('image_upload_limit_size');
            $info = $files->rule('uniqid')->validate(['size' => $image_upload_limit_size, 'ext' => 'jpg,png,gif,jpeg'])->move($save_url);
            if ($info) {
                // 成功上传后 获取上传信息
                $return_imgs = '/'.$save_url . '/' . $info->getFilename();
            } else {
                // 上传失败获取错误信息
                return ['status' => -1, 'msg' => $files->getError()];
            }
        }

        return ['status' => 1, 'msg' => '上传成功', 'result' => $return_imgs];
    }
}