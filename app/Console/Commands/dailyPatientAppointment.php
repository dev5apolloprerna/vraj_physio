<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Patient;
use App\Models\PatientSchedule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class dailyPatientAppointment extends Command
{

    protected $signature = 'patient:appointment';
  protected $description = 'Patient Appointment';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dayNumber = date('N');
        $Data=PatientSchedule::where(['day'=>$dayNumber])
              ->join('patient_master', 'patient_master.patient_id', '=', 'patient_schedule.patient_id')
              ->join('patient_suggested_treatment', 'patient_suggested_treatment.patient_id', '=', 'patient_schedule.patient_id')
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

                    $msg = "Dear Parent,\n\n"
                        . "This is a friendly reminder about your upcoming appointment.\n\n"
                        . "• *Appointment Time:* {$d->schedule_start_time} to {$d->schedule_end_time}\n\n"
                        . "• *Appointment Day:* {$daysOfWeek[$d->day]} \n\n"
                        . "For any queries or assistance, please feel free to contact us.\n\n"
                        . "Thank you!";

                if($d->iAvailableSession != 0)
                {
                  $status = $users->sendWhatsappMessage($d->phone,$key,$msg, $someOtherParam = null);
                }

            }
               $this->info("Appointment messages sent to patients successfully.");
        } else {
            $this->error("No data available to send messages.");
        }
        
    }
}
