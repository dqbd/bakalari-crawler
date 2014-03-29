<?php

namespace BakaParser;

class Response implements \JsonSerializable {

    private static $result_codes = array(-1 => "error", 0 => "fail", 1 => "ok");
    
    private $status = "ok";
    private $data = array();
    private $msg = "";

    public function setError($msg, $data = array()) {
        $this->setStatus(-1);
        $this->setResult($data);
        $this->msg = $msg;
        return $this;
    }

    public function setResult($response) {
        $this->data = $response;
        return $this;
    }

    public function setStatus($code) {
        $this->status = self::$result_codes[$code];
        return $this;
    }

    public function getStatus() {
        return array_flip(self::$result_codes)[$this->status];
    }
    
    public function getData() {
        return $this->data;
    }
    
    public function getMsg() {
        return $this->msg;
    }

    public function jsonSerialize() {
        $data = array();

        $data['status'] = $this->status;

        if (!empty($this->data) || $this->status > -1) {
            $data['data'] = (empty($this->data)) ? null : $this->data;
        }

        if (!empty($this->msg)) {
            $data['message'] = $this->msg;
        }

        return $data;
    }
    
    public function __toString() {
        return "<pre>" . print_r(array(
            "status" => $this->status,
            "data" => $this->data,
            "msg" => $this->msg
        ), true) . "</pre>";
    }

}

?>