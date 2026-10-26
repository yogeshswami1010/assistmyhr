<?php

namespace App\Mail;

use App\Saas\WebsiteEnquiry;
use Illuminate\Mail\Mailable;

class WebsiteEnquiryReceived extends Mailable
{
    public function __construct(public WebsiteEnquiry $enquiry) {}

    public function build()
    {
        return $this->subject($this->enquiry->kind === 'demo' ? 'New demo request | AssistMyHR' : 'New contact enquiry | AssistMyHR')
            ->replyTo($this->enquiry->email)
            ->text('email.website-enquiry');
    }
}
