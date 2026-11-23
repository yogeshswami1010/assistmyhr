@if($user->cans('edit_job_applications'))
    <input type="file" id="ja-resume-upload-{{ $application->id }}" accept=".pdf,.doc,.docx,.rtf,.txt,.jpg,.jpeg,.png" hidden>
    <button type="button" id="ja-resume-update-btn-{{ $application->id }}" class="ja-pdf-btn"
            onclick="document.getElementById('ja-resume-upload-{{ $application->id }}').click()">
        <i class="fa fa-upload"></i> <span>{{ !empty($resumeUrl) ? 'Update CV' : 'Upload CV' }}</span>
    </button>
    <script>
    (function () {
        var input = document.getElementById('ja-resume-upload-{{ $application->id }}');
        var button = document.getElementById('ja-resume-update-btn-{{ $application->id }}');
        if (!input || !button) return;

        input.addEventListener('change', function () {
            if (!input.files.length) return;
            var data = new FormData();
            data.append('_token', @json(csrf_token()));
            data.append('resume', input.files[0]);
            button.disabled = true;
            button.querySelector('span').textContent = 'Uploading...';

            fetch(@json(route('admin.job-applications.update-resume', $application->id)), {
                method: 'POST', body: data, headers: {'X-Requested-With': 'XMLHttpRequest'}
            }).then(function (response) {
                return response.json().then(function (json) {
                    if (!response.ok) throw json;
                    return json;
                });
            }).then(function (response) {
                if (!button.isConnected) return;
                var profile = button.closest('.ja-candidate-profile');
                var resumeActions = profile?.querySelector('.ja-resume-toolbar .ja-pdf-toolbar-actions');
                if (profile) {
                    var pdfContainer = profile.querySelector('#ja-pdf-container');
                    if (pdfContainer) pdfContainer.dataset.resumeReplaced = 'true';
                    window.jaUnloadPdf?.();
                    profile.querySelectorAll('#ja-resume-pages, #ja-resume-zoom').forEach(function (control) {
                        control.hidden = true;
                    });
                }
                document.querySelectorAll('.ja-current-resume-link').forEach(function (link) {
                    link.href = response.resume_url;
                });
                if (!document.querySelector('.ja-current-resume-link')) {
                    var viewLink = document.createElement('a');
                    viewLink.href = response.resume_url;
                    viewLink.target = '_blank';
                    viewLink.rel = 'noopener';
                    viewLink.className = 'ja-pdf-btn ja-current-resume-link';
                    viewLink.innerHTML = '<i class="fa fa-external-link"></i> View';

                    var downloadLink = document.createElement('a');
                    downloadLink.href = response.resume_url;
                    downloadLink.setAttribute('download', '');
                    downloadLink.className = 'ja-pdf-btn ja-current-resume-link';
                    downloadLink.innerHTML = '<i class="fa fa-download"></i> Download';

                    if (resumeActions) {
                        resumeActions.append(downloadLink, viewLink);
                    } else {
                        button.parentNode.insertBefore(viewLink, button.nextSibling);
                        button.parentNode.insertBefore(downloadLink, viewLink.nextSibling);
                    }
                }
                var frame = document.getElementById('ja-pdf-frame');
                if (frame) {
                    frame.src = 'about:blank';
                    frame.style.display = 'block';
                    requestAnimationFrame(function () {
                        frame.src = response.resume_url + '#view=FitH';
                    });
                    var scroll = document.getElementById('ja-pdf-scroll');
                    var loading = document.getElementById('ja-pdf-loading');
                    var error = document.getElementById('ja-pdf-error');
                    if (scroll) scroll.style.display = 'none';
                    if (loading) loading.style.display = 'none';
                    if (error) error.style.display = 'none';
                } else {
                    var embed = document.querySelector('embed.ja-pdf-frame');
                    if (embed) embed.src = response.resume_url;
                }
                var empty = document.querySelector('.ja-pdf-no-resume');
                if (empty) {
                    var newFrame = document.createElement('iframe');
                    newFrame.id = 'ja-pdf-frame';
                    newFrame.className = 'ja-pdf-frame';
                    newFrame.title = 'Applicant CV';
                    newFrame.src = response.resume_url + '#view=FitH';
                    newFrame.style.cssText = 'display:block;width:100%;height:100%;flex:1;min-height:0;border:0;background:#525659;position:relative;inset:auto;';
                    empty.replaceWith(newFrame);
                }
                var history = $('#ja-tab-history');
                if (history.length && history.data('url')) {
                    history.removeData('loaded').html('<div class="ja-tab-loading">Open the History tab to load activity.</div>');
                } else if (history.length && response.history_html) {
                    var oldResumeHistory = history.find('#ja-resume-history-card');
                    if (oldResumeHistory.length) oldResumeHistory.replaceWith(response.history_html);
                    else history.prepend(response.history_html);
                }
                button.querySelector('span').textContent = 'Update CV';
                if (typeof toastr !== 'undefined') toastr.success(response.message);
            }).catch(function (error) {
                var message = error.message || (error.errors && error.errors.resume && error.errors.resume[0]) || 'The CV could not be updated.';
                if (typeof toastr !== 'undefined') toastr.error(message); else alert(message);
            }).finally(function () {
                button.disabled = false;
                input.value = '';
                if (button.querySelector('span').textContent === 'Uploading...') button.querySelector('span').textContent = 'Update CV';
            });
        });
    })();
    </script>
@endif
