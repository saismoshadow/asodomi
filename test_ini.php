<?php
echo "memory_limit: " . ini_get("memory_limit") . "\n";
echo "max_execution_time: " . ini_get("max_execution_time") . "\n";
echo "max_input_time: " . ini_get("max_input_time") . "\n";
echo "output_buffering: " . ini_get("output_buffering") . "\n";
echo "implicit_flush: " . (ini_get("implicit_flush") ? "On" : "Off") . "\n";
echo "post_max_size: " . ini_get("post_max_size") . "\n";
echo "upload_max_filesize: " . ini_get("upload_max_filesize") . "\n";
