<? $o->append('_header'); ?>
<?

// Подготовительная работа
$columns = array();
foreach ( $o->data->ORMStructure as $row ){
	if ( $row->isHiddenInList() ) continue;
	$columns[] = $row;
}

$url_list = array_merge( $o->data->url_offset, array('list') );

?>


<table border="0" cellspacing="0" cellpadding="0" width="100%" class="ManageORMList">
<thead>
<tr>
<th class="minimal">&nbsp;</th>
<? foreach ( $columns as $row ):

// Направление сортировки
$current_order = 'desc';
if ( Core::$in->_get['of'] == $row->name ) $current_order = Core::$in->_get['oo'];
if ( $current_order == 'desc' ) $order = 'asc';
else $order = 'desc';


$class = array();
if ( $row->name == 'id' || $row instanceof Manage_ORM_TogglerField ) $class[] = 'minimal';

$caption = str_replace( '_', ' ', $row->getName());
if ( $row->name == 'id' ) $caption = '<span style="font-size: smaller;">ID</span>';
else if ( $row->name == 'show' && $row instanceof Manage_ORM_TogglerField ) $caption = '<img border="0" src="'.(new Renderable_Manage_Proxy('img/icons/fam/eye.png')).'" title="Отображать" />';

?>
<th <?=( count($class) ? 'class="'.implode(' ', $class).'"' : '' );?>>
	<? if ( $row->isOrderAble() ): ?>
	<?=( (Core::$in->_get['of'] == $row->name && $current_order == 'asc') ? '<span class="UpArrow">▲</span>' : '' )?>
	<a href="<?=new Renderable_URL($url_list,array('of'=>$row->name,'oo'=>$order,'f'=>Core::$in->_get['f']));?>"><?=$caption;?></a>
	<?=( (Core::$in->_get['of'] == $row->name && $current_order == 'desc') ? '<span class="DownArrow">▼</span>' : '' )?>
	<? else: ?>
	<?=$caption;?>
	<? endif; ?>
</th>
<? endforeach; ?>
</tr>
</thead>
<tbody>
<? $row_number = $o->data->ORMData->getLimit()*($o->data->ORMData->page-1)+1; foreach ( $o->data->ORMData as $row ): ?>
<tr ormId="<?=( isset($row['id']) ? $row['id'] : 0 );?>">
<td valign="top" class="Numeration minimal"><?=$row_number++?> <input type="checkbox" name="multicheck" class="multicheck" value="1" /></td>
<? foreach ( $columns as $col ):

$class = array();
if ( !$col->isText() && $col->name != 'id' ) $class[] = 'NotImportant';
if ( $col->isNumeric() ) $class[] = 'numeric';
if ( $col->name == 'id' || $col instanceof Manage_ORM_TogglerField || $col instanceof Manage_ORM_ExternalField ) $class[] = 'minimal';

?>



<td valign="top" <?=( count($class) ? 'class="'.implode(' ', $class).'"' : '' );?>>
<? if ( $col->isEmpty($row[$col->name]) ): ?>
	&nbsp;
<? elseif ( $col instanceof Manage_ORM_TogglerField ): ?>
	<img src="<?=new Renderable_Manage_Proxy('img/icons/fam/bullet_'.( ( $row[$col->name] == 1 || $row[$col->name] == 'y' ) ? 'green' : 'black' ).'.png');?>" class="JSHelperLink" name="<?=$col->name.$row['id'];?>" for="<?=$col->name.$row['id'];?>" js_helper="toggle" field="<?=$col->name?>" eid="<?=$row['id']?>" />
<? elseif ( $col instanceof Manage_ORM_TimestampField ): ?>
	<small><?=str_replace(' ', '&nbsp;', $col->decodeInRowValueForList( $row ));?></small>
<? elseif ( $col instanceof Manage_ORM_ExternalField ): ?>
	<abbr title="Ссылка на таблицу `<?=$col->getTable();?>` по id=<?=$row[$col->name];?>"><?=str_replace(' ', '&nbsp;', $col->decodeInRowValue($row));?></abbr>
<? else: ?>
	<?=( $col->isLinkInList() ? '<a href="'.( new Renderable_URL( array_merge( $o->data->url_offset, array('edit',$row['id']) ))).'">' : '' )?>
	<?=$col->decodeInRowValueForList( $row );?>
	<?=( $col->isLinkInList() ? '</a>' : '' )?>
<? endif; ?>
</td>
<? endforeach; ?>
</tr>
<? endforeach; ?>
</tbody>
</table>


<script>

var ORMListEditPath = '<?=new Renderable_URL( array_merge( $o->data->url_offset, array('edit') ))?>';

$( function(){
	var o = $('#FilterInputbox');
	if ( o.size() === 1 ){
		o.empty();
		var inp = $('<input type="text">');
		<? if ( Core::$in->_get['f'] != '' ): ?>
		inp.attr('value',"<?=htmlspecialchars( Core::$in->_get['f'] );?>");
		<? endif; ?>
		inp.css('width','100px').keyup(tryChangeFilter).appendTo(o);
	}
        
        $('.multicheck').bind('click change', tryMultiselect);
        $('#DeleteButton').data('initialText',$('#DeleteButton span').text());
        $('#DeleteButton').click( processMassdelete );
} );

function tryChangeFilter( e ){
	var code = e.which; // recommended to use e.which, it's normalized across browsers
    if(code==13)e.preventDefault();
    if(code!=32 && code!=13 && code!=188 && code!=186) return ;
    document.location = '?f=' + $(this).val();
}

function tryMultiselect(){
    var o = $(this);
    if ( $('.multicheck:checked').size() > 0 ){
        $('#DeleteButton').removeClass('Disabled');
        $('#DeleteButton span').text( $('#DeleteButton').data('initialText') + ' ('+$('.multicheck:checked').size()+')' );
    } else {
        $('#DeleteButton').addClass('Disabled');
        $('#DeleteButton span').text( $('#DeleteButton').data('initialText') );
    }
    
    if ( o.is(':checked') ){
        o.parent().parent().addClass('HighlightRow');
    } else {
        o.parent().parent().removeClass('HighlightRow');
    }
}

function processMassdelete(){
    var objs = $('.multicheck:checked');
    if ( objs.size() === 0 ) return;
    var idPool = [];
    objs.each( function(i,o){
        o = $(o);
        idPool.push( o.parent().parent().attr('ormId') );
    } );
    var url = ORMListEditPath +'/'+ idPool.join(',') + '/delete';
    if ( confirm("Вы действительно желаете удалить "+objs.size()+" записей?")) 
        document.location = url;
}

</script>

<? $o->append('_footer'); ?>