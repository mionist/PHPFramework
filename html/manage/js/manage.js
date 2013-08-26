ManageJS.insertIllustration = function( $this ){
        var o;
        o = $this.parent().find('.enableWYSIWYG');
        if ( o.size() == 0 ) o = $this.parent().parent().find('.enableWYSIWYG');
	o.elrte()[0].elrte.selection.deleteContents().insertHtml('<img border="0" src="'+$this.attr('src')+'" style="border:0;" />');
}

ManageJS.removeIllustration = function ( $this ){
    if ( !confirm('Удалить иллюстрацию?') ) return;
    $.post( ManageAJAXDataPath + "/edit/" + $this.prev().attr('forentry') + '/', { 'DeleteIllustration' : 'yes' ,'illustration' : $this.prev().attr('forillustration') } );
    $this.prev().fadeOut();
    $this.fadeOut();
}

$( function(){
    $('.MainPanelNavigationDiv .Name').click( ManageJS.toggleLeftSubmenu );
    var ManageJSHelperOID = 1;
    $('.JSHelperLink').live('click',function(){
            $('<img src="'+ManageStaticPath+'img/ajax16.gif" border="0" />').insertBefore( $(this) );
            var a = {};
            var attrs = $(this).get(0).attributes;
            for ( var key in attrs ){
                    if ( attrs[key].name == 'class' || typeof attrs[key].value != 'string' ) continue;
                    a[attrs[key].name] = attrs[key].value;
            }
            a['oid'] = ManageJSHelperOID;
            $(this).attr('oid', ManageJSHelperOID);
            ManageJSHelperOID++;
            $.post( ManageAJAXDataPath+'/list', a, ManageProcessJSHelperAnswer, 'json' );
            $(this).hide();
    });

    // Enabling WYSIWYG
    $('.enableWYSIWYG').elrte({
            lang: 'ru',
            styleWithCSS: false,
            height: 200,
            toolbar: 'maxi'
    });

    // Enabling illustrations
    $('.IllustrationImage').live( 'click', function(){ ManageJS.insertIllustration( $(this) ); } );
    $("input[name=MediantIllustration]").change(function() {
        $(this).css('display','none');
        $(this).closest("form").submit();
    });
    $('#IllustrationsBlock span').live( 'click', function(){ ManageJS.removeIllustration( $(this) ); } );

    // Folding
    $('.MainPanelNavigationDiv .Menu').each( function(i,o){
        o = $(o);
        var childs = o.find('.Subelements .Menu');
        if ( childs.size() > 0 ){
            o.children('.Name').html( o.children('.Name').html() +' <small style="display: none;">('+childs.size()+')</small>' );
        }

        if ( childs.size() > 10 ) o.children('.Name').trigger('click');
    } );

    // Resize
    $(window).resize( ManageJS.resizeEvent );
    // UI Toggler
    $('.MainPanelVisibilityToggler').click( ManageJS.toggleUI );
});

ManageJS.isUIHidden = false;
ManageJS.resizeEvent = function (){
    var w = $(window);
    $('.MainPanelToolbar').css({
        'top': 0,
        'left': 0,
        'width':w.width()
    });
    $('.MainPanelNavigationDiv').css({
        'top': $('.MainPanelToolbar').outerHeight()+'px',
        'left': 0,
        'height': (w.height() - $('.MainPanelToolbar').outerHeight())+'px'
    });

    if ( !ManageJS.isUIHidden )
    $('.MainPanelContentsDiv').css({
        'top': $('.MainPanelToolbar').outerHeight()+'px',
        'left': $('.MainPanelNavigationDiv').outerWidth()+'px',
        'height': ( w.height() - $('.MainPanelToolbar').outerHeight() -  parseInt($('.MainPanelContentsDiv').css('padding-top')) - parseInt($('.MainPanelContentsDiv').css('padding-bottom')) )+'px',
        'width': ( w.width() - $('.MainPanelNavigationDiv').outerWidth() -  parseInt($('.MainPanelContentsDiv').css('padding-left')) - parseInt($('.MainPanelContentsDiv').css('padding-right')) )+'px'
    });
    else
    $('.MainPanelContentsDiv').css({
        'top': 0,
        'left': 0,
        'height': ( w.height() -  parseInt($('.MainPanelContentsDiv').css('padding-top')) - parseInt($('.MainPanelContentsDiv').css('padding-bottom')) )+'px',
        'width': ( w.width() -  parseInt($('.MainPanelContentsDiv').css('padding-left')) - parseInt($('.MainPanelContentsDiv').css('padding-right')) )+'px'
    });


    $('.MainPanelContentsDiv').css('display','block');
    $('.MainPanelToolbar, .MainPanelNavigationDiv').css('display',( ManageJS.isUIHidden ? 'none' : 'block' ));
    $('.MainPanelVisibilityToggler').css('display',( !ManageJS.isUIHidden ? 'none' : 'block' ));
}
ManageJS.toggleUI = function(){
    ManageJS.isUIHidden = ! ManageJS.isUIHidden;
    ManageJS.resizeEvent();
}

ManageJS.processJSHelperAnswer = function( data ){
	// В первую очередь нужно вернуть обратно хелпер и скрыть лоадер
	if ( data.status != 'ok' ) return;
	var jshelper = $('*[oid='+data.oid+']');
	jshelper.show().prev().remove();

	// Если можем, делаем сразу
	if ( typeof data.attr != 'undefined' ){
		jshelper.parent().find('*[name="'+data['for']+'"], *[id="'+data['for']+'"]').attr( data.attr, data.value );
	}
}

ManageJS.toggleLeftSubmenu = function(){
	var $this = $(this);
	if ( $this.children('a').size() > 0 ) return;

	if ( $this.parent().children('.Subelements').css('display') == 'block' ){
		$this.children('small').css('display','inline');
		$this.parent().children('.Subelements').slideUp('fast');
	}else{
		$this.children('small').css('display','none');
		$this.parent().children('.Subelements').slideDown('fast');
	}
}

ManageJS.showUploadedIllustration = function( address ){
    var o = $('#IllustrationsBlock div');
    $('<img class="IllustrationImage">').attr('src',address).appendTo(o);
    o.parent().find('input[type=file]').css('display','block');
}





// Deprecated code
function ManageProcessJSHelperAnswer( data ){ return ManageJS.processJSHelperAnswer(data); }
function InsertIllustration(){ return ManageJS.insertIllustration(); }