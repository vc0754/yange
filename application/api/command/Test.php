<?php
namespace app\api\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
// use Workerman\Lib\Timer;
// use Workerman\Worker;

class Test extends Command {
  
  protected function configure() {
    parent::configure();
    $this->setName('test')->setDescription('this is test mark');
  }

  protected function execute(Input $input, Output $output) {
    $output->writeln('testCommand');
    $output->writeln(json_encode($this->checkdo()));
  }

  public function checkdo() {
    return [ 'a' => '1' ];
  }
}