<?php
$salt = "!8c5s6hfv84pq$8jz"; // 適当な文字列
echo $token = sha1($salt.time()); 
