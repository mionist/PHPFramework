<? $o->append('_header'); ?>

<h1>Перекомпиляция шаблонов</h1>

<table border="0" cellspacing="0" cellpadding="0" class="TableDiagnostic">
<? foreach ( $o->data->folders as $row ): ?>
<tr>
    <th><?=$row[0];?></th>
    <td><?=$row[1];?>&nbsp;&nbsp;&nbsp;</td>
    <td><?=$row[2];?>&nbsp;&nbsp;&nbsp;</td>
    <td><b style="color: black;">
    <? if ( !$row[3] ):?>
	    источник отсутствует, игнорируем
    <?elseif ( !$row[4] ) :?>
	    стоит защита от записи
    <? else:?>	    
	    используется
    <? endif; ?>
    </b></td>
</tr>
<? endforeach; ?>
</table>
<form method="post">
    <input type="hidden" name="compile" value="yes" />
    <input type="submit" value="Перекомпилировать" />
</form>

<? $o->append('_footer'); ?>