<?php
$dir = 'c:\\htdocs\\htdocs\\academy-management';

echo "Updating Controllers...\n";
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir . '/app/Http/Controllers'));
foreach($it as $file) {
    if ($file->isFile() && $file->getExtension() == 'php') {
        $content = file_get_contents($file->getPathname());
        $originalContent = $content;
        
        $content = preg_replace_callback(
            "/'student_name'\s*=>\s*\\\$([a-zA-Z0-9_]+(?:\??->[a-zA-Z0-9_]+)*?->(?:student_name|first_name))\s*\?\?\s*'-',/",
            function($matches) {
                $varPath = $matches[1];
                $rollPath = str_replace(['student_name', 'first_name'], 'roll_no', $varPath);
                return "'student_name' => (\$" . $varPath . " ?? '-') . ' (Roll: ' . (\$" . $rollPath . " ?? 'N/A') . ')',";
            },
            $content
        );

        $content = preg_replace_callback(
            "/\\\$studentName\s*=\s*\\\$([a-zA-Z0-9_]+(?:\??->[a-zA-Z0-9_]+)*?->(?:student_name|first_name))\s*\?\?\s*'-';/",
            function($matches) {
                $varPath = $matches[1];
                $rollPath = str_replace(['student_name', 'first_name'], 'roll_no', $varPath);
                return "\$studentName = (\$" . $varPath . " ?? '-') . ' (Roll: ' . (\$" . $rollPath . " ?? 'N/A') . ')';";
            },
            $content
        );

        if ($content !== $originalContent) {
            file_put_contents($file->getPathname(), $content);
            echo "- " . $file->getFilename() . "\n";
        }
    }
}
echo "Done.\n";
