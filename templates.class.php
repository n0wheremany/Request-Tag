<?php
/*
=====================================================
 DataLife Engine - by SoftNews Media Group 
-----------------------------------------------------
 http://dle-news.ru/
-----------------------------------------------------
 Copyright (c) 2004,2013 SoftNews Media Group
=====================================================
 Данный код защищен авторскими правами
=====================================================
 Файл: templates.class.php
-----------------------------------------------------
 Назначение: Парсинг шаблонов
=====================================================
*/

class dle_template {
  
	var $dir = '';
	var $template = null;
	var $copy_template = null;
	var $data = array ();
	var $block_data = array ();
	var $result = array ('info' => '', 'vote' => '', 'speedbar' => '', 'content' => '' );
	var $allow_php_include = true;
	
	var $template_parse_time = 0;

    function __construct(){
		$this->dir = ROOT_DIR . '/templates/';    
	}
	
	function set($name, $var) {
		if( is_array( $var ) && count( $var ) ) {
			foreach ( $var as $key => $key_var ) {
				$this->set( $key, $key_var );
			}
		} else
			$this->data[$name] = str_ireplace( "{include", "&#123;include",  $var );
	}
	
	function set_block($name, $var) {
		if( is_array( $var ) && count( $var ) ) {
			foreach ( $var as $key => $key_var ) {
				$this->set_block( $key, $key_var );
			}
		} else
			$this->block_data[$name] = str_ireplace( "{include", "&#123;include",  $var );
	}
	
