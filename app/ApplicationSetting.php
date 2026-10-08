<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ApplicationSetting extends Model
{
    public function getMailSettingAttribute($value): array
    {
        $settings = is_array($value) ? $value : json_decode($value ?? '', true);
        if (is_array($settings)) {
            return $settings;
        }

        // Fresh workspaces may have no saved notification configuration yet.
        return ApplicationStatus::on($this->getConnectionName())->global()
            ->orderBy('id')->get()->mapWithKeys(fn ($stage) => [
                $stage->id => ['name' => $stage->status, 'status' => true],
            ])->all();
    }

    public function getLegalTermAttribute($value) { return tenant_html($value); }
    protected $casts = [
        'mail_setting' => 'array'
    ];
}
