<?php
/*
 * HTML TO BBCode Library
 * By AliReza_Tofighi
 * Website: http://www.white-crow.ir
 */

require_once './phpQuery/phpQuery.php';
require_once './JSLikeHTMLElement/JSLikeHTMLElement.php';

class html2bbcode {
	public $doc;
	public $replaces = [];
	public function parse($html)
	{
		$html = str_replace(array("\r", "\t", "\n", "\s"), '', ltrim(rtrim(trim($html))));
		$this->doc = phpQuery::newDocument($html);
		$this->doc->document->registerNodeClass('DOMElement', 'JSLikeHTMLElement');
		$this->run();
		$text = $this->doc->text();
		$text = str_replace('[newline]', "\r\n", $text);
		return $text;
	}
	
	public function addReplace($p1, $p2, $p3, $p4)
	{
		array_push($this->replaces, [
			$p1, $p2, $p3, $p4
		]);
	}

	private function replace($query, $where = [], $func, $params = [])
	{
		$list = $this->doc[$query];
		foreach($list as $elm)
		{
			if($this->checkWheres($elm, $where))
			{
				$elm = $func($elm, $params);
			}
		}
	}
	
	public function simpleBBCode($query, $before, $after, $name, $where = [])
	{
		return $this->addReplace($query, array_merge([
			['attr:data-runned', 'nothas', ','.$name.',']
		], $where), function($elm, $params)
		{
			$before = $params[0];
			$after = $params[1];
			$name = $params[2];
			foreach($elm->attributes as $attr_name => $attr)
			{
				if($attr)
				{
					$before = str_replace('{'.$attr_name.'}', $attr->value, $before);
					$after = str_replace('{'.$attr_name.'}', $attr->value, $after);
				}
			}
			$elm->innerHTML = $before.$elm->innerHTML.$after;
			$elm->setAttribute('data-runned', $elm->getAttribute('data-runned').','.$name.',');
			return $elm;
		}, [$before, $after, $name]);
	}
	private function checkWheres($elm, $wheres)
	{
		$ok = true;
		foreach($wheres as $where)
		{
			$attr = $this->getAttr($elm, $where[0]);
			if($where[1] == 'regex')
			{
				$val = $where[2];
			}
			else
			{
				$val = $this->getAttr($elm, $where[2]);
			}

			switch($where[1])
			{
				case '>':
					$ok &= $attr > $val;
					break;
				case '<':
					$ok &= $attr < $val;
					break;
				case '>=':
					$ok &= $attr >= $val;
					break;
				case '<=':
					$ok &= $attr <= $val;
					break;
				case '=':
				case '==':
					$ok &= $attr == $val;
					break;
				case '!=':
				case '><':
				case '<>':
					$ok &= $attr != $val;
					break;
				case 'has':
					$ok &= preg_match(preg_quote($val), $attr);
					break;
				case 'nothas':
					$ok &= !preg_match(preg_quote($val), $attr);
					break;
				case 'in':
					$ok &= in_array($attr, $val);
					break;
				case 'regex':
					$ok &= preg_match($val, $attr, $match);
					if($ok)
					{
						foreach($match as $i => $myans)
						{
							$elm->setAttribute("data-h2b-regex-{$where[0]}-{$i}", $myans);
						}
					}
					break;
				case 'is':
					if($val == 'url')
						$ok &= preg_match('#[-a-zA-Z0-9@:%_\+.~\#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~\#?&//=]*)?#si', $attr);
					elseif($val == 'numeric')
					{
						$ok &= is_numeric($attr);
					}
					break;
			}
		}
		return $ok;
	}
	
	private function getAttr($elm, $x)
	{
		if(is_array($x))
			return $x;
		if(strstr($x, '+'))
		{
			$x = explode('+', $x, 2);
			return $this->getAttr($elm, $x[0]).$this->getAttr($elm, $x[1]);
		}
		if($x == 'innerHTML')
		{
			return $elm->innerHTML;
		}
		elseif($x == 'innerTEXT')
		{
			return $elm->textContent;
		}
		elseif(substr($x, 0, 5) == 'attr:')
		{
			return $elm->getAttribute(substr($x, 5));
		}
		else
		{
			return $x;
		}
	}

	private function run()
	{
		foreach($this->replaces as $replace)
		{
			$this->replace($replace[0], $replace[1], $replace[2], $replace[3]);
		}
	}
}