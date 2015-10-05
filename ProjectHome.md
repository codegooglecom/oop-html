# oop-html #

oop-html is a quick way to markup data in html and not have to worry about nesting, formatting, or otherwise generating html. Much lighter weight syntax than DOM for the sole purpose of quickly and simply generating html code.

  * Tags auto generate fully formatted code from simple data
  * You can just dump your data into tags and be assured that it will be propperly nested
  * No more mucking about with complex logic to insure that generated code is formatted
  * Uses refferences to hold children, no more worrying about when you create a tag
  * Just hold onto a reffence to it if you are going to add it later.


---


```
$div = new Tag("div", "id='content'");
$div->add(Tag::Craft("h1", "oop-html"));
$div->add(Tag::Craft("p","oop-html is a quick way to markup 
	data in html and not have to worry about nesting, formatting, 
	or otherwise generating html. Much lighter weight syntax than 
	DOM for the sole purpose of quickly and simply generating html code."));
$lists = array(	"Tags auto generate fully formatted code from simple data",
	"You can just dump your data into tags and be assured that it will be propperly nested",
	"No more mucking about with complex logic to insure that generated code is formatted",
	"Uses refferences to hold children, no more worrying about when you create a tag,",
			"Just hold onto a reffence to it if you are going to add it later.");
$ul = new Tag("ul", array("id"=>"features", "class"=>"list"));
$div->add($ul);
$class = "";
foreach ($lists as $key=>$value){
	if($class == "odd"){
		$class = "even";
	}else{
		$class = "odd";
	}
	$ul->add(Tag::Craft("li", $value, array("class"=>$class)));
}
echo $div;
```