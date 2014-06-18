curl --data "t=create_settings1Time" http://localhost/sd/do.php
curl --data "t=publish_settings" http://localhost/sd/do.php

sleep 5m && curl --data "t=update_ip" http://localhost/sd/do.php