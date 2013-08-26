<? $o->append('_header');
// Данные
$defaults = $o->data->ORMDefaults;

// Для загрузчика
$unique = 1;

// Сортируем поля
$fields_ro = array();
$fields_togglers = array();
$fields_external = array();
$fields_text = array();
$fields_integer = array();
$fields_list = array();
$fields_other = array();
$fields_file = array();
$fields_datetime = array();

foreach ( $o->data->ORMStructure as $row ){
	if ( $row->isReadOnly() ) {
		$fields_ro[] = $row;
		continue;
	}
	if ( $row instanceof Manage_ORM_FileField ){
		$fields_file[] = $row;
		continue;
	}
	if ( $row instanceof Manage_ORM_TogglerField ){
		$fields_togglers[] = $row;
		continue;
	}
	if ( $row instanceof Manage_ORM_ExternalField ){
		$fields_external[] = $row;
		continue;
	}
	if ( $row instanceof Manage_ORM_TimestampField ){
		$fields_datetime[] = $row;
		continue;
	}
	if ( $row instanceof Manage_ORM_ListField ){
		$fields_list[] = $row;
		continue;
	}
	if ( $row instanceof Manage_ORM_NumericField ){
		$fields_integer[] = $row;
		continue;
	}
	if ( $row instanceof Manage_ORM_StringField && $row->isBigText() ){
		$fields_text[] = $row;
		continue;
	}

	$fields_other[] = $row;
}

function buildRow( $rowObject, $handlerObject, $defaults = NULL ){
	echo "<tr>";
	echo "<th>".str_replace('_', ' ', $rowObject->getName())."</th>";
	echo "<td>".$handlerObject."</td>";
	echo "</tr>";
        if (method_exists($rowObject, 'getPropertiesEdit') && $defaults !== NULL ) foreach ( $rowObject->getPropertiesEdit($defaults) as $k=>$v ){
            ?><tr><th valign="top"><i><?=$k?></i></th><td><?=$v?></td></tr><?
        }
}
?>
<form method="post" enctype="multipart/form-data" name="MainEditForm" id="MainEditForm">
<div class="ManageORMEntry">
<div class="Header">
	<h2><?=( $o->data->ORMMode == 'edit' ? 'Редактирование записи' : 'Добавление записи' ).( Core::$in->_get['saved'] == 'yes' ? ' - успешно сохранено' : '' );?></h2>
	<? if ( $o->data->ORMMode == 'edit' ): ?>
	<div class="ReadonlyFields">
	<? foreach ( $fields_ro as $row ): ?>
		<div class="Field"><span><?=$row->getName();?>:</span> <b><?=$row->decodeInRowValue($defaults);?></b></div>
	<? endforeach; ?>
	</div>
	<? endif; ?>
	<? if ( count( $fields_togglers ) > 0 ): ?>
	<div class="TogglersFields">
	<? foreach ( $fields_togglers as $row ): ?>
		<div class="Field"><?=new Renderable_HTML( Renderable_HTML::HTML_CHECKBOX, $row->name, $row->name, NULL, NULL, '1', (isset($defaults[$row->name]) ? $defaults[$row->name] : NULL), NULL, $row->getName() );?></div>
	<? endforeach; ?>
	</div>
	<? endif; ?>
</div>
<? if ( count($fields_external) ): ?>
<table border="0" cellpadding="0" cellspacing="0" class="FieldsTable FieldsList">
<thead><tr><th colspan="2" class="Caption"><div>Внешние связи</div><hr /></th></tr></thead>
<tbody>
	<? foreach ( $fields_external as $row ) buildRow( $row, new Renderable_HTMLSingleSelect($row->name, $row->getValues(), (isset($defaults[$row->name]) ? $defaults[$row->name] : NULL), 'CommonInput' )); ?>
</tbody>
</table>
<? endif; ?>
<? if ( count($fields_list) ): ?>
<table border="0" cellpadding="0" cellspacing="0" class="FieldsTable FieldsList">
<thead><tr><th colspan="2" class="Caption"><div>Выбор</div><hr /></th></tr></thead>
<tbody>
	<? foreach ( $fields_list as $row ) buildRow( $row, new Renderable_HTMLSingleSelect($row->name, $row->getValues(), (isset($defaults[$row->name]) ? $defaults[$row->name] : NULL), 'CommonInput' )); ?>
