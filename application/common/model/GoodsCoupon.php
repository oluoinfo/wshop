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

class GoodsCoupon extends Model
{
    public function goods()
    {
        return $this->hasOne('Goods','goods_id','goods_id');
    }
    public function goodsCategory()
    {
        return $this->hasOne('GoodsCategory','id','goods_category_id');
    }
}
