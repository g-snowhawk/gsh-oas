/**
 * This file is part of G.Snowhawk Application.
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @author    PlusFive.
 * @copyright (c)2022 PlusFive. (http://www.plus-5.com/)
 */

let searchQueries = [];
let clearSearches = [];
let searchTimer = undefined;

switch (document.readyState) {
    case 'loading' :
        window.addEventListener('DOMContentLoaded', acceptedDocumentInit)
        window.addEventListener('load', acceptedDocumentInit)
        break;
    case 'interactive':
    case 'complete':
        acceptedDocumentInit();
        break;
}

function acceptedDocumentInit(event) {
    if (typeof TM.subform == 'object' && event.type == 'load') {
        TM.subform.addListener('opened', acceptedDocumentSetRedirectMode);
    }

    acceptedDocumentSetListener();

    document.querySelectorAll('input.search-query').forEach((element) => {
        element.form.dataset.freeUnload = "1";
        element.dataset.previous = element.value;
        element.addEventListener('compositionstart', execSearchReceipt);
        element.addEventListener('compositionend', execSearchReceipt);
        element.addEventListener('keyup', execSearchReceipt);

        searchQueries.push(element);

        let next = element.nextSibling;
        while (next.nodeType != Node.ELEMENT_NODE) {
            next = next.nextSibling;
        }
        if (next.classList.contains('clear-search')) {
            next.style.visibility = (element.value.length === 0) ? 'hidden' : 'visible';
            next.addEventListener('click', execSearchReceipt);
        }
        clearSearches.push(next);
    });
}

function acceptedDocumentSetListener() {
    const rows = document.querySelectorAll('*[data-id]');
    rows.forEach(element => {
        element.addEventListener('click', acceptedDocumentOpenDoc);
    });

    const sorters = document.querySelectorAll('td.change-sort');
    sorters.forEach(element => {
        element.addEventListener('click', acceptedDocumentSetOrder);
    });
}

function acceptedDocumentOpenDoc(event) {
    event.stopPropagation();
    const element = event.currentTarget;
    const csrfToken = document.querySelector('input[name=stub]');
    const data = {
        stub : csrfToken.value,
        mode : 'oas.accepted-docs.receive:display-document',
        id : element.dataset.id
    };
    const form = document.createElement('form');
    form.action = location.pathname;
    form.method = 'POST';
    form.target = 'gsh-oas-docs';

    for (let key in data) {
        const node = document.createElement('input');
        node.type = 'hidden';
        node.name = key;
        node.value = data[key];

        form.appendChild(node);
    }

    document.body.appendChild(form);

    const w = window.open('', form.target);
    form.submit();
    w.focus();

    document.body.removeChild(form);
}

function acceptedDocumentCloseSubForm(args) {
    if (args === 'created') {
        if (location.search.indexOf('oas.transfer.response') !== -1) {
            if (typeof reloadTransferPage === 'function') {
                const issueDate = document.querySelector('input[name=issue_date]');
                const pageNumber = document.querySelector('input[name=page_number]');
                if (issueDate && pageNumber) {
                    reloadTransferPage(issueDate.value, pageNumber.value);
                }
            }
            return;
        }
    }
    let query = location.search.replace(/[&\?]q=[^=&\?]*/g, '');
    query = query.replace(/^[&\?]/g, '');
    location.href = location.pathname + '?' + query;
}

function getSearchURI(keywords) {
    let query = location.search.replace(/[&\?]?[qp]=[^=&\?]*/g, '');
    const separator = query.length > 0 ? '&' : '?';
    query += separator + "p=1";
    query += "&q=" + encodeURIComponent(element.value);

    return location.pathname + query;
}

