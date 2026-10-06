@extends('layouts.offer')

@section('header-text')
    <h1 class="hidden sm:block">{{ ucwords($job->title) }}</h1>
    <h5 class="hidden sm:block"><i class="icon-map-pin"></i> {{ ucwords($offer->applications->location->location) }}</h5>
@endsection
@push('head-style')
    <style>
        .signature-pad {
            /*position: absolute;*/
            left: 0;
            top: 0;
            width:100%;
            height: 100%;
            background-color: white;
        }
        .hide-box{
            display: none;
        }
        .file-bg {
            height: 150px;
            overflow: hidden;
            position: relative;
        }
        .file-bg .overlay-file-box {
            opacity: .9;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            text-align: center;
        }
        .card-img-top {
            border-top-right-radius: calc(.25rem - 1px);
            border-top-left-radius: calc(.25rem - 1px);
        }
        .carousel-inner>.item>a>img, .carousel-inner>.item>img, .img-responsive, .thumbnail a>img, .thumbnail>img {
            display: block;
            max-width: 100%;
            height: auto;
        }
        .card{
            border: 1px solid #ccc;
        }
        .card-block {
            -webkit-box-flex: 1;
            -webkit-flex: 1 1 auto;
            -ms-flex: 1 1 auto;
            flex: 1 1 auto;
            padding: 1.25rem;
        }
        .card-title {
            margin-bottom: .75rem;
        }
    </style>
