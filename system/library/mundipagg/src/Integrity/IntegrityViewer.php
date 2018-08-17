<?php

namespace Mundipagg\Integrity;

class IntegrityViewer
{
    public function handleDefaultInfoView($title, $info)
    {
        echo "<h3>$title</h3>";
        echo '<pre>';
        print_r($info);
        echo '</pre>';
        echo json_encode($info);
    }

    public function handleNonEmptyInfoView($message, $info)
    {
        if (count($info) > 0) {
            echo "<h3 style='color:red'>$message (" . count($info). ")</h3>";
            echo '<pre>';
            print_r($info);
            echo '</pre>';
            echo json_encode($info);
        }
    }

    public function handleLogListView($logs, $url, $params)
    {
        echo '<h3>Logs ('.count($logs).')</h3><pre>';
        foreach($logs as $logFile) {
            $link = "<strong style='color:red'>$logFile</strong><br />";

            if (is_readable($logFile)) {
                $fileRoute =  $url;
                $fileRoute .= '&token=';
                $fileRoute .= isset($params['token']) ? $params['token'] : '';
                $fileRoute .= '&file=' . base64_encode($logFile);

                $link =
                    '<a href="'.$fileRoute.'" target="_self">' .
                    $logFile . ' (' . filesize($logFile) . ' bytes)'.
                    '</a><br />';
            }
            echo $link;
        }
    }
}