/*
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    /* ==========================================================
       1. FORM VALIDATION
       Each input lists its rules in data-rules, e.g. "required,min:2,max:100"
       ========================================================== */
    var EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    // Each rule returns an error message, or '' when the value is fine.
    var rules = {
        required: function (value) {
            return value.trim() === '' ? 'This field is required.' : '';
        },
        min: function (value, n) {
            return value.trim() !== '' && value.trim().length < Number(n)
                ? 'Enter at least ' + n + ' characters.' : '';
        },
        max: function (value, n) {
            return value.length > Number(n) ? 'Use ' + n + ' characters or fewer.' : '';
        },
        email: function (value) {
            return value.trim() !== '' && !EMAIL_PATTERN.test(value.trim())
                ? 'Enter a valid email address.' : '';
        },
        password: function (value) {
            if (value === '') { return ''; }
            if (value.length < 8) { return 'Password must be at least 8 characters.'; }
            if (!/[A-Za-z]/.test(value) || !/\d/.test(value)) {
                return 'Password must contain at least one letter and one number.';
            }
            return '';
        },
        match: function (value, otherName, field) {
            var other = field.form.elements[otherName];
            return other && value !== other.value ? 'The two passwords do not match.' : '';
        }
    };

    /** Show (or clear) the message under a field. */
    function showError(field, message) {
        var holder = field.form
            ? field.form.querySelector('.field-error[data-for="' + field.name + '"]')
            : null;
        if (holder) { holder.textContent = message; }      // textContent = safe, no HTML injection
        field.classList.toggle('invalid', message !== '');
        field.setAttribute('aria-invalid', message !== '' ? 'true' : 'false');
    }

    /** Run every rule on a field. Returns the first error message, or ''. */
    function checkField(field) {
        var list = (field.getAttribute('data-rules') || '').split(',');
        for (var i = 0; i < list.length; i++) {
            if (list[i] === '') { continue; }
            var parts = list[i].split(':');
            var rule = rules[parts[0]];
            var message = rule ? rule(field.value, parts[1], field) : '';
            if (message !== '') { return message; }
        }
        return '';
    }

    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        var fields = Array.prototype.slice.call(form.querySelectorAll('[data-rules]'));

        fields.forEach(function (field) {
            field.addEventListener('blur', function () { showError(field, checkField(field)); });
            field.addEventListener('input', function () {
                if (field.classList.contains('invalid')) { showError(field, checkField(field)); }
            });
        });

        form.addEventListener('submit', function (event) {
            var firstBad = null;
            fields.forEach(function (field) {
                var message = checkField(field);
                showError(field, message);
                if (message !== '' && !firstBad) { firstBad = field; }
            });

            if (firstBad) {
                event.preventDefault();          // stop the form: fix the errors first
                firstBad.focus();
                return;
            }

            // Valid: stop double-clicks from sending the form twice
            var button = form.querySelector('button[type="submit"]');
            if (button) { setTimeout(function () { button.disabled = true; }, 0); }
        });
    });

    /* ==========================================================
       2. IMAGE PREVIEW BEFORE UPLOAD
       ========================================================== */
    var ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    var MAX_BYTES = 2 * 1024 * 1024;

    document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
        var img = document.querySelector(input.getAttribute('data-preview'));
        var objectUrl = null;
        if (!img) { return; }

        function reject(message) {
            showError(input, message);
            input.value = '';                    // forget the bad file
        }

        input.addEventListener('change', function () {
            if (objectUrl) { URL.revokeObjectURL(objectUrl); objectUrl = null; }
            img.hidden = true;
            img.removeAttribute('src');
            showError(input, '');

            var file = input.files && input.files[0];
            if (!file) { return; }
            if (ALLOWED_TYPES.indexOf(file.type) === -1) {
                reject('Only JPG, PNG or WEBP images are allowed.');
                return;
            }
            if (file.size > MAX_BYTES) {
                reject('Image is too large. Maximum size is 2 MB.');
                return;
            }

            objectUrl = URL.createObjectURL(file);   // temporary local URL, nothing is uploaded yet
            img.src = objectUrl;
            img.hidden = false;
        });
    });

    /* ==========================================================
       3. LIVE SEARCH  (asks search_live.php while the user types)
       ========================================================== */
    var searchBox = document.getElementById('live-search');
    if (searchBox) {
        var resultsBox = document.getElementById('search-results');
        var statusBox = document.getElementById('search-status');
        var timer = null;          // debounce timer
        var controller = null;     // lets us cancel an out-of-date request

        /** Tiny helper: create an element with a class and text. */
        function el(tag, className, text) {
            var node = document.createElement(tag);
            if (className) { node.className = className; }
            if (text !== undefined) { node.textContent = text; }
            return node;
        }

        function avatarNode(user) {
            if (user.avatar) {
                var img = el('img', 'avatar avatar-sm');
                img.src = user.avatar;
                img.alt = '';
                return img;
            }
            var initial = Array.from(user.name)[0] || '?';
            return el('span', 'avatar avatar-sm avatar-fallback', initial.toUpperCase());
        }

        function usersCard(users) {
            var card = el('section', 'card');
            card.appendChild(el('h2', '', 'Users'));
            if (users.length === 0) {
                card.appendChild(el('p', 'muted', 'No users found.'));
                return card;
            }
            var list = el('ul', 'list');
            users.forEach(function (user) {
                var li = el('li', 'row');
                var info = el('div', 'grow');
                info.appendChild(el('strong', '', user.name));
                info.appendChild(document.createElement('br'));
                info.appendChild(el('small', 'muted', user.email));
                var view = el('a', 'btn btn-sm', 'View');
                view.href = 'user_view.php?id=' + encodeURIComponent(user.id);
                li.appendChild(avatarNode(user));
                li.appendChild(info);
                li.appendChild(view);
                list.appendChild(li);
            });
            card.appendChild(list);
            return card;
        }

        function eventsCard(events) {
            var card = el('section', 'card');
            card.appendChild(el('h2', '', 'Events'));
            if (events.length === 0) {
                card.appendChild(el('p', 'muted', 'No events found.'));
                return card;
            }
            var list = el('ul', 'list');
            events.forEach(function (ev) {
                var li = el('li', 'event-item');
                var title = el('div');
                title.appendChild(el('strong', '', ev.title + ' '));
                title.appendChild(el('span', 'badge', ev.category));
                var host = el('small', 'muted', 'Hosted by ');
                var hostLink = el('a', '', ev.host);
                hostLink.href = ev.host_url;
                host.appendChild(hostLink);
                li.appendChild(title);
                li.appendChild(el('small', 'muted', ev.when + ' \u00B7 ' + ev.location));
                li.appendChild(host);
                list.appendChild(li);
            });
            card.appendChild(list);
            return card;
        }

        function render(data) {
            resultsBox.replaceChildren(usersCard(data.users), eventsCard(data.events));
        }

        function runSearch(query) {
            if (controller) { controller.abort(); }      // cancel the previous request
            controller = new AbortController();
            statusBox.textContent = 'Searching\u2026';

            fetch('search_live.php?q=' + encodeURIComponent(query), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
                signal: controller.signal
            })
                .then(function (response) {
                    if (response.status === 401) { window.location.href = 'login.php'; throw new Error('auth'); }
                    if (!response.ok) { throw new Error('bad response'); }
                    return response.json();
                })
                .then(function (data) {
                    render(data);
                    statusBox.textContent = '';
                })
                .catch(function (error) {
                    if (error.name === 'AbortError' || error.message === 'auth') { return; }
                    statusBox.textContent = 'Live search is unavailable. Press Enter to search instead.';
                });
        }

        searchBox.addEventListener('input', function () {
            clearTimeout(timer);
            var query = searchBox.value.trim();
            if (query === '') {
                if (controller) { controller.abort(); }
                resultsBox.replaceChildren();
                statusBox.textContent = '';
                return;
            }
            timer = setTimeout(function () { runSearch(query); }, 300);   // wait until typing pauses
        });
    }

    /* ==========================================================
       4. SMALL HELPERS
       ========================================================== */

    // Ask before deleting (forms marked data-confirm="message")
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) { event.preventDefault(); }
        });
    });

    // Live character counter + Ctrl+Enter to send (message box)
    document.querySelectorAll('textarea[data-counter]').forEach(function (area) {
        var counter = document.querySelector(area.getAttribute('data-counter'));
        function update() { if (counter) { counter.textContent = area.value.length; } }
        area.addEventListener('input', update);
        area.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
                event.preventDefault();
                if (area.form.requestSubmit) { area.form.requestSubmit(); } else { area.form.submit(); }
            }
        });
        update();
    });

    // Show the newest chat message
    var chat = document.getElementById('chat');
    if (chat) { chat.scrollTop = chat.scrollHeight; }
});
