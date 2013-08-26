<? $o->append('_header'); ?>
<?
function resolveType( $t ){
	switch ( $t ){
		case 'add': return 'внесение записи в базу';
		case 'edit': return 'редактирование';
		case 'delete': return 'удаление из базы';
	}
	return $t;
}
?>
<h1>Информация об операции</h1>

<table cellspacing="0" cellpadding="0" class="TableDiagnostic">
<tbody>
	<tr>
		<th>Номер операции</th>
		<td colspan="3"><?=$o->data->Entry[0]['oid'];?></td>
	</tr>
	<tr>
		<th>Тип</th>
		<td colspan="3" style="color: black;"><?=resolveType($o->data->Entry[0]['type']);?></td>
	</tr>
	<tr>
		<th>Идентификатор пользователя</th>
		<td colspan="3"><?=$o->data->Entry[0]['uid'];?></td>
	</tr>
	<tr>
		<th>Время</th>
		<td colspan="3"><?=new Renderable_DateTime( $o->data->Entry[0]['time'], Renderable_DateTime::SYMBOLIC );?></td>
	</tr>
<tr>
	<th colspan="4" class="Caption"><div>Детализация</div><hr /></th>
</tr>
<? foreach ( $o->data->Entry as $row ): ?>
<tr>
	<th><?=$row['table'].'.'.$row['field'].'.'.$row['entity']?></th>
	<? if ( $row['type'] == 'add' || $row['type'] == 'delete' ): ?>
	<td style="color: black;"><?=htmlspecialchars( $row['type'] == 'add' ? $row['value_new'] : $row['value_old'] )?></td>
	<td colspan="2">&nbsp;</td>
	<? else: ?>
	<td style="color: black;"><?=htmlspecialchars($row['value_old'])?></td>
	<th><span style="font-size: 20px; line-height: 12px;">→</span></th>
	<td style="color: black;"><?=htmlspecialchars($row['value_new'])?></td>
	<? endif; ?>
</tr>
<? endforeach; ?>
</tbody>
</table>

<? $o->append('_footer'); ?> 