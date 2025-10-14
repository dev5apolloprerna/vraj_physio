<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class dailyTheraoistAppointment extends Command
{

protected $signature = 'therapist:appointment';
  protected $description = 'Therapist Appointment';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dayNumber = date('N');
        $Data=Schedule::where(['days'=>$dayNumber])
              ->join('users', 'users.id', '=', 'schedule.therapist_id')
              ->get();

        if(!empty($Data))
        {
            $key = $_ENV['WHATSAPPKEY'];
            $users = new User();
               
            foreach ($Data as $d) 
            {
  
                    $daysOfWeek = [
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                        7 => 'Sunday',
                    ];

                    $msg = "Dear DOCTOR,\n\n"
                        . "We are pleased to confirm your upcoming appointment. Here are the details:.\n\n"
                        . "• *Appointment Time:* {$d->start_time} to {$d->end_time}\n\n"
                        . "• *Appointment Day:* {$daysOfWeek[$d->days]} \n\n"
                        . "If there are any changes, we will update you promptly. We look forward to seeing you!.\n\n"
                        . "Thank you!";

                  $status = $users->sendWhatsappMessage($d->mobile_number,$key,$msg, $someOtherParam = null);

            }
               $this->info("Appointment messages sent to therapist successfully.");
        } else {
            $this->error("No data available to send messages.");
        }
        
    }
}
