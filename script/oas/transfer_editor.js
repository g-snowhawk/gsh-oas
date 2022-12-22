/**
 * This file is part of G.Snowhawk Online Accounting System.
 *
 * Copyright (c)2019 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */
'use strict';

const debugLevel = 0;

const formatter = new Intl.NumberFormat('ja-JP');
const viewMode = 'oas.transfer.response';
const editMode = 'oas.transfer.response:edit';
const calendarMode = 'oas.transfer.response:calendar';
const mainFormID = 'TMS-mainform';
let locationHash = location.hash;
let inputIssueDate = undefined;
let inputAmountLeft = undefined;
let inputAmountRight = undefined;
let selectItemCodeLeft = undefined;
let selectItemCodeRight = undefined;
let displayTotalLeft = undefined;
let displayTotalRight = undefined;
let itemCodeTemplate = undefined;
let buttonAddPage = undefined;
let buttonUnlock = undefined;
let buttonCancel = undefined;
let buttonSave = undefined;
let linkPreviousPage = undefined;
let linkNextPage = undefined;
let token = undefined;
let issueDate = undefined;
let pageNumber = undefined;
let naviPagination = undefined;
let calcApportionment = undefined;
let apportionment = undefined;

switch (document.readyState) {
    case 'loading' :
        window.addEventListener('DOMContentLoaded', initializeTransferEditor)
        window.addEventListener('DOMContentLoaded', transferSummarySuggestionInit)
        break;
    case 'interactive':
    case 'complete':
        initializeTransferEditor();
        transferSummarySuggestionInit();
        break;
}

function initializeTransferEditor(event) {
    token = document.querySelector('input[name=stub]').value;

    if (locationHash) {
        moveToPage();
        locationHash = undefined;
        return;
    }

    inputIssueDate = document.querySelector('input[name=issue_date]');
    inputAmountLeft = document.querySelectorAll('input[name^=amount_left]');
    inputAmountRight = document.querySelectorAll('input[name^=amount_right]');
    selectItemCodeLeft = document.querySelectorAll('select[name^=item_code_left]');
    selectItemCodeRight = document.querySelectorAll('select[name^=item_code_right]');
    displayTotalLeft = document.getElementById('total-left');
    displayTotalRight = document.getElementById('total-right');
    itemCodeTemplate = document.getElementById('account-items');
    buttonAddPage = document.getElementById('addpage');
    buttonUnlock = document.getElementById('unlock');
    buttonCancel = document.getElementById('cancel');
    buttonSave = document.querySelector('input[name=s1_submit]');
    naviPagination = document.getElementById('page-nav');
    calcApportionment = document.getElementById('calc-apportionment');
    linkPreviousPage = document.getElementById('prev-page-link');
    linkNextPage = document.getElementById('next-page-link');
    apportionment = document.getElementById('apportionment')

    issueDate = document.querySelector('input[name=issue_date]').value;
    pageNumber = document.querySelector('input[name=page_number]').value;

    const currentForm = document.getElementById(mainFormID);
    currentForm.addEventListener('submit', checkTransferBeforeSubmit);

    lockForm();

    let i;
    for (i = 0; i < inputAmountLeft.length; i++) {
        inputAmountLeft[i].addEventListener('keyup', calculateTotals);
    }
    calculateTotals(event);
    for (i = 0; i < inputAmountRight.length; i++) {
        inputAmountRight[i].addEventListener('keyup', calculateTotals);
    }
    calculateTotals(event, 'right');

    for (i = 0; i < selectItemCodeLeft.length; i++) {
        let element = selectItemCodeLeft[i];
        element.addEventListener('focus', focusSelectElement);
        appendItemCodeOptions(element);
    }
    for (i = 0; i < selectItemCodeRight.length; i++) {
        let element = selectItemCodeRight[i];
        element.addEventListener('focus', focusSelectElement);
        appendItemCodeOptions(element);
    }

    if (buttonAddPage) buttonAddPage.addEventListener('click', addNewPage);
    if (buttonUnlock) buttonUnlock.addEventListener('click', unlockForm);
    if (buttonCancel) buttonCancel.addEventListener('click', backToViewMode);
    if (buttonSave) buttonSave.addEventListener('click', checkTransferBeforeSubmit);
    if (linkPreviousPage) linkPreviousPage.addEventListener('click', moveToPage);
    if (linkNextPage) linkNextPage.addEventListener('click', moveToPage);
    if (apportionment) {
        apportionment.addEventListener('keydown', calculateApportionment);
    }

    const calendarSearch = document.getElementById('calendar-search');
    calendarSearch.addEventListener('click', openCalendarForSearch);

    // TODO: avoid dependencies
    if (TM.form) TM.form.through = true;
    if (TM.subform) {
        TM.subform.inited = false;
        TM.subform.init();
    }
}

