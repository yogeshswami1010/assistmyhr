<?php

use App\Http\Controllers\Admin\AdminAiSettingsController;
use App\Http\Controllers\Admin\AdminAtsOverviewController;
use App\Http\Controllers\Admin\AdminAtsSyncController;
use App\Http\Controllers\Admin\AdminApplicationArchiveController;
use App\Http\Controllers\Admin\AdminApplicationStatusController;
use App\Http\Controllers\Admin\AdminCandidateMarketingController;
use App\Http\Controllers\Admin\AdminCompanyController;
use App\Http\Controllers\Admin\AdminCurrencyController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminDepartmentController;
use App\Http\Controllers\Admin\AdminDesignationController;
use App\Http\Controllers\Admin\AdminDocumentController;
use App\Http\Controllers\Admin\AdminJobAlertController;
use App\Http\Controllers\Admin\AdminInternalMessageController;
use App\Http\Controllers\Admin\AdminJobApplicationController;
use App\Http\Controllers\Admin\AdminJobCategoryController;
use App\Http\Controllers\Admin\AdminJobOfferQuestionController;
use App\Http\Controllers\Admin\AdminJobOnboardController;
use App\Http\Controllers\Admin\AdminJobsController;
use App\Http\Controllers\Admin\AdminJobTypeController;
use App\Http\Controllers\Admin\AdminLinkedInSettingsController;
use App\Http\Controllers\Admin\AdminLocationsController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AdminQuestionController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminSecurityController;
use App\Http\Controllers\Admin\AdminSkillsController;
use App\Http\Controllers\Admin\AdminSmsSettingsController;
use App\Http\Controllers\Admin\AdminSmtpSettingController;
use App\Http\Controllers\Admin\AdminStickyNotesController;
use App\Http\Controllers\Admin\AdminTeamController;
use App\Http\Controllers\Admin\AdminThemeSettingsController;
use App\Http\Controllers\Admin\AdminTodoItemController;
use App\Http\Controllers\Admin\AdminWorkExperienceController;
use App\Http\Controllers\Admin\AdminZoomMeetingController;
use App\Http\Controllers\Admin\AiSearchPromptController;
use App\Http\Controllers\Admin\ApplicantNoteController;
use App\Http\Controllers\Admin\TrashController;
use App\Http\Controllers\Admin\ApplicationSettingsController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CompanySettingsController;
use App\Http\Controllers\Admin\FooterSettingController;
use App\Http\Controllers\Admin\InterviewScheduleController;
use App\Http\Controllers\Admin\LanguageSettingsController;
use App\Http\Controllers\Admin\ManageRolePermissionController;
use App\Http\Controllers\Admin\StorageController;
use App\Http\Controllers\Admin\ZoomMeetingSettingController;
use App\Http\Controllers\Front\FrontJobOfferController;
use App\Http\Controllers\Front\FrontJobsController;
use App\Http\Controllers\Admin\AdminJobApiSettingsController;
use App\Http\Controllers\Admin\AdminConsortiumRegistrationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\VerifyMobileController;
use App\Http\Controllers\ZoomWebhookController;
use App\Http\Controllers\TelnyxWebhookController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\PublicWebsiteController;
use App\Http\Controllers\Admin\AdminJobClientNoteController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ── Zoom webhook ───────────────────────────────────────────────────────────
Route::post('/zoom-webhook', [ZoomWebhookController::class, 'index'])
    ->name('zoom-webhook');
Route::post('/telnyx-webhook', TelnyxWebhookController::class)
    ->name('telnyx-webhook');

// ── Front public job board ─────────────────────────────────────────────────
Route::get('/', [PublicWebsiteController::class, 'index'])->name('home');

Route::get('/jobs-widget/{integration}', \App\Http\Controllers\Front\JobsWidgetController::class)->middleware('throttle:60,1')->name('jobs.widget');

