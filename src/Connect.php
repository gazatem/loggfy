<?php 

namespace Loggfy\Connect;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\Curl\Util;

class Connect extends AbstractProcessingHandler {

    private $api_key = null;
    private $httpConnection = null;

    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }    

    protected function write(array $record)
    {
        $this->post_remote($record);
    }

    private function post_remote(array $record)
    {
        $date = $record['datetime'];
        $data = array('time' => $date->format('Y-m-d\TH:i:s.uO'));
        unset($record['datetime']);
        if (isset($record['context']['type'])) {
            $data['type'] = $record['context']['type'];
            unset($record['context']['type']);
        } else {
            $data['type'] = $record['channel'];
        }
        $data['data'] = $record['context'];
        $data['level'] = $record['level'];
        $data['level_name'] = $record['level_name'];
        $data['message'] = $record['message'];
        $postString = json_encode($data);
        $this->writeHttp($postString);
    }
    private function writeHttp($data)
    {
        if (!$this->httpConnection) {
            $this->connectHttp();
        }
        curl_setopt($this->httpConnection, CURLOPT_POSTFIELDS, $data);
        curl_setopt($this->httpConnection, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer " . $this->api_key,
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data)
            )
        );
        Util::execute($this->httpConnection, 5, false);
    }
    /**
     * Establish a connection to a http server
     */
    protected function connectHttp()
    {
        if (!extension_loaded('curl')) {
            throw new \LogicException('The curl extension is needed to use http URLs');
        }
        $this->httpConnection = curl_init(config('glog.remote_host', 'http://test.gazatem.com'));
        if (!$this->httpConnection) {
            throw new \LogicException('Unable to connect to ' . config('glog.remote_host', 'http://test.gazatem.com'));
        }
        curl_setopt($this->httpConnection, CURLOPT_POST, "POST");
        curl_setopt($this->httpConnection, CURLOPT_RETURNTRANSFER, true);
    }


}
