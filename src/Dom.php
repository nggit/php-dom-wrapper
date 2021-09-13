<?php

/**
 * PHP DOM Wrapper.
 * https://github.com/nggit/php-dom-wrapper
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2016 nggit.
 */

namespace Nggit\PHPDOMWrapper;

use \DOMDocument;
use \DOMNode;
use \DOMXpath;
use \Exception;

class Dom
{
    protected $dom, $xpath, $node = array(), $element = array(null, null, null), $prepare = array();

    public function __construct($str)
    {
        $this->dom = new DOMDocument();
        libxml_use_internal_errors(true);
        if (file_exists(substr($str, 0, 255))) {
            $this->dom->loadHTMLFile($str);
        } else {
            $this->dom->loadHTML($str . "\n");
        }
        libxml_clear_errors();
        $this->xpath = new DOMXPath($this->dom);
    }

    public function __call($name, $args)
    {
        if (method_exists($this->xpath, $name)) {
            return call_user_func_array(array($this->xpath, $name), $args);
        }
        if (method_exists($this->dom, $name)) {
            return call_user_func_array(array($this->dom, $name), $args);
        }
        throw new Exception('method ' . $name . ' not found in ' . __CLASS__);
    }

    public function __get($name)
    {
        if (property_exists($this->xpath, $name)) {
            return $this->xpath->$name;
        }
        if (property_exists($this->dom, $name)) {
            return $this->dom->$name;
        }
        throw new Exception('property ' . $name . ' not found in ' . __CLASS__);
    }

    protected function getElementsById($id) // watch out, there's an 's' here
    {
        return $this->xpath->query("//*[@id='$id']");
    }

    public function getElementById($id) // safe getElementById()
    {
        if (($elements = $this->getElementsById($id)) && $elements->length > 0) {
            return $elements->item(0);
        }
        return null;
    }

    public function getElementsByClassName($class)
    {
        return $this->xpath->query("//*[contains(concat(' ', @class, ' '), ' $class ')]");
    }

    public function getElementsByTagName($tag)
    {
        return $this->xpath->query("//$tag");
    }

    public function find($item, $n = 0) // selector
    {
        switch ($item[0]) {
            case '#':
                if (is_null($n)) {
                    return $this->getElementsById(ltrim($item, '#'));
                }
                if (!isset($this->node[$item][$n])) {
                    if (($elements = $this->getElementsById(ltrim($item, '#'))) && $elements->length > $n) {
                        $this->node[$item][$n] = $elements->item($n);
                    }
                }
                break;
            case '.':
                if (is_null($n)) {
                    return $this->getElementsByClassName(ltrim($item, '.'));
                }
                if (!isset($this->node[$item][$n])) {
                    if (($elements = $this->getElementsByClassName(ltrim($item, '.'))) && $elements->length > $n) {
                        $this->node[$item][$n] = $elements->item($n);
                    }
                }
                break;
            case '/':
                if (is_null($n)) {
                    return $this->xpath->query($item);
                }
                if (!isset($this->node[$item][$n])) {
                    if (($elements = $this->xpath->query($item)) && $elements->length > $n) {
                        $this->node[$item][$n] = $elements->item($n);
                    }
                }
                break;
            default:
                if (is_null($n)) {
                    return $this->getElementsByTagName($item);
                }
                if (!isset($this->node[$item][$n])) {
                    if (($elements = $this->getElementsByTagName($item)) && $elements->length > $n) {
                        $this->node[$item][$n] = $elements->item($n);
                    }
                }
        }
        $this->element[0] = $item;
        $this->element[1] = isset($this->node[$item][$n]) ? $this->node[$item][$n] : null;
        $this->element[2] = $n;
        return $this;
    }

    public function element($element, $item = 1, $n = 0) {
        if ($element instanceof DOMNode) {
            $this->node[$item][$n] = $element;
            $this->element         = array($item, $element, $n);
        }
        return $this;
    }

    public function replace($content) // outer
    {
        if ($this->element[1] instanceof DOMNode) {
            if ($content instanceof DOMNode) {
                $fragment = $content;
            } else {
                $fragment = $this->dom->createDocumentFragment();
                $fragment->appendXML('<![CDATA[' . $content . ']]>');
            }
            $element = $this->element[1];
            $element->parentNode->replaceChild($fragment, $element);
            unset($this->node[$this->element[0]][$this->element[2]]);
            $this->element = array(null, null, null);
        }
    }

