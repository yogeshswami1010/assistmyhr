<?php

namespace App\Http\Requests;

use App\Job;
use App\Question;
use App\JobApplication;
use Illuminate\Support\Arr;
use App\GoogleCaptchaSetting;
use App\Rules\CheckApplication;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class FrontJobApplication extends CoreRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $google_captcha = GoogleCaptchaSetting::first();
        $job = Job::select('id', 'required_columns', 'section_visibility')->where('id', $this->job_id)->first();
        $applicationMail = JobApplication::with('status')->where('email', request()->email)->where('job_id', $this->job_id)->first();
        $requiredColumns = $job->required_columns;
        $sectionVisibility = $job->section_visibility;
        if(!is_null($applicationMail) && $applicationMail->status->status != 'rejected' ){
            $rules = [
                'full_name' => 'required',
                'email' => [
                    'required','email', new CheckApplication
                    // Rule::unique('job_applications')->where(function ($query) {
                    //     return $query->where('job_id', $this->job_id);
                    // })
                ],
                'phone' => 'nullable|string|max:30',
    
            ];
    
        }else{
            $rules = [
                'full_name' => 'required',
                'email' => 'required|email',
                'phone' => 'nullable|string|max:30',
    
            ];
        }

        $rules['resume'] = 'nullable|file|mimes:pdf,doc,docx|max:10240';

        if($google_captcha->status == 'active' && $google_captcha->job_apply_page == 'active')
        {
            $rules['recaptcha'] = 'required';
        }
        
        if($sectionVisibility){
            foreach ($sectionVisibility as $key => $section) {
                if ($section === 'yes') {
                    if ($key === 'profile_image') {
                        $rules = Arr::add($rules, 'photo', 'required|mimes:jpeg,jpg,png');
                    }
                    if ($key === 'resume') {
                        $rules = Arr::add($rules, 'resume', 'required|file|mimes:pdf,doc,docx|max:10240');
                    }
                    if ($key === 'terms_and_conditions') {
                        $rules = Arr::add($rules, 'term_agreement', 'required');
                    }
                }
            }
        }

        if ($requiredColumns['gender']) {
            $rules = Arr::add($rules, 'gender', 'required|in:male,female,others');
        }
        if ($requiredColumns['dob']) {
            $rules = Arr::add($rules, 'dob', 'required|date');
        }
        if ($requiredColumns['country']) {
            $rules = Arr::add($rules, 'country', 'required|integer|min:1');
            $rules = Arr::add($rules, 'state', 'required|integer|min:1');
            $rules = Arr::add($rules, 'city', 'required');
        }

        $this->get('answer');
        if(!empty($this->get('answer')))
        {
            foreach($this->get('answer') as $key => $value){

                $answer = Question::where('id', $key)->first();
                if($answer->required == 'yes')
                $rules["answer.{$key}"] = 'required';
            }
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'resume.mimes' => 'Please upload your CV as a PDF, DOC or DOCX file.',
            'resume.max' => 'Your CV must be 10 MB or smaller.',
            'answer.*.required' => 'This answer field is required.',
            'dob.required' => 'Date of Birth field is required.',
            'country.min' => 'Please select country.',
            'state.min' => 'Please select state.',
            'city.required' => 'Please enter city.',
            'email.unique' => 'You have already applied for this job with this email. Try different one.'
        ];
    }
}
