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
        break;
    case 'interactive':
    case 'complete':
        acceptedDocumentInit();
        break;
}

function acceptedDocumentInit(event) {
    const tbody = document.getElementById('document-list');
    const rows = tbody.querySelectorAll('tr');
    rows.forEach(element => {
        element.addEventListener('click', acceptedDocumentOpenDoc);
    });

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

function acceptedDocumentOpenDoc(event) {
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
