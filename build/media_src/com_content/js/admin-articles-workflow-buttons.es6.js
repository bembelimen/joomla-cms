/**
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

( () => {
    'use strict';

    document.addEventListener( 'DOMContentLoaded', function () {
        const dropDownBtn = document.getElementById( 'toolbar-dropdown-group' ),
            publishBtn = dropDownBtn.getElementsByClassName( 'button-publish' )[ 0 ],
            unpublishBtn = dropDownBtn.getElementsByClassName( 'button-unpublish' )[ 0 ],
            archiveBtn = dropDownBtn.getElementsByClassName( 'button-archive' )[ 0 ],
            trashBtn = dropDownBtn.getElementsByClassName( 'button-trash' )[ 0 ],
            articleList = document.querySelector( '#articleList' ),
            articleListRows = articleList.querySelectorAll( 'tbody tr' );

        let artListRowLength = articleListRows.length,
            publishBool = false,
            unpublishBool = false,
            archiveBool = false,
            trashBool = false,
            countChecked = 0;

        // listen to click event to get selected rows
        articleList.addEventListener( "click", function ( ) {
            for ( let i = 0; i < artListRowLength; i += 1 ) {
                let checkedBox = articleListRows[ i ].querySelectorAll( 'input[type=checkbox]' )[ 0 ];

                if ( articleListRows[ i ].querySelectorAll( 'input[type=checkbox]' )[ 0 ].checked === true ) {
                    const parentTr = checkedBox.closest( 'tr' );
                    checkForAttributes( parentTr );
                    countChecked += 1;
                }
            }
            disableButtons();
            countChecked = 0;
        } );

        // check for common attributes for which the conditions for a transition are possible or not and save this
        // information in a boolean variable.
        function checkForAttributes( row ) {
            if ( ( row.getAttribute( 'data-condition-publish' ) == 1 ) &&
                ( ( countChecked == 0 ) || ( publishBool === true ) ) ) {
                publishBool = true;
            }
            else {
                publishBool = false;
            }

            if ( ( row.getAttribute( 'data-condition-unpublish' ) == 1 ) &&
                ( ( countChecked == 0 ) || ( unpublishBool === true ) ) ) {
                unpublishBool = true;
            }
            else {
                unpublishBool = false;
            }

            if ( ( row.getAttribute( 'data-condition-trash' ) == 1 ) &&
                ( ( countChecked == 0 ) || ( archiveBool === true ) ) ) {
                archiveBool = true;
            }
            else {
                archiveBool = false;
            }

            if ( ( row.getAttribute( 'data-condition-trash' ) == 1 ) &&
                ( ( countChecked == 0 ) || ( trashBool === true ) ) ) {
                trashBool = true;
            }
            else {
                trashBool = false;
            }
        }

        // disable or enable Buttons of transitions depending on the boolean variables
        function disableButtons() {
            ( publishBool === false ) ? setOrRemDisabled( publishBtn, 'set' ) : setOrRemDisabled( publishBtn, 'rem' );
            ( unpublishBool === false ) ? setOrRemDisabled( unpublishBtn, 'set' ) : setOrRemDisabled( unpublishBtn, 'rem' );
            ( archiveBool === false ) ? setOrRemDisabled( archiveBtn, 'set' ) : setOrRemDisabled( archiveBtn, 'rem' );
            ( trashBool === false ) ? setOrRemDisabled( trashBtn, 'set' ) : setOrRemDisabled( trashBtn, 'rem' );
        }

        function setOrRemDisabled( btn, SetOrRem ) {
            ( SetOrRem === 'set' )
                ? btn.setAttribute( 'disabled', true )
                : btn.removeAttribute( 'disabled' );
        }

    } );

} )();
