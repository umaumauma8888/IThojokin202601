<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\DunningRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class DunningNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Invoice       $invoice,
        private readonly DunningRecord $record,
        private readonly string        $dunningType
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = match($this->dunningType) {
            'first'  => "【ご入金のお願い】請求書番号 {$this->invoice->invoice_number}",
            'second' => "【重要・再度のご入金のお願い】請求書番号 {$this->invoice->invoice_number}",
            'final'  => "【最終通告】請求書番号 {$this->invoice->invoice_number}",
            default  => "【入金のご確認】請求書番号 {$this->invoice->invoice_number}",
        };

        $balance     = number_format($this->invoice->receivable_balance);
        $overdueDays = $this->invoice->overdue_days;

        return (new MailMessage)
            ->subject($subject)
            ->greeting("{$notifiable->company_name} ご担当者様")
            ->line("平素より格別のご愛顧を賜り、誠にありがとうございます。")
            ->line("")
            ->line("下記請求書につきまして、お支払期日より**{$overdueDays}日**が経過しておりますが、入金の確認ができておりません。")
            ->line("")
            ->line("■ 請求書番号: {$this->invoice->invoice_number}")
            ->line("■ お支払期日: {$this->invoice->due_date}")
            ->line("■ 未払残高: ¥{$balance}")
            ->line("")
            ->line("お手数ですが、至急ご確認の上、お振込みいただきますようお願い申し上げます。")
            ->line("既にお振込み済みの場合はご連絡ください。")
            ->action('請求書を確認する', url("/invoices/{$this->invoice->id}"))
            ->salutation('合同会社UMA CLAUDE SUITE 経理部');
    }
}
