<?php
/*
 * HTML TO BBCode Library
 * By AliReza_Tofighi
 * Website: http://www.white-crow.ir
 */

require_once dirname(__FILE__).'/phpQuery/phpQuery.php';

class htmlParser {
	public $doc;
	public $replaces = [];
	public function parse($html)
	{
		$html = str_replace(array("\r", "\t", "\n", "\s"), '', ltrim(rtrim(trim($html))));
		$this->doc = phpQuery::newDocument($html);
		$this->run();
		$text = $this->doc->text();
		$text = str_replace('[newline]', "\r\n", $text);
		return $text;
	}
	
	public function addReplace($p1, $p2 = [], $p3, $p4 =[])
	{
		array_push($this->replaces, [
			$p1, $p2, $p3, $p4
		]);
	}

	private function replace($query, $where = [], $func, $params = [])
	{
		phpQuery::getDocument($this->doc->getDocumentID());
		$list = pq($query);
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
		], $where), function(&$elm, $params)
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
			pq($elm)->html($before.pq($elm)->html().$after);
			pq($elm)->attr('data-runned', $elm->getAttribute('data-runned').','.$name.',');
			return $elm;
		}, [$before, $after, $name]);
	}
	private function checkWheres(&$elm, $wheres)
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
							pq($elm)->attr("data-h2b-regex-{$where[0]}-{$i}", $myans);
						}
					}
					break;
				case 'is':
					if($val == 'url')
						$ok &= preg_match('#([a-z]+?://)([^\r\n\"<]+?)?#si', $attr);
					elseif($val == 'numeric')
					{
						$ok &= is_numeric($attr);
					}
					break;
			}
		}
		return $ok;
	}
	
	private function getAttr(&$elm, $x)
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
			return pq($elm)->html();
		}
		elseif($x == 'innerTEXT')
		{
			return pq($elm)->text();
		}
		elseif(substr($x, 0, 5) == 'attr:')
		{
			return pq($elm)->attr(substr($x, 5));
		}
		elseif(substr($x, 0, 4) == 'css:')
		{
			return pq($elm)->css(substr($x, 4));
		}
		else
		{
			return $x;
		}
	}

	private function run()
	{
		pq($this->doc)->find('style,script,head')->remove();
		foreach($this->replaces as $replace)
		{
			$this->replace($replace[0], $replace[1], $replace[2], $replace[3]);
		}
	}

	public function setupMyCode()
	{
		// newline:
		$this->simpleBBCode('br', "[newline]", '', 'newline');
		
		// bold:
		$this->simpleBBCode('b,strong', '[b]', '[/b]', 'bold');
		$this->simpleBBCode('*', '[b]', '[/b]', 'bold', [
			['css:font-weight', '=', 'bold']
		]);

		// italic:
		$this->simpleBBCode('i,em', '[i]', '[/i]', 'italic');
		$this->simpleBBCode('*', '[i]', '[/i]', 'italic', [
			['css:font-style', '=', 'italic']
		]);

		// underline:
		$this->simpleBBCode('u', '[u]', '[/u]', 'underline');
		$this->simpleBBCode('*', '[u]', '[/u]', 'underline', [
			['css:text-decoration', '=', 'underline']
		]);

		// strike:
		$this->simpleBBCode('s,strike,del', '[s]', '[/s]', 'strike');
		$this->simpleBBCode('*', '[s]', '[/s]', 'strike', [
			['css:text-decoration', '=', 'line-throught']
		]);

		// email:
		$this->simpleBBCode('a[href]', '[email]', '[/email]', 'link', [
			['attr:href', 'regex', '#mailto:(.*)#si'],
			['attr:href', '=', 'mailto:+innerHTML']
		]);
		$this->simpleBBCode('a[href]', '[email={data-h2b-regex-attr:href-1}]', '[/email]', 'link', [
			['attr:href', 'regex', '#mailto:(.*)#si'],
		]);

		// link:
		$this->simpleBBCode('a[href]', '[url]', '[/url]', 'link', [
			['attr:href', 'is', 'url'],
			['attr:href', '=', 'innerHTML']
		]);
		$this->simpleBBCode('a[href]', '[url={href}]', '[/url]', 'link', [
			['attr:href', 'is', 'url']
		]);

		// image:
		$this->simpleBBCode('img[src]', '[img={width}x{height}]{src}[/img]', '', 'image', [
			['attr:src', 'is', 'url'],
			['attr:width', 'is', 'numeric'],
			['attr:height', 'is', 'numeric']
		]);
		$this->simpleBBCode('img[src]', '[img]{src}[/img]', '', 'image', [
			['attr:src', 'is', 'url']
		]);

		// list:
		$this->simpleBBCode('li', "\n[*]", "", 'list-item');
		$this->simpleBBCode('ul', "\n[list={type}]", "\n[/list]", 'list-item',[
			['css:list-type', '!=', ''],
			['css:list-type', 'in', ['a', 'A', 'i', 'I', '1']]
		]);
		$this->simpleBBCode('ul', "\n[list={type}]", "\n[/list]", 'list-item',[
			['attr:type', '!=', ''],
			['attr:type', 'in', ['a', 'A', 'i', 'I', '1']]
		]);
		$this->simpleBBCode('ol', "\n[list=1]", "\n[/list=1]", 'list-item');
		$this->simpleBBCode('ul', "\n[list]", "\n[/list]", 'list-item');

		// align:
		foreach(['left', 'right', 'center', 'justify'] as $align)
		{
			$this->simpleBBCode('p,div', "[align={$align}]", '[/align]', 'align', [
				['css:text-align', '=', $align]
			]);
			$this->simpleBBCode('p,div', "[align={$align}]", '[/align]', 'align', [
				['attr:align', '=', $align]
			]);
		}
		
		// hr:
		$this->simpleBBCode('hr', '[hr]', '', 'hr');

		// quote:
		$this->addReplace('blockquote', [
			['attr:data-runned', 'nothas', ',blockquote,']
		], function(&$elm) {
			$cite = '';
			if(pq($elm)->find('cite:first')->text())
			{
				$cite = "='".pq($elm)->find('cite:first')->text()."'";
				pq($elm)->find('cite:first')->remove();
			}
			pq($elm)->attr('data-runned', pq($elm)->attr('data-runned').',blockquote,');
			pq($elm)->html("[quote{$cite}]".pq($elm)->html().'[/quote]');
		});
	}
}