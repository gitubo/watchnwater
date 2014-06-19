<?php

/**
* Watch 'n' Water Brigde Class to communicate with Arduino 
*
* This is a PHP class used to communicate with Arduino
* using the mailbox provided by the system as a
* TCPJSONServer at 127.0.0.1:5700 (on the Linino side)
*
* LICENSE: GPL v3
*
*/

error_reporting(E_ERROR);

class wnw_bridge
{

    private $address = "127.0.0.1";
    private $port = 5700;
    private $socket;

    public function connect()
    {
        ($this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))
           || die("Creation ofthe socket failed: " . socket_strerror(socket_last_error()) . "\n");
        
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 3, "usec" => 0));
        
        socket_connect($this->socket, $this->address, $this->port)
           || die("Connection to the socket failed: " . socket_strerror(socket_last_error($this->socket)) . "\n");
    }

    public function disconnect()
    {
        socket_close($this->socket);
    }

    public function put($key, $value)
    {
        return $this->publish("put", $key);
    }

    public function get($key)
    {
        return $this->publish("get", $key);
    }

    private function publish($command, $key, $value)
    {
        if($command == null || $key == null || $value == null) return;

        $jsonBuffer = "";
        $num_open_braces = 0;
        $num_close_braces = 0;

        $message = '{"command":"' . $command . '","key":"' . $key . '","value":"' . $value . '"}';
        socket_write($this->socket, $message, strlen($message));

        do {
            socket_recv($this->socket, $buffer, 1, 0);
            $jsonBuffer .= $buffer;
            if ($buffer == "{") $num_open_braces++;
            if ($buffer == "}") $num_close_braces++;
        } while ($num_open_braces != $num_close_braces);

        $jsonArray = json_decode($jsonBuffer);
        if ($jsonArray->{'value'} == NULL) $jsonArray->{'value'} = "";

        return $jsonArray->{'value'};
    }

}

?>

