<?php

if (!$user->isLoggedIn()) {
    exit(json_encode(['value' => 0]));
}

$pms = Alert::getPMs($user->data()->id);

echo json_encode(['value' => count($pms), 'pms' => $pms]);
