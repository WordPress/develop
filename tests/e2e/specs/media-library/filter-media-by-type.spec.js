/**
 * WordPress dependencies
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );
import path from 'path';

test.describe( 'Filter wp-media-library by type test', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		// delete all media
		await requestUtils.deleteAllMedia();

		// upload files
		const files = [
			'tests/e2e/assets/test-data.jpg',
			'tests/e2e/assets/test-data1.jpg',
			'tests/e2e/assets/test-mp3.mp3'
		];

		for ( const file of files ) {
			await requestUtils.uploadMedia(
				path.resolve( process.cwd(), file )
			);
		}
	} );

	test( 'Should be able to filter the media based on media type in grid view', async ( {
		page,
		admin,
	} ) => {
		// navigate to url
		await admin.visitAdminPage( '/upload.php?mode=grid' );
		
		await expect( page.locator( '.load-more-count' ) ).toHaveText(
			'Showing 3 of 3 media items'
		);

		// validate media by video
		await page
			.getByRole( 'combobox', { name: 'Filter by type' } )
			.selectOption( 'video' );

		// validate media does not exist
		await expect( page.locator( '.no-media' ) ).toHaveText(
			'No media items found.'
		);

		// validate media by audio
		await page
			.getByRole( 'combobox', { name: 'Filter by type' } )
			.selectOption( 'audio' );

		// validate media count
		await expect( page.locator( '.load-more-count' ) ).toHaveText(
			'Showing 1 of 1 media items'
		);

		// open the file
		await page.locator( '.thumbnail' ).first().click();

		// validate file type
		await expect( page.locator( "div[class='file-type']" ) ).toHaveText(
			'File type: audio/mpeg'
		);

		// close the modal
		await page.locator( '.media-modal-close' ).click();

		// validate filter by image
		await page
			.getByRole( 'combobox', { name: 'Filter by type' } )
			.selectOption( 'image' );

		// validate media count
		await expect( page.locator( '.load-more-count' ) ).toHaveText(
			'Showing 2 of 2 media items'
		);

		// open the image
		await page.getByLabel('test-data', { exact: true }).click();

		// validate file type
		await expect( page.locator( "div[class='file-type']" ) ).toHaveText(
			'File type: image/jpeg'
		);
	} );

	test( 'Should be able to filter the media based on media type in list view', async ( {
		page,
		admin,
	} ) => {
		// navigate to url
		await admin.visitAdminPage( '/upload.php?mode=list' );
		
		await expect( page.locator( '.tablenav.top .displaying-num' ) ).toHaveText(
			'3 items'
		);

		// validate media by audio
		await page
			.getByRole( 'combobox', { name: 'Filter by type' } )
			.selectOption( 'Audio' );
		
		await page.getByRole( 'button', { name: 'Filter' } ).click();

		// validate media count
		await expect( page.locator( '.tablenav.top .displaying-num' ) ).toHaveText(
			'1 item'
		);

		await page.locator( '.title a' ).first().click();

		// validate file type
		await expect( page.locator( "div.misc-pub-section.misc-pub-filetype" ) ).toHaveText(
			'File type: MP3 (audio/mpeg)'
		);

		await admin.visitAdminPage( '/upload.php?mode=list' );

		// validate filter by image
		await page
			.getByRole( 'combobox', { name: 'Filter by type' } )
			.selectOption( 'Images' );

		await page.getByRole( 'button', { name: 'Filter' } ).click();

		// validate media count
		await expect( page.locator( '.tablenav.top .displaying-num' ) ).toHaveText(
			'2 items'
		);

		// open the image
		await page.locator( '.title a' ).first().click();

		// validate file type
		await expect( page.locator( "div.misc-pub-section.misc-pub-filetype" ) ).toHaveText(
			'File type: JPG'
		);
	} );
} );
