<?php
// Site settings — edit these.
const SITE_NAME    = 'TalkFellas';
const SITE_TAGLINE = 'The Best Products, Ranked & Reviewed';
const SITE_URL     = 'https://talkfellas.com';
const SITE_EMAIL   = 'contact@talkfellas.com';
// Database lives OUTSIDE the web root so it can never be downloaded.
define('DATA_DIR', dirname(__DIR__, 2) . '/tf-data');
