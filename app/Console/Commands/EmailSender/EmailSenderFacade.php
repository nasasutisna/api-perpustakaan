<?php

namespace App\Console\Commands\EmailSender;

use Exception;
use Illuminate\Support\Facades\DB;

class EmailSenderFacade
{

    private $bookingId;

    public function __construct($bookingId)
    {
        $this->bookingId = $bookingId;
    }

    public function getAllEmailQueue()
    {
        return DB::table('email_queue')->where('bookingId', '=', $this->bookingId)->orderBy('createdDt', 'asc')->get();
    }

    public function doBookingEmailQueue()
    {
        return DB::table('email_queue')->orderBy('createdDt', 'asc')->limit(10)->update(['bookingId' => $this->bookingId]);
    }

    public function doMoveToSuccess($email)
    {
        try {
            DB::beginTransaction();
            DB::table('email_sent')->insert([
                'emailQueueId' => $email->emailId,
                'emailDest' => $email->emailDest,
                'emailTitle' => $email->emailTitle,
                'emailContent' => $email->emailContent,
                'attachment1' => $email->attachment1,
                'attachment2' => $email->attachment2,
                'createdDt' => $email->createdDt,
                'sentDt' => $email->emailSentDt,
            ]);
            DB::table('email_queue')->where(['emailId' => $email->emailId])->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function doRollbackById($email)
    {
        DB::table('email_queue')->where('emailId', '=', $email->emailId)->update(['bookingId' => '', 'lastTryDt' => $email->emailSentDt, 'smtpResponse' => $email->response, 'lastTryCount' => $email->lastTryCount + 1]);
    }

    public function doRollback()
    {
        DB::table('email_queue')->update(['bookingId' => '']);
    }
}
