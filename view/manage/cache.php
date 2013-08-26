<? $o->append('_header'); ?>

<h1>Управление кешем</h1>
<div class="ManageORMEntry">
<table border="0" cellpadding="0" cellspacing="0" class="FieldsTable FieldsHashtable" style="margin: -15px 0 20px 10px;">
<tbody>
<tr>
	<td>
		<?=Configuration::CACHE_PREFIX?>
		<input id="CacheOperator" class="CommonInput" size="20" /> <br />
		<input type="button" cacheop="get" value="Получить" />
		<input type="button" cacheop="delete" value="Удалить" />
	</td>
</tr>
<tr>
	<td colspan="2">
		<div id="Result"></div>
	</td>
</tr>
</tbody>
</table>
</div>


<h1>Свойства</h1>
<table border="0" cellspacing="0" cellpadding="0" class="TableDiagnostic">
<? foreach ( $o->data->CacheInfo as $name=>$array ): ?>
<tr>
	<th colspan="3" class="Caption"><div><?=$name?></div><hr /></th>
</tr>
<? foreach ( $array as $row ): ?>
<tr>
	<td>
	<? switch($row[0]){
		case 'ok':
			echo '<img src="'.(new Renderable_Manage_Proxy('img/icons/fam/bullet_green.png')).'" />';
			break;
		case 'warning':
			echo '<img src="'.(new Renderable_Manage_Proxy('img/icons/fam/bullet_yellow.png')).'" />';
			break;
		case 'error':
			echo '<img src="'.(new Renderable_Manage_Proxy('img/icons/fam/bullet_red.png')).'" />';
			break;
		default:
			echo '<img src="'.(new Renderable_Manage_Proxy('img/icons/fam/bullet_error.png')).'" />';
			break;
	}
	?>
	</td>
	<th><?=$row[1]?></th>
	<td style="color: black;<?=(is_numeric( $row[2] ) ? 'text-align: right;': '');?>"><?=$row[2]?></td>
</tr>
<? endforeach; ?>
<? endforeach; ?>
</table>

<script>

function sendCacheOp(){
	var value = $('#CacheOperator').val();
	if ( typeof value == 'undefined' || value == '' ) return alert('Задайте ключ кеша');
	$.post('<?=(new Renderable_URL(array('ajax','system','cache')));?>', { operation: $(this).attr('cacheop'), key: value }, getCacheOp, 'json' );
}

function getCacheOp( data ){
	if ( data.status != 'ok' ) return alert('Ошибка');
	$('#Result').empty();
	if ( data.mode == 'erase' ){
		$('#Result').text('Удаление успешно завершено');
	} else {
		$('<textarea>').css('width','500px').text( data.data ).appendTo( '#Result' );
		console.log( eval("("+data.data+")") );
	} 
}

$( function(){
	$('input[cacheop]').click(sendCacheOp);
} );

</script>

<? $o->append('_footer'); ?>