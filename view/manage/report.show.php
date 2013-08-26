<? 

$print = (Core::$in->_navigation[3] == 'print');

if ( !$print ) $o->append('_header'); 

$headers = $o->data->report->getHeaders();
$headers_count = count( $headers );
$data = $o->data->report->getRowset();
$summary = $o->data->report->getSummary();
$graphs = $o->data->report->getGraphs();
$report_line = 0;
?>

<h1><?=$o->data->report->getTitle();?></h1>
<small class="ReportDescription"><?=$o->data->report_description;?></small>

<table border="0" cellspacing="0" cellpadding="0" width="100%" class="ManageORMList">
<thead>
<tr>
    <th>&nbsp;</th>
<? foreach ( $headers as $row ):?>
    <? if ( is_array($row) ):?>
    <th title="<?=$row[1];?>"><?=$row[0];?></th>
    <? else: ?>
    <th><?=$row;?></th>
    <? endif;?>
<? endforeach;?>    
</tr>
</thead>
<tbody>
<? foreach ( $data as $rowindex=>$row ):?>
    <tr row="<?=$rowindex?>">
	<td valign="top" class="Numeration minimal"><?=++$report_line;?></td>
	<? for ( $i=0; $i < $headers_count; $i++ ):
	if ( isset( $row[$i] ) && is_object( $row[$i] ) && $row[$i] instanceof Renderable_Item ) $row[$i]->setContext(Renderable_Item::CONTEXT_HTML );
	if ( !isset( $row[$i] ) ):?>
	<td>&nbsp;</td>
	<? else: ?>
        <td <?=(is_object($row[$i])&&$row[$i] instanceof Renderable_Digit?' style="text-align: right;" class="NumericValue" column="'.$i.'" value="'.$row[$i].'" clean="'.$row[$i]->getContext( Renderable_Item::CONTEXT_PLAINTEXT ).'"':'')?>><?=$row[$i];?></td>
	<? endif; ?>
	<? endfor; ?>
    </tr>    
<? endforeach;?>
<? foreach ( $summary as $rowindex=>$row ):?>
    <tr class="Summary" style="font-weight: bold;">
	<td valign="top" class="Numeration minimal">&nbsp;</td>
	<? for ( $i=0; $i < $headers_count; $i++ ):
	if ( isset( $row[$i] ) && is_object( $row[$i] ) && $row[$i] instanceof Renderable_Item ) $row[$i]->setContext(Renderable_Item::CONTEXT_HTML );
	if ( !isset( $row[$i] ) ):?>
	<td>&nbsp;</td>
	<? else: ?>
        <td <?=(is_object($row[$i])&&$row[$i] instanceof Renderable_Digit?' style="text-align: right;"':'')?>><?=$row[$i];?></td>
	<? endif; ?>
	<? endfor; ?>
    </tr>    
<? endforeach;?>
</tbody>
</table></tbody>

<? if ( count($graphs) > 0 && !$print ) $o->append('report.show.graph'); ?>

<? if ( !$print ): ?>
<script>

function showNumericRelariveValues(){
    var $this = $(this);
    var pure = parseFloat( $this.attr('clean') );
    if ( pure == 0 ) return;
        $('td[column="'+$this.attr('column')+'"]').each( function( i, o ){
            o = $(o);
            if ( o.parent().attr('row') == $this.attr('row') ) return;
            var v = parseFloat( o.attr('clean') );
            var delta = ffloat(v - pure, v);
            if ( v == 0 ) delta = 0;
            
            if ( v == pure )
                o.html( '<b>'+v+'</b>' );
            else if ( v==0 )
                o.html( '&nbsp;' );
            else
                o.html( (delta > 0 ? '<span style="color: blue">+'+delta+'&nbsp;(+'+Math.round( delta/pure * 100 )+'%)</span>' : '<span style="color:red">'+delta+'&nbsp;('+Math.round( delta/pure * 100 )+'%)</span>') );
            o.addClass('HighlightedNumeric');
        } );
}

function ffloat( num, pattern ){
    var sn = ""+pattern;
    if ( sn.indexOf('.') == -1 ) return num;
    var decimals = sn.length - sn.indexOf('.') - 1;
    var p = Math.pow(10,decimals);
    return Math.round( num * p ) / p;
}

function leaveNumericRelativeValues(){
    $('td.HighlightedNumeric').each( function( i,o ){
        o = $(o);
        o.removeClass('HighlightedNumeric');
        o.html( o.attr('value') );
    } ).css('cursor','pointer');
}

$( function(){
    $('#PH_rel_checker').html('<input type="checkbox" id="NumericDeltaToggler" /> <label for="NumericDeltaToggler">показывать</label>');
    $('#NumericDeltaToggler').change( function(){
	if ( $(this).is(':checked') ) $('.NumericValue').bind('mouseenter', showNumericRelariveValues).bind('mouseleave',leaveNumericRelativeValues);
	else $('.NumericValue').unbind('mouseenter mouseleave')
    } );
    $('#SavedReportUpdater').click( function(){
	if ( confirm('Этот отчёт уже сохранён. Обновление приведёт к полной перегенерации всех данных. Продолжить?') ) document.location='<?=new Renderable_URL(array( 'reports', Core::$in->_navigation[2] ,'refresh' ));?>';
    } );
} );

</script>
<? endif; ?>

<? if ( !$print ) $o->append('_footer'); ?>