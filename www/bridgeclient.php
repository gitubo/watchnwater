<?php
/*

Required packages
------------
php5
php5-mod-sockets
php5-mod-json
php5-cgi or php5-cli

*/

error_reporting(E_ERROR);

class bridgeclient
{

    private $address = "127.0.0.1";
    private $port = 5700;
    private $socket;

    public function connect()
    {
        ($this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))
           || die("socket_create() failed: " . socket_strerror(socket_last_error()) . "\n");
        
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 3, "usec" => 0));
        
        socket_connect($this->socket, $this->address, $this->port)
           || die("socket_connect() failed: " . socket_strerror(socket_last_error($this->socket)) . "\n");
    }

    public function disconnect()
    {
        socket_close($this->socket);
    }

    public function put($key, $value)
    {
        return $this->sendcommand("put", $key, $value);
    }

    public function get($key)
    {
        return $this->sendcommand("get", $key);
    }

    private function sendcommand($command, $key, $value = "")
    {
        $jsonreceive = "";
        $obraces = 0;
        $cbraces = 0;

        $jsonsend = '{"command":"' . $command . '","key":"' . $key . '","value":"' . $value . '"}';
        socket_write($this->socket, $jsonsend, strlen($jsonsend));

        do {
            socket_recv($this->socket, $buffer, 1, 0);
            $jsonreceive .= $buffer;
            if ($buffer == "{") $obraces++;
            if ($buffer == "}") $cbraces++;
        } while ($obraces != $cbraces);

        $jsonarray = json_decode($jsonreceive);
        if ($jsonarray->{'value'} == NULL) $jsonarray->{'value'} = "None";

        return $jsonarray->{'value'};
    }

}

?>

