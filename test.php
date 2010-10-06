<?php
$start = microtime();
ini_set("display_errors" , "1");
ini_set("error_reporting" , "6143");
include("Tag.php");
include("EmptyTag.php");

echo "<pre>";
//attribute string test
echo Tag::Craft("h2", "Attribute String");
echo htmlentities(new EmptyTag("p", "id='moo' class='Re Ani Mate' value=5"));

//construct test
echo "<h2>Construct Test</h2>";
$tag = new Tag("div", array("id"=>"moo", "rel"=>"marco"));
echo htmlentities($tag);

//EmptyTag test
echo Tag::Craft("h2", "EmptyTag");
$tag->add(new EmptyTag("emptytag", array("id"=>"reallyempty")));
$tag->add(EmptyTag::Craft("emptytag"));
echo htmlentities($tag);

//Echo Test
echo "<h2>Echo Test</h2>";
$div = new Tag("div", array("id"=>"pre"));
$tag->add($div);
$div->add("some text");
$div->add(new Tag("input", array("id"=>"post")));
$tag->add("and some more");

$tag->add("You want some?");
$tag->add(Tag::Craft("p", "here is some content"));
$tag->add(new Tag("input", array("type"=>"checkbox",
										"id"=>"mook",
										"name"=>"took")));
$tag->add("Come and see" . Tag::Craft("em", "some sub content", array("id"=>"emph")));
//*/

$xxx = new Tag("xxx");
$tag->add($xxx);
$yyy = new EmptyTag("yyy");
$xxx->add($yyy);
echo htmlentities($tag);

echo "<h2>serialize Test</h2>";
$serial = serialize($tag);
echo $serial;

echo "<h2>Unserialize Test</h2>";
$unserialize = unserialize($serial);
$untxt = $unserialize->__toString();
$tagtxt = $tag->__toString();
$diff = strcmp($tagtxt, $untxt);
echo "strcmp: " . $diff . "<br>";
echo "print_r cmp:" . strcmp(print_r($tag, TRUE), print_r($unserialize,TRUE));

unset($tag);

echo Tag::Craft("h2", "id test");
$id = new Tag("div");
$id->add(Tag::Craft("div", "One", array("id"=>'one')));
$id->add(Tag::Craft("div", "two", array("id"=>'two')));
$id->add(Tag::Craft("div", "three", array("id"=>'three')));
echo htmlentities($id);

echo Tag::Craft("h2", "indented");
Tag::$indent = 2;
echo "\n".htmlentities($id);
Tag::$indent = 0;

echo Tag::Craft("h2", "Dup testing");
$one = new Tag("one");
$two = new Tag("two");
$three = new Tag("three");
$four = new Tag("four");
$also = new Tag("also");
$one->add($two);
$one->add($also);
$two->add($three);
$two->add($also);
$three->add($four);
echo htmlentities($one);
echo Tag::Craft("h3", "Strict Multiparent");
Tag::$strict = Tag::NO_MULTIPARENT;
try{
	$three->add($also);
}catch(Exception $e){
	echo $e;
}
echo htmlentities($one);

echo Tag::Craft("h3", "Add Loop");
try{
	$four->add($one);
}catch(Exception $e){
	echo $e;
}
echo "\n" . htmlentities($one);
echo Tag::Craft("h2", "Test time");
echo microtime()-$start;
?>