@endpush
@section('content')

    <div class="container mx-auto px-4">
        <div class="flex flex-wrap -mx-4">
            <div class="w-full text-xs pt-12 pb-4 border-b border-gray-200 mb-5">
                <h4 class="text-primary mt-5 text-xl font-semibold">@lang('app.jobOffer')</h4>
            </div>


            <div class="w-full" id="">
                <div class="flex flex-wrap -mx-4">
                    <div class="w-full px-4">
                        @if($job->company->show_in_frontend == 'true')
                            <p>@lang('app.by') {{ ucwords($job->company->company_name) }}</p>
                        @endif
                    </div>

                    <div class="w-full px-4">
                        <h4 class="text-primary mt-5 text-xl font-semibold">@lang('app.candidate') @lang('app.detail')</h4>
                    </div>
                    <div class="w-full md:w-1/2 px-4">
                        <h6 class="font-semibold mb-2">@lang('app.name')</h6>
                        <p>{{ ucwords($offer->applications->full_name) }}</p>
                    </div>
                    <div class="w-full md:w-1/2 px-4">
                        <h6 class="font-semibold mb-2">@lang('app.email')</h6>
                        <p>{{ ucwords($offer->applications->email) }}</p>
                    </div>

                    <div class="w-full px-4">
                        <h4 class="text-primary mt-5 text-xl font-semibold">@lang('app.job') @lang('app.detail')</h4>
                    </div>
                    <div class="w-full px-4">
                        <h6 class="font-semibold mb-2"> @lang('app.title') </h6>
                        <p>{{ ucwords($job->title) }}</p>
                    </div>
                    <div class="w-full px-4">
                        <h6 class="font-semibold mb-2">@lang('app.requirement') </h6>
                        <p>{!! $job->job_requirement !!}</p>
                    </div>
                    <div class="w-full md:w-1/2 px-4">
                        <h6 class="font-semibold mb-2">@lang('app.location') </h6>
                        <p>{{ ucwords($offer->applications->location->location) }}</p>
                    </div>
                    <div class="w-full md:w-1/2 px-4">
                        <h6 class="font-semibold mb-2">@lang('app.category') </h6>
                        <p>{{ ucwords($job->category->name) }}</p>
                    </div>
                    <div class="w-full px-4">
                        <h4 class="text-primary mt-5 text-xl font-semibold">@lang('app.offer') @lang('app.detail')</h4>
                    </div>
                    <div class="w-full md:w-1/2 px-4">
                        <h6 class="font-semibold mb-2">@lang('app.designation') </h6>
                        <span>{{ ucwords($offer->department->name) }}</span> -> <span>{{ ucwords($offer->designation->name) }}</span>
                    </div>
                    <div class="w-full md:w-1/2 px-4">
                        <h6 class="font-semibold mb-2">@lang('app.salaryOfferedPerMonth') </h6>
                        <p>{{ $offer->currency ? $offer->currency->currency_symbol : '$'}} {{ $offer->salary_offered }}</p>
                    </div>
                    <div class="w-full md:w-1/2 px-4">
                         <h6 class="font-semibold mb-2">@lang('app.joiningDate') </h6>
                        <p>{{ $offer->joining_date->format('d M Y') }}</p>
                    </div>
                    <div class="w-full md:w-1/2 px-4">
                        <h6 class="font-semibold mb-2">@lang('app.acceptLastDate') </h6>
                        <p>{{ $offer->accept_last_date->format('d M Y') }}</p>
                    </div>
                    <div class="w-full px-4">
                        @if(!empty($offer->files) && count($offer->files) > 0 ) 
                        <h4 class="text-primary mt-5 text-xl font-semibold">@lang('app.files')</h4>
                        <div class="flex flex-wrap -mx-4">
                            @forelse($offer->files as $file)
                                <div class="w-full md:w-1/4 px-4 mb-4">
                                <a target="_blank" href="{{ asset_url('onboard-files/'.$file->hash_name) }}">
                                        <div class="border border-gray-300 rounded-lg overflow-hidden bg-white">
                                            <div class="file-bg">
                                                <div class="overlay-file-box">
                                                    <div class="user-content">
                                                        @if(array_key_exists($file->ext, $imageExt))
                                                            <img class="w-full h-auto rounded-t-lg" src="{{ asset_url('onboard-files/'.$file->hash_name) }}" >
                                                        @elseif(isset($fileExt[$file->ext]))
                                                            <i class="fa {{ $fileExt[$file->ext]}}" style="font-size: -webkit-xxx-large; padding-top: 65px;"></i>
                                                        @else
                                                            <i class="fa fa-file" style="font-size: -webkit-xxx-large; padding-top: 65px;"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="p-4">
                                                <h6 class="font-semibold mb-2">{{ $file->name }}</h6>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @empty

                            @endforelse
                        </div>
                        @endif

                        @if($offer->hired_status == 'offered')
                        <div id="offeredBox">
                            <div class="my-8 text-center">
                                <a onclick="actionJobOffer('accept')" class="bg-green-600 text-white px-4 py-3 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors mr-4 inline-block"
                                   href="javascript:;">@lang('app.accept')</a>
                                <a onclick="actionJobOffer('rejected')" class="bg-red-600 text-white px-4 py-3 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors inline-block"
                                   href="javascript:;">@lang('app.reject')</a>
                            </div>
                            
                           <div class="w-full md:w-2/3 mb-4"  id="signBox">
                            <form id="createForm" method="POST">
                        @forelse($offer->onboardQuestion as $question)
                        <div class="mb-4">
                            <label class="block mb-1" for="answer[{{ $question->id}}]">{{ $question->question }}</label><br>
                            @if($question->type == 'text')
                            <input class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent answer"  type="text" id="answer[{{ $question->id }}]" name="answer[{{ $question->id }}]" placeholder="@lang('modules.front.yourAnswer')">
                            @else
                            {{-- <input type="hidden" name="answer[{{ $question->id}}]" value = ""> --}}
                            <input class="select-file answer" accept=".png,.jpg,.jpeg,.pdf,.doc,.docx,.xls,.xlsx,.rtf" type="file"  id="answer[{{ $question->id }}]" name="answer[{{ $question->id }}]"><br>
                            <span>@lang('modules.front.resumeFileType')</span>
                        @endif
                        </div>
                    @empty
                    @endforelse
                </form>
                                <div class="mb-4">
                                    <label class="block mb-1">@lang('app.signature')</label>
                                    <div class="wrapper border border-gray-300 rounded-md p-2">
                                        <canvas id="signature-pad" class="signature-pad"></canvas>
                                    </div>
                                </div>
                                <div class="my-8 text-center">
                                    <button id="undo" class="bg-primary text-white px-3 py-1.5 rounded-md hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-colors text-sm mr-2"
                                    >@lang('app.undo')</button>
                                    <button id="clear" class="bg-red-600 text-white px-3 py-1.5 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors text-sm"
                                    >@lang('app.clear')</button>
                                </div>

                            </div>
                            <div class="w-full mb-4 hidden" id="reasonBox">
                                <div class="mb-4">
                                    <label class="block mb-1">@lang('app.reason')</label>
                                    <textarea class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" rows="5" id="reason"></textarea>

                                </div>
                            </div>

                            <div class="my-8 text-center saveButton hidden" >
                                <button class="bg-green-600 text-white px-4 py-3 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors save-form"
                                        href="javascript:;">@lang('app.submit')</button>
                            </div>
                        </div>


                        @elseif($offer->hired_status == 'accepted')
                            <div class="w-full" >
                                <img class="w-full h-auto" src="{{ asset_url('offer/sign/'.$offer->sign) }}" alt="Candidate Signiture">
                            </div>
                           <div class="p-4 rounded-md bg-green-50 text-green-800 border border-green-200 my-24" role="alert">
                                 @lang('messages.acceptedOffer')
                           </div>
                        @else
                            <div class="p-4 rounded-md bg-red-50 text-red-800 border border-red-200 my-24" role="alert">
                                @lang('messages.rejectedOffer')
                            </div>
                        @endif
                    </div>

                </div>

            </div>

        </div>
    </div>