    public function html($content = null) // inner
    {
        if ($this->element[1] instanceof DOMNode) {
            if (is_null($content)) {
                $str   = $this->get();
                $start = strpos($str, '>') + 1;
                return substr($str, $start, strrpos($str, '<') - $start);
            }
            if ($content instanceof DOMNode) {
                $fragment = $content;
            } else {
                $fragment = $this->dom->createDocumentFragment();
                $fragment->appendXML('<![CDATA[' . $content . ']]>');
            }
            $element = $this->element[1];
            $node    = $element->cloneNode();
            $node->appendChild($fragment);
            $element->parentNode->replaceChild($node, $element);
            unset($this->node[$this->element[0]][$this->element[2]]);
            $this->element = array(null, null, null);
        }
    }

    public function prepend($content)
    {
        if ($this->element[1] instanceof DOMNode) {
            if ($content instanceof DOMNode) {
                $fragment = $content;
            } else {
                $fragment = $this->dom->createDocumentFragment();
                $fragment->appendXML('<![CDATA[' . $content . ']]>');
            }
            $element = $this->element[1];
            $element->insertBefore($fragment, $element->firstChild);
            $this->element[0] = null;
        }
        return $this;
    }

    public function append($content)
    {
        if ($this->element[1] instanceof DOMNode) {
            if ($content instanceof DOMNode) {
                $fragment = $content;
            } else {
                $fragment = $this->dom->createDocumentFragment();
                $fragment->appendXML('<![CDATA[' . $content . ']]>');
            }
            $element = $this->element[1];
            $element->appendChild($fragment);
            $this->element[0] = null;
        }
        return $this;
    }

    public function before($content)
    {
        if ($this->element[1] instanceof DOMNode) {
            if ($content instanceof DOMNode) {
                $fragment = $content;
            } else {
                $fragment = $this->dom->createDocumentFragment();
                $fragment->appendXML('<![CDATA[' . $content . ']]>');
            }
            $element = $this->element[1];
            $element->parentNode->insertBefore($fragment, $element);
            $this->element[0] = null;
        }
        return $this;
    }

    public function after($content)
    {
        if ($this->element[1] instanceof DOMNode) {
            if ($content instanceof DOMNode) {
                $fragment = $content;
            } else {
                $fragment = $this->dom->createDocumentFragment();
                $fragment->appendXML('<![CDATA[' . $content . ']]>');
            }
            $element = $this->element[1];
            $element->parentNode->insertBefore($fragment, $element->nextSibling);
            $this->element[0] = null;
        }
        return $this;
    }

    public function text($content = null)
    {
        if ($this->element[1] instanceof DOMNode) {
            if (is_null($content)) {
                return $this->element[1]->textContent;
            }
            $element = $this->element[1];
            $text    = $this->dom->createTextNode($content);
            $node    = $element->cloneNode();
            $node->appendChild($text);
            $element->parentNode->replaceChild($node, $element);
            unset($this->node[$this->element[0]][$this->element[2]]);
            $this->element = array(null, null, null);
        }
    }

    public function attr($name, $value = null)
    {
        if ($this->element[1] instanceof DOMNode) {
            if (is_null($value)) {
                return $this->element[1]->getAttribute($name);
            }
            $element = $this->element[1];
            $element->setAttribute($name, $value);
            $this->element[0] = null;
        }
        return $this;
    }

    public function removeAttr($name)
    {
        if ($this->element[1] instanceof DOMNode) {
            $element = $this->element[1];
            $element->removeAttribute($name);
            $this->element[0] = null;
        }
        return $this;
    }

    public function prepare($items)
    {
        is_array($items) or $items = func_get_args();
        $this->prepare = array_merge($this->prepare, $items);
        return $this;
    }

