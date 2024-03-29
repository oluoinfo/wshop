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
 * Author: lhb
 * Date: 2017-05-15
 */

namespace app\common\logic;

use think\Model;

/**
 * 活动逻辑类
 */
class StoreGoodsClass extends Model
{
    /**
     * 获取店铺商品分类
     * @param $store_id
     * @param int $parent_id
     * @param array $result
     * @return array
     */
    public function getStoreGoodsClass($store_id, $parent_id = 0, &$result = array())
    {
        $store_goods_class_where = array(
            'store_id' => $store_id,
            'parent_id' => $parent_id,
        );
        $arr = M('store_goods_class')->where($store_goods_class_where)->order('cat_sort desc')->select();
        if (empty($arr)) {
            return array();
        }
        foreach ($arr as $cm) {
            $thisArr =& $result[];
            $cm['children'] = $this->getStoreGoodsClass($store_id, $cm['cat_id'], $thisArr);
            $thisArr = $cm;
        }
        return $result;
    }
}