	function load_template($tpl_name) {

		$time_before = $this->get_real_time();

		$url = @parse_url ( $tpl_name );

		$file_path = dirname ($this->clear_url_dir($url['path']));
		$tpl_name = pathinfo($url['path']);

		$tpl_name = totranslit($tpl_name['basename']);
		$type = explode( ".", $tpl_name );
		$type = strtolower( end( $type ) );

		if ($type != "tpl") {

			return "";

		}

		if ($file_path AND $file_path != ".") $tpl_name = $file_path."/".$tpl_name;
	
		if( $tpl_name == '' || !file_exists( $this->dir . "/" . $tpl_name ) ) {
			die( "Template not found: " . str_replace(ROOT_DIR, '', $this->dir)."/".$tpl_name );
			return false;
		}

		$this->template = file_get_contents( $this->dir . "/" . $tpl_name );

		if (strpos ( $this->template, "[aviable=" ) !== false) {
			$this->template = preg_replace ( "#\\[aviable=(.+?)\\](.*?)\\[/aviable\\]#ies", "\$this->check_module('\\1', '\\2')", $this->template );
		}
		
		if (strpos ( $this->template, "[not-aviable=" ) !== false) {
			$this->template = preg_replace ( "#\\[not-aviable=(.+?)\\](.*?)\\[/not-aviable\\]#ies", "\$this->check_module('\\1', '\\2', false)", $this->template );
		}

		if (strpos ( $this->template, "[not-group=" ) !== false) {
			$this->template = preg_replace ( "#\\[not-group=(.+?)\\](.*?)\\[/not-group\\]#ies", "\$this->check_group('\\1', '\\2', false)", $this->template );
		}
		
		if (strpos ( $this->template, "[group=" ) !== false) {
			$this->template = preg_replace ( "#\\[group=(.+?)\\](.*?)\\[/group\\]#ies", "\$this->check_group('\\1', '\\2')", $this->template );
		}
		
		if (strpos ( $this->template, "[page-count=" ) !== false) {
			$this->template = preg_replace ( "#\\[page-count=(.+?)\\](.*?)\\[/page-count\\]#ies", "\$this->check_page('\\1', '\\2')", $this->template );
		}


		if (strpos ( $this->template, "[not-page-count=" ) !== false) {
			$this->template = preg_replace ( "#\\[not-page-count=(.+?)\\](.*?)\\[/not-page-count\\]#ies", "\$this->check_page('\\1', '\\2', false)", $this->template );
		}

		if (strpos ( $this->template, "[tags=" ) !== false) {
			$this->template = preg_replace ( "#\\[tags=(.+?)\\](.*?)\\[/tags\\]#ies", "\$this->check_tag('\\1', '\\2', 'tags')", $this->template );
		}


		if (strpos ( $this->template, "[not-tags=" ) !== false) {
			$this->template = preg_replace ( "#\\[not-tags=(.+?)\\](.*?)\\[/not-tags\\]#ies", "\$this->check_tag('\\1', '\\2', 'tags', false)", $this->template );
		}

		if (strpos ( $this->template, "[news=" ) !== false) {
			$this->template = preg_replace ( "#\\[news=(.+?)\\](.*?)\\[/news\\]#ies", "\$this->check_tag('\\1', '\\2', 'news')", $this->template );
		}


		if (strpos ( $this->template, "[not-news=" ) !== false) {
			$this->template = preg_replace ( "#\\[not-news=(.+?)\\](.*?)\\[/not-news\\]#ies", "\$this->check_tag('\\1', '\\2', 'news', false)", $this->template );
		}

		if( strpos( $this->template, "{include file=" ) !== false ) {
			
			$this->template = preg_replace( "#\\{include file=['\"](.+?)['\"]\\}#ies", "\$this->load_file('\\1', 'tpl')", $this->template );
		
		}

		if( strpos( $this->template, "{request=" ) !== false or strpos( $this->template, "[request=" ) !== false ) {		
			preg_match_all("#[\\{\\[]request=['\"](.+?)['\"]#is",$this->template,$matchs,PREG_PATTERN_ORDER);
			$matchs = ((isset($matchs[1]) and is_array($matchs[1]))?$matchs[1]:array());
			$matchs = array_unique($matchs);
			foreach($matchs as $match){
				$vmatch = explode('=',$match);
				$amatch = explode('->',$vmatch[0]);
				$value = $_REQUEST;
				foreach($amatch as $imatch) $value = (($value and isset($value[$imatch]))?$value[$imatch]:false);
				if((isset($vmatch[1]) and $value==$vmatch[1]) or (!isset($vmatch[1]) and $value)){
					$this->template = preg_replace ( "#\\{request=['\"]{$vmatch[0]}['\"]\\}#is", $value, $this->template );
					$this->template = preg_replace ( "#\\[request=['\"]{$vmatch[0]}['\"]\\](.+?)\\[/request\\]#is", '\\1', $this->template );
					$this->template = preg_replace ( "#\\[request=['\"]{$vmatch[0]}={$vmatch[1]}['\"]\\](.+?)\\[/request\\]#is", '\\1', $this->template );
					$this->template = preg_replace ( "#\\[request=['\"]{$vmatch[0]}=(.+?)['\"]\\](.+?)\\[/request\\]#is", '', $this->template );
				} else {
					$this->template = preg_replace ( "#\\{request=['\"]{$vmatch[0]}['\"]\\}#is", '', $this->template );
					$this->template = preg_replace ( "#\\[request=['\"]{$vmatch[0]}['\"]\\](.+?)\\[/request\\]#is", '', $this->template );
					$this->template = preg_replace ( "#\\[request=['\"]{$vmatch[0]}={$vmatch[1]}['\"]\\](.+?)\\[/request\\]#is", '', $this->template );
				}
			}
		}
		if( strpos( $this->template, "{request}" ) !== false) {
			$this->template = str_replace('{request}', var_export($_REQUEST), $this->template );
		}

		$this->copy_template = $this->template;
		
		$this->template_parse_time += $this->get_real_time() - $time_before;
		return true;
	}

	function load_file( $name, $include_file = "tpl" ) {
		global $db, $is_logged, $member_id, $cat_info, $config, $user_group, $category_id, $_TIME, $lang, $smartphone_detected, $dle_module;

		$name = str_replace( '..', '', $name );

		$url = @parse_url ($name);
		$type = explode( ".", $url['path'] );
		$type = strtolower( end( $type ) );

		if ($type == "tpl") {

			return $this->sub_load_template( $name );

		}

		if ($include_file == "php") {

			if ( !$this->allow_php_include ) return;

			if ($type != "php") return "To connect permitted only files with the extension: .tpl or .php";

			if ($url['path']{0} == "/" )
				$file_path = dirname (ROOT_DIR.$url['path']);
			else
				$file_path = dirname (ROOT_DIR."/".$url['path']);

			$file_name = pathinfo($url['path']);
			$file_name = $file_name['basename'];

			if ( stristr ( php_uname( "s" ) , "windows" ) === false )
				$chmod_value = @decoct(@fileperms($file_path)) % 1000;

			if ( stristr ( dirname ($url['path']) , "uploads" ) !== false )
				return "Include files from directory /uploads/ is denied";

			if ( stristr ( dirname ($url['path']) , "templates" ) !== false )
				return "Include files from directory /templates/ is denied";

			if ($chmod_value == 777 ) return "File {$url['path']} is in the folder, which is available to write (CHMOD 777). For security purposes the connection files from these folders is impossible. Change the permissions on the folder that it had no rights to the write.";

			if ( !file_exists($file_path."/".$file_name) ) return "File {$url['path']} not found.";

			$url['query'] = str_ireplace(array("file_path","file_name", "dle_login_hash", "_GET","_FILES","_POST","_REQUEST","_SERVER","_COOKIE","_SESSION") ,"Filtered", $url['query'] );

			if( substr_count ($this->template, "{include file=") < substr_count ($this->copy_template, "{include file=")) return "Filtered";

			if ( isset($url['query']) AND $url['query'] ) {

				$module_params = array();

				parse_str( $url['query'], $module_params );

				extract($module_params, EXTR_SKIP);

				unset($module_params);
				

			}

			ob_start();
			$tpl = new dle_template();
			$tpl->dir = TEMPLATE_DIR;
			include $file_path."/".$file_name;
			return ob_get_clean();

		}

		return '{include file="'.$name.'"}';


	}
	
