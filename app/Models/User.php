<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id', 'name', 'email', 'mobile_number', 'email_verified_at', 'password', 'role_id', 'status', 'remember_token', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->name}";
    }
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function getAuthPassword()
    {
        return $this->password;
    }
    public function sendWhatsappMessage($mobile,$key,$msg,$pdf) {
       
       if(!empty($pdf)){
            //$data = "http://api.bulkcampaigns.com/api/wapi?json=true&apikey=".$key."&mobile=".$mobile."&msg=".urlencode($msg)."&pdf=".$pdf;
            //$data ="https://newweb.technomantraa.com/api/send?number=".$mobile."&type=media&message=".urlencode($msg)."&media_url=".$pdf."&instance_id=65B0AA55DBFC6&access_token=65ae0fdc57bce";
            //$data ="https://newweb.technomantraa.com/api/send?number=91".$mobile."&type=media&message=".urlencode($msg)."&media_url=".$pdf."&instance_id=65C48823AC1D6&access_token=65c486860588c";
            $data = "https://newweb.technomantraa.com/api/send?number=91".$mobile."&type=media&message=".urlencode($msg)."&media_url=".$pdf."&instance_id=666946D557590&access_token=65c486860588c";
            
       }else{
            //$data = "http://api.bulkcampaigns.com/api/wapi?json=true&apikey=".$key."&mobile=".$mobile."&msg=".urlencode($msg);
            //$data = "https://newweb.technomantraa.com/api/send?number=".$mobile."&type=text&message=".urlencode($msg)."&instance_id=65B0AA55DBFC6&access_token=65ae0fdc57bce";
            //$data = "https://newweb.technomantraa.com/api/send?number=91".$mobile."&type=text&message=".urlencode($msg)."&instance_id=65C48823AC1D6&access_token=65c486860588c";
            $data = "https://newweb.technomantraa.com/api/send?number=91".$mobile."&type=text&message=".urlencode($msg)."&instance_id=666946D557590&access_token=65c486860588c";
       }
      
        $ret = file_get_contents($data);
        $result = json_decode($ret);
        
        //echo "<pre>";
        //print_r($result['status']);
        return $result;
    }
}
