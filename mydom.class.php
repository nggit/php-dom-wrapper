<?php

# MyDOM class
# PHP template engine using DOM
# (c) 20161228 nggit

class MyDOM {

    protected $dom, $xpath, $node = array(), $element = array(null, null, null), $prepare = array();

    public function __construct($str) {
        $this->dom = new DOMDocument();
        libxml_use_internal_errors(true);
        if (file_exists($str)) {
            $this->dom->loadHTMLFile($str);
        }
        else {
            $this->dom->loadHTML($str);
        }
        libxml_clear_errors();
        $this->xpath = new DOMXPath($this->dom);
    }

    protected function getElementById($id) { // safe getElementById()
        return $this->xpath->query("//*[@id='$id']")->item(0);
    }

    protected function getElementsByClassName($class) {
        return $this->xpath->query("//*[contains(concat(' ', @class, ' '), ' $class ')]");
    }

    public static function xmlentities($str) { // for safe appendXML
        $str = html_entity_decode(
                   strtr($str,
                       array(
                           '&quot;' => '&amp;quot;',
                           '&apos;' => '&amp;#039;',
                           '&#039;' => '&amp;#039;',
                           '&lt;'   => '&amp;lt;',
                           '&gt;'   => '&amp;gt;',
                           '&amp;'  => '&amp;amp;'
                       )
                   ), ENT_NOQUOTES, 'UTF-8');
        $str = str_replace('?', '&#63;', $str);
        $iso = utf8_decode($str);
        static $chars = array();
        if (!$chars) {
            for ($i = 128; $i < 256; $i++) {
                $chars[] = chr($i);
            }
        }
        $ascii = str_replace($chars, '?', $iso);
        $a     = explode('?', $ascii);
        array_pop($a);
        $data = null;
        foreach ($a as $pre) {
            $current   = substr($str, strlen($pre));
            $firstByte = str_pad(decbin(ord($current[0])), 8, '0', STR_PAD_LEFT);
            $pos       = strpos($firstByte, '0');
            $len       = $pos ? $pos : 1;
            $u         = substr($firstByte, $pos + 1);
            for ($i = 1; $i < $len; $i++) {
                $nextByte = decbin(ord($current[$i]));
                $u       .= substr($nextByte, strpos($nextByte, '0') + 1);
            }
            $data .= $pre.'&#'.bindec($u).';';
            $str   = substr($current, $len);
        }
        $data .= $str;
        return str_replace('&#63;', '?', $data);
    }

    public function find($item, $n = 0) {
        switch ($item[0]) {
            case '#':
                isset($this->node[$item][$n]) or $this->node[$item][$n] = $this->getElementById(ltrim($item, '#'));
                break;
            case '.':
                isset($this->node[$item][$n]) or $this->node[$item][$n] = $this->getElementsByClassName(ltrim($item, '.'))->item($n);
                break;
            default:
                isset($this->node[$item][$n]) or $this->node[$item][$n] = $this->dom->getElementsByTagName($item)->item($n);
        }
        $this->element[0] = $item;
        $this->element[1] = $this->node[$item][$n];
        $this->element[2] = $n;
        return $this;
    }

    public function replace($content) {
        if ($this->element[1]) {
            if (is_object($content)) {
                $fragment = $content;
            }
            else{
                $fragment = $this->dom->createDocumentFragment();
                $fragment->appendXML(self::xmlentities($content));
            }
            $element = $this->element[1];
            $element->parentNode->replaceChild($fragment, $element);
            unset($this->node[$this->element[0]][$this->element[2]]);
            $this->element = array(null, null, null);
        }
    }

    public function prepend($content) {
        if ($this->element[1]) {
            if (is_object($content)) {
                $fragment = $content;
            }
            else {
                $fragment = $this->dom->createDocumentFragment();
                $fragment->appendXML(self::xmlentities($content));
            }
            $element = $this->element[1];
            $element->insertBefore($fragment, $element->firstChild);
            $this->element[0] = null;
        }
        return $this;
    }

    public function append($content) {
        if ($this->element[1]) {
            if (is_object($content)) {
                $fragment = $content;
            }
            else {
                $fragment = $this->dom->createDocumentFragment();
                $fragment->appendXML(self::xmlentities($content));
            }
            $element = $this->element[1];
            $element->appendChild($fragment);
            $this->element[0] = null;
        }
        return $this;
    }

    public function before($content) {
        if ($this->element[1]) {
            if (is_object($content)) {
                $fragment = $content;
            }
            else {
                $fragment = $this->dom->createDocumentFragment();
                $fragment->appendXML(self::xmlentities($content));
            }
            $element = $this->element[1];
            $element->parentNode->insertBefore($fragment, $element);
            $this->element[0] = null;
        }
        return $this;
    }

    public function after($content) {
        if ($this->element[1]) {
            if (is_object($content)) {
                $fragment = $content;
            }
            else {
                $fragment = $this->dom->createDocumentFragment();
                $fragment->appendXML(self::xmlentities($content));
            }
            $element = $this->element[1];
            $element->parentNode->insertBefore($fragment, $element->nextSibling);
            $this->element[0] = null;
        }
        return $this;
    }

