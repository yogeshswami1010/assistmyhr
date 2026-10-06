<script>
(function () {
    if (window._jaClientReviewTimer) clearInterval(window._jaClientReviewTimer);
    if (window._jaClientReviewSelectionHandler) document.removeEventListener('selectionchange', window._jaClientReviewSelectionHandler);
    $(document).off('.jaClientReviews');
    var panel = document.getElementById('ja-client-reviews-panel');
    var configNode = document.getElementById('ja-client-review-config');
    if (!panel || !configNode) return;
    var config = JSON.parse(configNode.textContent);
    var list = document.getElementById('ja-client-review-conversations');
    var badge = document.getElementById('ja-client-review-unread');
    var modal = document.getElementById('ja-client-review-modal');
    var opener = null;
    var pending = false;
    var savedRanges = {};

    function uuid() {
        if (window.crypto && window.crypto.randomUUID) return window.crypto.randomUUID();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) { var r = Math.random() * 16 | 0; return (c === 'x' ? r : r & 3 | 8).toString(16); });
    }
    function encoded(html) {
        return btoa(Array.from(new TextEncoder().encode(html), function (byte) { return String.fromCharCode(byte); }).join(''));
    }
    function feedback(target, text, failed) {
        if (!target) return;
        target.textContent = text;
        target.style.display = 'block';
        target.style.color = failed ? '#B91C1C' : '#047857';
    }
    function errorMessage(xhr) {
        var data = xhr.responseJSON || {};
        var first = data.errors && Object.values(data.errors)[0];
        return first ? (Array.isArray(first) ? first[0] : first) : (data.message || 'The request failed. Please try again.');
    }
    function closeModal(force) {
        if (!modal || !modal.open) return;
        var form = modal.querySelector('[data-client-review-send]');
        if (!force && form && form.dataset.busy === '1') return;
        modal.close();
        if (opener && opener.isConnected) opener.focus();
    }
    $(document).on('click.jaClientReviews', '[data-client-review-open]', function () {
        if (!modal || modal.open || !panel.isConnected) return;
        opener = this;
        modal.showModal();
        modal.querySelector('[name="client_email"]').focus();
    });
    $(document).on('click.jaClientReviews', '[data-client-review-close]', function () { closeModal(false); });
    if (modal) {
        modal.addEventListener('cancel', function (event) { event.preventDefault(); closeModal(false); });
        modal.addEventListener('click', function (event) { if (event.target === modal) closeModal(false); });
    }
    function active() {
        var pane = document.getElementById('ja-tab-client-reviews');
        return pane && pane.style.display !== 'none';
    }
    function load() {
        if (pending || !panel.isConnected || list.querySelector('[data-review-dirty="1"]')) return;
        pending = true;
        $.ajax({url: config.indexUrl, type: 'GET', cache: false, global: false}).done(function (response) {
            if (!panel.isConnected || list.querySelector('[data-review-dirty="1"]')) return;
            if (list.dataset.lastView !== response.view && typeof response.view === 'string') {
                list.innerHTML = response.view;
                list.dataset.lastView = response.view;
            }
            if (badge) badge.style.display = 'none';
        }).fail(function () {
            if (!list.dataset.lastView) list.textContent = 'Could not load client reviews. Open the tab again to retry.';
        }).always(function () { pending = false; });
    }
    function tick() {
        if (!panel.isConnected) {
            clearInterval(window._jaClientReviewTimer);
            document.removeEventListener('selectionchange', window._jaClientReviewSelectionHandler);
            $(document).off('.jaClientReviews');
            return;
        }
        if (document.hidden) return;
        if (active()) { load(); return; }
        if (pending) return;
        pending = true;
        $.ajax({url: config.unreadUrl, type: 'GET', cache: false, global: false}).done(function (response) {
            if (!badge || !panel.isConnected) return;
            var count = Number(response.unread || 0);
            badge.textContent = count + ' new';
            badge.style.display = count ? 'inline-flex' : 'none';
        }).always(function () { pending = false; });
    }
    window._jaClientReviewSelectionHandler = function () {
        var selection = window.getSelection();
        if (!selection || !selection.rangeCount) return;
        var node = selection.anchorNode;
        var element = node && (node.nodeType === 1 ? node : node.parentElement);
        var editor = element && element.closest('.ja-review-editor');
        if (editor && (panel.contains(editor) || (modal && modal.contains(editor)))) savedRanges[editor.id] = selection.getRangeAt(0).cloneRange();
    };
    document.addEventListener('selectionchange', window._jaClientReviewSelectionHandler);
    $(document).on('mousedown.jaClientReviews', 'button[data-review-command]', function (event) { event.preventDefault(); });
    $(document).on('click.jaClientReviews change.jaClientReviews', '[data-review-command]', function (event) {
        if (event.type === 'click' && this.tagName === 'SELECT') return;
        var editor = document.getElementById(this.dataset.editor);
        if (!editor || editor.contentEditable === 'false') return;
        editor.focus();
        if (savedRanges[editor.id] && editor.contains(savedRanges[editor.id].commonAncestorContainer)) {
            var selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(savedRanges[editor.id]);
        }
        document.execCommand(this.dataset.reviewCommand, false, this.tagName === 'SELECT' ? this.value : null);
        editor.dataset.reviewDirty = '1';
    });
    $(document).on('paste.jaClientReviews', '.ja-review-editor', function (event) {
        event.preventDefault();
        var clipboard = event.originalEvent.clipboardData;
        if (clipboard) document.execCommand('insertText', false, clipboard.getData('text/plain'));
    });
    $(document).on('input.jaClientReviews', '#ja-client-reviews-panel input, #ja-client-review-modal input, #ja-client-review-modal textarea, .ja-review-editor', function () {
        var form = this.closest('form');
        if (form && form.dataset.busy !== '1') delete form.dataset.submissionId;
        if (this.classList.contains('ja-review-editor')) this.dataset.reviewDirty = '1';
    });
    $(document).on('click.jaClientReviews', '.ja-tab[data-tab="client-reviews"]', load);
    $(document).on('submit.jaClientReviews', '[data-client-review-send], [data-client-review-reply]', function (event) {
        event.preventDefault();
        var form = this;
        if (form.dataset.busy === '1' || !form.reportValidity()) return;
        var editor = form.querySelector('.ja-review-editor');
        var output = form.querySelector('.ja-review-feedback');
        if (!editor.textContent.trim()) { feedback(output, 'Write a message for the client.', true); editor.focus(); return; }
        if (new TextEncoder().encode(editor.innerHTML).length > 50000) { feedback(output, 'The message is too long. Shorten it and try again.', true); return; }
        var replyId = form.getAttribute('data-client-review-reply');
        var url = replyId ? config.replyUrl.replace('__REVIEW__', replyId) : config.sendUrl;
        form.dataset.submissionId = form.dataset.submissionId || uuid();
        var data = {_token: config.token, message_payload: encoded(editor.innerHTML), submission_id: form.dataset.submissionId};
        if (!replyId) {
            data.client_email = form.elements.client_email.value;
            data.subject = form.elements.subject.value;
            data.client_message = form.elements.client_message.value;
        }
        form.dataset.busy = '1';
        editor.contentEditable = 'false';
        form.querySelectorAll('button,input,select,textarea').forEach(function (control) { control.disabled = true; });
        feedback(output, 'Sending…', false);
        $.ajax({url: url, type: 'POST', data: data}).done(function (response) {
            if (!panel.isConnected) return;
            form.reset();
            editor.innerHTML = '';
            editor.dataset.reviewDirty = '0';
            delete form.dataset.submissionId;
            if (window._jaProfileCache) window._jaProfileCache.clear();
            feedback(output, response.message || 'Email sent.', false);
            if (!replyId) {
                closeModal(true);
                var tab = document.querySelector('.ja-profile-toolbar [data-tab="client-reviews"]');
                if (tab) tab.click();
                feedback(document.getElementById('ja-client-review-send-feedback'), response.message || 'Email sent.', false);
            }
            load();
        }).fail(function (xhr) {
            if (!panel.isConnected) return;
            var saved = (xhr.responseJSON || {}).saved;
            feedback(output, errorMessage(xhr) + (saved ? ' The conversation is saved; use Retry email below.' : ''), true);
            if (saved && replyId) {
                // The failed message is already stored in the thread; reveal its retry action.
                editor.innerHTML = '';
                editor.dataset.reviewDirty = '0';
                delete form.dataset.submissionId;
            }
            load();
        }).always(function () {
            form.dataset.busy = '0';
            editor.contentEditable = 'true';
            form.querySelectorAll('button,input,select,textarea').forEach(function (control) { control.disabled = false; });
        });
    });
    $(document).on('click.jaClientReviews', '[data-client-review-retry], [data-client-review-revoke]', function () {
        var button = this;
        if (button.disabled) return;
        var revokeId = button.getAttribute('data-client-review-revoke');
        var reviewId = revokeId || button.getAttribute('data-client-review-retry');
        if (revokeId && !window.confirm('Revoke this client’s access to the shared CV and review page?')) return;
        var url = (revokeId ? config.revokeUrl : config.retryUrl).replace('__REVIEW__', reviewId).replace('__MESSAGE__', button.dataset.message || '');
        var output = button.closest('.ja-review-thread').querySelector('.ja-review-thread-feedback');
        button.disabled = true;
        $.ajax({url: url, type: 'POST', data: {_token: config.token}}).done(function (response) {
            if (!panel.isConnected) return;
            feedback(output, response.message, false);
            load();
        }).fail(function (xhr) { feedback(output, errorMessage(xhr), true); }).always(function () { button.disabled = false; });
    });
    window._jaClientReviewTimer = setInterval(tick, 15000);
    tick();
})();
</script>