@endsection

@push('footer-script')
<script src="{{ asset('assets/plugins/jQueryUI/jquery-ui.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@2.3.2/dist/signature_pad.min.js"></script>
    <script>
        actionType = '';
        code = '{{ $offer->offer_code }}';
        $(function () {
            var canvas = document.getElementById('signature-pad');

// Adjust canvas coordinate space taking into account pixel ratio,
// to make it look crisp on mobile devices.
// This also causes canvas to be cleared.
            function resizeCanvas() {
                // When zoomed out to less than 100%, for some very strange reason,
                // some browsers report devicePixelRatio as less than 1
                // and only part of the canvas is cleared then.
                var ratio =  Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext("2d").scale(ratio, ratio);
            }

            window.onresize = resizeCanvas;
            resizeCanvas();

            signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255, 255, 255)' // necessary for saving image as JPEG; can be removed is only saving as PNG or SVG
            });

            document.getElementById('clear').addEventListener('click', function (e) {
                e.preventDefault();
                signaturePad.clear();
            });

            document.getElementById('undo').addEventListener('click', function (e) {
                e.preventDefault();
                var data = signaturePad.toData();
                if (data) {
                    data.pop(); // remove the last dot or line
                    signaturePad.fromData(data);
                }
            });

            $('#signBox').hide();
        });

       function actionJobOffer (type) {
           actionType = type;
           console.log(actionType);
           if(actionType == 'accept'){
               $('#signBox').show();
               $('#reasonBox').hide();
           }
           else{
               $( "#clear" ).click();
               $('#signBox').hide();
               $('#reasonBox').show();
           }
           $('.saveButton').show();
        }

       $('.save-form').click(function () {
        
          var signature = '';
          var reason = $('#reason').val();
        
           if(actionType == 'accept'){
          var inputTypes = [];

          @forelse($offer->onboardQuestion as $question)
          var ids = "{{ $question->id }}";
          inputTypes.push($("input[name='answer["+ids+"]']").prop('value'));
          @empty    
          @endforelse
        
          $.each(inputTypes, function(key,val) {
            
            if(val == '' ){
                return $.showToastr("Please provide data first.", 'error');
            }
        });

                signature = signaturePad.toDataURL('image/png');

               if (signaturePad.isEmpty()) {
                   return $.showToastr("Please provide a signature first.", 'error');
               }
           }
           else{
               if (reason === '' || reason === null || reason === undefined) {
                   return $.showToastr("Please provide a reason first.", 'error');
               }
           }

           $.easyAjax({
               url: '{{tenant_route('jobs.save-offer')}}',
               container: '#createForm',
               type: "POST",
               file: true,
               data: {
                   code: code,
                   type: actionType,
                   signature:signature,
                   reason:reason,
                   _token: '{{ csrf_token() }}'
               },
               success: function(data){
                   if(actionType == 'accept'){
                        var text = '<div class="p-4 rounded-md border bg-green-50 text-green-800 border-green-200 my-6" role="alert"> @lang('messages.acceptedOffer') </div>';
                   }
                   else{
                        var text = '<div class="p-4 rounded-md border bg-red-50 text-red-800 border-red-200 my-6" role="alert"> @lang('messages.rejectedOffer') </div>';
                   }

                    $('#offeredBox').html(text);
               }
           });
       });

    </script>
@endpush