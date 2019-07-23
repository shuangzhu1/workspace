<?php

namespace Models;

use Phalcon\Mvc\Model;

class Industries extends Model {
    private $cacheKeyPrefix = "industries_cache_key_id_";
    public function afterSave()
    {
//        $this->refreshCache($this->pid);
    }

    private function refreshCache($pid = 0)
    {
        # code...
        if(is_null($pid)) {
            return false;
        }
        $data = $this->find(array("pid" => $pid));
        $this->getDI()->get("memcached")->save($this->cacheKeyPrefix . $pid, $data);
        if($pid == 0 && count($data)) {
            foreach ($data as $industry) {
                $this->refreshCache($industry->id);
            }
        }
    }
}