    public function remove($item = null)
    {
        switch (true) {
            case $item:
                $items = func_get_args();
                $this->prepare($items);
                $this->remove();
                break;
            case $this->element[0]: // use find() method
                if ($this->element[1] instanceof DOMNode) {
                    $element = $this->element[1];
                    $element->parentNode->removeChild($element);
                }
                unset($this->node[$this->element[0]][$this->element[2]]);
                $this->element = array(null, null, null);
                break;
            case $this->prepare: // use prepare() method
                foreach ($this->prepare as $prepare) {
                    if (isset($this->node[$prepare])) {
                        foreach ($this->node[$prepare] as $element) {
                            if ($element instanceof DOMNode) {
                                $element->parentNode->removeChild($element);
                            }
                        }
                        unset($this->node[$prepare]);
                    } else {
                        switch ($prepare[0]) {
                            case '#':
                                $elements = $this->getElementsById(ltrim($prepare, '#'));
                                break;
                            case '.':
                                $elements = $this->getElementsByClassName(ltrim($prepare, '.'));
                                break;
                            case '/':
                                $elements = $this->xpath->query($prepare);
                                break;
                            default:
                                $elements = $this->getElementsByTagName($prepare);
                        }
                        if ($elements instanceof DOMNodeList) {
                            for ($i = 0; $i < $elements->length; $i++) {
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

    public function dup($n = null)
    {
        if ($this->element[1] instanceof DOMNode) {
            if ($n > 0) {
                for ($j = 1; $j <= $n; $j++) {
                    $dup = $this->element[1]->cloneNode(true);
                    if ($elements = $dup->getElementsByTagName('*')) {
                        $i = $elements->length;
                        while ($i > 0) {
                            $i--;
                            $element = $elements->item($i);
                            if ($element->hasAttribute('id')) {
                                $element->setAttribute('id', $element->getAttribute('id') . $j);
                            }
                        }
                    }
                    if ($dup->hasAttribute('id')) {
                        $dup->setAttribute('id', $dup->getAttribute('id') . $j);
                    }
                    $this->element[1]->parentNode->insertBefore($dup, $this->element[1]);
                }
            } else {
                $dup = $this->element[1]->cloneNode(true);
                if ($elements = $dup->getElementsByTagName('*')) {
                    $i = $elements->length;
                    while ($i > 0) {
                        $i--;
                        $element = $elements->item($i);
                        if ($element->hasAttribute('id')) {
                            $element->setAttribute('id', $element->getAttribute('id') . '1');
                        }
                    }
                }
                if ($dup->hasAttribute('id')) {
                    $dup->setAttribute('id', $dup->getAttribute('id') . '1');
                }
                return $dup;
            }
        }
    }

    public function put($data)
    {
        if ($this->element[1] instanceof DOMNode) {
            static $n = 0;
            $n++;
            $dup = $this->element[1]->cloneNode(true);
            if ($elements = $dup->getElementsByTagName('*')) {
                $i = $elements->length;
                while ($i > 0) {
                    $i--;
                    $element = $elements->item($i);
                    if ($element->hasAttribute('id')) {
                        $attribute = $element->getAttribute('id');
                        if (empty($data[$attribute])) {
                            if ($element->childNodes->length <= 3) {
                                $element->parentNode->removeChild($element);
                            }
                        } else {
                            $element->setAttribute('id', $attribute . $n);
                            $fragment = $this->dom->createDocumentFragment();
                            $fragment->appendXML('<![CDATA[' . $data[$attribute] . ']]>');
                            $node = $element->cloneNode();
                            $node->appendChild($fragment);
                            $element->parentNode->replaceChild($node, $element);
                        }
                    }
                }
            }
            if ($dup->hasAttribute('id')) {
                $dup->setAttribute('id', $dup->getAttribute('id') . $n);
            }
            $this->element[1]->parentNode->insertBefore($dup, $this->element[1]);
        }
    }

    public function clean()
    {
        $this->remove();
    }

    public function output()
    {
        return $this->dom->saveHTML();
    }

    public function render()
    {
        echo $this->dom->saveHTML();
    }

    public function get($element = null) // outer
    {
        $element or $element = $this->element[1];
        if ($element instanceof DOMNode) {
            return version_compare(PHP_VERSION, '5.3.6', '>=') ? $this->dom->saveHTML($element) : $this->dom->saveXML($element);
        }
    }
}
