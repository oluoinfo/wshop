<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 *
 * Date: 2019-03-1
 */
namespace app\common\model;

use think\Db;
use think\Model;

class PreSell extends Model
{
    public function specGoodsPrice(){
        return $this->hasOne('SpecGoodsPrice','item_id','item_id')->bind(['price']);
    }
    public function goods(){
        return $this->hasOne('Goods','goods_id','goods_id')->bind(['shop_price']);
    }
    public function store(){
        return $this->hasOne('Store','store_id','store_id')->field('store_id,store_name');
    }

    public function getIngAmountAttr($value, $data){
        $price_ladder = json_decode($data['price_ladder'], true);
        $price_ladder = array_sort($price_ladder,'amount','asc');
        $price_ladder = array_values($price_ladder);
        $price_ladder_count = count($price_ladder);
        if($price_ladder_count == 1){
            return $price_ladder[0]['amount'];
        }
        for ($i = 0; $i < $price_ladder_count; $i++) {
            if($i == 0 && $price_ladder[$i]['amount'] >= $data['deposit_goods_num']){
                return $price_ladder[$i]['amount'];
            }
            if($i == ($price_ladder_count - 1)){
                return $price_ladder[$i]['amount'];
            }
            if($data['deposit_goods_num'] >= $price_ladder[$i]['amount'] && $data['deposit_goods_num'] < $price_ladder[$i+1]['amount']){
                return $price_ladder[$i]['amount'];
            }
        }
    }

    public function getIngPriceAttr($value, $data){
        $price_ladder = json_decode($data['price_ladder'], true);
        $price_ladder = array_sort($price_ladder,'amount','asc');
        $price_ladder = array_values($price_ladder);
        $price_ladder_count = count($price_ladder);
        if($price_ladder_count == 1){
            return $price_ladder[0]['price'];
        }
        for ($i = 0; $i < $price_ladder_count; $i++) {
            if($i == 0 && $price_ladder[$i]['amount'] >= $data['deposit_goods_num']){
                return $price_ladder[$i]['price'];
            }
            if($i == ($price_ladder_count - 1)){
                return $price_ladder[$i]['price'];
            }
            if($data['deposit_goods_num'] >= $price_ladder[$i]['amount'] && $data['deposit_goods_num'] < $price_ladder[$i+1]['amount']){
                return $price_ladder[$i]['price'];
            }
        }
    }

    public function getBalancePriceAttr($value, $data)
    {
        if($data['deposit_price'] == 0){
            return 0;
        }else{
            return strtotime($value);
        }
    }

    public function getStatusDescAttr($value, $data){
        $status = array('审核中', '已通过', '审核失败', '管理员关闭');
        return $status[$data['status']];
    }

    public function getFinishDescAttr($value, $data){
        if($data['is_finished'] == 0){
            if($data['pay_end_time'] > 0){
                if($data['sell_start_time'] > time()){
                    return '未开始';
                }else if($data['sell_start_time'] < time() && $data['pay_end_time'] > time()){
                    return '进行中';
                }else{
                    return '已过期';
                }
            }else{
                if($data['sell_start_time'] > time()){
                    return '未开始';
                }else if($data['sell_start_time'] < time() && $data['sell_end_time'] > time()){
                    return '进行中';
                }else{
                    return '已过期';
                }
            }
        }else if($data['is_finished'] == 1){
            return '结束(待处理)';
        }else if($data['is_finished'] == 2){
            return '成功结束';
        }else{
            return '失败结束';
        }
    }

    public function setPayStartTimeAttr($value, $data){
        if($data['deposit_price'] == 0){
            return 0;
        }else{
            return strtotime($value);
        }
    }

    public function setPayEndTimeAttr($value, $data){
        if($data['deposit_price'] == 0){
            return 0;
        }else{
            return strtotime($value);
        }
    }

    public function setSellStartTimeAttr($value){
        return strtotime($value);
    }
    public function setSellEndTimeAttr($value){
        return strtotime($value);
    }

    public function getPriceLadderAttr($value){
        return json_decode($value, true);
    }

    public function getFinishButtonAttr($value, $data){
        if(($data['is_finished'] == 0) && (($data['sell_start_time'] <= time()) && $data['deposit_goods_num'] < $data['stock_num'])){
            return true;
        }else{
            return false;
        }
    }

    public function getSuccessOrFailButtonAttr($value, $data){
        if($data['is_finished'] == 1 && ($data['sell_end_time'] < time() || $data['deposit_goods_num'] >= $data['stock_num'])){
            return true;
        }else{
            return false;
        }
    }

}
