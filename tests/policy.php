<?php
require_once __DIR__ . '/../wp-content/plugins/techjob-ai-core/includes/policy.php';
$count = 0;
function check($actual, $expected, string $name): void {
    global $count;
    if ($actual !== $expected) { throw new RuntimeException($name . ': ' . var_export($actual, true)); }
    $count++;
}
check(tj_open_policy('publish', '2026-11-02', '2026-11-02'), true, 'Deadline includes today');
check(tj_open_policy('publish', '2026-11-01', '2026-11-02'), false, 'Expired jobs reject applications');
check(tj_open_policy('draft', '', '2026-11-02'), false, 'Draft is not open');
check(tj_interview_status(['pending', 'passed', 'rejected'], false), 'passed', 'Latest final result wins');
check(tj_interview_status([], false), 'reviewing', 'Cancel last interview reopens review');
check(tj_interview_status([], true), 'interview', 'Reopened active interview');
check(tj_salary_matches(15000000, 25000000, false, 20000000, 30000000), true, 'Salary overlaps');
check(tj_salary_matches(15000000, 25000000, true, 20000000, 0), false, 'Negotiable is not numeric range');
$file = tempnam(sys_get_temp_dir(), 'tj-test');
file_put_contents($file, '<?php echo "bad";');
check(tj_file_signature($file, 'pdf'), false, 'Fake PDF rejected');
file_put_contents($file, "%PDF-1.4\n");
check(tj_file_signature($file, 'pdf'), true, 'PDF header');
unlink($file);
echo "PASS $count policy checks\n";
