<?function recursiveBuildMavigation( $array, $first = FALSE ){
	
	$hasChilds = isset($array['_']) && is_array($array['_']) && count($array['_']);
	echo "<div class='Menu".( (isset($array['disabled']) && $array['disabled']) ? ' Disabled' : '' )."'".( $first ? ' style="padding-left: 0;'.( $hasChilds ? 'padding-bottom: 25px' : '' ).'"' : '' ).">";
	echo "<div class='Name'>";
	if ( isset($array['icon']) ){
		echo "<img src='".( new Renderable_Manage_Proxy('img/icons/'.$array['icon']) )."' />";
	}
	if ( isset($array['relative_url']) ){
		$url = new Renderable_URL($array['relative_url'] );
		echo "<a href='{$url}'>{$array['name']}</a>";
	}else 
		echo $array['name'];
	echo "</div>";
	if ( $hasChilds ){
		echo "<div class='Subelements'>";
		foreach ($array['_'] as $row ) recursiveBuildMavigation($row);
		echo "</div>";
	}
	echo "</div>";
}

foreach ( $o->data['ManageMenu'] as $row ) recursiveBuildMavigation($row, true);?>
