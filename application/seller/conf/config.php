<?php
return array(
     'URL_HTML_SUFFIX'       =>  '',  // URL伪静态后缀设置
    // 'OUTPUT_ENCODE' =>  true, //页面压缩输出支持   配置了 没鸟用
    'PAYMENT_PLUGIN_PATH' =>  PLUGIN_PATH.'payment',
    'LOGIN_PLUGIN_PATH' =>  PLUGIN_PATH.'login',
    'FUNCTION_PLUGIN_PATH' => PLUGIN_PATH.'function',
	'SHOW_PAGE_TRACE' => false,
	'CFG_SQL_FILESIZE'=>5242880,
    //'URL_MODEL'=>1, // 
    //默认错误跳转对应的模板文件
    'TMPL_ACTION_ERROR' => 'public:dispatch_jump',
    //默认成功跳转对应的模板文件
    'TMPL_ACTION_SUCCESS' => 'public:dispatch_jump',
		
	'VIEW_PATH'       =>'./application/seller/', // 改变某个模块的模板文件目录
	'DEFAULT_THEME'   =>'new', // 模板名称
   
);