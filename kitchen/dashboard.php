<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['kitchen']);
redirect('kitchen/orders.php');
