<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">
<head> 
	<meta http-equiv=Content-Type content="text/html; charset=utf-8" /> 
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW" />
	<title>Вход в панель управления</title> 
	<link rel="stylesheet" media="all" type="text/css" href="<?=new Renderable_Manage_Proxy('css/main.css');?>" />
	<link rel="stylesheet" media="all" type="text/css" href="<?=new Renderable_Manage_Proxy('css/login.css');?>" /> 
	<script src="<?=new Renderable_Manage_Proxy('js/jquery.js');?>"></script>
</head>
<body>

<div class="ContainerLogin">
	<form method="post">
	<h3>Вход в панель управления</h3>
	<p>Для входа в панель управления требуется ввести свои логин и пароль.</p>
	<? if ( !is_null( $o->data->LoginPage['about'] ) ): ?>
	<p><?=$o->data->LoginPage['about'];?></p>
	<? endif; ?>
	<hr />
	<? if ( !is_null( $o->data->LoginPage['error'] ) ): ?>
	<div style="margin: -13px 0 15px 0;">
	<b>Ошибка:</b><br />
	<?=$o->data->LoginPage['error']?>
	</div>
	<? endif; ?>
	<? if ( $o->data->LoginPage['reset'] == 'yes' ): ?>
	<div class="table_wrapper">
	<table border="0" cellpadding="2" cellspacing="0" width="100%">
		<tr>
			<td>Логин&nbsp;&nbsp;&nbsp;</td>
			<td width="1%" nowrap="nowrap"><input type="text" name="login" id="login" class="ManageInput" autocomplete="off" /></td>
		</tr>
		<tr>
			<td>Старый пароль&nbsp;&nbsp;&nbsp;</td>
			<td width="1%" nowrap="nowrap"><input type="password" name="old_password" class="ManageInput" autocomplete="off" /></td>
		</tr>
		<tr>
			<td>Новый пароль&nbsp;&nbsp;&nbsp;</td>
			<td width="1%" nowrap="nowrap"><input type="password" name="new_password" class="ManageInput" autocomplete="off" /></td>
		</tr>
	</table>
	</div>
	<? else: ?>
	<div class="table_wrapper">
	<table border="0" cellpadding="2" cellspacing="0" width="100%">
		<tr>
			<td>Логин&nbsp;&nbsp;&nbsp;</td>
			<td width="1%" nowrap="nowrap"><input type="text" name="login" id="login" class="ManageInput" /></td>
		</tr>
		<tr>
			<td>Пароль&nbsp;&nbsp;&nbsp;</td>
			<td width="1%" nowrap="nowrap"><input type="password" name="password" class="ManageInput" /></td>
		</tr>
	</table>
	</div>
	<? endif; ?>
	<div class="footer">
		<input type="submit" value="Войти" style="float: right;" />
	</div>
	</form>
</div>
<script>
$( function(){
	$('#login').focus();
	$(window).trigger('resize');
});
$(window).resize( function(){
	$('.ContainerLogin').css( 'margin-top', ($(window).height() - $('.ContainerLogin').height() )/2 );
} );
</script>
</body> 
</html>