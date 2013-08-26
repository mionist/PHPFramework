<? $o->append('_header'); 

function bullet( $trueFalse, $icon, $description ){
	if ( !$trueFalse ) return '<img src="'.(new Renderable_Manage_Proxy('img/icons/fam/bullet_white.png')).'" title="'.$description.'" />';
	return '<img src="'.(new Renderable_Manage_Proxy('img/icons/fam/'.$icon.'.png')).'" title="'.$description.'" />';
}

$starBricks = array();

?>

<h1>Страницы</h1>
<table id="Pages" border="0" cellspacing="0" cellpadding="0" width="100%" class="ManageORMList">
<thead>
<tr>
	<th>Тип</th>
	<th class="minimal">Бриксы</th>
	<th>Название</th>
	<th>Определение</th>
	<th>Обработчики</th>
</tr>
</thead>
<tbody>
<? foreach ( $o->data->Pages as $row ):
$bricks = array();
if ( isset($row['bricks']) && count($row['bricks']) > 0 ) $bricks = array_merge( $starBricks, $row['bricks'] );
if ( $row['name'] == '*' && isset($row['bricks']) ) $starBricks = $row['bricks'];
?>
<tr bricks="<?=implode(' ',$bricks)?>">
	<td class="NotImportant"><?=( $row['call_type'] == 'ajax' ? 'AJAX' : 'HTML' );?></td>
	<td class="numeric">
		<?=( isset($row['bricks']) && is_array($row['bricks']) && count($row['bricks']) > 0 ? count($row['bricks']) : '&nbsp;' ); ?>
	</td>
	<td>
	<?
		if ( $row['name'] == '*' ){
			echo '<i>Глобальное подключение</i>';
		} elseif ( is_array( $row['detect'] ) && $row['detect'][0] == 'urlpos' && $row['detect'][1] == 1 && $row['detect'][2] == 'robots.txt' ){
			echo '<i>Служебный</i> robots.txt';
		} elseif ( is_array( $row['detect'] ) && $row['detect'][0] == 'urlpos' && $row['detect'][1] == 1 && $row['detect'][2] == 'sitemap.xml' ){
			echo '<i>Служебный</i> sitemap.xml';
		}else{
			echo $row['name'];
		}
	?>
	</td>
	<td>
		<?
		if ( !isset($row['detect']) ){
			echo '&nbsp;';
		} elseif ( is_array( $row['detect'] ) && $row['detect'][0] == 'urlpos' ){
			$implode = array(); $index = 1;
			if ( Configuration::I18N && Configuration::I18N_IN_URL ) $implode[] = '&lt;язык&gt;';
			if ( $row['call_type'] == 'ajax' ) $implode[] = 'ajax';
			for( $i=1; $i < count($row['detect']); $i+=2 ){
				if ( $row['detect'][$i] > $index ){
					if ( $row['detect'][$i] > $index +1 ) for ( $j= $index+1; $j < $row['detect'][$i]; $j++ ) $implode[] = '*';
					$index = $row['detect'][$i];
				}
				$implode[] = $row['detect'][$i+1];
			}
			echo '/'.implode('/',$implode);
		} elseif ( is_array( $row['detect'] ) && $row['detect'][0] == 'index'  ) {
			echo 'Главная страница'; 
		}else {
			echo JSON::encode( $row['detect'] );
		}
		?>
	</td>
	<td>
		<?
		if ( !isset($row['event_handler']) ){
			echo '&nbsp;';
		} elseif ( !is_array( $row['event_handler'] ) ){
			echo $row['event_handler'];
		} else {
			echo implode(', ', $row['event_handler']);
		}
		?>
	</td>
</tr>
<? endforeach; ?>
</tbody>
</table>
<br /><br />
<h1>Бриксы</h1>
<!-- Bricks -->
<table id="Bricks" border="0" cellspacing="0" cellpadding="0" width="100%" class="ManageORMList">
<thead>
<tr>
	<th>Тип</th>
	<th class="minimal">Флаги</th>
	<th>Название</th>
	<th>Класс</th>
	<th>Таблица</th>
	<th colspan="2">Кеш</th>
	<th>Фильтр</th>
	<th>Обработчики</th>
