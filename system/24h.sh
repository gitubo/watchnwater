logrotate /etc/logrotate.d/rhc
curl --data "t=update_ip" http://localhost/sd/do.php
curl --data "t=clean" http://localhost/sd/do.php
curl --data "t=process_historical_data" http://localhost/sd/do.php


