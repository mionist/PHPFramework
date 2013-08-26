<? $o->append('_header'); ?>

<h1>Недавние отчёты</h1>

<table border="0" cellspacing="0" cellpadding="0" class="TableDiagnostic">
<? foreach ( $o->data->list as $row ): ?>
<tr>
	<td><?=date('d.m.Y H:i', strtotime($row['time_modify']))?></td>
	<td><img src="<?=new Renderable_Manage_Proxy('img/icons/fam/report.png');?>" style="margin: 2px 0;" /></td>
	<th><a href="<?=new Renderable_URL(array('reports',$row['id']));?>"><?=$row['title']?></a></th>
</tr>
<? endforeach; ?>
</table>

<? $o->append('_footer'); ?>