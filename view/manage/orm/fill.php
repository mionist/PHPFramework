<? $o->append('_header'); ?>

<h1>Множественное добавление данных</h1>
<p>Этот инструмент предназначен для массового внесения строк в базу данных. Входной формат - CSV, один ряд входящих будет преобразован в один ряд в базе данных.</p>

<form action="" method="post" id="ImportForm">
<table border="0" cellspacing="0" cellpadding="0" class="TableDiagnostic">
<tr><th colspan="3" class="Caption"><div>Поля</div><hr /></th></tr>
<tr>
	<td>Имя</td>
	<td>Значение по умолчанию</td>
	<td>или ввод вручную</td>
</tr>
<? foreach( $o->data->ORMStructure as $row ): ?>
<? if ( $row instanceof Manage_ORM_FileField ) continue; ?>
<tr>
	<th><?=$row->getName();?></th>
	<? if ( $row->name == 'id' ): ?>
	<td><i>auto increment</i></td>
	<? else: ?>
	<td><input type="text" name="default_field_<?=$row->name;?>" /></td>
	<? endif; ?>
	<td><input type="checkbox" name="enable_field_<?=$row->name;?>" value="y" <?=($row->isReadOnly() ? '' : 'checked' )?> /></td>
</tr>
<? endforeach; ?>
</table>
<table border="0" cellspacing="0" cellpadding="0" class="TableDiagnostic" width="100%">
<tr><th class="Caption"><div>Значения</div><hr /></th></tr>
<tr><td>
<textarea rows="10" style="width: 60%" name="values"></textarea>
<br />
<input type="submit" value="Добавить" />
</td></tr>
</form>

<script>

function checkInputFields(){
	$('#ImportForm tr td input[type="text"]').attr('disabled',false);
	$('#ImportForm tr td input[type="checkbox"]:checked').each( function( i, o ){
		$(o).parent().parent().find('input[type="text"]').attr('disabled',true);
	} );
	
}

$( function(){
	$('#ImportForm tr td input[type="checkbox"]').bind('click change keyup', checkInputFields);
	checkInputFields();
	
} );

</script>

<? $o->append('_footer'); ?>