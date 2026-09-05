( function () {
	'use strict';

	var WISHLIST_KEY = 'lai_wishlist';
	var COMPARE_KEY = 'lai_compare';
	var COMPARE_MAX = 4;

	function getIds( key ) {
		try {
			var raw = window.localStorage.getItem( key );
			var ids = raw ? JSON.parse( raw ) : [];
			return Array.isArray( ids ) ? ids.map( Number ) : [];
		} catch ( e ) {
			return [];
		}
	}

	function setIds( key, ids ) {
		try {
			window.localStorage.setItem( key, JSON.stringify( ids ) );
		} catch ( e ) {
			// Armazenamento indisponível (modo privado, etc.) — ignora silenciosamente.
		}
	}

	function toggleId( key, id ) {
		var ids = getIds( key );
		var index = ids.indexOf( id );
		if ( index > -1 ) {
			ids.splice( index, 1 );
		} else {
			ids.push( id );
		}
		setIds( key, ids );
		return ids;
	}

	function fetchProdutos( ids ) {
		if ( ! ids.length ) {
			return Promise.resolve( [] );
		}
		var url = LAI_DATA.restUrl + '/produtos?ids=' + ids.join( ',' );
		return fetch( url, { headers: { 'X-WP-Nonce': LAI_DATA.nonce } } ).then( function ( response ) {
			return response.ok ? response.json() : [];
		} );
	}

	function refreshToggleButtons() {
		var wishlistIds = getIds( WISHLIST_KEY );
		var compareIds = getIds( COMPARE_KEY );

		document.querySelectorAll( '.lai-wishlist-toggle' ).forEach( function ( btn ) {
			var id = Number( btn.getAttribute( 'data-product-id' ) );
			btn.classList.toggle( 'is-ativo', wishlistIds.indexOf( id ) > -1 );
		} );
		document.querySelectorAll( '.lai-compare-toggle, [data-lai-add-compare]' ).forEach( function ( btn ) {
			var id = Number( btn.getAttribute( 'data-product-id' ) || btn.getAttribute( 'data-lai-add-compare' ) );
			btn.classList.toggle( 'is-ativo', compareIds.indexOf( id ) > -1 );
		} );
	}

	function bindToggleButtons() {
		document.addEventListener( 'click', function ( event ) {
			var wishlistBtn = event.target.closest( '.lai-wishlist-toggle' );
			if ( wishlistBtn ) {
				toggleId( WISHLIST_KEY, Number( wishlistBtn.getAttribute( 'data-product-id' ) ) );
				refreshToggleButtons();
				renderWishlistPage();
				return;
			}

			var compareBtn = event.target.closest( '.lai-compare-toggle' );
			var addCompareLink = event.target.closest( '[data-lai-add-compare]' );

			if ( compareBtn || addCompareLink ) {
				var id = Number( ( compareBtn || addCompareLink ).getAttribute( 'data-product-id' ) || ( compareBtn || addCompareLink ).getAttribute( 'data-lai-add-compare' ) );
				var current = getIds( COMPARE_KEY );
				var isAdding = current.indexOf( id ) === -1;

				if ( isAdding && current.length >= COMPARE_MAX ) {
					if ( compareBtn ) {
						event.preventDefault();
					}
					window.alert( LAI_DATA.i18n.comparadorCheio );
					return;
				}

				toggleId( COMPARE_KEY, id );
				refreshToggleButtons();
				renderComparador();

				if ( compareBtn ) {
					event.preventDefault();
				}
				// addCompareLink segue navegando normalmente até a página do comparador.
			}
		} );
	}

	/** Galeria de miniaturas na página de produto. */
	function bindGaleria() {
		document.querySelectorAll( '.lai-galeria__thumb' ).forEach( function ( thumb ) {
			thumb.addEventListener( 'click', function () {
				var full = thumb.getAttribute( 'data-full' );
				var principal = document.getElementById( 'lai-imagem-principal' );
				if ( full && principal ) {
					principal.src = full;
				}
				document.querySelectorAll( '.lai-galeria__thumb' ).forEach( function ( t ) {
					t.classList.remove( 'is-ativa' );
				} );
				thumb.classList.add( 'is-ativa' );
			} );
		} );
	}

	function renderWishlistPage() {
		var grid = document.getElementById( 'lai-wishlist-grid' );
		if ( ! grid ) {
			return;
		}
		var vazio = document.getElementById( 'lai-wishlist-vazio' );
		var ids = getIds( WISHLIST_KEY );

		if ( ! ids.length ) {
			grid.innerHTML = '';
			if ( vazio ) {
				vazio.hidden = false;
			}
			return;
		}

		fetchProdutos( ids ).then( function ( produtos ) {
			if ( vazio ) {
				vazio.hidden = produtos.length > 0;
			}
			grid.innerHTML = produtos.map( renderCardHtml ).join( '' );
			refreshToggleButtons();
		} );
	}

	function renderCardHtml( produto ) {
		return (
			'<div class="lai-card" data-product-id="' + produto.id + '">' +
			'<a class="lai-card__media" href="' + produto.permalink + '"><img src="' + ( produto.imagem || '' ) + '" alt="" loading="lazy"></a>' +
			'<div class="lai-card__body">' +
			( produto.marca ? '<p class="lai-card__marca">' + escapeHtml( produto.marca.toUpperCase() ) + '</p>' : '' ) +
			'<h3 class="lai-card__titulo"><a href="' + produto.permalink + '">' + escapeHtml( produto.titulo ) + '</a></h3>' +
			'<p class="lai-card__preco">' + produto.preco_formatado + '</p>' +
			'<div class="lai-card__acoes">' +
			'<a class="lai-btn lai-btn--primario" href="' + produto.link_redirect + '" target="_blank" rel="nofollow sponsored noopener">Comprar</a>' +
			'<button type="button" class="lai-icon-btn lai-wishlist-toggle" data-product-id="' + produto.id + '">♡</button>' +
			'<button type="button" class="lai-icon-btn lai-compare-toggle" data-product-id="' + produto.id + '">⇄</button>' +
			'</div></div></div>'
		);
	}

	function escapeHtml( text ) {
		var div = document.createElement( 'div' );
		div.textContent = text || '';
		return div.innerHTML;
	}

	function renderComparador() {
		var tabela = document.getElementById( 'lai-comparador-tabela' );
		if ( ! tabela ) {
			return;
		}
		var vazio = document.getElementById( 'lai-comparador-vazio' );
		var ids = getIds( COMPARE_KEY );

		if ( ! ids.length ) {
			tabela.innerHTML = '';
			if ( vazio ) {
				vazio.hidden = false;
			}
			return;
		}

		fetchProdutos( ids ).then( function ( produtos ) {
			if ( vazio ) {
				vazio.hidden = produtos.length > 0;
			}
			if ( ! produtos.length ) {
				tabela.innerHTML = '';
				return;
			}

			var chaves = [];
			produtos.forEach( function ( produto ) {
				( produto.especificacoes || [] ).forEach( function ( linha ) {
					if ( chaves.indexOf( linha.chave ) === -1 ) {
						chaves.push( linha.chave );
					}
				} );
			} );

			var html = '<tr><th></th>';
			produtos.forEach( function ( produto ) {
				html += '<th>' +
					'<img src="' + ( produto.imagem || '' ) + '" alt="" style="width:70px;height:70px;object-fit:contain;display:block;margin-bottom:6px;">' +
					'<a href="' + produto.permalink + '">' + escapeHtml( produto.titulo ) + '</a>' +
					'<br><button type="button" class="lai-comparador__remover" data-remover="' + produto.id + '">remover</button>' +
					'</th>';
			} );
			html += '</tr>';

			html += '<tr><th>Preço</th>';
			produtos.forEach( function ( produto ) {
				html += '<td><strong>' + produto.preco_formatado + '</strong></td>';
			} );
			html += '</tr>';

			html += '<tr><th>Avaliação</th>';
			produtos.forEach( function ( produto ) {
				html += '<td>' + ( produto.avaliacao_nota ? produto.avaliacao_nota + ' ★ (' + produto.avaliacao_total + ')' : '—' ) + '</td>';
			} );
			html += '</tr>';

			chaves.forEach( function ( chave ) {
				html += '<tr><th>' + escapeHtml( chave ) + '</th>';
				produtos.forEach( function ( produto ) {
					var linha = ( produto.especificacoes || [] ).filter( function ( l ) {
						return l.chave === chave;
					} )[ 0 ];
					html += '<td>' + ( linha ? escapeHtml( linha.valor ) : '—' ) + '</td>';
				} );
				html += '</tr>';
			} );

			html += '<tr><th></th>';
			produtos.forEach( function ( produto ) {
				html += '<td><a class="lai-btn lai-btn--primario" href="' + produto.link_redirect + '" target="_blank" rel="nofollow sponsored noopener">Comprar</a></td>';
			} );
			html += '</tr>';

			tabela.innerHTML = html;

			tabela.querySelectorAll( '[data-remover]' ).forEach( function ( btn ) {
				btn.addEventListener( 'click', function () {
					toggleId( COMPARE_KEY, Number( btn.getAttribute( 'data-remover' ) ) );
					refreshToggleButtons();
					renderComparador();
				} );
			} );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		bindToggleButtons();
		bindGaleria();
		refreshToggleButtons();
		renderWishlistPage();
		renderComparador();
	} );
} )();
