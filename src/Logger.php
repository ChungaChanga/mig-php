<?php

namespace Andrey\PhpMig;

class Logger
{
    private $filename;

    public function __construct($filename)
    {
        $this->setFileName($filename);
    }

    public function log($message, $filename = null)
    {
        if ($filename !== null) {
            $this->setFileName($filename);
        }
        $date = date('Y-m-d H:i:s');
        if (is_array($message) || is_object($message)) {
            $message = json_encode($message, JSON_PRETTY_PRINT);
        }
        file_put_contents($this->filename, $date . ' - ' . $message . "\n", FILE_APPEND);
    }
    public function setFileName($filename)
    {
        $this->filename = 'log/' . $filename;
    }
}
