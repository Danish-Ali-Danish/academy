<?php
$dir = 'c:\\htdocs\\htdocs\\academy-management';

echo "Updating Controllers safely...\n";
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir . '/app/Http/Controllers'));
foreach($it as $file) {
    if ($file->isFile() && $file->getExtension() == 'php') {
        $content = file_get_contents($file->getPathname());
        $originalContent = $content;
        
        // 1. Select statements
        $content = str_replace("Student::select('id', 'student_name', 'admission_no')", "Student::select('id', 'student_name', 'admission_no', 'roll_no')", $content);
        $content = str_replace("student:id,student_name,admission_no'", "student:id,student_name,admission_no,roll_no'", $content);
        
        if ($content !== $originalContent && !empty($content)) {
            file_put_contents($file->getPathname(), $content);
            echo "- " . $file->getFilename() . "\n";
        }
    }
}
echo "Done.\n";
