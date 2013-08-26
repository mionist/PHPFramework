<? $o->append('_header'); ?>

<h1>Экспорт данных</h1>
<form action="" method="post" id="ExportForm">
<table border="0" cellspacing="0" cellpadding="0" class="TableDiagnostic">
<tr><th colspan="3" class="Caption"><div>Формат экспорта</div><hr /></th></tr>
<? foreach ( $o->data->formats as $k=>$v ): ?>
	<tr>
		<td><input type="radio" name="format" id="format<?=$k?>" value="<?=$k?>" /></td>
		<th><label for="format<?=$k?>"><?=$v['name'];?></label></th>
		<td><?=( isset($v['description']) ? $v['description'] : '&nbsp;' );?></td>
	</tr>
<? endforeach; ?>
<tr><th colspan="3" class="Caption"><div>Параметры</div><hr /></th></tr>
	<tr>
		<td><input type="checkbox" name="headers" id="headers" value="y" checked /></td>
		<th colspan="2"><label for="headers">Добавить информацию о структуре</label></th>
	</tr>
	<tr>
            <td><input type="checkbox" name="asfile" id="asfile" value="y" disabled="disabled" /></td>
		<th><label for="asfile">В виде файла</label></th>
		<td>&nbsp;</td>
	</tr>
<tr><th colspan="3" class="Caption"><div>Кодировка</div><hr /></th></tr>
	<tr>
		<td>&nbsp;</td>
		<th colspan="2"><select name="charset">
		<? foreach ( $o->data->charsets as $k=>$v ): ?>
			<option value="<?=$k?>"><?=$v;?></option>
		<? endforeach; ?>
		</select><input type="submit" value="Экспорт" disabled="disabled" /></th>
	</tr>	
</table>
</form>

<script type="text/javascript">

$( function(){
	$('#ExportForm input[name="format"]').bind( 'click change keyup', function(){ $('#ExportForm input[type="submit"]').attr('disabled', false); } );
} );

</script>

<? $o->append('_footer'); ?>