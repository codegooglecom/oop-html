<?php
/**
 * EmptyTag object
 * Produces an empty tag, created in step with Tag
 * @author tvanover
 * @version 0.2.1.1 Requires Tag version 0.2.1.1 or greater.
 */
class EmptyTag extends Tag{
	const FORMAT = "<%s%s />";
	
	public static function Craft($tag, $content="", $attributes=array()){
		$new = new EmptyTag($tag, $attributes, $inline = FALSE);
		return $new;
	}
}
