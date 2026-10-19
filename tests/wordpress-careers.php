<?php
// Isolated WordPress template checks. No live HTTP or WordPress database access.
define('MINUTE_IN_SECONDS', 60);
$cache = []; $requests = []; $lifetimes = []; $response = [];
function absint($value) { return abs((int) $value); }
function wp_unslash($value) { return stripslashes($value); }
function get_transient($key) { global $cache; return $cache[$key] ?? false; }
function set_transient($key, $value, $lifetime) { global $cache, $lifetimes; $cache[$key] = $value; $lifetimes[] = $lifetime; }
function wp_remote_get($url, $options) { global $requests, $response; $requests[] = [$url, $options]; return $response; }
function is_wp_error($response) { return $response instanceof Exception; }
function wp_remote_retrieve_response_code($response) { return $response['code']; }
function wp_remote_retrieve_body($response) { return $response['body']; }
function home_url($path = '') { return 'https://www.theburkegroup.com'.$path; }
function get_permalink() { return home_url('/careers/'); }
function get_template_directory_uri() { return home_url('/wp-content/themes/theburkhegroup'); }
function add_query_arg($key, $value, $url) {
    $parts = parse_url($url); parse_str($parts['query'] ?? '', $query); $query[$key] = $value;
    return $parts['scheme'].'://'.$parts['host'].($parts['path'] ?? '').'?'.http_build_query($query);
}
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function esc_url($url) { return preg_match('~\Ahttps?://~i', $url) ? esc_html($url) : ''; }
function get_header() {
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Careers preview</title>';
    echo '<link rel="stylesheet" href="'.get_template_directory_uri().'/style.css"><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&amp;display=swap">';
    echo '<style>body{margin:0;background:#000;color:#fff}header.preview-header{height:160px;box-sizing:border-box;padding:45px 7%;font-family:serif;letter-spacing:5px;font-size:18px}</style></head><body><header class="preview-header">the BURKE GROUP</header>';
}
function get_footer() { echo '<footer style="padding:30px 7%;color:#c9ad85">The Burke Group · Careers preview</footer></body></html>'; }
function renderCareers(array $query = []) {
    $_GET = $query; ob_start(); include __DIR__.'/../integrations/wordpress/the-burke-group/careers.php'; return ob_get_clean();
}
$checks = 0;
function check($ok, $label) { global $checks; if (!$ok) throw new RuntimeException($label); $checks++; }
$job = ['job_title'=>'Senior <script>unsafe()</script> Analyst', 'job_type'=>'Full Time', 'salary'=>null,
    'location'=>'Toronto & Niagara', 'start_date'=>'2026-10-20',
    'detail_url'=>'https://assistmyhr.com/job/senior-analyst/1?workspace=the-burke-group-py3hyy7yle'];
$feed = ['status'=>true,'total_jobs'=>101,'jobs'=>[$job],
    'pagination'=>['current_page'=>1,'last_page'=>2,'has_more'=>true]];
$response = ['code'=>200,'body'=>json_encode($feed)];
$html = renderCareers();
check(str_contains($html,'Senior &lt;script&gt;unsafe()&lt;/script&gt; Analyst')&&!str_contains($html,'<script>unsafe()'),'ATS job titles are escaped');
check(str_contains($html,'Toronto &amp; Niagara')&&str_contains($html,'Posted on Oct 20 2026'),'ATS location and posting date render in existing rows');
check(!str_contains($html,'<p>Salary:')&&!str_contains($html,'Labour Relations Consultant'),'Hidden salaries and old manual vacancies are not displayed');
check(str_contains($html,'href="'.esc_url($job['detail_url']).'"')&&str_contains($html,'More Details'),'Details link keeps the client workspace');
check(str_contains($html,'ats_jobs_page=2#current-opportunities'),'Pagination navigates to the next career-page result');
check($requests[0][1]['headers']['Accept']==='application/json'&&!isset($requests[0][1]['headers']['Authorization']),'WordPress uses the signed public feed without a private API key');
parse_str(parse_url($requests[0][0],PHP_URL_QUERY),$query);
check($query['workspace']==='the-burke-group-py3hyy7yle'&&strlen($query['signature'])===64&&$query['page']==='1','Signed feed URL keeps workspace and signature with an allowed page parameter');
renderCareers();check(count($requests)===1&&$lifetimes===[60],'Repeated visits reuse the one-minute feed cache');
$feed['pagination']=['current_page'=>2,'last_page'=>2,'has_more'=>false];$response['body']=json_encode($feed);
$secondPage=renderCareers(['ats_jobs_page'=>'2']);
check(count($requests)===2&&str_contains($secondPage,'ats_jobs_page=1#current-opportunities')&&!str_contains($secondPage,'Next &rarr;'),'Further pages load separately and link back correctly');
$cache=[];$response=['code'=>403,'body'=>'Forbidden'];
$unavailable=renderCareers();
check(str_contains($unavailable,'temporarily unavailable')&&!str_contains($unavailable,'There are no current openings'),'Revoked or unavailable feeds do not claim there are no vacancies');
check(end($lifetimes)===15,'Feed failures have a short retry cache');
$cache=[];$response=new RuntimeException('Connection timed out');
check(str_contains(renderCareers(),'temporarily unavailable'),'Network errors preserve the career page');
$cache=[];$response=['code'=>200,'body'=>'<html>Old widget response</html>'];
check(str_contains(renderCareers(),'temporarily unavailable'),'An ATS server awaiting deployment fails gracefully');
$cache=[];$response=['code'=>200,'body'=>json_encode(['status'=>true,'jobs'=>[],'pagination'=>['last_page'=>1]])];
check(str_contains(renderCareers(['ats_jobs_page'=>['invalid']]),'There are no current openings'),'A valid empty feed has a distinct message and invalid page input is safe');
check(str_contains($html,"The right opportunity isn't always advertised.")&&str_contains($html,'Qualifications matter. Alignment makes the difference.'),'Original page introduction and closing sections remain');
if (isset($argv[1])) {
    $cache=[];$feed['jobs']=array_map(fn($title)=>array_replace($job,['job_title'=>$title,'salary'=>'$80,000 - $100,000 / year']),['Director of Clinical Services','Payroll Coordinator','Senior Human Resources Consultant']);
    $feed['pagination']=['current_page'=>1,'last_page'=>1,'has_more'=>false];$response=['code'=>200,'body'=>json_encode($feed)];
    file_put_contents($argv[1],renderCareers());
}
echo "WordPress career template checks passed: $checks\n";