function execSearchReceipt(event) {
    const element = event.target;
    switch (event.type) {
        case "click":
            event.stopPropagation();
            let prev = element.previousSibling;
            while (prev.nodeType != Node.ELEMENT_NODE) {
                prev = prev.previousSibling;
            }
            if (prev.nodeName.toLowerCase() === 'input') {
                prev.value = '';
                location.href = getSearchURI() + '&q=';
            }
            return;
        case "compositionend":
            element.dataset.composing = "end";
            return;
        case "compositionstart":
            element.dataset.composing = "start";
            return;
        case "keydown":
        case "keyup":
            if (searchTimer > 0) {
                clearTimeout(searchTimer);
                element.disabled = false;
                element.dataset.previous = "";
                searchTimer = undefined;
            }

            let i = searchQueries.indexOf(element);
            const next = clearSearches[i];
            if (next.classList.contains('clear-search')) {
                next.style.visibility = (element.value.length === 0) ? 'hidden' : 'visible';
            }

            if (event.key !== "Enter" || (element.value !== '' && element.value === element.dataset.previous)) {
                return;
            }
            if (element.dataset.composing === "end") {
                delete element.dataset.composing;
                return;
            }
            break;
        default:
            return;
    }

    if (element.dataset.composing) {
        console.warn(element.dataset.composing);
        return;
    }

    element.dataset.previous = element.value;
    element.disabled = true;
    searchTimer = setTimeout((url) => {
        location.href = url;
    }, 300, getSearchURI(element.value));
}

function acceptedDocumentSetRedirectMode(event) {
    const form = document.getElementById('subform');
    form.redirect_mode.value = location.search.substr(1);

    acceptedDocumentSuggestionInit(event);
}

/*
 * for Suggestion
 */
const acceptedDocumentSuggestionListID = 'suggestion-list';
const acceptedDocumentSuggestionDataClassName = 'sender-data';

let suggestionListLock = false;
let acceptedDocumentSuggestionIsComposing = false;
let acceptedDocumentIsFetching = false;
let acceptedDocumentValueAtKeyDown = undefined;
let acceptedDocumentSuggestionListContainer = undefined;
let acceptedDocumentSenderInput = undefined;
let acceptedDocumentFetchCanceller = new AbortController();

function acceptedDocumentSuggestionInit(event) {
    const form = document.getElementById('subform');
    acceptedDocumentSenderInput = form.sender;
    acceptedDocumentSenderInput.addEventListener('compositionend', acceptedDocumentSwitchComposing);
    acceptedDocumentSenderInput.addEventListener('compositionstart', acceptedDocumentSwitchComposing);
    acceptedDocumentSenderInput.addEventListener('focus', acceptedDocumentSuggestion);
    acceptedDocumentSenderInput.addEventListener('keydown', acceptedDocumentSuggestion);
    acceptedDocumentSenderInput.addEventListener('keyup', acceptedDocumentSuggestion);

    acceptedDocumentSuggestionListContainer = document.getElementById(acceptedDocumentSuggestionDataClassName);
}

function acceptedDocumentSwitchComposing(event) {
    acceptedDocumentSuggestionIsComposing = false; /*(event.type !== 'compositionend');*/
}

function acceptedDocumentSuggestion(event) {
    let inputedValue = event.target.value;
    switch (event.type) {
        case 'keyup':
            if (event.key === 'ArrowDown'
                || event.key === inputedValue
                || (!acceptedDocumentSuggestionIsComposing && acceptedDocumentValueAtKeyDown !== inputedValue)
            ) {
                acceptedDocumentSuggestSender();
            }
        case 'focus':
            acceptedDocumentValueAtKeyDown = inputedValue;
            break;
    }
}

function acceptedDocumentSuggestSender() {
    if (acceptedDocumentSenderInput.value === '') {
        acceptedDocumentDisplaySuggestionList('');

        return;
    }

    if (document.getElementById(acceptedDocumentSuggestionListID)) {
    }

    const form = acceptedDocumentSenderInput.form;

    let data = new FormData();
    data.append('stub', form.stub.value);
    data.append('keyword', acceptedDocumentSenderInput.value);
    data.append('mode', 'oas.accepted-docs.receive:suggest-sender');

    if (acceptedDocumentIsFetching) {
        acceptedDocumentFetchCanceller.abort();
        acceptedDocumentIsFetching = false;
    }

    acceptedDocumentIsFetching = true;
    fetch(form.action, {
        signal: acceptedDocumentFetchCanceller.signal,
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
            acceptedDocumentDisplaySuggestionList(json.source);
        } else {
            throw new Error(json.message);
        }
    }).catch(error => {
        if (error.name === 'AbortError') {
            console.warn("Aborted!!");
            acceptedDocumentFetchCanceller = new AbortController()
        } else {
            console.error(error)
        }
    }).then(() => {
        acceptedDocumentIsFetching = false;
    });
}