function openCalendarForSearch(event) {
    event.preventDefault();

    let queryString = '?mode=' + calendarMode + '&date=' + inputIssueDate.value;
    fetch(location.pathname + queryString, {
        method: 'GET',
        credentials: 'same-origin'
    })
        .then(function(response){
            response.json().then(function(result){
                const date = new Date(result.date);
                const days = result.days || null;
                const element = event.currentTarget || event.target;

                popupCalendar(
                    element,
                    date.getFullYear(), date.getMonth() + 1, days,
                    moveToPage, moveCalendar, addNewPage
                );
            })
        })
        .catch(error => console.error(error));
}

function hashFromCalendar(element) {
    const table = element.findParent('table');
    const current = table.querySelector('.current').innerHTML;
    return '#' + current + '-' + element.innerHTML;
}

function moveCalendar(event) {
    event.preventDefault();

    const element = event.currentTarget;
    let queryString = '?mode=' + calendarMode + '&date=' + element.hash.substr(1) + '-01';
    fetch(location.pathname + queryString, {
        method: 'GET',
        credentials: 'same-origin'
    })
        .then(function(response){
            response.json().then(function(result){
                const date = new Date(result.date);
                const days = result.days || null;
                const element = event.currentTarget || event.target;

                const container = element.findParent('table');
                refreshCalendar(container, date.getFullYear(), date.getMonth() + 1, days);
            })
        })
        .catch(error => console.error(error));
}

function focusSelectElement(event)
{
    appendItemCodeOptions(event.currentTarget);
}

function appendItemCodeOptions(element)
{
    if (!itemCodeTemplate || element.options.length > 0) return;

    const clone = document.importNode(itemCodeTemplate.content, true);
    element.appendChild(clone);

    if (element.dataset.defaultValue !== '') {
        let i;
        for (i = 0; i < element.options.length; i++) {
            const option = element.options[i];
            if (option.value === element.dataset.defaultValue) {
                option.selected = true;
                break;
            }
        }
    }
}

function calculateTotals(event, leftOrRight) {
    let amountItems = inputAmountLeft;
    let displayTotal = displayTotalLeft;

    if (event && event.currentTarget) {
        if (event.currentTarget.name.indexOf('amount_right') === 0) {
            leftOrRight = 'right';
        }
    }

    if (leftOrRight === 'right') {
        amountItems = inputAmountRight;
        displayTotal = displayTotalRight;
    }

    if (displayTotal === null) return;

    let total = 0;
    let i;
    for (i = 0; i < amountItems.length; i++) {
        let amount = parseInt(amountItems[i].value.replace(/,/g, ''));
        if (isNaN(amount)) amount = 0;

        total += amount;
    }

    displayTotal.innerHTML = (total === 0) ? '' : formatter.format(total);
}

function addNewPage(event) {
    const element = event.currentTarget;

    let queryString = '?mode=' + editMode + '&add=1';

    if (element.findParent('.calendar-ui')) {
        const hash = hashFromCalendar(element).substr(1);
        const match = hash.match(/([0-9]+)-([0-9]+)-([0-9]+)/);
        const day = match[1] + '-' + ('00' + match[2]).slice(-2) + '-' + ('00' + match[3]).slice(-2);
        queryString += '&issue_date=' + day;
        removeCalendar();
    } else /* if (linkNextPage) */ {
        if (buttonUnlock) {
            queryString += '&issue_date=' + issueDate;
        }
    }

    fetch(location.pathname + queryString, {
        method: 'GET',
        credentials: 'same-origin'
    }).then(response => response.text())
    .then(source => replaceForm(source))
    .catch(error => console.error(error));
}

function reloadTransferPage(date, page) {
    let data = new FormData();
    data.append('stub', token);
    data.append('mode', viewMode);
    data.append('cur', date);
    if (page) data.append('p', page);

    fetch(location.pathname, {
        method: 'POST',
        credentials: 'same-origin',
        body: data
    }).then(response => response.text())
    .then(source => replaceForm(source))
    .catch(error => console.error(error));
}

function backToViewMode(event) {
    reloadTransferPage(issueDate, pageNumber);
}

