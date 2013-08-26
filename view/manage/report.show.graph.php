<?
$headers = $o->data->report->getHeaders();
$data = $o->data->report->getRowset();
$graphs = $o->data->report->getGraphs();
?>

<script src="<?=new Renderable_Manage_Proxy('js/flot/jquery.flot.min.js');?>"></script>
<script src="<?=new Renderable_Manage_Proxy('js/flot/jquery.flot.stack.min.js');?>"></script>
<script src="<?=new Renderable_Manage_Proxy('js/flot/jquery.flot.threshold.min.js');?>"></script>
<script>
    var GData = {};
    GData.headers = [];
    <? foreach ( $headers as $row ): if ( is_array($row) ) $row = $row[0];?>
        GData.headers.push("<?=str_replace('"','',$row);?>");
    <? endforeach;?>
        
    function mapToArray( m ){
        var a=[];
        for ( var k in m ) a.push(m[k]);
        return a;
    }
    
    function toogleGraphLineNormal(){
        var set = GData[$(this).parent().attr('for')].set;
        var a = [];
        for ( var i in set ){
            if ( $(this).parent().children('input[name="'+i+'"]').is(':checked') ) a.push( set[i] );
        }
        $.plot($("#GraphFor"+$(this).parent().attr('for')), mapToArray(a), GData[$(this).parent().attr('for')].options );
    }
    
    function buildFilters( x ){
        if ( Object.keys(GData[x].set).length > 1 ){
            $('#OptionsFor'+x).empty();
            for ( var k in GData[x].set ){
                $('<input type="checkbox" name="'+k+'" id="U_'+k+'" checked="checked" value="1" /><label for="U_'+k+'">'+k+'</label>').appendTo('#OptionsFor'+x);
            }
        }
    }

$('.ReportGraphOptions input').live('change',toogleGraphLineNormal);
</script>


<br /><br />
<? foreach ( $graphs as $id=>$row ):?>


<? if ( $row['type'] == 'normal' ):?>
<h1><?=$row['name'];?></h1>
<div id="GraphFor<?=$id?>" for="<?=$id?>" class="ReportGraph"></div>
<div id="OptionsFor<?=$id?>" for="<?=$id?>" class="ReportGraphOptions"></div>
<script>
// Datagrid for <?=$row['name'];?>   
var x = <?=$id?>;
GData[x] = {set:{},options:{}};
<? if ( isset( $row['options'] ) ) echo 'GData[x].options'.JSON::encode($row['options']).";\n"; ?>

// Auto check for datetime
<? if ( is_object($data[0][$row['x']]) && $data[0][$row['x']] instanceof Renderable_DateTime ): ?>
GData[x].options.xaxis = { 'mode':'time', 'timeformat': "%0d.%0m.%y" };
<? endif; ?>
<? foreach ( $row['y'] as $y ): ?>
var j = GData.headers[<?=$y?>];
GData[x].set[j] = {'label':j,data:[]};  
<? foreach ( $data as $i ):
    $plot_x = $i[$row['x']];
    if ( is_object( $plot_x ) && $plot_x instanceof Renderable_Item ) $plot_x->setContext(Renderable_Item::CONTEXT_PLAINTEXT );
    if ( is_object( $plot_x ) && $plot_x instanceof Renderable_DateTime ) {
        $plot_x->setContext(Renderable_Item::CONTEXT_PLAINTEXT );
        $plot_x->setBehavior(Renderable_DateTime::TIMESTAMP_JAVASCRIPT);
    }
    $plot_x = ''.$plot_x.'';
    
    $plot_y = $i[$y];
    if ( is_object( $plot_y ) && $plot_y instanceof Renderable_Item ) $plot_y->setContext(Renderable_Item::CONTEXT_PLAINTEXT );
    $plot_y = ''.$plot_y;
?>
GData[x].set[j].data.push([<?=$plot_x.','.$plot_y?>]);
<? endforeach;?>
buildFilters( x );
<? endforeach;?>
$( function(){ $.plot($("#GraphFor<?=$id?>"), mapToArray(GData[<?=$id?>].set), GData[<?=$id?>].options); } );
</script>


<? elseif ( $row['type'] == 'intersect' ):?>
<h1><?=$row['name'];?></h1>
<div id="GraphFor<?=$id?>" class="ReportGraph"></div>
<div id="OptionsFor<?=$id?>" for="<?=$id?>" class="ReportGraphOptions"></div>
<script>
// Datagrid for <?=$row['name'];?>   
var x = <?=$id?>;
GData[x] = {set:{},options:{}};
<? if ( isset( $row['options'] ) ) echo 'GData[x].options = '.JSON::encode($row['options']).";\n"; ?>

// Ticks
GData[x].options.xaxis = { ticks: [] };
<? $i=0; foreach ( $row['data'] as $j ): ?>
GData[x].options.xaxis.ticks.push([<?=$i++;?>,GData.headers[<?=$j;?>]]);
<? endforeach; ?>

// Data
<? foreach ( $data as $datarow ):?>
var j = "<?=str_replace('"','',$datarow[ $row['legend'] ]);?>";    
GData[x].set[j] = {'label':j,data:[]};    
<? $i=0; foreach ( $row['data'] as $j ):
    $value = $datarow[$j];
    if ( is_object( $value ) && $value instanceof Renderable_Item ) $value->setContext(Renderable_Item::CONTEXT_PLAINTEXT );
?>    
GData[x].set[j].data.push([<?=$i++?>,<?=$value;?>]);
<? endforeach; ?>    
buildFilters( x );
<? endforeach; ?>
$( function(){ $.plot($("#GraphFor<?=$id?>"), mapToArray(GData[<?=$id?>].set), GData[<?=$id?>].options); } );
</script>
<? endif; ?>
<? endforeach;?>

<br /><br />