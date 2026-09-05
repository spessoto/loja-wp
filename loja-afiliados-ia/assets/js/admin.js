( function ( $ ) {
	'use strict';

	var frame;

	function getInput( $manager ) {
		return $manager.find( '#lai-galeria-ids' );
	}

	function getIds( $manager ) {
		var value = getInput( $manager ).val();
		return value ? value.split( ',' ).filter( Boolean ) : [];
	}

	function setIds( $manager, ids ) {
		getInput( $manager ).val( ids.join( ',' ) );
	}

	function renderItem( attachment ) {
		var isVideo = attachment.type === 'video';
		var thumbUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.icon;
		var thumbHtml = isVideo
			? '<span class="lai-media-item__video-icone dashicons dashicons-video-alt3"></span>'
			: '<img src="' + thumbUrl + '" width="60" height="60" alt="">';

		return (
			'<li class="lai-media-item" data-id="' + attachment.id + '">' +
			'<div class="lai-media-item__thumb">' + thumbHtml + '</div>' +
			'<button type="button" class="lai-media-item__remover" aria-label="Remover">&times;</button>' +
			'</li>'
		);
	}

	$( document ).on( 'click', '.lai-media-adicionar', function ( event ) {
		event.preventDefault();
		var $manager = $( this ).closest( '.lai-media-manager' );
		var $lista = $manager.find( '#lai-galeria-lista' );

		frame = wp.media( {
			title: ( window.LAI_ADMIN && LAI_ADMIN.tituloSeletor ) || 'Selecionar imagens ou vídeos',
			button: { text: ( window.LAI_ADMIN && LAI_ADMIN.botaoSeletor ) || 'Usar selecionado(s)' },
			multiple: true,
			library: { type: [ 'image', 'video' ] },
		} );

		frame.on( 'select', function () {
			var selecionados = frame.state().get( 'selection' ).toJSON();
			var ids = getIds( $manager );

			selecionados.forEach( function ( attachment ) {
				var idTexto = String( attachment.id );
				if ( ids.indexOf( idTexto ) === -1 ) {
					ids.push( idTexto );
					$lista.append( renderItem( attachment ) );
				}
			} );

			setIds( $manager, ids );
		} );

		frame.open();
	} );

	$( document ).on( 'click', '.lai-media-item__remover', function ( event ) {
		event.preventDefault();
		var $item = $( this ).closest( '.lai-media-item' );
		var $manager = $item.closest( '.lai-media-manager' );
		var id = String( $item.data( 'id' ) );

		setIds( $manager, getIds( $manager ).filter( function ( existingId ) {
			return existingId !== id;
		} ) );
		$item.remove();
	} );
} )( jQuery );
