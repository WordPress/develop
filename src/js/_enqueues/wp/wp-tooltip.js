/**
 * Tooltip functionality for WordPress.
 *
 * @version 6.8.0
 * @output wp-includes/js/wp-tooltip.js
 */

document.addEventListener( 'DOMContentLoaded', function () {
	const tooltipContainers = document.querySelectorAll(
		'.wp-tooltip-container'
	);

	tooltipContainers.forEach( function ( tooltipContainer ) {
		let tooltipButton = tooltipContainer.querySelector( '.wp-tooltip-button, button, a' );
		let tooltipContent = tooltipContainer.querySelector( '.wp-tooltip-content' );

		// Generate tooltips declaratively.
		if ( null === tooltipContent ) {
			let tooltipString = tooltipButton.getAttribute( 'data-tooltip' );
			let tooltipId     = tooltipButton.id;
			if ( tooltipString && tooltipId ) {
				tooltipContent = document.createElement( 'div' );
				tooltipContent.setAttribute( 'role', 'tooltip' );
				tooltipContent.classList.add( 'wp-tooltip-content' );
				tooltipContent.id = tooltipId + '-content';
				tooltipButton.setAttribute( 'aria-describedby', tooltipId + '-content' );
				tooltipContent.innerHTML = '<p>' + tooltipString + '</p>'; 
				tooltipContainer.appendChild( tooltipContent );
			}
		}

		function showTooltip() {
			tooltipContent.style.display = 'block';
			adjustTooltipPosition( tooltipContainer, tooltipContent );
		}

		function hideTooltip() {
			tooltipContent.style.display = 'none';
		}

		// Event listeners for mouse and touch events
		tooltipContainer.addEventListener( 'mouseenter', showTooltip );
		tooltipContainer.addEventListener( 'focusin', showTooltip );
		tooltipButton.addEventListener( 'touchstart', showTooltip );
		tooltipButton.addEventListener( 'touchend', hideTooltip );

		document.addEventListener( 'keydown', function ( event ) {
			if (
				event.key === 'Escape' &&
				tooltipContent.style.display === 'block'
			) {
				// Hide the tooltip on Escape key press.
				tooltipContent.style.display = 'none';
				tooltipButton.focus();
			}
		} );

		document.body.addEventListener( 'click', function ( event ) {
			// Check if the clicked element is not within the tooltip container.
			if (
				! tooltipContent.contains( event.target ) &&
				! tooltipButton.contains( event.target )
			) {
				tooltipContent.style.display = 'none';
			}
		} );

		tooltipContainer.addEventListener( 'focusout', function ( event ) {
			if ( ! tooltipContainer.contains( event.relatedTarget ) ) {
				hideTooltip();
			}
		} );
	} );

	// Function to adjust tooltip position based on screen availability
	function adjustTooltipPosition( container, content ) {
		let containerRect = container.getBoundingClientRect(),
			contentRect = content.getBoundingClientRect(),
			viewportWidth = window.innerWidth,
			viewportHeight = window.innerHeight;

		// Check if there's enough space in each direction
		let fitsAbove = containerRect.top >= contentRect.height,
			fitsBelow = viewportHeight - containerRect.bottom >= contentRect.height,
			fitsLeft = containerRect.left >= contentRect.width,
			fitsRight = viewportWidth - containerRect.right >= contentRect.width;

		let defaultPosition = 'right';

		if ( container.classList.contains( 'position-top' ) ) {
			defaultPosition = 'top';
		} else if ( container.classList.contains( 'position-bottom' ) ) {
			defaultPosition = 'bottom';
		} else if ( container.classList.contains( 'position-left' ) ) {
			defaultPosition = 'left';
		}

		let newPosition = defaultPosition;

		if ( defaultPosition === 'top' && ! fitsAbove && fitsBelow ) {
			newPosition = 'bottom';
		} else if ( defaultPosition === 'bottom' && ! fitsBelow && fitsAbove ) {
			newPosition = 'top';
		} else if ( defaultPosition === 'left' && ! fitsLeft && fitsRight ) {
			newPosition = 'right';
		} else if ( defaultPosition === 'right' && ! fitsRight && fitsLeft ) {
			newPosition = 'left';
		}

		// Apply position adjustments
		container.classList.remove(
			'adjusted-position-top',
			'adjusted-position-bottom',
			'adjusted-position-left',
			'adjusted-position-right'
		);

		// Add the new position class
		container.classList.add( 'adjusted-position-' + newPosition );
	}
} );
