<?php

namespace App\Http\Controllers\Front;

use App\ConsortiumRegistration;
use App\Helper\Files;
use App\Http\Controllers\Controller;
use App\Services\ResumePdfConverter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class ConsortiumRegistrationController extends Controller
{
    public function create(Request $request)
    {
        $embedAction = $request->boolean('embed') ? $this->embedAction() : null;
        $response = response()->view('front.consortium-registration', compact('embedAction'))
            ->header('Cache-Control', 'private, no-store');

        if ($embedAction) {
            $response->header('Content-Security-Policy', "frame-ancestors 'self' https://consortiumstaffing.ca https://www.consortiumstaffing.ca");
            $response->header('Referrer-Policy', 'no-referrer');
        }

        return $response;
    }

    private function embedAction(): string
    {
        return URL::temporarySignedRoute('consortium-registration.embed', now()->addHours(4), tenant_parameters(), false);
    }

    public function token(Request $request): JsonResponse
    {
        return response()->json($request->boolean('embed')
            ? ['action' => $this->embedAction()]
            : ['token' => $request->session()->token()])
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request)
    {
        $this->persist($request);
        if ($request->expectsJson()) {
            $request->session()->flash('registration_success', true);

            return response()->json(['redirect' => tenant_route('consortium-registration.create', [], false)]);
        }

        return back()->with('registration_success', true);
    }

    public function storeApi(Request $request): JsonResponse
    {
        try {
            $registration = $this->persist($request);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return $this->cors(response()->json([
                'status' => 'validation_error',
                'message' => 'Please correct the highlighted information.',
                'errors' => $exception->errors(),
            ], 422), $request);
        }

        return $this->cors(response()->json([
            'status' => 'success',
            'message' => 'Registration submitted successfully.',
            'registration_id' => $registration->id,
        ]), $request);
    }

    public function storeEmbed(Request $request): JsonResponse
    {
        $this->persist($request);

        return response()->json(['submitted' => true]);
    }

    private function persist(Request $request): ConsortiumRegistration
    {
        abort_if($request->filled('website'), 422);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:40'],
            'gender' => ['nullable', 'string', 'max:40'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'street_address' => ['nullable', 'string', 'max:190'],
            'city' => ['required', 'string', 'max:100'],
            'eligible_to_work_canada' => ['required', 'boolean'],
            'status_in_canada' => ['nullable', 'string', 'max:100'],
            'preferred_job_type' => ['nullable', 'string', 'max:100'],
            'commute_mode' => ['nullable', 'string', 'max:100'],
            'years_experience' => ['nullable', 'numeric', 'min:0', 'max:80'],
            'industry_expertise' => ['nullable', 'string', 'max:190'],
            'available_weekends' => ['required', 'boolean'],
            'available_night_shifts' => ['required', 'boolean'],
            'referral_source' => ['nullable', 'string', 'max:100'],
            'additional_information' => ['nullable', 'string', 'max:5000'],
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx,rtf', 'max:10240'],
            'information_certified' => ['accepted'],
            'agreement_accepted' => ['accepted'],
            'sms_consent' => ['nullable', 'accepted'],
        ]);

        unset($data['resume']);
        $data['information_certified'] = true;
        $data['agreement_accepted'] = true;
        $data['sms_consent'] = $request->boolean('sms_consent');

        if ($request->hasFile('resume')) {
            $converter = app(ResumePdfConverter::class);
            $resume = $request->file('resume');

            try {
                $resume = $converter->convert($resume);
                // Converted Word files expose a .pdf client name, ensuring the
                // stored hash, download name, and ATS preview all use PDF.
                $data['resume_original_name'] = $resume->getClientOriginalName();
                $data['resume_file'] = Files::uploadLocalOrS3($resume, 'registration-resumes');
            } catch (\Throwable $exception) {
                report($exception);
                throw ValidationException::withMessages([
                    'resume' => 'The Word resume could not be converted to PDF. Please upload a valid DOC, DOCX, or PDF file.',
                ]);
            } finally {
                $converter->cleanup($resume);
            }
        }

        return ConsortiumRegistration::create($data);
    }

    private function cors(JsonResponse $response, Request $request): JsonResponse
    {
        $origin = $request->headers->get('Origin');
        $allowed = ['https://consortiumstaffing.ca', 'https://www.consortiumstaffing.ca'];
        if (in_array($origin, $allowed, true)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Vary', 'Origin');
        }
        return $response;
    }
}
