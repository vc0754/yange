<?php

namespace app\api\job;

use app\admin\model\vc\ActivityPlayer;
use app\admin\services\crawler;
use Error;
use think\queue\Job;

class Cron {

  /**
   * 定时消费者
   */
  public function fire(Job $job, $data) {
    $isJobDone = $this->doCronJob($data);
    if ($isJobDone) {
      $job->delete();
      print("data:{$data['id']} 执行完成\n");
    } else {
      if ($job->attempts() > 3) {
        print("data:{$data['id']}重试3次失败，抛弃任务\n");
        $job->delete();
        
        // 也可以重新发布这个任务
        // print("<info>Hello Job will be availabe again after 2s."."</info>\n");
        // $job->release(2); //$delay为延迟时间，表示该任务延迟2秒后再执行
      }
    }
  }

  /**
   * 根据消息中的数据进行实际的业务处理...
   */
  private function doCronJob($data) {
    // print("<info>Hello Job Started. job Data is: ".var_export($data,true)."</info> \n");
    // print("<info>Hello Job is Fired at " . date('Y-m-d H:i:s') ."</info> \n");
    // print("<info>Hello Job is Done!"."</info> \n");
    // return false;

    $exist = ActivityPlayer::find($data['id']);
    if (!$exist) return false;
    
    $service = new crawler($data['post_url']);
    try {
      $res = $service->parse_post();

      $exist->zan = $res['attitudes_count'];
      $exist->save();
      
      return true;
    } catch (Error $e) {
      return false;
    }
  }

  /**
 * 该方法用于接收任务执行失败的通知，你可以发送邮件给相应的负责人员
 * @param $jobData  string|array|...      //发布任务时传递的 jobData 数据
 */
  public function failed($jobData){
    // send_mail_to_somebody() ; 
    
    // print("Warning: Job failed after max retries. job data is :".var_export($data,true)."\n"; 
  }
}