</tbody>
</table>
<? endif; ?>
<? if ( count($fields_datetime) ): ?>
<table border="0" cellpadding="0" cellspacing="0" class="FieldsTable FieldsDatetime">
<thead><tr><th colspan="2" class="Caption"><div>Даты и время</div><hr /></th></tr></thead>
<tbody>
	<? foreach ( $fields_datetime as $row ) buildRow($row, new Renderable_HTMLInput($row->name,(isset($defaults[$row->name]) ? $defaults[$row->name] : ''), FALSE, NULL, 'CommonInput' ));?>
</tbody>
</table>
<? endif; ?>
<? if ( count($fields_integer) ): ?>
<table border="0" cellpadding="0" cellspacing="0" class="FieldsTable FieldsNumeric">
<thead><tr><th colspan="2" class="Caption"><div>Числа</div><hr /></th></tr></thead>
<tbody>
	<? foreach ( $fields_integer as $row ) buildRow($row, new Renderable_HTMLInput($row->name,(isset($defaults[$row->name]) ? $defaults[$row->name] : ''), FALSE, NULL, 'CommonInput', NULL, $row->pattern ));?>
</tbody>
</table>
<? endif; ?>
<? if ( count($fields_other) ): ?>
<table border="0" cellpadding="0" cellspacing="0" class="FieldsTable FieldsOther">
<thead><tr><th colspan="2" class="Caption"><div>Остальное</div><hr /></th></tr></thead>
<tbody>
	<? foreach ( $fields_other as $row ) buildRow( $row, new Renderable_HTMLInput($row->name,(isset($defaults[$row->name]) ? htmlspecialchars($row->decodeInRowValue( $defaults )) : ''), FALSE, NULL, 'CommonInput', NULL, $row->pattern ), $defaults); ?>
</tbody>
</table>
<? endif; ?>
<? if ( count($fields_text) ): ?>
<table border="0" cellpadding="0" cellspacing="0" class="FieldsTable FieldsOther">
<thead><tr><th colspan="2" class="Caption"><div>Остальное</div><hr /></th></tr></thead>
<tbody>
	<? foreach ( $fields_text as $row ): ?>
	<tr>
		<th><?=str_replace('_', ' ', $row->getName())?></th>
		<td>
			<textarea name="<?=$row->name?>" class="CommonInput<?=( $row->isHTML() ? ' enableWYSIWYG' : '' )?>" rows="10"><?=(isset($defaults[$row->name]) ? htmlspecialchars($defaults[$row->name]) : '')?></textarea>
                        <? if ( $row->isHTML() && isset( $o->data->ORMIllustrations ) ):?>
                        <div class="IllustrationsToggle">Иллюстрации</div><br /><br />
                        <? endif; ?>
		</td>
	</tr>
	<? endforeach; ?>
</tbody>
</table>
<? endif; ?>
<? if ( count($fields_file) ): ?>
<table border="0" cellpadding="0" cellspacing="0" class="FieldsTable FieldsFile">
<thead><tr><th colspan="2" class="Caption"><div>Прикрепленные файлы</div><hr /></th></tr></thead>
<tbody>
	<? foreach ( $fields_file as $row ):?>
		<tr>
		<th><?=str_replace('_', ' ', $row->getName())?></th>
                <? if ( $o->data->ORMMode == 'edit' ):?>
		<td><input type="file" name="<?=$row->getFileFieldName();?>" /></td>
		</tr>
                <? if ( $row->hasFile( $defaults ) ):?>
                <tr>
                    <th>&nbsp;</th>
                    <td><input type="checkbox" name="delete_file_<?=$row->getFileFieldName();?>" id="delete_file_<?=$row->getFileFieldName();?>" value="yes" /><label for="delete_file_<?=$row->getFileFieldName();?>">пометить на удаление</label></td>
                </tr>
                <? endif; ?>
		<? if ( $row->hasFile( $defaults ) ) foreach ( $row->getPropertiesEdit( $defaults ) as $k=>$v ): ?>
		<tr><th valign="top"><i><?=$k?></i></th><td><?=$v?></td></tr>
		<? endforeach; ?>
                <? else:?>
                <td><i>Файл можно будет загрузить после нажатия на кнопку "Сохранить"</i></td></tr>
                <? endif;?>

	<? endforeach; ?>
