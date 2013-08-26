<? $o->append('_header'); ?>
<? $section = NULL; ?>
<div class="ManageORMEntry">
<? foreach ( $o->data->ORMData as $row ): ?>
<? if ( !isset($section) || $section != $row['section'] ): ?>
<? if ( isset($section) ): ?>
</tbody></table>
<? endif; ?>
<table border="0" cellpadding="0" cellspacing="0" class="FieldsTable FieldsHashtable" style="margin-bottom: 50px;" width="100%">
<thead><tr><th colspan="3" class="Caption"><div><?=( empty( $row['section'] ) ? '<i>Раздел без названия</i>' : 'Раздел "'.$row['section'].'"')?></div><hr /></th></tr></thead>
<tbody>
<? $section = $row['section']; endif; ?>

<tr>
	<th width="1%" nowrap="nowrap">
		<b><?=$row['name']?></b>
		<? if ( isset($row['description']) ): ?>
		<small><?=$row['description']?></small>
		<? endif; ?>
	</th>
	<td width="1%" nowrap="nowrap">
		<? foreach ( $row['value'] as $lang=>$value ): ?>
		<div>
		<?=( $lang != '' ? $lang.': ' : '' )?>
		<? if ( $row['type'] === 'text' ): ?>
		<textarea name="<?=$row['key']?>" rows="7" cols="50" class="CommonInput" lang="<?=$lang?>" initial="<?=htmlspecialchars($value);?>"><?=htmlspecialchars($value);?></textarea><br />
		<? elseif ( $row['type'] === 'html' ):?>
                <form onsubmit="return sendPostFor(this,false);">
		<textarea name="<?=$row['key']?>" rows="7" cols="50" class="CommonInput enableWYSIWYG" lang="<?=$lang?>" initial="<?=htmlspecialchars($value);?>"><?=htmlspecialchars($value);?></textarea><br />
                </form>
		<? else: ?>
		<input type="text" name="<?=$row['key']?>" lang="<?=$lang?>" initial="<?=htmlspecialchars($value);?>" value="<?=htmlspecialchars($value);?>" class="CommonInput" style="width: 700px;" />
		<? endif; ?>
		<input type="button" value="сохранить" style="display: none;" />
		</div>
		<? endforeach; ?>
	</td>
	<td>&nbsp;</td>
</tr>

<? endforeach; ?>
<? if ( count( $o->data->ORMData ) ): ?>
</tbody></table>
<? endif; ?>
</div>

<script>

$( function(){
	$('.FieldsHashtable input[type="text"], .FieldsHashtable textarea').bind( 'change keyup', HashtableElementEdited );
	$('.FieldsHashtable input[type="button"]').click(HashtableElementSendChanges);
	var o = $('#FilterInputbox');
	if ( o.size() === 1 ){
		o.empty();
		var inp = $('<input type="text">');
		inp.css('width','100px').keyup(tryChangeFilter).appendTo(o);
	}
} );

function tryChangeFilter(){
	var value = $(this).val();

	if ( value == '' || value == ' ' ){
		$('.FieldsHashtable tbody tr').show();
		return;
	}

	$('.FieldsHashtable tbody tr').each( function( i, o ){
		var $o = $(o);
		if ( $o.find('th b, th small').text().indexOf( value ) !== -1
				|| $o.find('td textarea, td input[type="text"]').val().indexOf( value ) !== -1
			 ){
			$o.show();
		} else $o.hide();
	} );
}

function HashtableElementEdited(){
	var $this = $(this);
	if ( $this.attr('initial') != $this.val() ){
		$this.parent().children('input[type="button"]').fadeIn(500);
	} else {
		$this.parent().children('input[type="button"]').hide();
	}
}

function HashtableElementSendChanges(){
    sendPostFor(this, true);
}

function sendPostFor( obj, useHide ){
	var $this = $(obj);

	var $obj = $this.parent().find('input[type="text"], textarea');

	// Отправляем запрос
	var url = "<?=new Renderable_URL(array('a','data',Core::$in->_navigation[2],'savevalue'));?>/"+$obj.attr('name');

	$.post( url, {'value':$obj.val(), 'lang':$obj.attr('lang') } );
	$obj.attr('initial',$obj.val());
	if ( useHide ) $this.hide();
        return false;

}

</script>

<? $o->append('_footer'); ?>