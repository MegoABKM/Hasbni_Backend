<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model {
    protected $guarded = [];
    protected $hidden = ['manager_password'];
    
    // Helper to check if manager password exists
    protected $appends = ['has_manager_password'];
    public function getHasManagerPasswordAttribute() {
        return !empty($this->manager_password);
    }
}