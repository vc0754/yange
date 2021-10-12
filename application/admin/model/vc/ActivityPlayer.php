<?php
namespace app\admin\model\vc;
use think\Model;

class ActivityPlayer extends Model {
  protected $name = 'vc_activity_player';
  
  public function activity() {
    return $this->belongsTo('Activity', 'activity_id', 'id');
  }
}