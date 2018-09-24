<?php
  require 'redis.php'; // https://github.com/ziogas/PHP-Redis-implementation

  function redis_error($error) {
    throw new error($error);
  }

  function get_server_memory_usage(){
      $free = shell_exec('free');
      $free = (string)trim($free);
      $free_arr = explode("\n", $free);
      $mem = explode(" ", $free_arr[1]);
      $mem = array_filter($mem);
      $mem = array_merge($mem);
      $memory_usage = $mem[2]/$mem[1]*100;

      return $memory_usage;
    }

  function get_server_cpu_usage(){
    $load = sys_getloadavg();
    return $load[0];
  }

  $response = array();
  $response["cpu"] = get_server_cpu_usage();
  $response["mem"] = get_server_memory_usage();

  $redis = new redis_cli();
  $redis->connect();
  $redis->set_error_function('redis_error');

  $procs = array();

  $keys = $redis->cmd('KEYS', 'proc:*')->get();

  foreach ($keys as $key) {
    $p = $redis->cmd('HGETALL', $key)->get();
    $proc = array();
    $proc["id"] = $key;

    for ($i=0; $i < sizeof($p); $i+=2) { 
      $proc[$p[$i]] = $p[$i+1];
    }

    $procs[] = $proc;
  }

  $response["proc"] = $procs;

  header('Content-Type: application/json');
  echo json_encode($response);
?>
