<?php
defined('ABSPATH') || exit;
if (function_exists('tj_render')) { tj_render('jobs', ['route' => '', 'section' => '']); }
else { get_header(); echo '<main style="padding:3rem"><h1>TechJob AI</h1><p>Activate the TechJob AI Core plugin.</p></main>'; get_footer(); }
