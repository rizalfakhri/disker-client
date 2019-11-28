<?php 

if( !function_exists('to_size') ) {
    /**
     * Convert bytes to readable size.
     *
     * @param  int $bytes
     * @return string
     */
    function to_size($bytes) {

        if($bytes == 0) return $bytes;

        $base = log($bytes) / log(1024);

        $sizes = ["", "KB", "MB", "GB", "TB"];

        return sprintf("%s%s", 
            round(
                pow(1024, $base - floor($base)), 1
            ),
            $sizes[floor($base)]
        );
    }
}
