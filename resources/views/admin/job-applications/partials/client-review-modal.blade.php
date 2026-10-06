@if($user->cans('edit_job_applications'))
<style>
    #ja-client-review-modal{border:1px solid #E2E8F0;border-radius:18px;padding:0;margin:auto;width:calc(100% - 32px);max-width:600px;max-height:calc(100dvh - 40px);background:#fff;color:#17253E;box-shadow:0 24px 70px rgba(15,31,61,.25);overflow:hidden;font-family:'Plus Jakarta Sans',sans-serif}
    #ja-client-review-modal[open]{display:flex;flex-direction:column}
    #ja-client-review-modal::backdrop{background:rgba(15,31,61,.55)}
    .ja-review-modal-header{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 22px;border-bottom:1px solid #E2E8F0;flex-shrink:0}
    .ja-review-modal-header h2{font-size:16px;font-weight:700;margin:0}
    .ja-review-modal-close{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border:1px solid #E2E8F0;border-radius:8px;background:#F8FAFC;color:#64748B;cursor:pointer;flex-shrink:0}
    .ja-review-modal-form{padding:20px 22px;overflow-y:auto;min-height:0}
    .ja-review-modal-form .ja-review-actions{justify-content:flex-end;margin-top:16px}
    .ja-review-modal-note{font-size:11px;line-height:1.6;color:#64748B;margin:12px 0 0}
    .ja-review-client-message{min-height:100px;resize:vertical;font-size:13px;line-height:1.6}
</style>
<dialog id="ja-client-review-modal" aria-labelledby="ja-client-review-modal-title">
    <div class="ja-review-modal-header">
        <h2 id="ja-client-review-modal-title">Send profile to client</h2>
        <button type="button" class="ja-review-modal-close" data-client-review-close aria-label="Close send profile popup"><i class="fa fa-times" aria-hidden="true"></i></button>
    </div>
    <form class="ja-review-modal-form" data-client-review-send>
        <label class="ja-review-label" for="ja-review-email">Client email</label>
        <input id="ja-review-email" class="ja-review-field" name="client_email" type="email" maxlength="255" required placeholder="client@company.com" autofocus>
        <label class="ja-review-label" for="ja-review-subject">Subject</label>
        <input id="ja-review-subject" class="ja-review-field" name="subject" type="text" maxlength="191" required value="Candidate for review: {{ mb_substr($application->full_name, 0, 160) }}">
        <label class="ja-review-label" style="margin-bottom:7px" for="ja-review-compose">Email message</label>
        @include('admin.job-applications.partials.client-review-editor', ['editorId' => 'ja-review-compose'])
        <label class="ja-review-label" style="margin-top:16px" for="ja-review-client-message">Client message</label>
        <textarea id="ja-review-client-message" class="ja-review-field ja-review-client-message" name="client_message" rows="4" maxlength="10000" placeholder="Add instructions or information for the client to see above the review form…"></textarea>
        <p class="ja-review-modal-note" style="margin-top:0">The client message appears above Reply with your review on the private candidate review page.</p>
        <p class="ja-review-modal-note">The email includes your message and a private CV review button. The link expires in 30 days.</p>
        <div class="ja-review-feedback" role="status" aria-live="polite"></div>
        <div class="ja-review-actions">
            <button type="button" class="ja-pdf-btn" data-client-review-close>Cancel</button>
            <button type="submit" class="ja-pdf-btn ja-pdf-btn-primary"><i class="fa fa-paper-plane-o" aria-hidden="true"></i> Send to client</button>
        </div>
    </form>
</dialog>
@endif
