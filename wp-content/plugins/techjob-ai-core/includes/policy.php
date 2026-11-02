<?php
/** Pure policies are shared by handlers and regression tests. */
function tj_open_policy(string $status, string $deadline, string $today): bool {
    return $status === 'publish' && ($deadline === '' || $deadline >= $today);
}
function tj_interview_status(array $remaining, bool $current_active): string {
    foreach ($remaining as $result) {
        if (in_array($result, ['passed', 'rejected'], true)) { return $result; }
    }
    return $current_active || count($remaining) ? 'interview' : 'reviewing';
}
function tj_salary_matches(int $min, int $max, bool $negotiable, int $wanted_min, int $wanted_max): bool {
    if (!$wanted_min && !$wanted_max) { return true; }
    if ($negotiable) { return false; }
    return (!$wanted_max || $min <= $wanted_max) && (!$wanted_min || $max >= $wanted_min);
}
function tj_file_signature(string $path, string $extension): bool {
    $header = file_get_contents($path, false, null, 0, 8);
    if ($extension === 'pdf') { return str_starts_with($header ?: '', '%PDF-'); }
    if ($extension === 'doc') { return $header === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"; }
    if ($extension !== 'docx' || !class_exists('ZipArchive')) { return false; }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) { return false; }
    $valid = $zip->locateName('[Content_Types].xml') !== false && $zip->locateName('word/document.xml') !== false;
    $zip->close();
    return $valid;
}
