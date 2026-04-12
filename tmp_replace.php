<?php
$dir = 'c:\\htdocs\\htdocs\\academy-management';

echo "Updating Controllers...\n";
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir . '/app/Http/Controllers'));
foreach($it as $file) {
    if ($file->isFile() && $file->getExtension() == 'php') {
        $content = file_get_contents($file->getPathname());
        $originalContent = $content;
        
        // 1. Select statements
        $content = str_replace("Student::select('id', 'student_name', 'admission_no')", "Student::select('id', 'student_name', 'admission_no', 'roll_no')", $content);
        $content = str_replace("student:id,student_name,admission_no", "student:id,student_name,admission_no,roll_no", $content);
        
        // 2. DataTables formatting: 'student_name' => $c->studentEnrollment?->student?->student_name ?? '-',
        // Regex to match and capture the variable path
        $content = preg_replace_callback(
            "/'student_name'\s*=>\s*\\$([a-zA-Z0-9_\>?->]+(?:student_name|first_name))\s*\?\?\s*'-',/",
            function($matches) {
                $varPath = $matches[1]; // e.g., $c->studentEnrollment?->student?->student_name
                // replace student_name or first_name with roll_no
                $rollPath = str_replace(['student_name', 'first_name'], 'roll_no', $varPath);
                return "'student_name' => (\$" . $varPath . " ?? '-') . ' (Roll: ' . (\$" . $rollPath . " ?? 'N/A') . ')',";
            },
            $content
        );

        // Another variant: $studentName = $voucher->studentEnrollment?->student?->student_name ?? '-';
        $content = preg_replace_callback(
            "/(?:\\$studentName\s*=\s*\\$([a-zA-Z0-9_\>?->]+(?:student_name|first_name))\s*\?\?\s*'-')/",
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

echo "Updating Vue Files...\n";
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir . '/resources/js/Pages'));
foreach($it as $file) {
    if ($file->isFile() && $file->getExtension() == 'vue') {
        $content = file_get_contents($file->getPathname());
        $originalContent = $content;
        
        $content = str_replace("{{ s.student_name }} ({{ s.admission_no }})", "{{ s.student_name }} (Roll: {{ s.roll_no || 'N/A' }}, Adm: {{ s.admission_no || 'N/A' }})", $content);
        $content = str_replace("{{ student.student_name }} ({{ student.admission_no }})", "{{ student.student_name }} (Roll: {{ student.roll_no || 'N/A' }}, Adm: {{ student.admission_no || 'N/A' }})", $content);
        $content = str_replace("{{ e.student?.student_name || e.student?.first_name }} ({{ e.student?.admission_no }})", "{{ e.student?.student_name || e.student?.first_name }} (Roll: {{ e.student?.roll_no || 'N/A' }}, Adm: {{ e.student?.admission_no || 'N/A' }})", $content);
        
        
        if ($content !== $originalContent) {
            file_put_contents($file->getPathname(), $content);
            echo "- " . $file->getFilename() . "\n";
        }
    }
}
echo "Done.\n";
