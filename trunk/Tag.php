<?php
/**
 * Tag object
 * This is a quick html generating object.  It handles the generation of the html when echoed to string.
 * Now will parse out attrib strings instead of arrays
 * @todo add smart inline tag formatting support.  Currently supports one level of inline.
 * @author tvanover
 * @version 0.2.1.1
 */
class Tag{
	const NO_MULTIPARENT = "NO_MULTIPARENT";
	const NOTICE_MULTIPARENT = "NOTICE_MULTIPARENT";
	const SILENT_MULTIPARENT = "SILENT_MULTIPARENT";
	const FORMAT = "<%s%s>%s</%s>";
	public static $indent = 0;			//the amount to indent the current level.
	public static $indent_char = "\t";
	public static $outputting = FALSE;	//the first to call toString sets it to true, then unsets it as __toString Returns
	public static $strict = self::NOTICE_MULTIPARENT;
	public $tag;
	private $attributes = array();
	private $contents = array();
	private $parents = array();
	private $id_cache = array();
	private $no_indent = FALSE;
	
	public function __construct($tag, $attributes=array(), $no_indent=FALSE){
		$this->tag = $tag;
		$this->no_indent = $no_indent;
		if(is_array($attributes)){
			$this->attributes = $attributes;
		}elseif(is_string($attributes)){
			$this->attributes = $this->_parse_attributes($attributes);
		}
	}
	
	/**
	 * Adds a tag or content
	 */
	public function add($content){
		if(is_a($content, "Tag")){
			$content->registerParent($this);
			$this->checkLoop($content);
			if(isset($content->id)){
				$this->contents[$content->id] &= $content;
			}else{
				$this->contents[] = $content;
			}
//		}elseif(is_string($content) && strpos($content, "\n")){
//			$this->contents = array_merge($this->contents, explode("\n", $content));
		}else{
			$this->contents[] = $content;
		}
	}

	public function registerParent($object){
		if(count($this->parents) > 0){
			switch (Tag::$strict){
				case Tag::NO_MULTIPARENT:
					throw new Exception("Tag '" . $this->tag . "' has been added to second parent '" . $object->tag ."'");
					break;
				case Tag::NOTICE_MULTIPARENT:
					trigger_error("Tag '" . $this->tag . "' has been added to second parent '" . $object->tag ."'");
				case Tag::SILENT_MULTIPARENT:
			}
		}
		$this->parents[] = $object;
	}
	
	public function checkLoop($child){
		if($child===$this){
			throw new Exception("A Tag has been made it's own parent");
		}
		foreach($this->parents as $parent){
			$parent->checkLoop($child);
		}
	}
	
	/**
	 * Gets child by id
	 */
	public function get($id){
		if(isset($this->contents[$id])){
			return $this->contents[$id];
		}
		return NULL;
	}
	
	/**
	 * Set's attribute
	 */
	public function __set($attribute, $content){
		$this->attributes[$attribute] = $content;
	}
	
	/**
	 * get's attribute
	 */
	public function __get($attribute){
		if(isset($this->attributes[$attribute])){
			return $this->attributes[$attribute];
		}
		return NULL;
	}

	/**
	 * returns a string
	 */
	public function __toString(){
		reset($this->contents);
		$first = FALSE;
		if(Tag::$outputting){
			Tag::$indent++;
		}else{
			$first = Tag::$outputting = TRUE;
		}
		
		$attribs = "";
		foreach($this->attributes as $key=>$value){
			if(is_numeric($key)){
				$attribs .= " " . $value;
			}else{
				$attribs .= sprintf(" %s='%s'", $key, $value);
			}
		}

		$args = array($this->tag, $attribs, "", $this->tag);
		
		if(count($this->contents) == 0){			//No children
			$out = str_repeat(Tag::$indent_char, Tag::$indent).vsprintf(constant(get_class($this)."::FORMAT"), $args);
		}elseif(count($this->contents) == 1 && !is_a(current($this->contents), "Tag")){		//one plaintext child
			$args[2] = current($this->contents);
			$out = str_repeat(Tag::$indent_char, Tag::$indent).vsprintf(self::FORMAT, $args);
		}else{										//Multiple children
			$args[2] = "";
			foreach($this->contents as $content){
				if(is_a($content, "Tag")){
					$args[2] .= "\n" . $content->__toString();
				}else{
					$args[2] .= "\n" . str_repeat(Tag::$indent_char, Tag::$indent+1) . $content;
				}
			}
			$args[2] .= "\n" . str_repeat(Tag::$indent_char, Tag::$indent);
			$out = 	str_repeat(Tag::$indent_char, Tag::$indent).vsprintf(self::FORMAT, $args);
		}
	
		if($first){
			Tag::$outputting = FALSE;
		}else{
			Tag::$indent--;
		}
		return $out;
	}

	public function _parse_attributes($attributes){
		$state = "id";
		$cache = array();
		$attribs = array();
		$id = "";
		$eov = "";
		$ignore_next = FALSE;
		
		foreach(str_split($attributes) as $char){
			switch($state){
				case("id"):
					switch($char){
						case(" "):
						case("\n"):
						case("\t"):
						break;
						case("="):
							$id = implode("",$cache);
							$cache = array();
							$state = "value";
						break;
						default:
							$cache[] = $char;
						break;
					}
				break;
				case("value"):
					if($char == "'" || $char == "\""){
						$state = "multi_value";
						$eov = $char;
					}else{
						$state = "single_value";
						$cache[] = $char;
					}
				break;
				case("single_value"):
					switch($char){
						case(" "):
						case("\n"):
						case("\t"):
							$attribs[$id] = implode("",$cache);
							$cache = array();
							$state = "id";
						break;
						default:
							$cache[] = $char;
						break;
					}
				
				break;
				case("multi_value"):
					switch($char){
						case("\\"):
							$ignore_next = TRUE;
						break;
						case($eov):
							if($ignore_next){
								$cache[] = $char;
								$ignore_next = FALSE;
							}else{
								$attribs[$id] = implode("",$cache);
								$cache = array();
								$state = "id";
							}
						break;
						default:
							$cache[] = $char;
						break;
					}
				break;
			}
		}
		if(count($cache)>0){
			$attribs[$id] = implode("",$cache);
		}
		return $attribs;
	}
	
	public function testformat(){
		
		return constant(get_class($this)."::FORMAT");
	}
	/**
	 * returns a Tag object
	 */
	public static function Craft($tag, $content="", $attributes=array(), $no_indent=FALSE){
		$new = new Tag($tag, $attributes, $no_indent);
		$new->add($content);
		return $new;
	}
}
