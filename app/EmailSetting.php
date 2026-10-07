<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class EmailSetting extends Authenticatable
{
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $table = 'smtp_settings';


    protected $guarded = ['id'];
    protected $appends = ['set_smtp_message'];

    public function verifySmtp()
    {
        if ($this->mail_driver !== 'smtp') {
            return [
                'success' => true,
                'message' => __('messages.smtpSuccess'),
            ];
        }

        try {
            $settings = \App\Services\SmtpConfiguration::transport($this);
            $transport = new EsmtpTransport($settings['host'], $settings['port'], $settings['scheme'] === 'smtps');
            $transport->setAutoTls($settings['auto_tls']);
            if (method_exists($transport, 'setRequireTls')) { $transport->setRequireTls($settings['require_tls']); }
            $transport->getStream()->setTimeout(20);
            $transport->setUsername((string) $this->mail_username);
            $transport->setPassword((string) $this->mail_password);
            $transport->start();
            $transport->stop();

            if ($this->verified == 0) {
                $this->verified = 1;
                $this->save();
            }

            return [
                'success' => true,
                'message' => __('messages.smtpSuccess'),
            ];
        } catch (TransportException|\Exception $e) {
            $this->verified = 0;
            $this->save();

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getSetSmtpMessageAttribute(){
        if ($this->verified === 0 && $this->mail_driver == 'smtp') {
            return ' <div class="alert alert-danger">
                    '.__('messages.smtpNotSet').'
                    <a href="'.route('admin.smtp-settings.index').'" class="btn btn-info btn-small">Visit SMTP Settings <i
                                class="fa fa-arrow-right"></i></a>
                </div>';
        }
        return null;
    }
}