    public function setText($content) {
        if ($this->element[1]) {
            $element = $this->element[1];
            $text    = $this->dom->createTextNode($content);
            $node    = $element->cloneNode();
            $node->appendChild($text);
            $element->parentNode->replaceChild($node, $element);
            unset($this->node[$this->element[0]][$this->element[2]]);
            $this->element = array(null, null, null);
        }
    }

    public function setAttr($name, $value = null) {
        if ($this->element[1]) {
            $element = $this->element[1];
            $element->setAttribute($name, $value);
            $this->element[0] = null;
        }
        return $this;
    }

    public function removeAttr($name) {
        if ($this->element[1]) {
            $element = $this->element[1];
            $element->removeAttribute($name);
            $this->element[0] = null;
        }
        return $this;
    }

    public function prepare($items) {
        is_array($items) or $items = func_get_args();
        $this->prepare = array_merge($this->prepare, $items);
        return $this;
    }

    public function remove($item = null) {
        switch (true) {
            case $item:
                $items = func_get_args();
                $this->prepare($items);
                $this->remove();
                break;
            case $this->element[0]: // use find() method
                $element = $this->element[1];
                $element->parentNode->removeChild($element);
                unset($this->node[$this->element[0]][$this->element[2]]);
                $this->element = array(null, null, null);
                break;
            case $this->prepare: // use prepare() method
                foreach ($this->prepare as $prepare) {
                    if (isset($this->node[$prepare])) {
                        foreach ($this->node[$prepare] as $element) {
                            $element->parentNode->removeChild($element);
                        }
                        unset($this->node[$prepare]);
                    }
                    else {
                        switch ($prepare[0]) {
                            case '#':
                                $element = $this->getElementById(ltrim($prepare, '#'));
                                if ($element) {
                                    $element->parentNode->removeChild($element);
                                }
                                break;
                            case '.':
                                $elements = $this->getElementsByClassName(ltrim($prepare, '.'));
                                $i        = $elements->length;
                                while ($i > 0) {
                                    $i--;
                                    $element = $elements->item($i);
                                    $element->parentNode->removeChild($element);
                                }
                                break;
                            default:
                                $elements = $this->dom->getElementsByTagName($prepare);
                                $i        = $elements->length;
                                while ($i > 0) {
                                    $i--;
                                    $element = $elements->item($i);
                                    $element->parentNode->removeChild($element);
                                }
                        }
                    }
                }
                $this->prepare = array();
                break;
        }
    }

    public function dup($n = null) {
        if ($this->element[1]) {
            if ($n > 0) {
                for ($j = 1; $j <= $n; $j++) {
                    $dup      = $this->element[1]->cloneNode(true);
                    $elements = $dup->getElementsByTagName('*');
                    $i        = $elements->length;
                    while ($i > 0) {
                        $i--;
                        $element = $elements->item($i);
                        if ($element->hasAttribute('id')) {
                            $element->setAttribute('id', $element->getAttribute('id').$j);
                        }
                    }
                    if ($dup->hasAttribute('id')) {
                        $dup->setAttribute('id', $dup->getAttribute('id').$j);
                    }
                    $this->element[1]->parentNode->insertBefore($dup, $this->element[1]);
                }
            }
            else {
                $dup      = $this->element[1]->cloneNode(true);
                $elements = $dup->getElementsByTagName('*');
                $i        = $elements->length;
                while ($i > 0) {
                    $i--;
                    $element = $elements->item($i);
                    if ($element->hasAttribute('id')) {
                        $element->setAttribute('id', $element->getAttribute('id').'1');
                    }
                }
                if ($dup->hasAttribute('id')) {
                    $dup->setAttribute('id', $dup->getAttribute('id').'1');
                }
                return $dup;
            }
        }
    }

    public function put($data) {
        if ($this->element[1]) {
            static $n;
            $n += 1;
            $dup      = $this->element[1]->cloneNode(true);
            $elements = $dup->getElementsByTagName('*');
            $i        = $elements->length;
            while ($i > 0) {
                $i--;
                $element = $elements->item($i);
                if ($element->hasAttribute('id')) {
                    $attribute = $element->getAttribute('id');
                    if (empty($data[$attribute])) {
                        if ($element->childNodes->length <= 3) {
                            $element->parentNode->removeChild($element);
                        }
                    }
                    else {
                        $element->setAttribute('id', $attribute.$n);
                        $fragment = $this->dom->createDocumentFragment();
                        $fragment->appendXML(self::xmlentities($data[$attribute]));
                        $node = $element->cloneNode();
                        $node->appendChild($fragment);
                        $element->parentNode->replaceChild($node, $element);
                    }
                }
            }
            if ($dup->hasAttribute('id')) {
                $dup->setAttribute('id', $dup->getAttribute('id').$n);
            }
            $this->element[1]->parentNode->insertBefore($dup, $this->element[1]);
        }
    }

    public function output() {
        return $this->dom->saveHTML();
    }

    public function render() {
        echo $this->dom->saveHTML();
    }

}
