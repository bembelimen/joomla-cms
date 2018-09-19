/**
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

(() => {

    document.addEventListener('DOMContentLoaded', function () {
        const dropDownBtn = document.getElementById('toolbar-dropdown-group'),
            publishBtn = dropDownBtn.getElementsByClassName("button-publish")[0],
            unpublishBtn = dropDownBtn.getElementsByClassName("button-unpublish")[0],
            archiveBtn = dropDownBtn.getElementsByClassName("button-archive")[0],
            trashBtn = dropDownBtn.getElementsByClassName("button-trash")[0],
            articleList = document.querySelector('#articleList'),
            articleListRows = articleList.querySelectorAll('tbody tr');

        let publishBool = false,
            unpublishBool = false,
            archiveBool = false,
            trashBool = false,
            countChecked = 0;

        articleList.addEventListener("click", function (event) {

            for (let i = 0; i < articleListRows.length; i += 1) {

                let checkedBox = articleListRows[i].querySelectorAll('input[type=checkbox]')[0];

               if (articleListRows[i].querySelectorAll('input[type=checkbox]')[0].checked === true) {
                    const parentTr = checkedBox.closest("tr");
                    console.log(parentTr);

                    checkForAttributes(parentTr);

                    countChecked += 1;
                }
            }
            disableButtons();
            countChecked = 0;
        });

        function checkForAttributes(row) {
            if (row.getAttribute('data-condition-publish') == 1) {
                if ((countChecked == 0) || (publishBool === true)) {
                    publishBool = true;
                }
                else {
                    publishBool = false;
                }
            }
            else {
                publishBool = false;
            }

            if (row.getAttribute('data-condition-unpublish') == 1) {
                if ((countChecked == 0) || (unpublishBool === true)) {
                    unpublishBool = true;
                }
                else {
                    unpublishBool = false;
                }
            }
            else {
                unpublishBool = false;
            }

            if (row.getAttribute('data-condition-trash') == 1) {
                if ((countChecked == 0) || (archiveBool === true)) {
                    archiveBool = true;
                }
                else {
                    archiveBool = false
                }
            }
            else {
                archiveBool = false
            }

            if (row.getAttribute('data-condition-trash') == 1) {
                if ((countChecked == 0) || (trashBool === true)) {
                    trashBool = true;
                }
                else {
                    trashBool = false;
                }
            }
            else {
                trashBool = false;
            }
        }

        function disableButtons() {
            (publishBool === false)
                ? publishBtn.setAttribute('disabled', true)
                : publishBtn.removeAttribute('disabled');

            (unpublishBool === false)
                ? unpublishBtn.setAttribute('disabled', true)
                : unpublishBtn.removeAttribute('disabled');

            (archiveBool === false)
                ? archiveBtn.setAttribute('disabled', true)
                : archiveBtn.removeAttribute('disabled');

            (trashBool === false)
                ? trashBtn.setAttribute('disabled', true)
                : trashBtn.removeAttribute('disabled');
        }

    });

})();
