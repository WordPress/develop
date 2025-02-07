/**
 * WordPress dependencies
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

test.describe( 'Bulk Edit the wp-page', () => {
	test.beforeEach( async ( { admin, editor, requestUtils } ) => {
		// delete all pages
		await requestUtils.deleteAllPages();

		// create pages to use in test
		const postTitles = [
			'Test Post for bulk edit 1',
			'Test Post for bulk edit 2',
		];

		for ( const title of postTitles ) {
			await admin.createNewPost( { title, postType: 'page' } );
			await editor.publishPost();
		}
	} );

	test( 'Should able to edit the pages in bulk', async ( {
		page,
		admin,
	} ) => {
		await admin.visitAdminPage( '/edit.php?post_type=page' );

		// click on select all checkbox
		await page.locator( '#cb-select-all-1' ).click();

		// select the edit option from the dropdown
		await page.selectOption( '#bulk-action-selector-top', 'edit' );

		// click on apply button
		await page.click( 'role=button[name="Apply"i] >> nth=0' );

		// select the draft option
		await page
			.getByRole( 'combobox', { name: 'Status' } )
			.selectOption( 'draft' );

		// click on the update button
		await page.getByRole( 'button', { name: 'Update' } ).click();

		await expect(
			page.locator( "div[id='message'] p" ).first()
		).toHaveText( /pages updated./ );

		const listTable = page.getByRole( 'table', {
			name: 'Table ordered by',
		} );
		const posts = listTable.locator( '.page-title  strong span' );

		// Validate that the page is in draft status
		await expect( posts.first() ).toHaveText( 'Draft' );
	} );
	
} );
