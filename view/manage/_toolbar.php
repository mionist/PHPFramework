<? foreach ( $o->data->buttons as $bundle ): ?>
<div class="MenuBundle">
<div class="MenuContents">
	<? if ( isset($bundle['__']) ): ?>
		<? foreach( $bundle['__'] as $row ): ?>
			<? if ( $row['type'] == 'relative_url' ): ?>
			<a class="BigButton FancyHover<?=( isset($row['disabled']) && $row['disabled'] ? ' Disabled' : '' )?>" href="<?=new Renderable_URL($row['url'], isset( $row['params']) ? $row['params'] : NULL)?>">
				<img src="<?=new Renderable_Manage_Proxy('img/icons/'.$row['icon']);?>" />
				<span><?=$row['name']?></span>
			</a>
			<? endif; ?>
			<? if ( $row['type'] == 'onclick' ): ?>
			<a class="BigButton FancyHover<?=( isset($row['disabled']) && $row['disabled'] ? ' Disabled' : '' )?>" href="javascript: void(0);" onclick="<?=$row['onclick']?>">
				<img src="<?=new Renderable_Manage_Proxy('img/icons/'.$row['icon']);?>" />
				<span><?=$row['name']?></span>
			</a>
			<? endif; ?>
			<? if ( $row['type'] == 'javascript' ): ?>
			<a class="BigButton FancyHover<?=( isset($row['disabled']) && $row['disabled'] ? ' Disabled' : '' )?>" href="javascript: void(0);" id="<?=$row['id'];?>">
				<img src="<?=new Renderable_Manage_Proxy('img/icons/'.$row['icon']);?>" />
				<span><?=$row['name']?></span>
			</a>
			<? endif; ?>
		<? endforeach; ?>
	<? endif; ?>
	<? if ( isset($bundle['_'])  ): ?>
	<table border="0" cellpadding="1" cellspacing="0" style="float: left;">
                <?
                $tableChunk = 3;
                $tableSplit = array_chunk( $bundle['_'], $tableChunk);
                for ( $i=0; $i < $tableChunk; $i ++ ):?>
                <tr>
                <? for ( $j=0; $j < count($tableSplit); $j++ ):
                $row = ( isset( $tableSplit[$j][$i] ) ? $tableSplit[$j][$i] : NULL );
		?>
                        <? if( !isset($row) ):?>
                        <th colspan="2">&nbsp;</th>
			<? elseif ( $row['type'] == 'text' ): ?>
			<th><?=$row['name']?>:</th>
			<td><?=$row['value']?></td>
			<? elseif ( $row['type'] == 'relative_url' ): ?>
                            <td colspan="2">
                            <? if ( isset($row['disabled']) && $row['disabled'] === true ): ?>
                            <span class="Disabled"><img src='<?=new Renderable_Manage_Proxy('img/icons/'.$row['icon']);?>' /> <?=$row['name']?></span>
                            <? else: ?>
                            <a class="FancyHover" href="<?=new Renderable_URL($row['url'], isset( $row['params']) ? $row['params'] : NULL)?>"><img src='<?=new Renderable_Manage_Proxy('img/icons/'.$row['icon']);?>' /> <?=$row['name']?></a>
                            <? endif; ?>
                            </td>
                        <? elseif ( $row['type'] == 'onclick' ): ?>    
                            <td colspan="2">
                            <? if ( isset($row['disabled']) && $row['disabled'] === true ): ?>
                            <span class="Disabled"><img src='<?=new Renderable_Manage_Proxy('img/icons/'.$row['icon']);?>' /> <?=$row['name']?></span>
                            <? else: ?>
                            <a class="FancyHover" href="javascript: void(0);" onclick="<?=$row['onclick']?>"><img src='<?=new Renderable_Manage_Proxy('img/icons/'.$row['icon']);?>' /> <?=$row['name']?></a>
                            <? endif; ?>
                            </td>
			<? elseif ( $row['type'] == 'caption' ): ?>
			<th colspan="2"><?=$row['value']?></th>
			<? elseif ( $row['type'] == 'jsid'): ?>
			<? if ( isset($row['name']) ):?>
			<th><?=$row['name']?>:</th>
			<td><div id="<?=$row['value']?>"><i>инициализация</i></div></td>
			<? else: ?>
			<th colspan="2"><div id="<?=$row['value']?>"><i>инициализация</i></div></th>
			<? endif; ?>
			<? endif; ?>
		
                <? endfor; ?>
                </tr>
		<? endfor; ?>
	</table>
	<? endif; ?>
</div>
<div class="MenuCaption"><?=$bundle['name']?></div>
</div>
<div class="MenuSeparator"></div>
<? endforeach; ?>