	function sub_load_template( $tpl_name ) {

		$url = @parse_url ( $tpl_name );

		$file_path = dirname ($this->clear_url_dir($url['path']));
		$tpl_name = pathinfo($url['path']);
		$tpl_name = totranslit($tpl_name['basename']);
		$type = explode( ".", $tpl_name );
		$type = strtolower( end( $type ) );

		if ($type != "tpl") {

			return "";

		}

		if ($file_path AND $file_path != ".") $tpl_name = $file_path."/".$tpl_name;

		if( $tpl_name == '' || ! file_exists( $this->dir . "/" . $tpl_name ) ) {
			return "Template not found: " . $tpl_name ;
			return false;
		}
		$template = file_get_contents( $this->dir . "/" . $tpl_name );

		if (strpos ( $template, "[aviable=" ) !== false) {
			$template = preg_replace ( "#\\[aviable=(.+?)\\](.*?)\\[/aviable\\]#ies", "\$this->check_module('\\1', '\\2')", $template );
		}
		
		if (strpos ( $template, "[not-aviable=" ) !== false) {
			$template = preg_replace ( "#\\[not-aviable=(.+?)\\](.*?)\\[/not-aviable\\]#ies", "\$this->check_module('\\1', '\\2', false)", $template );
		}

		if (strpos ( $template, "[not-group=" ) !== false) {
			$template = preg_replace ( "#\\[not-group=(.+?)\\](.*?)\\[/not-group\\]#ies", "\$this->check_group('\\1', '\\2', false)", $template );
		}
		
		if (strpos ( $template, "[group=" ) !== false) {
			$template = preg_replace ( "#\\[group=(.+?)\\](.*?)\\[/group\\]#ies", "\$this->check_group('\\1', '\\2')", $template );
		}

		if (strpos ( $template, "[page-count=" ) !== false) {
			$template = preg_replace ( "#\\[page-count=(.+?)\\](.*?)\\[/page-count\\]#ies", "\$this->check_page('\\1', '\\2')", $template );
		}

		if (strpos ( $template, "[not-page-count=" ) !== false) {
			$template = preg_replace ( "#\\[not-page-count=(.+?)\\](.*?)\\[/not-page-count\\]#ies", "\$this->check_page('\\1', '\\2', false)", $template );
		}

		if (strpos ( $template, "[tags=" ) !== false) {
			$template = preg_replace ( "#\\[tags=(.+?)\\](.*?)\\[/tags\\]#ies", "\$this->check_tag('\\1', '\\2', 'tags')", $template );
		}


		if (strpos ( $template, "[not-tags=" ) !== false) {
			$template = preg_replace ( "#\\[not-tags=(.+?)\\](.*?)\\[/not-tags\\]#ies", "\$this->check_tag('\\1', '\\2', 'tags', false)", $template );
		}

		if (strpos ( $template, "[news=" ) !== false) {
			$template = preg_replace ( "#\\[news=(.+?)\\](.*?)\\[/news\\]#ies", "\$this->check_tag('\\1', '\\2', 'news')", $template );
		}


		if (strpos ( $template, "[not-news=" ) !== false) {
			$template = preg_replace ( "#\\[not-news=(.+?)\\](.*?)\\[/not-news\\]#ies", "\$this->check_tag('\\1', '\\2', 'news', false)", $template );
		}
		
		return $template;
	}

