if ( !! window.navigation && 'CSSViewTransitionRule' in window ) {
	const getParentElement = ( element, selector ) => {
		element = element.parentElement;
		while ( element && element !== document ) {
			if ( ! selector || element.matches( selector ) ) {
				return element;
			}
		}
		return null;
	};

	const setTemporaryViewTransitionNames = async ( entries, vtPromise ) => {
		for ( const [ element, name ] of entries ) {
			if ( ! element ) {
				continue;
			}
			element.style.viewTransitionName = name;
		}

		await vtPromise;

		for ( const [ element, _ ] of entries ) {
			if ( ! element ) {
				continue;
			}
			element.style.viewTransitionName = '';
		}
	};

	window.addEventListener( 'pageswap', ( e ) => {
		if ( e.viewTransition ) {
			if ( document.body.classList.contains( 'single' ) ) {
				const article = document.querySelectorAll( 'article.post' );
				if ( article.length !== 1 ) {
					return;
				}

				setTemporaryViewTransitionNames( [
					[ article[ 0 ].querySelector( '.entry-title' ), 'post-title' ],
					[ article[ 0 ].querySelector( '.post-thumbnail' ), 'post-thumbnail' ],
				], e.viewTransition.finished );
			} else if ( document.body.classList.contains( 'home' ) || document.body.classList.contains( 'archive' ) ) {
				const articleLink = document.querySelector( 'article.post a[href="' + e.activation.entry.url + '"]' );
				if ( ! articleLink ) {
					return;
				}

				const article = getParentElement( articleLink, 'article.post' );
				if ( ! article ) {
					return;
				}

				setTemporaryViewTransitionNames( [
					[ article.querySelector( '.entry-title' ), 'post-title' ],
					[ article.querySelector( '.post-thumbnail' ), 'post-thumbnail' ],
				], e.viewTransition.finished );
			}
		}
	} );

	window.addEventListener( 'pagereveal', ( e ) => {
		if ( ! window.navigation.activation.from ) {
			return;
		}

		if ( e.viewTransition ) {
			if ( document.body.classList.contains( 'single' ) ) {
				const article = document.querySelectorAll( 'article.post' );
				if ( article.length !== 1 ) {
					return;
				}

				setTemporaryViewTransitionNames( [
					[ article[ 0 ].querySelector( '.entry-title' ), 'post-title' ],
					[ article[ 0 ].querySelector( '.post-thumbnail' ), 'post-thumbnail' ],
				], e.viewTransition.ready );
			} else if ( document.body.classList.contains( 'home' ) || document.body.classList.contains( 'archive' ) ) {
				const articleLink = document.querySelector( 'article.post a[href="' + window.navigation.activation.from.url + '"]' );
				if ( ! articleLink ) {
					return;
				}

				const article = getParentElement( articleLink, 'article.post' );
				if ( ! article ) {
					return;
				}

				setTemporaryViewTransitionNames( [
					[ article.querySelector( '.entry-title' ), 'post-title' ],
					[ article.querySelector( '.post-thumbnail' ), 'post-thumbnail' ],
				], e.viewTransition.ready );
			}
		}
	} );
} else {
	window.console.warn( 'View transitions not loaded as the browser is lacking support.' );
}
