<? $o->append('_header'); ?>

<h1>Подготовка отчёта</h1>

<form method="post">
<input type="hidden" name="generate" value="yes" />
<table border="0" cellspacing="0" cellpadding="0" class="TableReportForm">
    <tbody>
<? foreach ( $o->data->form as $row ):?>
        <tr <?=(( is_object( $row['object'] ) && $row['object'] instanceof Renderable_DateTimeInput )? 'class="FieldsDatetime"' : '' )?>>
	<th><?=$row['caption'];?></th>
	<td>
	    <?=$row['object'];?>
	</td>
    </tr>
<? endforeach; ?>	
    </tbody>
</table>
<br /><br />
<input type="submit" value="Создать отчёт"/>
</form>

<script>
$( function(){
    $('.FieldsDatetime input').each( function(){ console.log(this); $(this).datepicker({
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
		yearSuffix: '' }) } );
    
    
} );
</script>

<? $o->append('_footer'); ?>