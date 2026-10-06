@extends('layouts.app') @push('head-script')
<link rel="stylesheet" href="{{ asset('assets/node_modules_files/dropify/dist/css/dropify.min.css') }}"> 
@endpush 
@section('content')
<div class="flex flex-col">
    <div class="w-full">
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-4">@lang('app.edit')</h4>

                <form id="editSettings" class="ajax-form space-y-4">
                    @csrf @method('PUT')

                    <div class="form-group">
                        <label for="company_name" class="form-label">@lang('modules.accountSettings.companyName')</label>
                        <input type="text" class="form-control" id="company_name" name="company_name" value="{{ $company->company_name }}">
                    </div>
                    <div class="form-group">
                        <label for="company_email" class="form-label">@lang('modules.accountSettings.companyEmail')</label>
                        <input type="email" class="form-control" id="company_email" name="company_email" value="{{ $company->company_email }}">
                    </div>
                    <div class="form-group">
                        <label for="company_phone" class="form-label">@lang('modules.accountSettings.companyPhone')</label>
                        <input type="tel" class="form-control" id="company_phone" name="company_phone" value="{{ $company->company_phone }}">
                    </div>
                    <div class="form-group">
                        <label for="website" class="form-label">@lang('modules.accountSettings.companyWebsite')</label>
                        <input type="text" class="form-control" id="website" name="website" value="{{ $company->website }}">
                    </div>
                    <div class="form-group">
                        <label for="logo" class="form-label">@lang('modules.accountSettings.companyLogo')</label>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <input type="file" id="input-file-now" name="logo" class="dropify" @if(is_null($company->logo))
                            data-default-file="{{ asset('logo-not-found.png') }}" @else data-default-file="{{ asset_url_local_s3('company-logo/'.$company->logo)
                            }}" @endif />
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label">@lang('modules.accountSettings.companyAddress')</label>
                        <textarea class="form-control" id="address" rows="5" name="address">{{ $company->address }}</textarea>
                    </div>

                    <div class="form-group">
                        <label for="status" class="form-label">@lang('app.status')</label>
                        <select name="status" id="status" class="form-control">
                            <option @if($company->status == 'active') selected @endif>Active</option>
                            <option @if($company->status == 'inactive') selected @endif>Inactive</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="show_in_frontend" class="form-label">@lang('modules.company.showFrontend')</label>
                        <select name="show_in_frontend" id="show_in_frontend" class="form-control">
                            <option @if($company->show_in_frontend == 'true') selected @endif value="true">@lang('app.yes')</option>
                            <option @if($company->show_in_frontend == 'false') selected @endif value="false">@lang('app.no')</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button" id="save-form" class="btn btn-success">
                            @lang('app.save')
                        </button>
                        <button type="reset" class="btn btn-secondary">@lang('app.reset')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
 @push('footer-script')
<script src="{{ asset('assets/node_modules_files/select2/dist/js/select2.full.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('assets/node_modules_files/bootstrap-select/bootstrap-select.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('assets/node_modules_files/dropify/dist/js/dropify.min.js') }}" type="text/javascript"></script>

<script>
    // For select 2
        $(".select2").select2();

        $('.dropify').dropify({
            messages: {
                default: '@lang("app.dragDrop")',
                replace: '@lang("app.dragDropReplace")',
                remove: '@lang("app.remove")',
                error: '@lang('app.largeFile')'
            }
        });

       

        $('#save-form').click(function () {
            $.easyAjax({
                url: '{{route("admin.company.update", $company->id)}}',
                container: '#editSettings',
                type: "POST",
                redirect: true,
                file: true
            })
        });

</script>




@endpush