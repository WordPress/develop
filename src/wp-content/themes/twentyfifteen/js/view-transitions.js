if ( !! window.navigation && 'CSSViewTransitionRule' in window ) {
	const determineTransitionType = ( oldNavigationEntry, newNavigationEntry ) => {
		if ( ! oldNavigationEntry || ! newNavigationEntry ) {
			return 'unknown';
		}

		const currentURL = new URL( oldNavigationEntry.url );
		const destinationURL = new URL( newNavigationEntry.url );

		const currentPathname = currentURL.pathname;
		const destinationPathname = destinationURL.pathname;

		if ( currentPathname !== destinationPathname ) {
			// If post URLs start with a date, use that to determine "order".
			const currentDateMatches = currentPathname.match( /^\/(\d{4})\/(\d{2})\/(\d{2})\// );
			const destinationDateMatches = destinationPathname.match( /^\/(\d{4})\/(\d{2})\/(\d{2})\// );
			if ( currentDateMatches && destinationDateMatches ) {
				const currentDate = new Date( parseInt( currentDateMatches[ 1 ] ), parseInt( currentDateMatches[ 2 ] ) - 1, parseInt( currentDateMatches[ 3 ] ) );
				const destinationDate = new Date( parseInt( destinationDateMatches[ 1 ] ), parseInt( destinationDateMatches[ 2 ] ) - 1, parseInt( destinationDateMatches[ 3 ] ) );
				if ( currentDate < destinationDate ) {
					return 'forwards';
				}
				if ( currentDate > destinationDate ) {
					return 'backwards';
				}
				return 'unknown';
			}

			// Otherwise, check URL "hierarchy".
			if ( destinationPathname.startsWith( currentPathname ) ) {
				return 'forwards';
			}
			if ( currentPathname.startsWith( destinationPathname ) ) {
				return 'backwards';
			}
		}

		return 'unknown';
	};

	window.addEventListener( 'pageswap', ( e ) => {
		if ( e.viewTransition ) {
			const transitionType = determineTransitionType( e.activation.from, e.activation.entry );

			e.viewTransition.types.add( transitionType );
		}
	} );

	window.addEventListener( 'pagereveal', ( e ) => {
		if ( ! window.navigation.activation.from ) {
			return;
		}

		if ( e.viewTransition ) {
			const transitionType = determineTransitionType( window.navigation.activation.from, window.navigation.activation.entry );

			e.viewTransition.types.add( transitionType );
		}
	} );
} else {
	window.console.warn( 'View transitions not loaded as the browser is lacking support.' );
}

