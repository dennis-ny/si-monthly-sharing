<?php

$participantViews = $_SERVER['PARTICIPANT_VIEWS_PATH'] ?? null;
$paths = [];
if ($participantViews && is_dir($participantViews)) {
    $paths[] = $participantViews;
}
$paths[] = resource_path('views');

return [
    'paths' => $paths,
    'compiled' => $_SERVER['PARTICIPANT_COMPILED_VIEWS'] ?? realpath(storage_path('framework/views')),
];