Route::name('jobs.')
    ->group(function () {
        Route::get('/jobs', [FrontJobsController::class, 'jobOpenings'])
            ->name('jobOpenings')
            ->middleware('disable-frontend');

        Route::post('/more-data',   [FrontJobsController::class, 'moreData'])->name('more-data');
        Route::post('/search-job',  [FrontJobsController::class, 'searchJob'])->name('search-job');

        Route::get('/job-offer/{slug?}',  [FrontJobOfferController::class, 'index'])->name('job-offer');
        Route::post('/save-offer',        [FrontJobOfferController::class, 'saveOffer'])->name('save-offer');

        Route::get('/job/{slug}/{location?}/{hash?}',      [FrontJobsController::class, 'jobDetail'])->name('jobDetail');
        Route::get('/jobapply/{slug}/{location?}/{hash?}', [FrontJobsController::class, 'jobApply'])->name('jobApply');

        Route::post('/job/saveApplication',         [FrontJobsController::class, 'saveApplication'])->name('saveApplication');
        Route::post('/job/fetch-country-state',     [FrontJobsController::class, 'fetchCountryState'])->name('fetchCountryState');
        Route::post('change-language/{code}',       [FrontJobsController::class, 'changeLanguage'])->name('changeLanguage');

        // ── Email check ──────────────────────────────────────────
        Route::post('/job/check-email', [FrontJobsController::class, 'checkApplicantEmail'])->name('checkApplicantEmail');

        Route::get('auth/callback/{provider}', [FrontJobsController::class, 'callback'])->name('linkedinCallback');
        Route::get('auth/redirect/{provider}', [FrontJobsController::class, 'redirect'])->name('linkedinRedirect');

        Route::get('job-alert',               [FrontJobsController::class, 'jobAlert'])->name('jobAlert');
        Route::post('save-job-alert',         [FrontJobsController::class, 'saveJobAlert'])->name('saveJobAlert');
        Route::post('disable-job-alert/{id}', [FrontJobsController::class, 'disableJobAlert'])->name('disableJobAlert');
    });

// ── Auth ───────────────────────────────────────────────────────────────────
Route::middleware(['signed', 'throttle:60,1'])->prefix('candidate-review')->name('client-reviews.')->group(function () {
    Route::get('{review:public_id}', [\App\Http\Controllers\ClientCandidateReviewController::class, 'show'])->name('show');
    Route::get('{review:public_id}/cv', [\App\Http\Controllers\ClientCandidateReviewController::class, 'resume'])->name('resume');
    Route::post('{review:public_id}/reply', [\App\Http\Controllers\ClientCandidateReviewController::class, 'reply'])->middleware('throttle:10,1')->name('reply');
});

Auth::routes(['register' => false]);
require __DIR__.'/saas.php';

// Old bookmarks go to login; purchase verification is no longer registered.
Route::redirect('/verify-purchase', '/login');

