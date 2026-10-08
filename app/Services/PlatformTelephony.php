<?php
namespace App\Services;
use App\Saas\PlatformSetting;
use App\Saas\TenantContext;
use Illuminate\Support\Facades\Crypt;
class PlatformTelephony {
    public static function settings(): object {
        if (!config('saas.enabled')) return \App\SmsSetting::first() ?? (object)[];
        $tenant=app(TenantContext::class)->current();
        $data=$tenant ? json_decode(PlatformSetting::valueFor('telephony.'.$tenant->id,'{}'),true) : [];
        $secret=PlatformSetting::valueFor('telnyx_api_key');
        return (object)[
            'sms_provider'=>'telnyx','nexmo_status'=>!empty($data['sms_enabled'])?'active':'deactive',
            'telnyx_api_key'=>$secret ? Crypt::decryptString($secret) : null,
            'telnyx_public_key'=>PlatformSetting::valueFor('telnyx_public_key'),
            'telnyx_from_number'=>$data['sms_number']??null,
            'calls_enabled'=>!empty($data['calls_enabled']),
            'connection_id'=>$data['connection_id']??null,'voice_number'=>$data['voice_number']??null,
        ];
    }
}