</tr>
</thead>
<tbody>
<? foreach ( $o->data->Bricks as $k=>$v ): ?>
	<tr brick="<?=$k?>">
		<td class="NotImportant" style="text-transform: uppercase;">
		<?
			if ( !is_array( $v ) ){
				echo '&nbsp;';
			} elseif ( isset($v['behaviour']) ){
				echo $v['behaviour'][0];
			} else echo 'data';
		?>
		</td>
		<td class="minimal">
	<? if ( is_array( $v ) ): ?>
		<?=bullet( !isset($v['use_edit']) || $v['use_edit'], 'star', 'Редактируемый'); ?>
		<?=bullet( isset($v['use_lazy']) && !$v['use_lazy'], 'plugin_error', 'Неленивая инициализация'); ?>
		<?=bullet( !isset($v['use_show']) || $v['use_show'], 'eye', 'Фильтрация по `show` = 1'); ?>
		<?=bullet( isset($v['use_order']) && $v['use_order'], 'arrow_up', 'Сортировка по полю `order`'); ?>
		<?=bullet( isset($v['not_empty']) && $v['not_empty'], 'exclamation', 'Обазательное наличие данных'); ?>
	<? endif; ?>
		&nbsp;		
		</td>
		<td><?=( is_array($v) && isset($v['name']) ? $v['name'] : $k );?></td>
		<td><?=( is_array($v) ? 'Manage_ORM_Brick' : '<b>'.get_class( $v ).'</b>' );?></td>
	<? if ( is_array( $v ) ): ?>
		<td><?=isset($v['table']) ? $v['table'] : '&nbsp;' ;?></td>
		<? if ( !isset($v['cache']) ): ?>
		<td colspan="2" style="text-align: center; color: red;">&mdash;</td>
		<? else: ?>
		<td class="minimal"><?=( strpos( $v['cache'][0], '$') === FALSE ? $v['cache'][0] : preg_replace('/(\$[a-z0-9]*)/i', '<span style="color: blue">\1</span>', $v['cache'][0]) )?></td>
		<td class="minimal numeric"><?=$v['cache'][1]?>с.</td>
		<? endif; ?>
		<td>
		<? if ( !isset($v['filter']) || !count( $v['filter'] ) ): ?>
			&nbsp;
		<? else: 
			$implode = array();
			foreach ( $v['filter'] as $row ){
				if ( strpos( $row[1] , '$') !== FALSE ) $row[1] = '<span style="color: blue">'.$row[1].'</span>';
				if ( !isset($row[2]) ) $row[2] = '=';
				$implode[] = "`{$row[0]}` {$row[2]} '$row[1]'";
			}
			echo implode(', ', $implode);
		endif; ?>
		</td>
		<td>
			<?
			if ( !isset($v['event_handler']) ){
				echo '&nbsp;';
			} elseif ( !is_array( $v['event_handler'] ) ){
				echo $v['event_handler'];
			} else {
				echo implode(', ', $v['event_handler']);
			}
			?>
		</td>		
	<? endif; ?>
	</tr>
<? endforeach; ?>
</tbody>
</table>

<script>

var lock = false;

$('#Pages tr').bind( 'mouseenter', showBricksForMe ).bind( 'mouseleave', showAll ).css('cursor','pointer').click( lockUnlockBricks );
$('#Bricks tr').bind( 'mouseenter', showPagesForMe ).bind( 'mouseleave', showAll ).css('cursor','pointer').click( lockUnlockPages );

function showBricksForMe(){
	if ( lock !== false ) return;
	showBricksForObj( $(this));
}

function showBricksForObj( $this ){
	$('#Bricks tbody tr').removeClass('Selected');
	var b = $this.attr('bricks');
	if ( b == '' ) return;
	b = b.split(' ');
	for ( var i=0; i < b.length; i++)
		$('#Bricks tbody tr[brick="'+b[i]+'"]').addClass('Selected');
}

function showAll(){
	if ( lock !== false ) return;
	$('#Bricks tbody tr').removeClass('Selected');
	$('#Pages tbody tr').removeClass('Selected');
}

function lockUnlockBricks() {
	var $this = $(this);
	// Освобождаем стили
	$('#Bricks tbody tr').removeClass('Selected');
	$('#Pages tbody tr').removeClass('Selected');
	// Ставим стили
	showBricksForObj( $this ); 
	$this.addClass('Selected');
	// Перемещаем блокировку
	if ( lock !== false && typeof lock.attr('bricks') != 'undefined' && lock.attr('bricks') == $this.attr('bricks'))
		lock = false;
	else 
		lock = $this;
}

function showPagesForMe(){
	if ( lock !== false ) return;
	showPagesForObj( $(this));
}

function showPagesForObj( $this ){
	$('#Pages tbody tr').removeClass('Selected');
	var b = $this.attr('brick');
	if ( b == '' ) return;
	$('#Pages tbody tr').each( function(i,o){
		o = $(o);
		var x = o.attr('bricks');
		if ( x == '' ) return;
		x = x.split(' ');
		for ( var i=0; i < x.length; i++)
			if ( x[i] == b ) o.addClass('Selected');
	} );
}

function lockUnlockPages() {
	var $this = $(this);
	// Освобождаем стили
	$('#Pages tbody tr').removeClass('Selected');
	$('#Bricks tbody tr').removeClass('Selected');
	// Ставим стили
	showPagesForObj( $this );
	$this.addClass('Selected');
	// Перемещаем блокировку
	if ( lock !== false && typeof lock.attr('brick') != 'undefined' && lock.attr('brick') == $this.attr('brick'))
		lock = false;
	else 
		lock = $this;
}

</script>

<? $o->append('_footer'); ?>