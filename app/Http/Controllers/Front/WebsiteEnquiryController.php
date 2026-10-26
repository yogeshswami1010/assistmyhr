<?php

namespace App\Http\Controllers\Front;

use App\Saas\WebsiteEnquiry;
use App\Services\WebsiteEnquiryNotifier;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class WebsiteEnquiryController extends Controller
{
    public function store(Request $request, WebsiteEnquiryNotifier $notifier)
    {
        if (strlen($request->getContent()) > 20000) {
            return response()->json(['success' => false, 'message' => 'Your message is too large. Please shorten it.'], 413);
        }
        $data = $request->validate([
            'kind' => ['required', Rule::in(['demo', 'contact'])],
            'name' => ['required', 'string', 'max:150', 'not_regex:/[\r\n\x00]/'],
            'email' => ['required', 'string', 'email', 'max:254', 'not_regex:/[\r\n\x00]/'],
            'phone' => ['nullable', 'string', 'max:60', 'not_regex:/[\r\n\x00]/'],
            'company' => ['nullable', 'string', 'max:200', 'not_regex:/[\r\n\x00]/'],
            'topic' => ['required', Rule::in($request->input('kind') === 'demo' ? ['Live demo'] : WebsiteEnquiry::TOPICS)],
            'message' => ['required', 'string', 'max:10000', 'not_regex:/\x00/'],
            'website' => ['nullable', 'string', 'max:0'],
        ]);
        unset($data['website']);
        $data['email'] = strtolower(trim($data['email']));
        $enquiry = WebsiteEnquiry::create($data);
        $notifier->send($enquiry);

        return response()->json(['success' => true, 'message' => $enquiry->kind === 'demo'
            ? 'Thank you! Your demo request has been received. Our team will contact you to arrange a walkthrough.'
            : 'Thank you! Your enquiry has been received. Our team will get back to you soon.']);
    }
}
