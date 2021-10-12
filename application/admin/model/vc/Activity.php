<?php
namespace app\admin\model\vc;
use think\Model;

class Activity extends Model {
  protected $name = 'vc_activity';
  
  public function sponsor() {
    return $this->belongsTo('User', 'sponsor_id', 'id');
  }
  
  public function players() {
    return $this->hasMany('ActivityPlayer', 'id', 'activity_id');
  }
}