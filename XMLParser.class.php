<?php
/*
Copyright 2019 OffTheBricks - https://github.com/mircerlancerous/XMLParser-PHP

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

 http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/
class XMLParser{
	private $xml;
	
	private $pointer;
	private $xmllen;
	
	public function __construct($xml){
		$this->xml = $xml;
		$this->RemoveXMLHeader();
		$this->xmllen = strlen($xml);
	}
	
	public function Parse(){
		$this->pointer = 0;
		return $this->ParseXML();
	}
	
	private function RemoveXMLHeader(){
		if(substr($this->xml,0,2) == "<?"){
			$pos = strpos($this->xml,">");
			$this->xml = substr($this->xml,$pos+1);
		}
	}
	
	private function isWhiteSpace(){
		if($this->pointer >= $this->xmllen){
			return FALSE;
		}
		$char = substr($this->xml,$this->pointer,1);
		return ctype_space($char);
	}
	
	private function ParseXML(){
		while($this->isWhiteSpace()){
			$this->pointer++;
		}
		
		$nodes = array();
		
		//find first tag
		$pos = strpos($this->xml,"<",$this->pointer);
		while($pos !== FALSE){
			//if the first tag is not at zero
			if($pos > $this->pointer){
				$node = new Node();
				$node->content = trim(substr($this->xml,$this->pointer,$pos-$this->pointer));
				$nodes[] = $node;
			}
			$this->pointer = 1 + $pos;
			//find the end of the tag
			$pos = strpos($this->xml,">",$this->pointer);
			//check if this is the closing of a previous tag
			if(substr($this->xml,$this->pointer,1) == "/"){
				$this->pointer = 1 + $pos;
				while($this->isWhiteSpace()){
					$this->pointer++;
				}
				return $nodes;
			}
			else{
				//parse tag header
				$node = $this->ParseHeader(substr($this->xml,$this->pointer,$pos-$this->pointer),$selfTerminates);
				$this->pointer = 1 + $pos;
				if(!$selfTerminates){
					$node->children = $this->ParseXML();
					if(sizeof($node->children) == 1 && !$node->children[0]->tagName){
						$node->content = $node->children[0]->content;
						$node->children = array();
					}
				}
				else{
					while($this->isWhiteSpace()){
						$this->pointer++;
					}
				}
				$nodes[] = $node;
			}
			//reset and continue
			$pos = strpos($this->xml,"<",$this->pointer);
		}
		
		return $nodes;
	}
	
	private function ParseHeader($head,&$selfTerminates){
		$selfTerminates = FALSE;
		if(substr($head,strlen($head)-1,1) == "/"){
			$selfTerminates = TRUE;
			$head = substr($head,0,strlen($head)-1);
		}
		$node = new Node();
		$head = explode(" ",$head);
		$node->tagName = $head[0];
		$len = sizeof($head);
		for($i=1; $i<$len; $i++){
			if(!$head[$i]){
				continue;
			}
			$attr = explode("=",$head[$i]);
			$key = $attr[0];
			$node->attributes->$key = str_replace('"',"",$attr[1]);
		}
		return $node;
	}
}

class Node{
	public $tagName;
	public $content;
	public $attributes;
	public $children;
	
	public function __construct(){
		$this->tagName = "";
		$this->content = "";
		$this->attributes = new \stdClass();
		$this->children = array();
	}
}
?>