function lockForm() {
    const forms = document.querySelectorAll('form');
    for (let form of forms) {
        if (form.classList.contains('readonly')) {
            for (let i = 0; i < form.elements.length; i++) {
                let element = form.elements[i];
                if (element.dataset.lockType !== 'never') {
                    element.disabled = true;
                }
            }
            const hiddens = document.querySelectorAll('.hide-on-readonly');
            hiddens.forEach(element => {
                element.classList.add('hidden-block');
            });
            if (calcApportionment && calcApportionment.findParent('form') === form) {
                calcApportionment.classList.add('hidden-block');
            }
        } else {
            if (naviPagination && naviPagination.findParent('form') === form) {
                naviPagination.classList.add('hidden-block');
            }
        }
    }
}

function unlockForm() {
    const forms = document.querySelectorAll('form');
    for (let form of forms) {
        if (form.classList.contains('readonly')) {
            let i;
            for (i = 0; i < form.elements.length; i++) {
                let element = form.elements[i];
                if (element.dataset.lockType !== 'ever') {
                    element.disabled = false;
                }
            }

            const button = buttonUnlock || buttonAddPage;
            if (form === button.form) {
                button.parentNode.classList.add('hidden-block');
            }

            if (naviPagination) {
                naviPagination.classList.add('hidden-block');
            }

            if (calcApportionment) {
                calcApportionment.classList.remove('hidden-block');
            }

            const hiddens = document.querySelectorAll('.hide-on-readonly');
            hiddens.forEach(element => {
                element.classList.remove('hidden-block');
            });
        }
    }
}

function moveToPage(event) {
    let hash = location.hash;
    if (event) {
        event.preventDefault();
        const element = event.currentTarget;
        if (element.hash) {
            hash = element.hash;
        } else if (element.findParent('.calendar-ui')) {
            hash = hashFromCalendar(element);
            removeCalendar();
        }
    }

    const dateAndPage = hash.substr(1).split(':');
    reloadTransferPage(dateAndPage[0], dateAndPage[1]);
}

function replaceForm(source) {
    const template = document.createElement('template');
    template.innerHTML = source;

    const newForm = template.content.querySelector('#' + mainFormID);
    const currentForm = document.getElementById(mainFormID);

    if (newForm && currentForm) {
        currentForm.parentNode.replaceChild(newForm, currentForm);
        initializeTransferEditor();
    }

    if (typeof acceptedDocumentSetListener == 'function') {
        acceptedDocumentSetListener();
    }
    transferSummarySuggestionInit();
}

function checkTransferBeforeSubmit(event)
{
    const form = event.currentTarget;

    if (event.type === 'click') {
        const leftPrices = document.querySelectorAll('input[name^=amount_left]');
        const leftItems = document.querySelectorAll('select[name^=item_code_left]');
        const rightPrices = document.querySelectorAll('input[name^=amount_right]');
        const rightItems = document.querySelectorAll('select[name^=item_code_right]');

        const len = leftPrices.length;
        let empty = 0;
        for (let i = 0; i < len; i++) {
            const line = {
                left : [ leftPrices[i], leftItems[i] ],
                right : [ rightPrices[i], rightItems[i] ],
            };
            for (let lr in line) {
                for (let n = 0; n < 2; n++) {
                    const q = (n === 0) ? 1 : 0;
                    if (line[lr][n].value !== '' && line[lr][q].value === '') {
                        line[lr][q].parentNode.classList.add('empty');
                        empty++;
                    }
                }
            }
        }

        if (empty > 0) {
            event.preventDefault();
            alert(event.target.dataset.ifBlank);
            return;
        }

        // Compare totals
        if (displayTotalLeft
            && displayTotalRight
            && displayTotalLeft.innerHTML !== displayTotalRight.innerHTML
        ) {
            event.preventDefault();
            alert(displayTotalRight.dataset.message);
            return;
        }
    } else if (event.type === 'submit') {
        const elements = form.querySelectorAll('*[data-lock-type=ever]');
        for (let element of elements) {
            element.disabled = false;
        }
    }
}

