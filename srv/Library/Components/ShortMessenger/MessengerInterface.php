<?php
/**
 * Created by PhpStorm.
 * User: Arimis
 * Date: 14-8-6
 * Time: 下午3:32
 */

namespace Components\ShortMessenger;


interface MessengerInterface
{

    /**
     * @param $phone
     * @param $message
     * @return mixed
     */
    public function send($phone, $message);

    /**
     * @param array $config
     * @return MessengerInterface
     */
    public function config(array $config);

    public function massSend(array $phones, $message);
}