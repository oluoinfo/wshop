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
 * Author: 二月鸟飞
 * Date: 2017-09-09
 */

namespace app\common\logic;

use think\Model;
use think\Db;
/**
 * 保障服务类logic
 * Class CatsLogic
 * @package common\Logic
 */
class GuaranteeLogic extends Model
{
	public function guaranteeApply(){
		
	}
	
	public function guaranteeCheck(){
		
	}
	
	public function guaranteePay(){
		
	}
	
	public function guaranteeHandleLog($data){
		return db('guarantee_log')->insert($data);
	}
	
	public function costLog($data){
		return db('guarantee_costlog')->insert($data);
	}
}