	function clear_url_dir($var) {
		if ( is_array($var) ) return "";
	
		$var = str_ireplace( ".php", "", $var );
		$var = str_ireplace( ".php", ".ppp", $var );
		$var = trim( strip_tags( $var ) );
		$var = str_replace( "\\", "/", $var );
		$var = preg_replace( "/[^a-z0-9\/\_\-]+/mi", "", $var );
		$var = preg_replace( '#[\/]+#i', '/', $var );

		return $var;
	
	}

	function check_module($aviable, $block, $action = true) {
		global $dle_module;

		$aviable = explode( '|', $aviable );
		
		$block = str_replace( '\"', '"', $block );
		
		if( $action ) {
			
			if( ! (in_array( $dle_module, $aviable )) and ($aviable[0] != "global") ) return "";
			else return $block;
		
		} else {
			
			if( (in_array( $dle_module, $aviable )) ) return "";
			else return $block;
		
		}
	
	}

	function check_group($groups, $block, $action = true) {
		global $member_id;
		
		$groups = explode( ',', $groups );
		
		if( $action ) {
			
			if( ! in_array( $member_id['user_group'], $groups ) ) return "";
		
		} else {
			
			if( in_array( $member_id['user_group'], $groups ) ) return "";
		
		}
		
		$block = str_replace( '\"', '"', $block );
		
		return $block;
	
	}

	function check_page($pages, $block, $action = true) {
		
		$pages = explode( ',', $pages );
		$page = intval($_GET['cstart']);

		if ( $page < 1 ) $page = 1;
		
		if( $action ) {
			
			if( !in_array( $page, $pages ) ) return "";
		
		} else {
			
			if( in_array( $page, $pages ) ) return "";
		
		}
		
		$block = str_replace( '\"', '"', $block );
		
		return $block;
	
	}

	function check_tag($params, $block, $tag, $action = true) {
		global $config;
	
		$props = "";
		$params = trim($params);

		if ( $tag == "news" ) {

			if( defined( 'NEWS_ID' ) ) $props = NEWS_ID;
			$params = explode( ',', $params);
		
		} elseif ( $tag == "tags" ) {
		
			if( defined( 'CLOUDSTAG' ) ) {

				if( function_exists('mb_strtolower') ) {

					$params = mb_strtolower($params, $config['charset']);
					$props = trim(mb_strtolower(CLOUDSTAG, $config['charset']));

				} else {

					$params = strtolower($params);
					$props = trim(strtolower($props));

				}

			}

			$params = explode( ',', $params);

		
		} else return "";

		
		if( $action ) {
			
			if( !in_array( $props, $params ) ) return "";
		
		} else {
			
			if( in_array( $props, $params ) ) return "";
		
		}
		
		$block = str_replace( '\"', '"', $block );
		
		return $block;
	
	}
	
	function _clear() {
		
		$this->data = array ();
		$this->block_data = array ();
		$this->copy_template = $this->template;
	
	}
	
	function clear() {
		
		$this->data = array ();
		$this->block_data = array ();
		$this->copy_template = null;
		$this->template = null;
	
	}
	
	function global_clear() {
		
		$this->data = array ();
		$this->block_data = array ();
		$this->result = array ();
		$this->copy_template = null;
		$this->template = null;
	
	}
	
	function compile($tpl) {
		
		$time_before = $this->get_real_time();
		
		if( count( $this->block_data ) ) {
			foreach ( $this->block_data as $key_find => $key_replace ) {
				$find_preg[] = $key_find;
				$replace_preg[] = $key_replace;
			}
			
			$this->copy_template = preg_replace( $find_preg, $replace_preg, $this->copy_template );
		}

		foreach ( $this->data as $key_find => $key_replace ) {
			$find[] = $key_find;
			$replace[] = $key_replace;
		}
		
		$this->copy_template = str_replace( $find, $replace, $this->copy_template );

		if( strpos( $this->template, "{include file=" ) !== false ) {
			
			$this->copy_template = preg_replace( "#\\{include file=['\"](.+?)['\"]\\}#ies", "\$this->load_file('\\1', 'php')", $this->copy_template );
		
		}
		
		if( isset( $this->result[$tpl] ) ) $this->result[$tpl] .= $this->copy_template;
		else $this->result[$tpl] = $this->copy_template;
		
		$this->_clear();
		
		$this->template_parse_time += $this->get_real_time() - $time_before;
	}
	
	function get_real_time() {
		list ( $seconds, $microSeconds ) = explode( ' ', microtime() );
		return (( float ) $seconds + ( float ) $microSeconds);
	}
}
?>
