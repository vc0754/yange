<?php
namespace app\api\command;

use app\admin\model\vc\ActivityPlayer;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Queue;
use Workerman\Lib\Timer;
use Workerman\Worker;

class Cron extends Command {
  protected $timer;
  protected $interval = 180; // 3 * 60

  protected function configure() {
    parent::configure();

    // 指令配置
    $this->setName('cron')
      ->addArgument('status', Argument::REQUIRED, 'start/stop/reload/status/connections')
      ->addOption('d', null, Option::VALUE_NONE, 'daemon（守护进程）方式启动')
      ->addOption('i', null, Option::VALUE_OPTIONAL, '多长时间执行一次')
      ->setDescription('开启/关闭/重启 定时任务');
  }

  protected function init(Input $input, Output $output) {
    global $argv;
    if ($input->hasOption('i')) $this->interval = floatval($input->getOption('i'));
    $argv[3] = $input->getArgument('status') ? $input->getArgument('status') : 'status';

    if ($input->hasOption('d')) {
      $argv[3] = '-d';
    } else {
      unset($argv[3]);
    }

    // var_dump($argv);
  }

  protected function execute(Input $input, Output $output) {
    $this->init($input, $output);

    # 创建定时器任务
    $task = new Worker();
    $task->name = 'cron';
    $task->count = 1;
    // $task->eventHandler= \app\push\controller\Events::class;
    $task->onWorkerStart = [$this, $input->getArgument('status')];
    // $task->onWorkerStart = [$this, 'start'];
    // $task->onWorkerStop = [$this, 'stop'];
    // $task->onWorkerReload = [$this, 'reload'];
    $task->runAll();
  }
  
  public function stop() {
    // echo 'CronStop';
    // var_dump($this->timer);

    # 手动暂停定时器
    Timer::del($this->timer);

    // echo 'AA'.PHP_EOL;
  }

  public function start() {
    // echo 'CronStart';

    $last = time();
    $task = [
      6 => $last,
      10 => $last,
      30 => $last,
      60 => $last,
      180 => $last,
      300 => $last
    ];
    $this->timer = Timer::add($this->interval, function() use (&$task) {
      $res = ActivityPlayer::with([
        'activity'
      ])->where([
        'status' => 'approved'
      ])->select();
      
      $result = [];
      foreach($res as $v) {
        if (strtotime($v->activity->time_start) <= time() && strtotime($v->activity->time_verify_end) >= time()) {
          $result[] = [
            'id'        => $v->id,
            'post_url'  => $v->post_url,
            'platform'  => $v->activity->platform
          ];
        }
      }
      
      if (count($result)) {
        foreach($result as $v) {
          Queue::push('app\api\job\Cron', $v, 'getZanQueue');
        }
        echo 'Cron.Add ->' . count($result) . "\n";
      }

      //每隔2秒执行一次
      // try {
      //   $now = time();
      //   foreach ($task as $sec => $time) {
      //     if ($now - $time >= $sec) {
      //       # 每隔$sec秒执行一次
      //       $task[$sec] = $now;
      //     }
      //   }
      // } catch (\Throwable $e) {
      // }
    });

    // var_dump($this->timer);
  }
}