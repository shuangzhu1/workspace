<?php

/*
 *
 * inotify Event :array (
          'wd' => 1,
          'mask' => 2,
          'cookie' => 0,
          'name' => '',
      )
 *
 *
 * */

class G
{
    static $users = array();
    static $files = array();
    static $watchList = array();
    static $inotify;
    static $start_line = 1;
}

//**按行读取文件**/
function getFileByLines($filename, $startLine = 1, $endLine = 50, $method = 'rb')
{
    $content = array();
    if (version_compare(PHP_VERSION, '5.1.0', '>=')) { // 判断php版本（因为要用到SplFileObject，PHP>=5.1.0）
        $count = $endLine - $startLine;
        $fp = new \SplFileObject($filename, $method);
        $fp->seek($startLine - 1); // 转到第N行, seek方法参数从0开始计数
        for ($i = 0; $i <= $count; ++$i) {
            $content[] = $fp->current(); // current()获取当前行内容
            $fp->next(); // 下一行
        }
    } else { //PHP<5.1
        $fp = fopen($filename, $method);
        if (!$fp)
            return 'error:can not read file';
        for ($i = 1; $i < $startLine; ++$i) { // 跳过前$startLine行
            fgets($fp);
        }

        for ($i; $i <= $endLine; ++$i) {
            $content[] = fgets($fp); // 读取文件行内容
        }
        fclose($fp);
    }
    return array_filter($content); // array_filter过滤：false,null,''
}

/*获取文件的行数*/
function getFileLineCount($filename, $method = 'rb')
{
    $fp = new \SplFileObject($filename, $method);
    $fp->seek(filesize($filename)); //
    return $fp->key();
    /*  $line = 0;
      $fp = fopen($filename, 'r') or die("open file failure!");
      if ($fp) {
          while (stream_get_line($fp, 8192, "\n")) {
              $line++;
          }
          fclose($fp);
      }
      return $line;*/
}

$server = new swoole_websocket_server("0.0.0.0", 9502, SWOOLE_BASE);
$server->set(array('worker_num' => 1, 'daemonize' => true));

$server->on('WorkerStart', function (swoole_websocket_server $server, $worker_id) {
    G::$inotify = inotify_init();
    swoole_event_add(G::$inotify, function ($ifd) use ($server) {
        $events = inotify_read(G::$inotify);
        if (!$events) {
            return;
        }
        foreach ($events as $event) {
            $filename = G::$watchList[$event['wd']];
            $total_count = getFileLineCount($filename);
            $content = getFileByLines($filename, G::$start_line, $total_count);
            // $line = fgets(G::$files[$filename]['fp']);
            /*  if (!$line) {
                  echo "fgets failed\n";
              }*/
            $res = array('data_list' => [], 'start_line' => G::$start_line, 'line_count' => $total_count, "count" => 0);
            if ($content) {
                $count = 0;
                $data = [];
                foreach ($content as $k => $item) {
                    $item = urldecode(str_replace(' ', '&nbsp;', $item));
                    $data[] = '<li><span class="line-left"><b class="line_count">' . (G::$start_line + $k) . '</b></span><span class="content">' . nl2br($item) . '</span></li>';
                    $count++;
                }
                $res = array('data_list' => $data, 'start_line' => G::$start_line, 'line_count' => $total_count, "count" => $count);
            }
            $res["error"] = '';
            G::$start_line = $total_count + 1;
            //遍历监听此文件的所有用户，进行广播
            foreach (G::$files[$filename]['users'] as $fd) {
                $server->push($fd, json_encode($res, JSON_UNESCAPED_UNICODE));
            }
        }
    });
});
$server->on('Message', function (swoole_websocket_server $server, $frame) {
    echo "message: " . $frame->data;
    $result = json_decode($frame->data, true);
    $filename = $result['filename']; //以文件名作为数组的值，Key是fd
    $line_count = $result['line_count']; //读取末尾的行数

    $filename = realpath(__DIR__ . '/../../' . $filename);
    if (!is_file($filename)) {
        $server->push($frame->fd, json_encode(['error' => "file[$filename][".json_encode($result,JSON_UNESCAPED_UNICODE)."] is not exist.\n"], JSON_UNESCAPED_UNICODE));
        return;
    }
    //还没有创建inotify句柄
    if (empty(G::$files[$filename]['inotify_fd'])) {
        //添加监听
        $wd = inotify_add_watch(G::$inotify, $filename, IN_MODIFY);
        $total_count = getFileLineCount($filename);//文件总行数

        $fp = fopen($filename, 'r');
        clearstatcache();
        $filesizelatest = filesize($filename);
        fseek($fp, $filesizelatest);
        G::$watchList[$wd] = $filename;
        G::$files[$filename]['inotify_fd'] = $wd;
        G::$files[$filename]['fp'] = $fp;
        G::$start_line = $total_count + 1;
        G::$files[$filename]['line_count'] = $line_count;
    }
    //清理掉其他文件的监听
    if (!empty(G::$users[$frame->fd]['watch_file'])) {
        $oldfile = G::$users[$frame->fd]['watch_file'];
        $k = array_search($frame->fd, G::$files[$oldfile]['users']);
        unset(G::$files[$oldfile]['users'][$k]);
    }
    //用户监听的文件
    G::$users[$frame->fd]['watch_file'] = $filename;
    //文件被哪些人监听了
    G::$files[$filename]['users'][] = $frame->fd;
});
$server->on('close', function ($serv, $fd, $threadId) {
    if (G::$users[$fd]['watch_file']) {
        $file = G::$users[$fd]['watch_file'];
        $k = array_search($fd, G::$files[$file]['users']);
        unset(G::$files[$file]['users'][$k]);
        unset(G::$users[$fd]);
    }
});
$server->start();
