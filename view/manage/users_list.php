<? $o->append('_header'); ?>

<?
function resolveRight( $r ){
	switch ( $r ){
		case 'fullaccess': return 'полный доступ';
		case 'engineer': return 'техподдержка';
		case 'manager': return 'модератор';
	}
	return $r;
}

$has_time_create = isset( $o->data->List, $o->data->List[0], $o->data->List[0]['time_create'] );
$has_time_access = isset( $o->data->List, $o->data->List[0], $o->data->List[0]['time_last_access'] );
?>



<table border="0" cellspacing="0" cellpadding="0" width="100%" class="ManageORMList">
<thead>
<tr>
	<th>Доступ</th>
	<th>Уровень</th>
	<th>Логин</th>
	<? if ( $has_time_create ): ?>
	<th>Создан</th>
	<? endif; ?>
	<? if ( $has_time_access ): ?>
	<th>Активность</th>
	<? endif; ?>
</tr>
</thead>
<tbody>
<? foreach ( $o->data->List as $row ): ?>
<tr>
	<td class="minimal NotImportant"><?=($row[ $o->data->Params->field_banned ] == 0 ? 'открыт' : 'закрыт' ) ?></td>
	<td class="minimal NotImportant"><?=resolveRight($row[ $o->data->Params->field_admin_rights ])?></td>
	<td><a href="<?=new Renderable_URL(array('user','users',$row['id']))?>"><?=$row[ $o->data->Params->field_login ]?></a></td>
	<? if ( $has_time_create ): ?>
	<td class="minimal NotImportant"><?=new Renderable_DateTime( $row['time_create'] , Renderable_DateTime::SYMBOLIC);?></td>
	<? endif; ?>
	<? if ( $has_time_access ): ?>
	<td class="minimal NotImportant"><?=new Renderable_DateTime( $row['time_last_access'] , Renderable_DateTime::SYMBOLIC);?></td>
	<? endif; ?>
	
</tr>
<? endforeach; ?>
</tbody>
</table>

<script>

$( function(){
	var o = $('#FilterInputbox');
	if ( o.size() === 1 ){
		o.empty();
		var inp = $('<input type="text">');
		<? if ( Core::$in->_get['f'] != '' ): ?>
		inp.attr('value',"<?=htmlspecialchars( Core::$in->_get['f'] );?>");
		<? endif; ?>
		inp.css('width','100px').keyup(tryChangeFilter).appendTo(o);
	}
} );

function tryChangeFilter( e ){
	var code = e.which; // recommended to use e.which, it's normalized across browsers
    if(code==13)e.preventDefault();
    if(code!=32 && code!=13 && code!=188 && code!=186) return ;
    document.location = '?f=' + $(this).val();
}



</script>

<? $o->append('_footer'); ?> 