</tbody>
</table>
<? endif; ?>
</div>
</form>

<!-- Illustrations block -->
<div id="IllustrationsBlock">
<? if ( isset( $o->data->ORMIllustrations ) ): ?>
<form method="post" enctype="multipart/form-data" target="MediantIllustrationUploader">
    <input type="hidden" name="UploadMode" value="Illustration" />
    <input type="file" name="MediantIllustration" /><br />
</form>
<iframe name="MediantIllustrationUploader" width="1px" height="1px" style="visibility: hidden; float: right;"></iframe>
<div>
<? if ( count($o->data->ORMIllustrations) ): ?>
<? foreach( $o->data->ORMIllustrations as $ill ): ?>
<img border="0" class="IllustrationImage" src="/content/illustrations/<?=$ill['image_name'].'.'.$ill['image_ext'];?>" forentry="<?=$defaults['id']?>" forillustration="<?=$ill['id']?>" /><span>X</span>
<? endforeach; ?>
<? endif; ?>
</div>
<? endif; ?>
</div>
<!-- End of Illustrations block -->
            
<script>

$( function(){
	$('#SaveButton').click( doSaveForm );
	$('#DeleteButton').click( doDelete );
	$('.FieldsDatetime input').datepicker( {
		closeText: 'Закрыть',
		prevText: '&#x3c;Пред',
		nextText: 'След&#x3e;',
		currentText: 'Сегодня',
		monthNames: ['Январь','Февраль','Март','Апрель','Май','Июнь',
		'Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
		monthNamesShort: ['Янв','Фев','Мар','Апр','Май','Июн',
		'Июл','Авг','Сен','Окт','Ноя','Дек'],
		dayNames: ['воскресенье','понедельник','вторник','среда','четверг','пятница','суббота'],
		dayNamesShort: ['вск','пнд','втр','срд','чтв','птн','сбт'],
		dayNamesMin: ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'],
		weekHeader: 'Не',
		dateFormat: 'yy-mm-dd',
		firstDay: 1,
		isRTL: false,
		showMonthAfterYear: false,
		yearSuffix: ''} );

	$('.FieldsDatetime input').each( function( i, o ){
		$('<span class="JSHelperLink">').text('last+1').attr('js_helper','date').attr('for',$(o).attr('name')).attr('mode','last').insertAfter($(o));
		$('<span class="JSHelperLink">').text('вчера').attr('js_helper','date').attr('for',$(o).attr('name')).attr('mode','yesterday').insertAfter($(o));
		$('<span class="JSHelperLink">').text('завтра').attr('js_helper','date').attr('for',$(o).attr('name')).attr('mode','tomorrow').insertAfter($(o));
		$('<span class="JSHelperLink">').text('сегодня').attr('js_helper','date').attr('for',$(o).attr('name')).attr('mode','today').insertAfter($(o));
	});

        $('.IllustrationsToggle').click( ToggleIllustrations );
	$('input[pattern]').bind('change keyup click',doPatternedInputCheck);
} );

function doSaveForm(){
	$('#MainEditForm').submit();
}

function doDelete(){
	if ( !confirm('Вы действительно желаете удалить запись?') ) return;
	document.location = '<?=new Renderable_URL( array( 'data', Core::$in->_navigation[2], 'edit', Core::$in->_navigation[4], 'delete' ) )?>';
}

function ToggleIllustrations(){
    var o = $(this);
    if ( o.parent().find('#IllustrationsBlock').size() > 0 && o.parent().find('#IllustrationsBlock').css('display') == 'block' ){
        $('#IllustrationsBlock').css('display','none');
        return;
    }
   $('#IllustrationsBlock').css('display','block').insertAfter( o );
}

function doPatternedInputCheck(){
	var $this = $(this);
	var pattern = $this.attr('pattern');
	var answer = "";
	var characters = $this.val().split("");
	for ( var i=0; i<characters.length; i++ ){
		var j = characters[i];
		if ( !j.match( pattern) ) continue;
		answer += j;
	}
	if ( $this.val() != answer ) $this.val( answer );
}

</script>
<br /><br /><br />
<? $o->append('_footer'); ?>