function calculateApportionment(event) {
    if (event.key !== 'Enter') {
        return;
    }

    event.preventDefault();

    const element = event.target;
    const percentage = parseFloat(element.value);
    if (isNaN(percentage) || percentage <= 0) {
        return;
    }
    const rate = percentage / 100;
    const wbo = element.dataset.withdrawalsByOwner;
    const amountLeft = document.querySelectorAll('*[name^=amount_left]');
    const itemCodeLeft = document.querySelectorAll('*[name^=item_code_left]');

    let withdrawalsByOwner = 0;
    let fixedRow = undefined;
    let notEmpty = undefined;
    for (let i = 0; i < amountLeft.length; i++) {
        const amount = amountLeft[i];
        const itemCode = itemCodeLeft[i];
        if (itemCode.options[itemCode.selectedIndex].value == wbo) {
            fixedRow = i;
            continue;
        }
        let value = parseInt(amount.value);
        if (isNaN(value)) {
            continue;
        }
        notEmpty = i;
        const business = Math.ceil(value * rate);
        withdrawalsByOwner += (value - business);
        amount.value = business;
    }

    if (withdrawalsByOwner > 0) {
        const n = fixedRow || (notEmpty + 1);
        amountLeft[n].value = withdrawalsByOwner;
        if (!fixedRow) {
            const itemCode = itemCodeLeft[n];
            itemCode.options.forEach(option => {
                option.selected = (option.value == wbo);
            });
        }
        calculateTotals({ currentTarget: amountLeft[n] }, 'left')
    }
}

/*
 * for Suggestion
 */
const transferSummarySuggestionListID = 'summary-list';

let transferSummarySuggestionListLock = false;
let transferSummaryIsComposing = false;
let transferSummarySuggestionIsFetching = false;
let transferSummaryAtKeyDown = undefined;
let transferSummarySuggestionContainer = undefined;
let transferSummaryCurrentElement = undefined;
let transferSummarySuggestionFetchCanceller = new AbortController();

function transferSummarySuggestionInit(event) {
    const container = document.querySelector('table.transfer-detail');
    const elements = container.querySelectorAll('input[name^=summary]');
    elements.forEach(element => {
        element.addEventListener('focus', transferSummarySetListener);
    });
}

function transferSummarySetListener(event) {
    const element = event.target;
    if (event.type === 'focus') {
        element.addEventListener('blur', transferSummarySetListener);
        element.addEventListener('compositionend', transferSummarySwitchComposing);
        element.addEventListener('compositionstart', transferSummarySwitchComposing);
        element.addEventListener('keyup', transferSummarySuggestion);
        transferSummaryCurrentElement = element;
        transferSummaryAtKeyDown = element.value;
    } else if (event.type === 'blur') {
        element.removeEventListener('blur', transferSummarySetListener);
        element.removeEventListener('compositionend', transferSummarySwitchComposing);
        element.removeEventListener('compositionstart', transferSummarySwitchComposing);
        element.removeEventListener('keyup', transferSummarySuggestion);
        //transferSummaryCurrentElement = undefined;
        transferSummaryAtKeyDown = undefined;
    }
}

function transferSummarySwitchComposing(event) {
    transferSummaryIsComposing = (event.type === 'compostionstart');
}

function transferSummarySuggestion(event) {
    let inputedValue = event.target.value;
    switch (event.type) {
        case 'keyup':
            if (event.key === 'Escape') {
                transferSummaryHideSuggestion();
                return;
            }
            if (event.key === 'ArrowDown'
                || event.key === inputedValue
                || (!transferSummaryIsComposing && transferSummaryAtKeyDown !== inputedValue)
            ) {
                transferSummaryFetchSuggestion();
            }
            transferSummaryAtKeyDown = inputedValue;
            break;
    }
}

function transferSummaryFetchSuggestion() {
    if (transferSummaryCurrentElement.value === '') {
        transferSummaryDisplaySuggestion('');

        return;
    }

    const form = transferSummaryCurrentElement.form;

    let data = new FormData();
    data.append('stub', form.stub.value);
    data.append('keyword', transferSummaryCurrentElement.value);
    data.append('mode', 'oas.transfer.receive:suggest-summary');

    if (transferSummarySuggestionIsFetching) {
        transferSummarySuggestionFetchCanceller.abort();
        transferSummarySuggestionIsFetching = false;
    }

    transferSummarySuggestionIsFetching = true;
    fetch(form.action, {
        signal: transferSummarySuggestionFetchCanceller.signal,
        method: 'POST',
        credentials: 'same-origin',
        body: data,
    }).then(response => {
        if (response.ok) {
            let contentType = response.headers.get("content-type");
            if (contentType.match(/^application\/json/)) {
                return response.json();
            }
            throw new Error('Unexpected response'.translate());
        } else {
            throw new Error('Server Error'.translate());
        }
    }).then(json => {
        if (json.status === 0) {
            transferSummaryDisplaySuggestion(json.source);
        } else {
            throw new Error(json.message);
        }
    }).catch(error => {
        if (error.name === 'AbortError') {
            console.warn("Aborted!!");
            transferSummarySuggestionFetchCanceller = new AbortController()
        } else {
            console.error(error)
        }
    }).then(() => {
        transferSummarySuggestionIsFetching = false;
    });
}

