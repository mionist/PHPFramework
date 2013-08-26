<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">
<head>
	<meta http-equiv=Content-Type content="text/html; charset=utf-8" />
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW" />
	<title><?=$o->meta->title->reverse()->implode(' - ');?></title>
	<link rel="stylesheet" media="all" type="text/css" href="<?=new Renderable_Manage_Proxy('css/main.css');?>" />
	<link rel="stylesheet" media="all" type="text/css" href="<?=new Renderable_Manage_Proxy('jquery.ui/jquery-ui.css');?>" />
	<link rel="stylesheet" media="all" type="text/css" href="<?=new Renderable_Manage_Proxy('elrte/css/elrte.css');?>" />

	<script>
                var ManageJS = {};
		var ManageStaticPath = "<?=new Renderable_Manage_Proxy('');?>";
		<? if ( isset($o->data->url_offset) ): ?>
		var ManageAJAXDataPath = "<?= new Renderable_URL( array_merge(array('a'),$o->data->url_offset));?>";
		<? endif; ?>
	</script>
	<script src="<?=new Renderable_Manage_Proxy('js/jquery.js');?>"></script>
	<script src="<?=new Renderable_Manage_Proxy('js/jquery-ui.js');?>"></script>
	<script src="<?=new Renderable_Manage_Proxy('elrte/js/elrte.js');?>"></script>
	<script src="<?=new Renderable_Manage_Proxy('elrte/js/i18n/elrte.ru.js');?>"></script>
	<script src="<?=new Renderable_Manage_Proxy('js/manage.js');?>"></script>
</head>
<body>

<img class="MainPanelVisibilityToggler" src="<?=new Renderable_Manage_Proxy('img/icons/fam/star.png')?>" />
<div class="MainPanelToolbar"><? $o->append('_toolbar'); ?></div>
<div class="MainPanelNavigationDiv"> <? $o->append('_navigation'); ?></div>

<!--
Rights for this page:
<? foreach ( Core::$auth->getRightsLog() as $k=>$v ) echo $k.': '.( $v ? 'true' : 'false' )."\n"; ?>
-->

<div class="MainPanelContentsDiv">