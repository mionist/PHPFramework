<? $o->append('_header'); ?>

<?

function resolveType( $t ){
	switch ( $t ){
		case 'add': return 'добавлено';
		case 'edit': return 'изменено';
		case 'delete': return 'удалено';
	}
	return $t;
}

?>

<table border="0" cellspacing="0" cellpadding="0" width="100%" class="ManageORMList ManageLogList">
<thead>
<tr>
	<th>ID</th>
	<th>Кто</th>
	<th>Дата</th>
	<th>Тип</th>
	<th>Таблица</th>
	<th>Поле</th>
	<th>Было</th>
	<th>Стало</th>
</tr>
</thead>
<tbody>
<? $last = ''; $odd = false; ?>
<? foreach ( $o->data->List as $row ): ?>
<? if ( $row['oid'] != $last ){ $last = $row['oid']; $odd = !$odd; } ?>
<tr <?=( $odd ? 'class="Odder"' : '' )?>>
	<td class="minimal"><a href="<?=new Renderable_URL(array('user','log','view'))?>/<?=$row['oid']?>"><?=$row['oid']?></a></td>
	<td class="minimal NotImportant"><?=$row->getUsername();?></td>
	<td class="minimal NotImportant"><?=new Renderable_DateTime( $row['time'] , Renderable_DateTime::SYMBOLIC);?></td>
	<td class="minimal"><?=resolveType( $row['type'] )?></td>
	<td class="minimal NotImportant"><?=$row['table'];?></td>
	<td class="minimal NotImportant"><?=$row['field'];?></td>
	<td><?=htmlspecialchars($row['value_old'])?></td>
	<td><?=htmlspecialchars($row['value_new'])?></td>
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
		inp.css('width','100px').keyup(tryChangeFiltering).appendTo(o);
	}
        o = $('#TableInputbox');
	if ( o.size() === 1 ){
		o.empty();
		var inp = $('<input type="text">');
		<? if ( Core::$in->_get['t'] != '' ): ?>
		inp.attr('value',"<?=htmlspecialchars( Core::$in->_get['t'] );?>");
		<? endif; ?>
		inp.css('width','100px').keyup(tryChangeFiltering).appendTo(o);
	}
} );

function tryChangeFiltering( e ){
    var code = e.which; // recommended to use e.which, it's normalized across browsers
    if(code==13)e.preventDefault();
    if(code!=32 && code!=13 && code!=188 && code!=186) return ;
    document.location = '?f=' + $('#FilterInputbox input').val()+'&t=' + $('#TableInputbox input').val();
}

</script>

<? $o->append('_footer'); ?> 