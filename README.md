Request-Tag
===========

Мега хак: Работаем с глобальным массивом $_REQUEST

## Установка

Открываем engine/classes/templates.class.php

ищем:
  	
  	$this->copy_template = $this->template;
		
	$this->template_parse_time += $this->get_real_time() - $time_before;
    
вставляем выше
  
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
			var_dump($_REQUEST);
			$this->template = str_replace('{request}', '', $this->template );
		}
    
Уставнока завершена.


## Использование

В любом шаблоне можно использовать:
Для вывода глобального массива $_REQUEST: - debug тэг

	{request}
	
Для получения данных из $_REQUEST
Для вывода начения

	{request=do}
	
или для вывода из массива

	{request=do->some}
	
для проверки существования значения $_REQUEST[do][some]

	[request=do->some][/request]
	
для проверки значения $_REQUEST[do][some] == myvalue

	[request=do->some=myvalue][/request]