function acceptedDocumentHideSuggestion(event) {
    const element = event.target;
    if (element === acceptedDocumentSenderInput
        || element.childOf(acceptedDocumentSuggestionListContainer) !== -1
    ) {
        return;
    }

    suggestionListLock = false;
    acceptedDocumentDisplaySuggestionList('');
}

function acceptedDocumentDisplaySuggestionList(source, checkCurrent) {
    if (!acceptedDocumentSuggestionListContainer) {
        return;
    }

    let list = document.getElementById(acceptedDocumentSuggestionListID);
    if (list && !suggestionListLock) {
        list.parentNode.removeChild(list);
        window.removeEventListener('mouseup', acceptedDocumentHideSuggestion);
        window.removeEventListener('keydown', acceptedDocumentMoveFocus);
    }
    if (source === '') {
        if (!checkCurrent) {
            return;
        }
    }

    list = acceptedDocumentSuggestionListContainer.appendChild(document.createElement('div'));
    list.id = acceptedDocumentSuggestionListID;
    list.innerHTML = source;

    let i;
    let anchors = list.getElementsByTagName('a');
    for (i = 0; i < anchors.length; i++) {
        anchors[i].addEventListener('mousedown', acceptedDocumentSwitchSuggestionLock);
        anchors[i].addEventListener('mouseup', acceptedDocumentSwitchSuggestionLock);
        anchors[i].addEventListener('click', acceptedDocumentAutoFillSender);
    }

    window.addEventListener('mouseup', acceptedDocumentHideSuggestion);
    window.addEventListener('keydown', acceptedDocumentMoveFocus);
}

function acceptedDocumentMoveFocus(event) {
    if (event.key !== 'ArrowDown'
        && event.key !== 'ArrowUp'
        && event.key !== 'Tab'
        && event.key !== ' '
        && event.key !== 'Enter'
        && event.key !== 'Escape'
    ) {
        return;
    }

    const list = document.getElementById(acceptedDocumentSuggestionListID);
    if (!list) {
        return;
    }
    const anchors = list.getElementsByTagName('a');

    let current = document.activeElement;
    if (!current.findParent('#' + acceptedDocumentSuggestionListID)) {
        if (event.key !== 'Enter') {
            current = anchors[0];
            current.focus();
        }
        return;
    }

    event.preventDefault();
    switch (event.key) {
        case 'ArrowDown':
        case 'Tab':
        case ' ':
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
                    const next = anchors[(i-1)];
                    if (next) {
                        next.focus();
                        return;
                    }
                }
            }
            acceptedDocumentSenderInput.focus();
            break;
        case 'Enter':
            current.click();
            break;
        case 'Escape':
            acceptedDocumentDisplaySuggestionList('');
            acceptedDocumentSenderInput.focus();
            break;
    }
}

function acceptedDocumentSwitchSuggestionLock(event) {
    suggestionListLock = (event.type === 'mousedown');;
}

function acceptedDocumentAutoFillSender(event) {
    const element = event.target;

    acceptedDocumentSenderInput.value = element.dataset.sender;

    const list = document.getElementById(acceptedDocumentSuggestionListID);
    if (list) {
        list.parentNode.removeChild(list);
    }
}

function acceptedDocumentSetOrder(event) {
    const element = event.currentTarget;
    const parent = element.findParent('tr');
    if (!parent) {
        return;
    }
    let column = getcookie(parent.dataset.order);
    let sort = getcookie(parent.dataset.sort);
    if (column !== element.dataset.column) {
        column = element.dataset.column;
        sort = 'ASC';
    } else {
        sort = (sort === 'ASC') ? 'DESC' : 'ASC';
    }
    setcookie(parent.dataset.order, column);
    setcookie(parent.dataset.sort, sort);

    location.reload();
}