// ── Authenticated routes ───────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::post('mark-notification-read', [NotificationController::class, 'markAllRead'])->name('mark-notification-read');
    Route::post('mark-read',              [NotificationController::class, 'markRead'])->name('mark_single_notification_read');

    // ── Admin ──────────────────────────────────────────────────────────────
    Route::name('admin.')
        ->prefix('admin')
        ->group(function () { 

            Route::redirect('/', '/admin/dashboard');

            Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

            Route::get('job-applications/{id}/calls', [\App\Http\Controllers\Admin\CandidateCallController::class, 'index'])->name('candidate-calls.index');
            Route::post('job-applications/{id}/calls', [\App\Http\Controllers\Admin\CandidateCallController::class, 'start'])->middleware('throttle:10,1')->name('candidate-calls.start');
            Route::post('job-applications/{id}/calls/{callId}/finish', [\App\Http\Controllers\Admin\CandidateCallController::class, 'finish'])->name('candidate-calls.finish');
            Route::post('job-applications/{id}/calls/{callId}/process', [\App\Http\Controllers\Admin\CandidateCallController::class, 'process'])->middleware('throttle:10,1')->name('candidate-calls.process');

            Route::get('candidate-communications/templates', [\App\Http\Controllers\Admin\CandidateCommunicationController::class, 'templates'])->name('candidate-communications.templates');
            Route::post('candidate-communications/templates', [\App\Http\Controllers\Admin\CandidateCommunicationController::class, 'saveTemplate'])->name('candidate-communications.templates.save');
            Route::post('candidate-communications/preview', [\App\Http\Controllers\Admin\CandidateCommunicationController::class, 'preview'])->name('candidate-communications.preview');
            Route::post('candidate-communications/send', [\App\Http\Controllers\Admin\CandidateCommunicationController::class, 'send'])->middleware('throttle:120,1')->name('candidate-communications.send');
            Route::get('candidate-communications/{application}/email', [\App\Http\Controllers\Admin\CandidateCommunicationController::class, 'emailConversation'])->name('candidate-communications.email');
            Route::get('candidate-communications/{application}/email/unread', [\App\Http\Controllers\Admin\CandidateCommunicationController::class, 'emailUnread'])->name('candidate-communications.email.unread');
            Route::get('job-applications/{application}/client-reviews', [\App\Http\Controllers\Admin\CandidateClientReviewController::class, 'index'])->name('client-reviews.index');
            Route::get('job-applications/{application}/client-reviews/unread', [\App\Http\Controllers\Admin\CandidateClientReviewController::class, 'unread'])->name('client-reviews.unread');
            Route::post('job-applications/{application}/client-reviews', [\App\Http\Controllers\Admin\CandidateClientReviewController::class, 'send'])->middleware('throttle:20,1')->name('client-reviews.send');
            Route::post('job-applications/{application}/client-reviews/{review}/reply', [\App\Http\Controllers\Admin\CandidateClientReviewController::class, 'reply'])->middleware('throttle:20,1')->name('client-reviews.reply');
            Route::post('job-applications/{application}/client-reviews/{review}/messages/{message}/retry', [\App\Http\Controllers\Admin\CandidateClientReviewController::class, 'retry'])->middleware('throttle:20,1')->name('client-reviews.retry');
            Route::post('job-applications/{application}/client-reviews/{review}/revoke', [\App\Http\Controllers\Admin\CandidateClientReviewController::class, 'revoke'])->name('client-reviews.revoke');
            Route::get('ats-sync-state', [AdminAtsSyncController::class, 'state'])->name('ats-sync-state');
            Route::post('ats-presence-heartbeat', [AdminAtsSyncController::class, 'heartbeat'])->name('ats-presence-heartbeat');
            Route::get('ats-overview', [AdminAtsOverviewController::class, 'index'])->name('ats-overview.index');
            Route::get('trash', [TrashController::class, 'index'])->name('trash.index');
            Route::post('trash/{type}/{id}/restore', [TrashController::class, 'restore'])->name('trash.restore');
            Route::delete('trash/{type}/{id}', [TrashController::class, 'destroy'])->name('trash.destroy');
            // In routes/web.php (inside admin middleware group)
            Route::post('job-client-notes/store',           [AdminJobClientNoteController::class, 'store'])->name('job-client-notes.store');
            Route::post('job-client-notes/{id}/update',     [AdminJobClientNoteController::class, 'update'])->name('job-client-notes.update');
            Route::post('job-client-notes/{id}/destroy',    [AdminJobClientNoteController::class, 'destroy'])->name('job-client-notes.destroy');
            // Job Categories
            Route::get('job-categories/getSkills/{categoryId}', [AdminJobCategoryController::class, 'getSkills'])
                ->name('job-categories.getSkills');
            Route::post('job-categories/ai-generate', [AdminJobCategoryController::class, 'aiGenerateCategories'])
                ->name('job-categories.ai-generate');
            Route::resource('job-categories', AdminJobCategoryController::class);
            Route::get('ai-search', [AdminJobApplicationController::class, 'aiSearchPage'])->name('ai-search');
            Route::get('ai-search/results', [AdminJobApplicationController::class, 'aiSearchResults'])->name('ai-search.results');
            Route::post('ai-search/index-cvs', [AdminJobApplicationController::class, 'indexCvs'])->name('ai-search.index-cvs');
            Route::post('ai-search/parse-query', [AdminJobApplicationController::class, 'aiParseQuery'])->name('ai-search.parse-query');
            Route::post('ai-search/send-email', [AdminJobApplicationController::class, 'sendAiSearchEmail'])->name('ai-search.send-email');

            // ═══════════════════════════════════════════════════════════════
            // ═══ AI SAVED SEARCH PROMPTS ══════════════════════════════════
            // ═══════════════════════════════════════════════════════════════
            Route::get('ai-search/prompts', [AiSearchPromptController::class, 'index'])
                ->name('ai-search.prompts.index');
            Route::post('ai-search/prompts', [AiSearchPromptController::class, 'store'])
                ->name('ai-search.prompts.store');
            Route::post('ai-search/prompts/{id}/use', [AiSearchPromptController::class, 'use'])
                ->name('ai-search.prompts.use');
            Route::patch('ai-search/prompts/{id}/favorite', [AiSearchPromptController::class, 'toggleFavorite'])
                ->name('ai-search.prompts.favorite');
            Route::delete('ai-search/prompts/{id}', [AiSearchPromptController::class, 'destroy'])
                ->name('ai-search.prompts.destroy');
        
            // Questions
            Route::get('questions/data',         [AdminQuestionController::class, 'data'])->name('questions.data');
            Route::post('questions/ai-generate', [AdminQuestionController::class, 'aiGenerateQuestions'])->name('questions.ai-generate');
            Route::resource('questions', AdminQuestionController::class);

            // Todo Items
            Route::post('todo-items/update-todo-item',       [AdminTodoItemController::class, 'updateTodoItem'])->name('todo-items.updateTodoItem');
            Route::get('todo-items/board-data',              [AdminTodoItemController::class, 'boardData'])->name('todo-items.board-data');
            Route::post('todo-items/move-column',            [AdminTodoItemController::class, 'moveToColumn'])->name('todo-items.move-column');
            Route::post('todo-items/toggle-complete',        [AdminTodoItemController::class, 'toggleComplete'])->name('todo-items.toggle-complete');
            Route::post('todo-items/ai-generate-description',[AdminTodoItemController::class, 'aiGenerateDescription'])->name('todo-items.ai-generate-description');
            Route::resource('todo-items', AdminTodoItemController::class);

            // Job Alerts
            Route::get('job_alert/data', [AdminJobAlertController::class, 'data'])->name('job_alert.data');
            Route::resource('job_alert', AdminJobAlertController::class)->only(['index', 'destroy']);

            // Settings
            Route::prefix('settings')->group(function () {

                Route::get('jobs-api', [AdminJobApiSettingsController::class, 'index'])->name('job-api-settings.index');
                Route::post('jobs-api', [AdminJobApiSettingsController::class, 'store'])->name('job-api-settings.store');
                Route::post('jobs-api/{integration}/regenerate', [AdminJobApiSettingsController::class, 'regenerate'])->name('job-api-settings.regenerate');
                Route::put('jobs-api/{integration}', [AdminJobApiSettingsController::class, 'update'])->name('job-api-settings.update');
                Route::delete('jobs-api/{integration}', [AdminJobApiSettingsController::class, 'destroy'])->name('job-api-settings.destroy');

                Route::resource('settings', CompanySettingsController::class)->only(['edit', 'update', 'index']);
                Route::resource('application-setting', ApplicationSettingsController::class);

                Route::post('role-permission/assignAllPermission', [ManageRolePermissionController::class, 'assignAllPermission'])->name('role-permission.assignAllPermission');
                Route::post('role-permission/removeAllPermission', [ManageRolePermissionController::class, 'removeAllPermission'])->name('role-permission.removeAllPermission');
                Route::post('role-permission/assignRole',          [ManageRolePermissionController::class, 'assignRole'])->name('role-permission.assignRole');
                Route::post('role-permission/detachRole',          [ManageRolePermissionController::class, 'detachRole'])->name('role-permission.detachRole');
                Route::post('role-permission/storeRole',           [ManageRolePermissionController::class, 'storeRole'])->name('role-permission.storeRole');
                Route::post('role-permission/deleteRole',          [ManageRolePermissionController::class, 'deleteRole'])->name('role-permission.deleteRole');
                Route::get('role-permission/showMembers/{id}',     [ManageRolePermissionController::class, 'showMembers'])->name('role-permission.showMembers');
                Route::resource('role-permission', ManageRolePermissionController::class);

                Route::post('language-settings/change-language',          [LanguageSettingsController::class, 'changeLanguage'])->name('language-settings.change-language');
                Route::put('language-settings/change-language-status/{id}',[LanguageSettingsController::class, 'changeStatus'])->name('language-settings.changeStatus');
                Route::resource('language-settings', LanguageSettingsController::class);

                Route::resource('theme-settings', AdminThemeSettingsController::class);
                Route::post('theme-settings/disable-frontend', [AdminThemeSettingsController::class, 'disableFrontend'])->name('theme-settings.disableFrontend');

                Route::get('smtp-settings/sent-test-email', [AdminSmtpSettingController::class, 'sendTestEmail'])->name('smtp-settings.sendTestEmail');
                Route::resource('smtp-settings', AdminSmtpSettingController::class);

                Route::resource('sms-settings', AdminSmsSettingsController::class)->only(['index', 'update']);

                Route::get('ai-settings',                          [AdminAiSettingsController::class, 'index'])->name('ai-settings.index');
                Route::get('ai-settings/usage',                    [AdminAiSettingsController::class, 'usage'])->name('ai-settings.usage');
                Route::post('ai-settings',                         [AdminAiSettingsController::class, 'store'])->name('ai-settings.store');
                Route::post('ai-settings/test-key',                [AdminAiSettingsController::class, 'testKey'])->name('ai-settings.test-key');
                Route::post('ai-settings/{aiApiKey}/active',       [AdminAiSettingsController::class, 'toggleActive'])->name('ai-settings.toggle-active');
                Route::post('ai-settings/{aiApiKey}/clipboard-key',[AdminAiSettingsController::class, 'clipboardKey'])->name('ai-settings.clipboard-key');
                Route::put('ai-settings/{aiApiKey}',               [AdminAiSettingsController::class, 'update'])->name('ai-settings.update');
                Route::delete('ai-settings/{aiApiKey}',            [AdminAiSettingsController::class, 'destroy'])->name('ai-settings.destroy');

                Route::resource('linkedin-settings', AdminLinkedInSettingsController::class);

                Route::post('footer-settings/bulk-destroy', [FooterSettingController::class, 'bulkDestroy'])->name('footer-settings.bulk-destroy');
                Route::resource('footer-settings', FooterSettingController::class);
            });

            // Zoom Meetings
            Route::get('zoom-meeting/table',               [AdminZoomMeetingController::class, 'tableView'])->name('zoom-meeting.table-view');
            Route::get('zoom-meeting/data',                [AdminZoomMeetingController::class, 'data'])->name('zoom-meeting.data');
            Route::get('zoom-meeting/start-meeting/{id}',  [AdminZoomMeetingController::class, 'startMeeting'])->name('zoom-meeting.startMeeting');
            Route::post('zoom-meeting/cancel-meeting',     [AdminZoomMeetingController::class, 'cancelMeeting'])->name('zoom-meeting.cancelMeeting');
            Route::post('zoom-meeting/end-meeting',        [AdminZoomMeetingController::class, 'endMeeting'])->name('zoom-meeting.endMeeting');
            Route::post('zoom-meeting/updateOccurrence/{id}', [AdminZoomMeetingController::class, 'updateOccurrence'])->name('zoom-meeting.updateOccurrence');
            Route::resource('zoom-meeting', AdminZoomMeetingController::class);

            Route::resource('category',     CategoryController::class);
            Route::resource('zoom-setting', ZoomMeetingSettingController::class);
            Route::post('zoom-setting/change-status/{id}', [ZoomMeetingSettingController::class, 'changeStatus'])->name('zoom-setting.change-status');

            // Skills & Locations
            Route::resource('skills', AdminSkillsController::class);
            Route::post('skills/ai-generate',  [AdminSkillsController::class, 'aiGenerateSkills'])->name('skills.ai-generate');
            Route::post('skills/quick-create', [AdminSkillsController::class, 'quickCreate'])->name('skills.quick-create');
            Route::resource('locations', AdminLocationsController::class);

            // Jobs

            Route::get('jobs/data',                   [AdminJobsController::class, 'data'])->name('jobs.data');
            Route::post('jobs/bulk-destroy',           [AdminJobsController::class, 'bulkDestroy'])->name('jobs.bulkDestroy');
            Route::post('jobs/{job}/toggle-status',    [AdminJobsController::class, 'toggleStatus'])->name('jobs.toggleStatus');
            Route::post('jobs/refresh-date',           [AdminJobsController::class, 'refreshDate'])->name('jobs.refreshDate');
            Route::post('jobs/ai-generate-content',    [AdminJobsController::class, 'aiGenerateContent'])->name('jobs.ai-generate-content');
            Route::get('jobs/application-data',        [AdminJobsController::class, 'applicationData'])->name('jobs.applicationData');
            Route::post('jobs/send-emails',            [AdminJobsController::class, 'sendEmails'])->name('jobs.sendEmails');
            Route::get('jobs/send-email',              [AdminJobsController::class, 'sendEmail'])->name('jobs.sendEmail');
            Route::post('jobs/update-visibility',      [AdminJobsController::class, 'updateVisibility'])->name('jobs.updateVisibility');
            Route::resource('jobs', AdminJobsController::class);

            // Job Applications — SPECIFIC routes FIRST (no wildcards)
            Route::post('job-applications/rating-save/{id?}',              [AdminJobApplicationController::class, 'ratingSave'])->name('job-applications.rating-save');
            Route::get('job-applications/create-schedule/{id?}',           [AdminJobApplicationController::class, 'createSchedule'])->name('job-applications.create-schedule');
            Route::post('job-applications/store-schedule',                 [AdminJobApplicationController::class, 'storeSchedule'])->name('job-applications.store-schedule');
            Route::get('job-applications/question/{jobID}/{applicationId?}',[AdminJobApplicationController::class, 'jobQuestion'])->name('job-applications.question');
            Route::get('job-applications/export/{status}/{location}/{startDate}/{endDate}/{jobs}', [AdminJobApplicationController::class, 'export'])->name('job-applications.export');
            Route::get('job-applications/data',                            [AdminJobApplicationController::class, 'data'])->name('job-applications.data');
            Route::get('job-applications/load-more',                       [AdminJobApplicationController::class, 'loadMore'])->name('job-applications.loadMore');
            Route::get('job-applications/table-view',                      [AdminJobApplicationController::class, 'table'])->name('job-applications.table');
            Route::post('job-applications/updateIndex',                    [AdminJobApplicationController::class, 'updateIndex'])->name('job-applications.updateIndex');
            Route::post('job-applications/archive-job-application/{application}',   [AdminJobApplicationController::class, 'archiveJobApplication'])->name('job-applications.archiveJobApplication');
            Route::post('job-applications/unarchive-job-application/{application}', [AdminJobApplicationController::class, 'unarchiveJobApplication'])->name('job-applications.unarchiveJobApplication');
            Route::post('job-applications/add-skills/{applicationId}',    [AdminJobApplicationController::class, 'addSkills'])->name('job-applications.addSkills');
            Route::get('job-applications/skill-search',                   [AdminJobApplicationController::class, 'profileSkillSearch'])->name('job-applications.skill-search');
            Route::post('job-applications/ai-compare',                    [AdminJobApplicationController::class, 'aiCompare'])->name('job-applications.ai-compare');
            Route::post('job-applications/ai-update-status',              [AdminJobApplicationController::class, 'aiUpdateStatus'])->name('job-applications.ai-update-status');
            Route::get('job-applications/ai-compare-applicants',          [AdminJobApplicationController::class, 'aiCompareApplicants'])->name('job-applications.ai-compare-applicants');
            Route::post('job-applications/ai-generate-cover-letter',      [AdminJobApplicationController::class, 'aiGenerateCoverLetterAndDetails'])->name('job-applications.ai-generate-cover-letter');
            Route::post('job-applications/ai-parse-resume',               [AdminJobApplicationController::class, 'aiParseResumeFromUpload'])->name('job-applications.ai-parse-resume');

            // CASCADE routes — MUST be before Route::resource (no wildcards)
            Route::get('job-applications/get-jobs',                       [AdminJobApplicationController::class, 'getJobs'])->name('job-applications.get-jobs');
            Route::get('job-applications/get-locations',                    [AdminJobApplicationController::class, 'getLocations'])->name('job-applications.get-locations');
            Route::get('job-applications/stage-counts',                   [AdminJobApplicationController::class, 'stageCounts'])->name('job-applications.stage-counts');
            Route::post('job-applications/bulk-status-update',            [AdminJobApplicationController::class, 'bulkStatusUpdate'])->name('job-applications.bulk-status-update');
            Route::post('job-applications/bulk-restore-knockout',         [AdminJobApplicationController::class, 'bulkRestoreKnockout'])->name('job-applications.bulk-restore-knockout');
            Route::get('job-applications/job-statuses',                   [AdminJobApplicationController::class, 'jobStatuses'])->name('job-applications.job-statuses');
            Route::post('job-applications/{application}/stages',         [AdminJobApplicationController::class, 'storeProfileStage'])->name('job-applications.stages.store');
            Route::get('job-applications/{id}/profile-tab/{tab}',         [AdminJobApplicationController::class, 'profileTab'])->name('job-applications.profile-tab');
            Route::post('job-applications/bulk-parse-resume',             [AdminJobApplicationController::class, 'bulkParseResume'])->name('job-applications.bulk-parse-resume');
            Route::post('job-applications/{id}/send-sms',                 [AdminJobApplicationController::class, 'sendSms'])->name('job-applications.send-sms');
            Route::post('job-applications/{id}/temp-staffing',           [AdminJobApplicationController::class, 'toggleTempStaffing'])->name('job-applications.temp-staffing');
            Route::post('job-applications/{id}/update-resume',            [AdminJobApplicationController::class, 'updateResume'])->name('job-applications.update-resume');
            Route::get('job-applications/{id}/resume',                    [AdminJobApplicationController::class, 'viewResume'])->name('job-applications.resume.view');
            Route::get('job-applications/resume-history/{history}',       [AdminJobApplicationController::class, 'viewResumeHistory'])->name('job-applications.resume-history.view');

            // Resource route LAST — has wildcard {job_application} that catches everything
            Route::resource('job-applications', AdminJobApplicationController::class);

            // Routes with {id} parameter — AFTER resource
            Route::post('job-applications/{id}/parse-skills',             [AdminJobApplicationController::class, 'parseSkills'])->name('job-applications.parse-skills');
            Route::post('job-applications/{id}/assign-job',               [AdminJobApplicationController::class, 'assignJob'])->name('job-applications.assign-job');
            Route::post('job-applications/{id}/update-info', [AdminJobApplicationController::class, 'updateBasicInfo'])->name('job-applications.update-basic-info');
            Route::post('job-applications/{id}/toggle-marketing',         [AdminJobApplicationController::class, 'toggleMarketing'])->name('job-applications.toggle-marketing');
            Route::post('job-applications/{id}/update-marketing-label',   [AdminJobApplicationController::class, 'updateMarketingLabel'])->name('job-applications.update-marketing-label');

            // Other job-applications routes
            Route::get('job-applications/get-companies-by-location', [AdminJobApplicationController::class, 'getCompaniesByLocation'])->name('job-applications.get-companies-by-location');
            Route::get('job-applications/get-companies', [AdminJobApplicationController::class, 'getCompanies'])->name('job-applications.get-companies');
            Route::post('job-applications/bulk-parse-all-cvs', [AdminJobApplicationController::class, 'bulkParseAllCvs'])->name('job-applications.bulk-parse-all-cvs');
            Route::post('job-applications/{id}/update-marketing-label',   [AdminJobApplicationController::class, 'updateMarketingLabel'])->name('job-applications.update-marketing-label');
            Route::get('job-applications/search-users-mention', [AdminJobApplicationController::class, 'searchUsersForMention'])->name('job-applications.search-users-mention');

            // Candidate Marketing
            Route::get('candidate-marketing/data',          [AdminCandidateMarketingController::class, 'data'])->name('candidate-marketing.data');
            Route::post('candidate-marketing/{id}/remove',  [AdminCandidateMarketingController::class, 'remove'])->name('candidate-marketing.remove');
            Route::resource('candidate-marketing', AdminCandidateMarketingController::class)->only(['index']);
            Route::get('candidate-marketing/{id}/show', [AdminCandidateMarketingController::class, 'show'])->name('candidate-marketing.show');

            // Archive / Candidate Database cascade routes
            Route::get('applications-archive/get-locations', [AdminApplicationArchiveController::class, 'getLocations'])
                ->name('applications-archive.get-locations');

            // Applications Archive
            Route::get('applications-archive/data',            [AdminApplicationArchiveController::class, 'data'])->name('applications-archive.data');
            Route::get('applications-archive/export/{skill}',  [AdminApplicationArchiveController::class, 'export'])->name('applications-archive.export');
            Route::resource('applications-archive', AdminApplicationArchiveController::class);
            Route::post('applications-archive{id}',            [AdminApplicationArchiveController::class, 'deleteRecords'])->name('applications-archive.deleteRecords');

            // Job Onboard
            Route::get('job-onboard/data',              [AdminJobOnboardController::class, 'data'])->name('job-onboard.data');
            Route::get('job-onboard/send-offer/{id?}',  [AdminJobOnboardController::class, 'sendOffer'])->name('job-onboard.send-offer');
            Route::get('job-onboard/update-status/{id?}',[AdminJobOnboardController::class, 'updateStatus'])->name('job-onboard.update-status');
            Route::resource('job-onboard', AdminJobOnboardController::class);

            // Job Onboard Questions
            Route::get('job-onboard-questions/data', [AdminJobOfferQuestionController::class, 'data'])->name('job-onboard-questions.data');
            Route::resource('job-onboard-questions', AdminJobOfferQuestionController::class);

            Route::post('profile/signature-image', [AdminProfileController::class, 'uploadSignatureImage'])->name('profile.signature-image');
            Route::resource('profile',              AdminProfileController::class);
            Route::resource('application-status',   AdminApplicationStatusController::class);

            // Interview Schedule
            Route::get('interview-schedule/data',                       [InterviewScheduleController::class, 'data'])->name('interview-schedule.data');
            Route::get('interview-schedule/table-view',                 [InterviewScheduleController::class, 'table'])->name('interview-schedule.table-view');
            Route::post('interview-schedule/change-status',             [InterviewScheduleController::class, 'changeStatus'])->name('interview-schedule.change-status');
            Route::post('interview-schedule/change-status-multiple',    [InterviewScheduleController::class, 'changeStatusMultiple'])->name('interview-schedule.change-status-multiple');
            Route::get('interview-schedule/notify/{id}/{type}',         [InterviewScheduleController::class, 'notify'])->name('interview-schedule.notify');
            Route::get('interview-schedule/response/{id}/{type}',       [InterviewScheduleController::class, 'employeeResponse'])->name('interview-schedule.response');
            Route::resource('interview-schedule', InterviewScheduleController::class);

            // Team & Company
            Route::get('temp-staffing', [AdminConsortiumRegistrationController::class, 'tempStaffingIndex'])->name('temp-staffing.index');
            Route::post('consortium-registrations/{registration}/temp-staffing', [AdminConsortiumRegistrationController::class, 'toggleTempStaffing'])->name('consortium-registrations.temp-staffing');
            Route::get('consortium-registrations', [AdminConsortiumRegistrationController::class, 'index'])->name('consortium-registrations.index');
            Route::delete('consortium-registrations/{registration}', [AdminConsortiumRegistrationController::class, 'destroy'])->name('consortium-registrations.destroy');
            Route::post('consortium-registrations/{registration}/move-to-job', [AdminConsortiumRegistrationController::class, 'moveToJob'])->name('consortium-registrations.move-to-job');
            Route::get('consortium-registrations/{registration}', [AdminConsortiumRegistrationController::class, 'show'])->name('consortium-registrations.show');
            Route::get('consortium-registrations/{registration}/resume', [AdminConsortiumRegistrationController::class, 'resume'])->name('consortium-registrations.resume');
            Route::get('internal-messages', [AdminInternalMessageController::class, 'index'])->name('internal-messages.index');
            Route::get('internal-messages/conversation/{recipient}', [AdminInternalMessageController::class, 'conversation'])->name('internal-messages.conversation');
            Route::post('internal-messages', [AdminInternalMessageController::class, 'store'])->name('internal-messages.store');
            Route::get('team/data',          [AdminTeamController::class, 'data'])->name('team.data');
            Route::post('team/change-role',  [AdminTeamController::class, 'changeRole'])->name('team.changeRole');
            Route::resource('team',    AdminTeamController::class);
            Route::get('company/data', [AdminCompanyController::class, 'data'])->name('company.data');
            Route::resource('company', AdminCompanyController::class);

            // Currency
            Route::post('currency-settings/set-default',   [AdminCurrencyController::class, 'setDefault'])->name('currency-settings.set-default');
            Route::post('currency-settings/bulk-destroy',  [AdminCurrencyController::class, 'bulkDestroy'])->name('currency-settings.bulk-destroy');
            Route::post('currency-settings/ai-generate',   [AdminCurrencyController::class, 'aiGenerateCurrencies'])->name('currency-settings.ai-generate');
            Route::resource('currency-settings', AdminCurrencyController::class);

            // Security & Storage
            Route::get('security-setting/verifyCaptcha', [AdminSecurityController::class, 'verifyCaptcha'])->name('security-setting.verifyCaptcha');
            Route::resource('security-setting', AdminSecurityController::class)->only(['index', 'update']);
            Route::post('storage-settings/test-connection', [StorageController::class, 'testConnection'])->name('storage-settings.test-connection');
            Route::resource('storage-settings', StorageController::class);

            // Misc admin resources
            Route::resource('applicant-note',   ApplicantNoteController::class);
            Route::resource('sticky-note',      AdminStickyNotesController::class);
            Route::resource('departments',      AdminDepartmentController::class);
            Route::resource('job-type',         AdminJobTypeController::class);
            Route::resource('work-experience',  AdminWorkExperienceController::class);
            Route::resource('designations',     AdminDesignationController::class);

            // Documents
            Route::get('documents/data',                        [AdminDocumentController::class, 'data'])->name('documents.data');
            Route::get('documents/download-document/{document}',[AdminDocumentController::class, 'downloadDoc'])->name('documents.downloadDoc');
            Route::resource('documents', AdminDocumentController::class);

            Route::resource('report', AdminReportController::class);
        });

    // Mobile verification
    Route::get('change-mobile',             [VerifyMobileController::class, 'changeMobile'])->name('changeMobile');
    Route::post('send-otp-code',            [VerifyMobileController::class, 'sendVerificationCode'])->name('sendOtpCode');
    Route::post('send-otp-code/account',    [VerifyMobileController::class, 'sendVerificationCode'])->name('sendOtpCode.account');
    Route::post('verify-otp-phone',         [VerifyMobileController::class, 'verifyOtpCode'])->name('verifyOtpCode');
    Route::post('verify-otp-phone/account', [VerifyMobileController::class, 'verifyOtpCode'])->name('verifyOtpCode.account');
    Route::get('remove-session',            [VerifyMobileController::class, 'removeSession'])->name('removeSession');
});
Route::post('candidate-email-webhook', [\App\Http\Controllers\CandidateEmailWebhookController::class, '__invoke'])->name('candidate-email-webhook');
