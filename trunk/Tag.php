<?php
/**
 * Enter description here ...
 * @todo add smart inline tag formatting support.  Currently supports one level of inline.
 * @todo remove the need for empty tags, Tags should know whether or not they should be empty and the user should be able to dictate it.
 * @todo add array parsing logic to construction.  Passing an array("id='value'", "class='value'") should work.
 * @todo break out string generating recursion into private function, not {@link Tag::__toString()}, this should remove the need for $outputting
 * @example examples/new_tag.php An example of the many ways to create and nest tags.
 * @author Tyson Vanover <tyson.vanover@gmail.com>
 */

	/**
	 * This is a quick html generating object.  It handles the generation of the html when echoed to string.
	 * @class Tag
	 */
class Tag{
	/**
	 * Throw Exception in event of a tag being added to two parents and prevent it.
	 */
	const NO_MULTIPARENT = "NO_MULTIPARENT";
	/**
	 * Throw Warning in event of a tag being added to two parents, but allow it.
	 */
	const NOTICE_MULTIPARENT = "NOTICE_MULTIPARENT";
	/**
	 * Do nothing in event of a tag being added to two parents, and allow it.
	 */
	const SILENT_MULTIPARENT = "SILENT_MULTIPARENT";
	/**
	 * Default tag formatting string.  uses sprintf syntax.
	 */
	const FORMAT = "<%s%s>%s</%s>";
	
	/**
 	 * Indentation count of the first tag the next time {@link Tag::__toString()} called.
	 * @var integer
	 */
	public static $indent = 0;
	
	/**
	 * Characters that will be used for 1st level of indentation.
	 * @var string
	 * @see __toString()
	 */
	public static $indent_char = "\t";
	/**
	 * Set to True when first tag begins outputing, reset to False upon completion
	 * Only used internally, should not be used externally.
	 * @var bool
	 * @todo remove the need for this by breaking recursion out of __toString()
	 */
	public static $outputting = FALSE;
	/**
	 * Controls how strict MULTIPARENT checking is.
	 * @var string
	 */
	public static $strict = self::NOTICE_MULTIPARENT;
	
	/**
	 * Tag's entity type.
	 * @var string
	 */
	public $tag;
	
	/**
	 * @var array Tag's attributes, stored as [attribute]=>value
	 */
	private $attributes = array();
	/**
	 * @var array Holds child tags and text contents of the tag.
	 */
	private $contents = array();
	/**
	 * @var array Holds refferences to parent tags.  Tends to contain no or 1 entry.  May contain more parents if {@link Tag::$strict} not set to NO_MULTIPARENT
	 */
	private $parents = array();
	/**
	 * @var bool Whether or not the contents should be indented
	 * @todo add logic to override this if current tag or child tags have more than one tag.
	 */
	private $no_indent = FALSE;

	/**
	 * Construct a new Tag
	 * @param $tag the element name you want a tag to be
	 * @param $attributes attributes of the tag, either in a well formed attribute string or an array of attributes paired with values.
	 * @todo Add capability to proccess array of well formed attribute strings array("id='idvalue'", "class='classval')
	 * @param $no_indent Whether or not to indent contents [experimental]
	 * @see _parse_attributes
	 */
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
	 * Add content to a tag.
	 * @param string|Tag|mixed $content A Tag, any string, or object that can be made a string.
	 * @access public
	 * @throws Exception|Warning May throw an Exception or a Warning depending on {@link Tag::$strict} setting when a Tag is added to two parent tags.
	 * @throws Exception Will throw an Exception when a Tag is made a child of itself.
	 * @see registerParent
	 * @see checkLoop
	 * @todo [proposed] Add the ability to add an array of contents.
	 */
	public function add($content){
		if(is_a($content, "Tag")){
			$content->_registerParent($this);
			$this->_checkLoop($content);
			if(isset($content->id)){
				$this->contents[$content->id] &= $content;
			}else{
				$this->contents[] = $content;
			}
		}else{
			$this->contents[] = $content;
		}
	}
	/**
	 * Return a child Tag of this Tag with requested id attribute.  This does not search recursively.
	 * @todo add checking of child Tags for Tags with atribute id set to $id
	 * @param string $id Id attribute of desired Tag
	 * @return Tag|NULL Returns a Tag if child tag
	 */
	public function get($id){
		if(isset($this->contents[$id])){
			return $this->contents[$id];
		}
		return NULL;
	}
	/**
	 * Set an attribute of the Tag object
	 * @param string $attribute Attribute to set
	 * @param string $content Value to set attribute to
	 * @todo [proposed] add the ability to add content directly without using {@link add()}
	 */
	public function __set($attribute, $content){
		$this->attributes[$attribute] = $content;
	}
	/**
	 * Get the valure of an attribute of the Tag object
	 * @param string $attribute Attribute you request
	 * @return string|NULL Value of attribute or NULL if attribute not set
	 */
	public function __get($attribute){
		if(isset($this->attributes[$attribute])){
			return $this->attributes[$attribute];
		}
		return NULL;
	}
	/**
	 * Returns fully formatted Tag and all it's children
	 * @return string  Fully formatted html
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

	/**
	 * Attribute string parser
	 * @todo Add capability to proccess array of well formed attribute strings array("id='idvalue'", "class='classval')
	 * @param unknown_type $attributes
	 */
	private function _parse_attributes($attributes){
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
	/**
	 * When added to another Tag as a child the parent tag will call this function to register itself.
	 * Only called by Tags when a Tag is added to another one.
	 * @param Tag $parent Reference to the parent Tag current Tag has been added to.
	 * @throws Exception|Warning May throw an Exception or a Warning depending on {@link Tag::$strict} setting when a Tag is added to two parent tags.
	 * @access private
	 * @see add
	 */
	public function _registerParent($parent){
		if(count($this->parents) > 0){
			switch (Tag::$strict){
				case Tag::NO_MULTIPARENT:
					throw new Exception("Tag '" . $this->tag . "' has been added to second parent '" . $parent->tag ."'");
					break;
				case Tag::NOTICE_MULTIPARENT:
					trigger_error("Tag '" . $this->tag . "' has been added to second parent '" . $parent->tag ."'");
				case Tag::SILENT_MULTIPARENT:
			}
		}
		$this->parents[] = $parent;
	}
	/**
	 * When a Tag is added to this Tag walks through parents to make sure a child is not added to itself.
	 * @param Tag $child Tag which has been added.
 	 * @throws Exception Will throw an Exception when a Tag is made a child of itself.
	 * @access private
	 * @see add
	 */
	public function _checkLoop($child){
		if($child===$this){
			throw new Exception("A Tag has been made it's own parent");
		}
		foreach($this->parents as $parent){
			$parent->_checkLoop($child);
		}
	}
	
	/**
	 * Helper funciton to quickly populate a new tag with content.
	 * @param string $tag the element name you want a tag to be
	 * @param string|array $attributes attributes of the tag, either in a well formed attribute string or an array of attributes paired with values.
	 * @param bool $no_indent Whether or not to indent contents [experimental]
	 * @see __contruct
	 */
	public static function Craft($tag, $content="", $attributes=array(), $no_indent=FALSE){
		$new = new Tag($tag, $attributes, $no_indent);
		$new->add($content);
		return $new;
	}
}