function transferSummaryDisplaySuggestion(source) {
    let list = document.getElementById(transferSummarySuggestionListID);
    if (list && !transferSummarySuggestionListLock) {
        list.parentNode.removeChild(list);
        window.removeEventListener('mouseup', transferSummaryHideSuggestion);
        window.removeEventListener('keydown', transferSummarySuggestionMoveFocus);
    }
    if (source === '') {
        return;
    }

    transferSummarySuggestionContainer = transferSummaryCurrentElement.findParent('td');

    list = transferSummarySuggestionContainer.appendChild(document.createElement('div'));
    list.id = transferSummarySuggestionListID;
    list.innerHTML = source;

    let i;
    let anchors = list.getElementsByTagName('a');
    for (i = 0; i < anchors.length; i++) {
        anchors[i].addEventListener('mousedown', transferSummarySwitchSuggestionLock);
        anchors[i].addEventListener('mouseup', transferSummarySwitchSuggestionLock);
        anchors[i].addEventListener('click', transferSummaryAutoFill);
    }

    window.addEventListener('mouseup', transferSummaryHideSuggestion);
    window.addEventListener('keydown', transferSummarySuggestionMoveFocus);
}

function transferSummaryHideSuggestion(event) {
    if (event) {
        const element = event.target;
        if (element === transferSummaryCurrentElement
            || element.childOf(transferSummarySuggestionContainer) !== -1
        ) {
            return;
        }
    }

    transferSummarySuggestionListLock = false;
    const suggestions = document.getElementById(transferSummarySuggestionListID);
    if (suggestions) {
        suggestions.parentNode.removeChild(suggestions);
    }
}

function transferSummarySuggestionMoveFocus(event) {
    if (event.key !== 'ArrowDown'
        && event.key !== 'ArrowUp'
        && event.key !== 'Tab'
        && event.key !== ' '
        && event.key !== 'Space'
        && event.key !== 'Enter'
        && event.key !== 'Escape'
    ) {
        return;
    }

    const list = document.getElementById(transferSummarySuggestionListID);
    if (!list) {
        return;
    }
    const anchors = list.getElementsByTagName('a');

    let current = document.activeElement;
    if (!current.findParent('#' + transferSummarySuggestionListID)) {
        const ignoreKeys = ['Enter', 'Escape', ' ', 'Space'];
        if (ignoreKeys.indexOf(event.key) === -1) {
            current = anchors[0];
            current.focus();
            setTimeout(() => {
                const parent = document.getElementById(transferSummarySuggestionListID);
                if (parent) {
                    parent.scrollTo(0, 0);
                }
            }, 0);
        }
        return;
    }

    event.preventDefault();
    switch (event.key) {
        case 'ArrowDown':
        case 'Tab':
            for (let i = 0; i < anchors.length; i++) {
                if (anchors[i] === current) {
                    const next = anchors[(i+1)];
                    if (next) {
                        next.focus();
                        return;
                    }
                }
            }
            if (event.key !== 'ArrowDown') {
                anchors[0].focus();
            }
            break;
        case 'ArrowUp':
            for (let i = 0; i < anchors.length; i++) {
                if (anchors[i] === current) {
                    const prev = anchors[(i-1)];
                    if (prev) {
                        prev.focus();
                        return;
                    }
                }
            }
            //transferSummaryCurrentElement.focus();
            break;
        case ' ':
        case 'Enter':
        case 'Space':
            current.click();
            break;
        case 'Escape':
            transferSummaryHideSuggestion();
            transferSummaryCurrentElement.focus();
            break;
    }
}

function transferSummarySwitchSuggestionLock(event) {
    transferSummarySuggestionListLock = (event.type === 'mousedown');;
}

function transferSummaryAutoFill(event) {
    event.preventDefault();
    const element = event.target;

    transferSummaryCurrentElement.value = element.dataset.suggest;

    const list = document.getElementById(transferSummarySuggestionListID);
    if (list) {
        list.parentNode.removeChild(list);
    }
}
