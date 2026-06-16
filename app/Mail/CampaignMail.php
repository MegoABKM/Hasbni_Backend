<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subjectLine;
    public $bodyContent;

    public function __construct($subjectLine, $bodyContent)
    {
        $this->subjectLine = $subjectLine;
        $this->bodyContent = $bodyContent;
    }

    public function build()
    {
        // نرسل الإيميل باستخدام HTML مباشر لكي يدعم الألوان والروابط من الـ RichEditor
        return $this->subject($this->subjectLine)
                    ->html($this->bodyContent);
    }
}