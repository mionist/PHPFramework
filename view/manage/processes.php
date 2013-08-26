<? $o->append('_header'); 

$data = $o->data->list;
uasort( $data , function( $a, $b ){ 
     if ( $a['Time'] > $b['Time'] ) return -1;
     if ( $a['Time'] < $b['Time'] ) return 1;
     return 0;
});

$active = $waiting = $inactive = array();
foreach ( $data as $row ){
    if ( $row['State'] == 'Locked' ) $waiting[] = $row;
    elseif ( $row['State'] == '' || $row['State'] == 'NULL' || $row['Command'] == 'Sleep' ) $inactive[] = $row;
    else $active[] = $row;
}

function buildArray( $a ){
    echo '<table border="0" cellspacing="0" cellpadding="1">';
    foreach ( $a as $row ){
	echo '<tr>';
	echo '<td width="100px">'.$row['User'].'@'.$row['db'].'</td>';
	echo '<td width="120px" style="color: gray;">'.$row['State'].'</td>';
	echo '<td width="50px" align="right" style="color: gray;">'.$row['Time'].' сек.</td>';
	echo '<td full="'.  htmlspecialchars($row['Info']).'" style="font-size: 14px;">';
	if ( mb_strlen( $row['Info'] ) > 99 )
	    echo '[<span class="trigger"">full</span>]&nbsp;'.htmlspecialchars(mb_substr (  $row['Info'] , 0, 100)).'&nbsp;...';
	else
	    echo htmlspecialchars($row['Info']);
	echo '</td>';
	echo '</tr>';
    }
    echo '</table>';
}

?>

<style>
    td .trigger { cursor: pointer; color: #039; border-bottom: 1px dotted #039; }
</style>

<h1>Запросы к MySQL ( FULL PROCESSLIST )</h1>

<? if ( count( $active ) > 0 ): ?>
<table border="0" cellspacing="0" cellpadding="0" class="TableDiagnostic" width="80%">
    <tr><th class="Caption">Текущие</th></tr>
</table>
<? buildArray( $active ); ?>
<? endif; ?>

<? if ( count( $waiting ) > 0 ): ?>
<table border="0" cellspacing="0" cellpadding="0" class="TableDiagnostic" width="80%">
    <tr><th class="Caption">Ожидающие</th></tr>
</table>
<? buildArray( $waiting ); ?>
<? endif; ?>

<? if ( count( $inactive ) > 0 ): ?>
<table border="0" cellspacing="0" cellpadding="0" class="TableDiagnostic" width="80%">
    <tr><th class="Caption">Неактивные</th></tr>
</table>
<? buildArray( $inactive ); ?>
<? endif; ?>


<script>
function toggleFull(){
    var $this = $(this);
    $this.parent().html( $this.parent().attr('full') );
}

$( function(){ 
    $('td .trigger').click( toggleFull );
} );
</script>

<? $o->append('_footer'); ?>