<? $o->append('_header'); ?>
<form method="post" id="MainEditForm">
<table cellspacing="0" cellpadding="0" class="UserTable">
<tbody>
	<tr>
		<th>
			Логин
			<small>используется для входа</small>
		</th>
		<td>
			<?=new Renderable_HTMLInput($o->data->Params->field_login, $o->data->Defaults[ $o->data->Params->field_login ], FALSE, NULL, 'CommonInput')?>
		</td>
	</tr>
	<? if ( isset( $o->data->Defaults['name'] ) ): ?>
	<tr>
		<th>
			Имя
		</th>
		<td>
			<?=new Renderable_HTMLInput('name', $o->data->Defaults[ 'name' ], FALSE, NULL, 'CommonInput')?>
		</td>
	</tr>
	<? endif; ?>
	<tr>
		<th>
			Доступ
			<small>заблокированный пользователь не имеет<br /> возможности зайти в админку вне <br />зависимости от его прав</small>
		</th>
		<td>
			<?=new Renderable_HTMLSingleSelect( $o->data->Params->field_banned, array(0=>'Предоставлять доступ',1=>'Заблокировать доступ'), $o->data->Defaults[$o->data->Params->field_banned] ) ?>
		</td>
	</tr>
	<tr>
		<th>Права</th>
		<td>
			<div class="Right">
				<input id="r1" type="radio" name="<?=$o->data->Params->field_admin_rights?>" value=""<?=($o->data->Defaults[$o->data->Params->field_admin_rights] == 'engineer' ? ' disabled="disabled"' : '' )?> <?=($o->data->Defaults[$o->data->Params->field_admin_rights] == '' ? 'checked' : '' )?> />
				<label for="r1">Права отсутствуют</label>
				<p>
				Пользователь с неназначенными правами не имеет возможности зайти в админпанель
				</p>
			</div>
			<div class="Right">
				<input id="r2" type="radio" name="<?=$o->data->Params->field_admin_rights?>" value="manager"<?=($o->data->Defaults[$o->data->Params->field_admin_rights] == 'engineer' ? ' disabled="disabled"' : '' )?> <?=($o->data->Defaults[$o->data->Params->field_admin_rights] == 'manager' ? 'checked' : '' )?> />
				<label for="r2">Модератор сайта</label>
				<p>
				Пользователь с этими правами имеет возможность добавлять, редактировать и удалять содержимое сайта. 
				При этом он не имеет возможности создавать или же редактировать пользователей и просматривать системные 
				данные сайта
				</p>
			</div>
			<div class="Right">
				<input id="r3" type="radio" name="<?=$o->data->Params->field_admin_rights?>" value="fullaccess"<?=($o->data->Defaults[$o->data->Params->field_admin_rights] == 'engineer' ? ' disabled="disabled"' : '' )?> <?=($o->data->Defaults[$o->data->Params->field_admin_rights] == 'fullaccess' ? 'checked' : '' )?> />
				<label for="r3">Полный доступ</label>
				<p>
				Пользователь с этими правами имеет возможность добавлять, редактировать и удалять содержимое сайта, 
				управлять пользователями и просматривать системные данные сайта. Эти права следует выдавать осторожно, 
				так как пользователь с этими правами имеет возможность снять права с остальных пользователей, закрыв для
				них админпанель.
				</p>
			</div>
			<div class="Right">
				<input id="r4" type="radio" name="<?=$o->data->Params->field_admin_rights?>" value="engineer" disabled="disabled" <?=($o->data->Defaults[$o->data->Params->field_admin_rights] == 'engineer' ? 'checked' : '' )?> />
				<label for="r4">Инженерный заход (техподдержка)</label>
				<p>
				Специальные системные права, позволяющие пользователю, их имеющему, заходить в админпанель и
				получать доступ к содержимому сайта в режиме "Только для чтения", то есть инженер не имеет 
				возможности редактировать данные сайта. В то же время, инженерный заход позволяет видеть системную 
				информацию, и редактировать таблицу пользователей.<br />
				Вы не можете устанавливать или снимать кому-либо инженерный заход. Если требуется запретить 
				инженеру вход на сайта, достаточно в разделе "Доступ" установить режим "заблокировать доступ"
				</p>
			</div>
		</td>
	</tr>
	<tr>
		<th>Пароль</th>
		<td>
			<div class="PasswordChange">
				<input id="p1" type="radio" name="password_request" value="" checked><label for="p1">не менять</label><br />
			</div>
			<div class="PasswordChange">
				<input id="p2" type="radio" name="password_request" value="reset"><label for="p2">запросить сброс пароля</label><br />
				<p>
					Если установить этот флаг, то при следующем заходе в админпанель пользователю будет предложено ввести новый 
					пароль. 
				</p>
			</div>
			<div class="PasswordChange">
				<input id="p3" type="radio" name="password_request" value="manual"><label for="p3">указать вручную</label><br />
				<p>
					<input type="password" name="password" class="CommonInput" />
				</p>
			</div>
			
		</td>
	</tr>
</tbody>
</table>
</form>

<script>

function doSaveForm(){
	$('#MainEditForm').submit();
}

function togglePasswordChangeElements(){
	$(this).parent().parent().find('p').hide();
	$(this).parent().find('p').slideDown('fast');
}

$( function(){ 
	$('.PasswordChange input[type="radio"]').click(togglePasswordChangeElements);
	$('#SaveButton').click( doSaveForm );
} )
</script>

<? $o->append('_footer'); ?>