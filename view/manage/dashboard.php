<? $o->append('_header'); ?>

<? if ( $o->data->hasArray('MOTD') ):?>
<h1>Сообщение:</h1>
<? foreach ( $o->data->MOTD as $row ):?>
<?=$row;?><br />
<? endforeach; ?>
<br />
<? endif; ?>

<h1>Проблемы</h1>

<table border="0" cellspacing="0" cellpadding="0" class="TableDiagnostic">
<? $last = FALSE; foreach ( $o->data->Diagnostic as $row ): ?>
<? if ( $row[1] != 'error' ) continue; ?>
<? if ( $row[0] != $last ): $last = $row[0]; ?>
<tr>
	<th colspan="3" class="Caption"><div><?=$row[0];?></div><hr /></th>
</tr>
<? endif; ?>
<tr>
	<td>
	<? switch($row[1]){
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
	<th><?=$row[2]?></th>
	<td<?=( isset( $row[4] ) ? ' title="'.$row[4].'"' : '' ).( $row[1] == 'ok' ? '' : ' class="NotOk"' )?>><?=$row[3]?></td>
</tr>
<? endforeach; ?>
</table>
    
    
<? $o->append('_footer'); ?>