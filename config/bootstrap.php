<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.8
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/*
 * Configure paths required to find CakePHP + general filepath constants
 */
require __DIR__ . DIRECTORY_SEPARATOR . 'paths.php';

/*
 * Bootstrap CakePHP.
 *
 * Does the various bits of setup that CakePHP needs to do.
 * This includes:
 *
 * - Registering the CakePHP autoloader.
 * - Setting the default application paths.
 */
require CORE_PATH . 'config' . DS . 'bootstrap.php';

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Database\Type\StringType;
use Cake\Database\TypeFactory;
use Cake\Datasource\ConnectionManager;
use Cake\Error\ConsoleErrorHandler;
use Cake\Error\ErrorHandler;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Mailer\TransportFactory;
use Cake\Routing\Router;
use Cake\Utility\Security;

/*
 * See https://github.com/josegonzalez/php-dotenv for API details.
 *
 * Uncomment block of code below if you want to use `.env` file during development.
 * You should copy `config/.env.example` to `config/.env` and set/modify the
 * variables as required.
 *
 * The purpose of the .env file is to emulate the presence of the environment
 * variables like they would be present in production.
 *
 * If you use .env files, be careful to not commit them to source control to avoid
 * security risks. See https://github.com/josegonzalez/php-dotenv#general-security-information
 * for more information for recommended practices.
*/
if (!env('APP_NAME') && file_exists(CONFIG . '.env')) {
    $dotenv = new \josegonzalez\Dotenv\Loader([CONFIG . '.env']);
    $dotenv->parse()
        ->putenv()
        ->toEnv()
        ->toServer();
}

/*
 * Read configuration file and inject configuration into various
 * CakePHP classes.
 *
 * By default there is only one configuration file. It is often a good
 * idea to create multiple configuration files, and separate the configuration
 * that changes from configuration that does not. This makes deployment simpler.
 */
try {
    Configure::config('default', new PhpConfig());
    Configure::load('app', 'default', false);
} catch (\Exception $e) {
    exit($e->getMessage() . "\n");
}

/*
 * Load an environment local configuration file to provide overrides to your configuration.
 * Notice: For security reasons app_local.php **should not** be included in your git repo.
 */
if (file_exists(CONFIG . 'app_local.php')) {
    Configure::load('app_local', 'default');
}

/*
 * When debug = true the metadata cache should only last
 * for a short time.
 */
if (Configure::read('debug')) {
    Configure::write('Cache._cake_model_.duration', '+2 minutes');
    Configure::write('Cache._cake_core_.duration', '+2 minutes');
    // disable router cache during development
    Configure::write('Cache._cake_routes_.duration', '+2 seconds');
}

/*
 * Set the default server timezone. Using UTC makes time calculations / conversions easier.
 * Check http://php.net/manual/en/timezones.php for list of valid timezone strings.
 */
date_default_timezone_set(Configure::read('App.defaultTimezone'));

/*
 * Configure the mbstring extension to use the correct encoding.
 */
mb_internal_encoding(Configure::read('App.encoding'));

/*
 * Set the default locale. This controls how dates, number and currency is
 * formatted and sets the default language to use for translations.
 */
ini_set('intl.default_locale', Configure::read('App.defaultLocale'));

/*
 * Register application error and exception handlers.
 */
$isCli = PHP_SAPI === 'cli';
if ($isCli) {
    (new ConsoleErrorHandler(Configure::read('Error')))->register();
} else {
    (new ErrorHandler(Configure::read('Error')))->register();
}

/*
 * Include the CLI bootstrap overrides.
 */
if ($isCli) {
    require CONFIG . 'bootstrap_cli.php';
}

/*
 * Set the full base URL.
 * This URL is used as the base of all absolute links.
 */
$fullBaseUrl = Configure::read('App.fullBaseUrl');
if (!$fullBaseUrl) {
    /*
     * When using proxies or load balancers, SSL/TLS connections might
     * get terminated before reaching the server. If you trust the proxy,
     * you can enable `$trustProxy` to rely on the `X-Forwarded-Proto`
     * header to determine whether to generate URLs using `https`.
     *
     * See also https://book.cakephp.org/4/en/controllers/request-response.html#trusting-proxy-headers
     */
    $trustProxy = false;

    $s = null;
    if (env('HTTPS') || ($trustProxy && env('HTTP_X_FORWARDED_PROTO') === 'https')) {
        $s = 's';
    }

    $httpHost = env('HTTP_HOST');
    if (isset($httpHost)) {
        $fullBaseUrl = 'http' . $s . '://' . $httpHost;
    }
    unset($httpHost, $s);
}
if ($fullBaseUrl) {
    Router::fullBaseUrl($fullBaseUrl);
}
unset($fullBaseUrl);

Cache::setConfig(Configure::consume('Cache'));
ConnectionManager::setConfig(Configure::consume('Datasources'));
TransportFactory::setConfig(Configure::consume('EmailTransport'));
Mailer::setConfig(Configure::consume('Email'));
Log::setConfig(Configure::consume('Log'));
Security::setSalt(Configure::consume('Security.salt'));

/*
 * Setup detectors for mobile and tablet.
 * If you don't use these checks you can safely remove this code
 * and the mobiledetect package from composer.json.
 */
ServerRequest::addDetector('mobile', function ($request) {
    $detector = new \Detection\MobileDetect();

    return $detector->isMobile();
});
ServerRequest::addDetector('tablet', function ($request) {
    $detector = new \Detection\MobileDetect();

    return $detector->isTablet();
});

/*
 * You can enable default locale format parsing by adding calls
 * to `useLocaleParser()`. This enables the automatic conversion of
 * locale specific date formats. For details see
 * @link https://book.cakephp.org/4/en/core-libraries/internationalization-and-localization.html#parsing-localized-datetime-data
 */
// \Cake\Database\TypeFactory::build('time')
//    ->useLocaleParser();
// \Cake\Database\TypeFactory::build('date')
//    ->useLocaleParser();
// \Cake\Database\TypeFactory::build('datetime')
//    ->useLocaleParser();
// \Cake\Database\TypeFactory::build('timestamp')
//    ->useLocaleParser();
// \Cake\Database\TypeFactory::build('datetimefractional')
//    ->useLocaleParser();
// \Cake\Database\TypeFactory::build('timestampfractional')
//    ->useLocaleParser();
// \Cake\Database\TypeFactory::build('datetimetimezone')
//    ->useLocaleParser();
// \Cake\Database\TypeFactory::build('timestamptimezone')
//    ->useLocaleParser();

// There is no time-specific type in Cake
TypeFactory::map('time', StringType::class);

/*
 * Custom Inflector rules, can be set to correctly pluralize or singularize
 * table, model, controller names or whatever other string is passed to the
 * inflection functions.
 */
//Inflector::rules('plural', ['/^(inflect)or$/i' => '\1ables']);
//Inflector::rules('irregular', ['red' => 'redlings']);
//Inflector::rules('uninflected', ['dontinflectme']);
define('GAMES', serialize([
	'sbo' => 'SBO Sportsbook',
	'sbocasino'  => 'SBO Casino',
	'ino' => 'ION Casino',
	'cbet' => 'Cbet',
	'isin' => 'Isin4D',
	'togel'  => 'Togel Primbon Online',
	'maxbet' => 'MaxBet',
	'ibet' => 'Ibet789',
	'og' => 'Oriental Game',
	'gd88' => 'Green Dragon',
	'sv388' => 'SV388',
	'idn' => 'IDN Poker',
	'joker' => 'Joker123',
	'play1628' => 'Play1628',
	'tangkas' => 'Tangkas'
]));
//status
define('STATUS_ACTIVE', 1);
define('STATUS_INACTIVE', 0);
define('STATUS_APPROVED', 1);
define('STATUS_REJECTED', 2);
define('STATUS_REQUEST', 0);
define('STATUS_ERROR', 3);
define('STATUS_AUTO', 4);
define('LST_STATUS', serialize([
	STATUS_APPROVED => 'Approved',
	STATUS_REJECTED => 'Rejected',
	STATUS_REQUEST => 'Requesting',
	STATUS_ERROR => 'Error',
	STATUS_AUTO => 'Auto',
]));
define('CONTACTS', serialize([
	'we' => 'Wechat',
	'line' => 'Line',
	'wha' => 'WhatsApp',
	'bbm' => 'Blackberry Messager',
	'tele' => 'Telegram',
	'sk' => 'Skype',
]));
//define('OPERATOR_EMAIL', 'cs.sukabet@gmail.com');
define('OPERATOR_EMAIL', 'wordcap457@gmail.com');
define('TEST_EMAIL', 'wordcap457@gmail.com');
define('SYSTEM_EMAIL', 'sukabetreborn@gmail.com');
define('PAGE_HOME', 'ph');
define('PAGE_SPOTSBOOK', 'psb');
define('PAGE_CASINO', 'pcn');
define('PAGE_POKER', 'ppk');
define('PAGE_LOTTER', 'plt');
define('PAGE_GAME', 'pgm');
define('PAGE_BONUS', 'pbn');
define('PAGE_REWARD', 'prw');
define('PAGE_REGISTER', 'prg');
define('PAGE_RESPONSIBLE', 'prp');
define('PAGE_ABT', 'pabt');
define('PAGE_CONTACT', 'pct');
define('PAGE_RULE', 'prl');
define('PAGE_SLOT', 'psl');
define('PAGE_COCKFIGHT', 'pcf');
define('PAGE_FISHING', 'pf');
define('PAGE_DEPOSITE', 'pd');
define('PAGE_WITHDRAW', 'pw');
define('PAGE_SK', 'psk');
define('PAGE_PROMO', 'prm');
// api

define('ROLE_ADMIN', 'ad.1778');
define('ROLE_USER', 'us.1778');
define('ROLE_MEMBER', '76f2ec78-c056-4dea-a5fe-d986e163228a');

define('KEY_ENCRYPTION', 'l1U8MACadFTXGen29ZoiLwQGrLgdb09k');
define('KEY_ENCRYPTION1', 'l4U8MANadFTXGen91ZoiLwDGrLgdb19k');

define('MESSAGE_SUCCESS', 'Request is successfully.');
define('MESSAGE_REGISTER_SUCCESS', 'Your account has been created.');
define('MESSAGE_BANK_ACCOUNT_EXIST', 'Your bank account is already exist.');

define('MESSAGE_CREATE', 'Data has been created.');
define('MESSAGE_EDIT', 'Data has been updated.');
define('MESSAGE_SAVE', 'Data has been saved.');
define('MESSAGE_CONFIRM_DELETE', 'Are you sure you want to delete?');
define('MESSAGE_DELETE', 'Data has been deleted');
define('MESSAGE_ERROR', 'Request is not success.');
define('MESSAGE_LOGIN_ERROR', 'Invalide user name or password.');
define('MESSAGE_REQUIRED', 'Kolom ini harus di isi');
define('MESSAGE_NOT_UNIQUE_USER', 'Username sudah terdaftar.');
define('MESSAGE_NOT_UNIQUE_EMAIL', 'Email sudah terdaftar.');
define('MESSAGE_INVALIDE_EMAIL_FORMAT', 'Pastikan format email sudah benar.');
define('MESSAGE_NO_POOL', 'Please select a pool.');
define('MESSAGE_NO_BALANCE', 'Balance tidak cukup.');
define('MESSAGE_PASSWORD_NOT_MURCH', 'Konfirmasi password tidak sama.');
define('MESSAGE_INVALIDE_CAPTCHA', 'Kode Validasi tidak cocok.');
define('MESSAGE_MAX_LENGTH', 'Only one digit is need.');
define('MESSAGE_MISSING_VALUE', 'Some values must not be empty.');
define('MESSAGE_NOT_NUMBER', 'Harap di isi dengan angka.');
define('MESSAGE_MIN_LENGTH', 'password harus terdiri dari 8 karakter ( contoh Abcd1234 ).');
define('MESSAGE_ACTIVATION_SUCCESS', 'Your account has been activated.');
define('MESSAGE_ACTIVATION_FAILED', 'Your can not been activated.');
define('MESSAGE_ACTIVATION_EXPIRE', 'The link is expire.');
define('MESSAGE_BLOCK_CONFIRM', 'Are you sure you want to block #{0}?');
define('MESSAGE_BLOCK_SUCCESS', 'User has been blocked.');
define('MESSAGE_NO_RECORDS', 'tidak ada data!');
define('MESSAGE_CONFIRM_APPROVE', 'Are you sure you want to approve?');
define('MESSAGE_CONFIRM_REJECT', 'Are you sure you want to reject?');
define('MESSAGE_CONFIRM_ACTIVATE', 'Are you sure you want to activate this user?');
define('MESSAGE_RESULT_LENGTH_NUMBER', 'Result number must be 4 digits .');
define('MESSAGE_RESULT_RELEASE', 'The result in the date selected is already open.');
define('MESSAGE_NOT_UNIQUE', 'This field must be unique.');
define('MESSAGE_INVALIDE_CURRENT_PASSWORD', 'Pastikan password sudah benar.');
define('MESSAGE_NOT_TIME', 'It is not time to open yet.');
define('MESSAGE_ACTIVATE_USER', 'User has been activated.');
define('MESSAGE_DEPOSIT_WITHDRAW_NOTFOUND', 'Other operator has approved or cancel this request.');
define('MESSAGE_CATEGORY_NOT_EXIST', 'Category is not exist.');
define('MESSAGE_UPLOAD_IMAGE_ERROR', 'Image can not be upload.');
define('MESSAGE_OPEN_RESULT_SUCCESS', 'Pools for Selected Period has been updated successfully.');
define('MESSAGE_NOT_FOUND', 'Halaman tidak di temukan.');

define('MESSAGE_DEPOSITE_LIMITED', 'Minimum Deposit %s%s Maximum Deposit %s%s.');//%s%s (Rp. 200,000)
define('MESSAGE_WITHDRAW_LIMITED', 'Minimum Withdraw %s%s Maximum Withdraw %s%s.');//%s%s (Rp. 200,000)
define('MESSAGE_NUMBER_NOT_NULL', 'Tidak boleh kosong.');
define('MESSAGE_NO_GAME_SELECTION', 'No game has been choosed.');
define('MESSAGE_BET_LIMITED', 'Bet amount must be greater or equal %d and less or equal %d with modulus %d');
define('MESSAGE_SAME_NUMBER', 'All number must not be the same.');
define('MESSAGE_BET_SUCCESS', 'Bets submitted');
define('MESSAGE_BET_ERROR', 'Bets are not submitted');
define('MESSAGE_INVALIDE_ACCOUNT', 'Account ini tidak terdaftar.');
define('MESSAGE_INVALIDE_PROVIDER', 'Produk ini tidak terdaftar.');
define('MESSAGE_TRANSFER_TO_THE_SAME_ACCOUNT', 'Dari dan tujuan tidak boleh sama.');
define('MESSAGE_INVALIDE_AMOUNT', 'Balance tidak mencukupi.');
define('MESSAGE_TRANSFER_FALL', 'Proses transfer gagal, silahkan mencoba setelah 1 menit.');
define('MESSAGE_TRANSFER_SUCCESS', 'Proses transfer sukses.');
define('MESSAGE_MAX_LENGTH_USERNAME', 'User Name allow only 10 charecters.');
define('MESSAGE_CHECK_BALANCE_FAIL', 'Your balance can not be check for now.');
define('MESSAGE_INVALIDE_TRANSFER', 'Anda hanya bisa mentransfer dari dompet anda kedalam game dan dari dalam game ke dompet anda.');
define('MESSAGE_LOADGAME_FALL', 'Game belum bisa di akses, silahkan hubungi Cs kami.');
define('MESSAGE_REQUEST_DEPOSIT', 'Please, Deposit first!!');
define('MESSAGE_DEPOSIT_SUCCESS', 'Terima Kasih, deposit anda segera kami proseskan.');
define('MESSAGE_WITHDRAW_SUCCESS', 'Terima Kasih, withdraw anda segera kami proseskan.');
define('MESSAGE_CASHIN_LIMITED', 'Min Cashin %s%s Max Cashin %s%s.');//%s%s (Rp. 200,000)
define('MESSAGE_CASHOUT_LIMITED', 'Min Cashout %s%s Max Cashout %s%s.');//%s%s (Rp. 200,000)
define('MESSAGE_MAINTENANCE', 'Website sedang dalam perbaikan.');//%s%s (Rp. 200,000)
define('CONFRIM_MESSAGE_IDN_POKER', '*NOTE: Game Policy Your credit will deposit to IDNPOKER, min 1 minute to can withdraw back. during this period you can not leave IDNPOKER. Thanks for understanding.');
define('MESSAGE_ERROR_WITHDRAW_IDNPK', 'Please wait a minute');

define('TEXT_CREATE', 'Create');
define('TEXT_LOGIN', 'Login');
define('TEXT_REGISTER', 'Register');
define('TEXT_SAVE', 'Save');
define('TEXT_VIEW', 'View');
define('TEXT_EDIT', 'Edit');
define('TEXT_DELETE', 'Delete');
define('TEXT_CANCEL', 'Cancel');
define('TEXT_DEPOSIT', 'Deposit');
define('TEXT_WITHDRAW', 'Withdraw');
define('TEXT_REQUIRED', ' <span class="text-danger">*</span>');

define('EMPTY_DATE', '0000-00-00 00:00:00');

define('PAGE_LIMIT', 20);
define('PAGE_MAXLIMIT', 1000);

define('MODELS', serialize([
	'Users' => 'Member',
	'Banks' => 'Banks',
	'Accounts' => 'Bank Accounts',
	'Transactions' => 'Transactions',
	'GameSettings' => 'Game Setting',
	'SystemSettings' => 'System Setting',
	'Posts' => 'Posts',
	'Developers' => 'Developers',
	'DisplayFields' => 'Display Field'
]));

define('BANK_BCA', 'bca');
define('BANK_BNI', 'bni');
define('BANK_BRI', 'bri');
define('BANK_MANDIRI', 'mandiri');

define('BANKS', serialize([
	BANK_BCA => 'BCA Bank',
	BANK_BNI => 'BNI Bank',
	BANK_BRI => 'BRI Bank',
	BANK_MANDIRI => 'Mandiri Bank'
]));
define('TYPE_DEPOSIT', 1);
define('TYPE_TRANSFER', 2);
define('TYPE_WITHDRAW', 3);
define('TYPE_EDIT', 4);
define('TYPE_TRANSFER_IN', 5);
define('TYPE_TRANSFER_OUT', 6);

define('TYPE_GETBALANCE', 'get_balance');
define('TYPE_UNVOIDBET', 'unavoid_bet');
define('TYPE_VOIDBET', 'avoid_bet');
define('TYPE_ADJUSTBET', 'adjust_bet');
define('TYPE_CANCELBET', 'cancel_bet');
define('TYPE_BET', 'bet');
define('TYPE_WIN', 'win');

define('TYPE_RESULT', 'result');
define('TYPE_JACKPOT_WIN', 'jackpot_win');
define('TYPE_REFUND', 'refund');
define('TYPE_PROMOWIN', 'promowin');

define('TYPE_LOSS', 'loss');
define('TYPE_TIE', 'tie');
define('TYPE_CANCELLED', 'cancelled');
define('TYPE_TIP', 'tip');
define('TYPE_BONUS', 'bonus');
define('TYPE_ROLLBACK', 'rollback');
define('TYPE_SETTLED', 'settled');

define('TYPE_UNSETTLED', 'unsettled');
define('TYPE_VOIDSETTLED', 'voidsettled');
define('TYPE_UNVOIDSETTLED', 'unvoidsettled');
define('TYPE_BETNSETTLE', 'bet_n_settle');
define('TYPE_CANCELBETNSETTLE', 'cancel_bet_n_settle');
define('TYPE_FREESPIN', 'free_spin');
define('TYPE_TRANSACTION_FAILED', 'transaction failed');

define('TYPE_CHANGE_RESULT', 'correcting_result');
define('TYPE_CHANGE_BONUS', 'correcting_bonus');

define('COMPANY_CODE', 'tmp');

define('CODE_RTG', 'rtg');// RTG
define('CODE_SRTG', 'srtg');// RTG Slot
define('CODE_RTG_FISHING', 'frtg');// RTG Fishing
define('CODE_S128', 's128');
define('CODE_PG', 'pg');
define('CODE_PP', 'pp');
define('CODE_PP_CASINO', 'cpp');
define('CODE_PP_VIRTUAL_SPORT', 'vspp');
define('CODE_PP_FISHING', 'fpp');
define('CODE_SG', 'sg');// SG Slot
define('CODE_SSG', 'ssg');// SG Slot
define('CODE_SG_FISHING', 'fsg');// SG Fishing
define('CODE_DG', 'dg');
define('CODE_BG', 'bg');// BG
define('CODE_BG_CASINO', 'cbg');// SG Casino
define('CODE_BG_FISHING', 'fbg');// SG Fishing
define('CODE_ION', 'ion');
define('CODE_IDN', 'idn');
define('CODE_HB', 'haban');
define('CODE_GD88', 'gd88');
define('CODE_IDNPK', 'idnpk');
define('CODE_IBC', 'ibc');
define('CODE_JK', 'jk');
define('CODE_GW', 'gw');
define('CODE_CQ9', 'cq9');//CQ9
define('CODE_CQ9_SLOT', 'slcq9');//CQ9 Slots
define('CODE_SCQ9', 'scq9');//CQ9 Sports book
define('CODE_CCQ9', 'ccq9');//CQ9 Casino
define('CODE_CQ9_FISHING', 'fcq9'); //CQ9 Fishing
define('CODE_HG', 'hg');
define('CODE_MG', 'mg');
define('CODE_GMW', 'gmw');
define('CODE_S88', 's88');
define('CODE_BLKP', 'blkp');

define('CODE_SK', 'sk');
define('CODE_SK_BACCART', 'baccarat');
define('CODE_HOLDEM', 'holdem');
define('CODE_BIG2', 'big2');
define('CODE_CEME', 'ceme');
define('CODE_SUSUN', 'susun');

// SBO code start here
define('CODE_SBO', 'sbo'); // sbo sportsbook
define('CODE_LSBO', 'lsbo'); // sbo sportsbook
define('CODE_VSBO', 'vsbo'); // sbo virtual sports
define('CODE_GSBO', 'gsbo'); // sbo game
define('CODE_CSBO', 'csbo'); // sbo casino
define('CODE_LCSBO', 'lcsbo'); // sbo Live beauty
define('CODE_FG', 'fg'); // sbo flowgaming
define('CODE_WM','wm'); // sbo WM casino
define('CODE_SEXY', 'sbcr'); // sbo sexy casino

//Transfer Wallet
//Flah Tech
define('CODE_CMD368', 'cmd368');
define('CODE_ISIN', 'isin');
define('CODE_TRG', 'trg');// only ionman wallet
define('CODE_ONEPOCKER', 'opk');

/* Vender Error code */
define('CODE_SUCCESS', 0);
define('CODE_SUCCESS_200', 200);

// game lists
define('LOBBY_GAME', 'lobby');
define('BACCARAT_GAME', 'baccarat');
define('ROULETTE_GAME', 'roulette');
define('SICBO_GAME', 'sicbo');
define('DRAGONTIGER_GAME', 'dragontiger');
define('FANTAN_GAME', 'fantan');
define('FISHPRAWNCRAB', 'fishprawncrab');
define('NIUNIU', 'niuniu');
define('SEDIE', 'sedie');
define('THREECARD', 'threecard');
define('SAMGONG', 'samgnog');
define('MAHJONG', 'mahjong');
define('GAME_LIST', serialize([
	LOBBY_GAME => LOBBY_GAME,
	BACCARAT_GAME => BACCARAT_GAME,
	ROULETTE_GAME => ROULETTE_GAME,
	SICBO_GAME => SICBO_GAME,
	DRAGONTIGER_GAME => DRAGONTIGER_GAME,
	NIUNIU => NIUNIU,
	FISHPRAWNCRAB => FISHPRAWNCRAB,
	THREECARD => THREECARD,
	SEDIE => SEDIE,
	FANTAN_GAME => FANTAN_GAME,
	//SAMGONG => SAMGONG,
	//MAHJONG => MAHJONG,
]));

//source media
define('SOURCE_MEDIA_SMALL', '(max-width: 767px)');
define('SOURCE_MEDIA_BIG', '(min-width: 768px)');

define('SK_URL', 'https://kasirjudi.bet/');

define('MERCHANTCODE_SG', 'SUKABET');
define('PWD_SECRET_KEY', 'wt1U5MACWJFTXGenFoZoiLwQGrLgdbHA');
define('IMAGE_MAINTENANCE', '/img/asset/kj.png');
define('IMAGE_MAINTENANCE_MOBILE', '/img/asset/kj&skb3.png');
define('SPIN_GAME_URL', 'luckyspin/index.html');

define('IMAGE_MAINTENACE_1', '/img/asset/mtn1.png');
define('IMAGE_MAINTENACE_2', '/img/asset/mtn2.png');
define('IMAGE_MAINTENACE_3', '/img/asset/mtn3.png');
define('IMAGE_MAINTENACE_4', '/img/asset/mtn4.png');

define('REF_CODE', 'ref_code');

define('SHORTCUT_DATE', serialize([
	'today' => 'Today',
	'yesterday' => 'Yesterday',
	'thisweek' => 'This week',
	'lastweek' => 'Last Week',
	'monthtodate' => 'This Month',
	'lastmonth' => 'Last Month',
]));

define('_GAMELIST', json_encode([
	[
		'name' => 'SukaGames',
		'img' => 'baccarat',
		'url' => 'bandar-baccarat',
	],
	[
		'name' => 'Sportsbook',
		'img' => 'sportsbook',
		'url' => 'judi-bola',
	],
	[
		'name' => 'Casino',
		'img' => 'casino',
		'url' => 'casino-online',
	],
	[
		'name' => 'Slot',
		'img' => 'slots',
		'url' => 'judi-slot-online',
	],
	[
		'name' => 'Poker',
		'img' => 'poker',
		'url' => 'agen-poker',
	],
	[
		'name' => 'Togel',
		'img' => 'togel',
		'url' => 'togel-online',
	],
	[
		'name' => 'Tembak Ikan',
		'img' => 'tembakikan',
		'url' => 'tembak-ikan',
	],
	[
		'name' => 'Adu Ayam',
		'img' => 'aduayam',
		'url' => 'sabung-ayam',
	],
	[
		'name' => 'Bonus',
		'img' => 'bonus',
		'url' => 'bonus-judi-online',
	],
	[
		'name' => 'Rewards',
		'img' => 'rewards',
		'url' => 'hadiah-judi-online',
	]
]));

// mobile menu list
define('SPORTBOOK_MENU_LIST', serialize([
  'MAXBET' => '/judi-bola/play/ibc',
  'CMD368' => '/judi-bola/play/cmd368',
  'SBOBET' => '/judi-bola/play/sbo?action=SportsBook',
  'CQ9' => '/judi-bola/play/scq9',
  'SBO VIRTUAL SPORTS' => '/judi-bola/play/vsbo?action=VirtualSports',
  'PRAGMATIC VIRTUAL SPORTS' => '/judi-bola/play/vspp?game_id=vppso4&technology_id=H5',
]));
define('CASINO_MENU_LIST', serialize([
	'PRAGMATIC CASINO' => '/casino-online/play/cpp?game_id=101&technology_id=H5',
	'SBOBET CASINO' => '/casino-online/play/sbo',
	'ION CASINO' => '/casino-online/play/ion',
	'DREAMGAMING' => '/casino-online/play/dg',
	'BIGGAMING' => '/casino-online/play/bg',
	'HOGAMING' => '/casino-online/play/hg',
	'CQ9 CASINO' => '/casino-online/play/ccq9',
	'IDNLIVE' => '/casino-online/play/idn',
	'WM CASINO' => '/casino-online/play/wm',
	'GREENDRAGON88' => '/casino-online/play/gd88',
]));
define('SLOT_MENU_LIST', serialize([
	'PRAGMATICPLAY' => '/judi-slot-online/pp',
	'MICROGAMING' => '/judi-slot-online/mg',
	'JOKER' => '/judi-slot-online/jk',
	'CQ9GAMING' => '/judi-slot-online/cq9',
	'HABANERO' => '/judi-slot-online/haban',
	'RTGSLOTS' => '/judi-slot-online/rtg',
	'FLOWGAMING' => '/judi-slot-online/fg',
	'PG SOFT' => '/judi-slot-online/pg',
	'SPADEGAMING' => '/judi-slot-online/sg',
	'GAMING WORLD' => '/judi-slot-online/gw',
]));
define('POKER_MENU_LIST', serialize([
  'IDNPOKER' => '/agen-poker/play/idnpk',
  '9 GAMING' => '/agen-poker/play/opk',
]));
define('TOGEL_MENU_LIST', serialize([
  'ISIN4D' => '/togel-online/play/isin',
]));
define('TEMBAK_IKAN', serialize([
	'PP FISHING KING' => '/tembak-ikan/play/fpp?game_id=pp3fish&technology_id=H5',
	'PP FORTUNE FISHING' => '/tembak-ikan/play/fpp?game_id=pp4fortune&technology_id=H5',
	'BG' => '/tembak-ikan/play/bg',
	'RTG' => '/tembak-ikan/play/rtg?game=2162689',
	'SG FISHING WAR' => '/judi-slot-online/play/sg?game_id=F-SF02',
//	'SG FISHING GOD' => '/judi-slot-online/play/sg?game_id=F-SF01',
//	'SG ALIEN HUNTER' => '/judi-slot-online/play/sg?game_id=F-AH01',
//	'SG ZOMBIE PARTY' => '/judi-slot-online/play/sg?game_id=F-ZP01',
	'CQ9 PARADISE' => '/tembak-ikan/play/cq9?gamehall=cq9&gamecode=AB3',
	'CQ9 ONESHOT' => '/tembak-ikan/play/cq9?gamehall=cq9&gamecode=AT01',
	'CQ9 LUCKY FISHING' => '/tembak-ikan/play/cq9?gamehall=cq9&gamecode=AT05',
//	'CQ9 THUNDER FIGHTER' => '/tembak-ikan/play/cq9?gamehall=cq9&gamecode=AT04',
//	'CQ9 WATER MARGIN' => '/tembak-ikan/play/cq9?gamehall=cq9&gamecode=AT06',
]));
define('ADU_AYAM', serialize([
  'S1288' => '/sabung-ayam/play/s128',
]));

define('FISHING_GAMELIST', serialize([
	CODE_CQ9_FISHING => [
		'AB3' => [
			'param' => [
				'gamehall' => 'cq9',
				'gamecode' => 'AB3'
			],
		],
		'AT01' => [
			'param' => [
				'gamehall' => 'cq9',
				'gamecode' => 'AT01'
			],
		],
		'AT05' => [
			'param' => [
				'gamehall' => 'cq9',
				'gamecode' => 'AT05'
			],
		],
		// 'AT04' => [
		// 	'param' => [
		// 		'gamehall' => 'cq9',
		// 		'gamecode' => 'AT04'
		// 	],
		// ],
		// 'AT06' => [
		// 	'param' => [
		// 		'gamehall' => 'cq9',
		// 		'gamecode' => 'AT06'
		// 	],
		// ],
	],
	CODE_PP_FISHING => [
		'pp3fish' => [
			'param' => [
				'game_id' => 'pp3fish',
				'technology_id' => 'H5'
			],
		],
		'pp4fortune' => [
			'param' => [
				'game_id' => 'pp4fortune',
				'technology_id' => 'H5'
			],
		],
	],
	CODE_RTG_FISHING => [
		CODE_RTG_FISHING => [
			'param' => [
				'game' => '2162689',
			],
		],
	],
	CODE_SG_FISHING => [
//		'F-ZP01' => [
//			'param' => [
//				'game_id' => 'F-ZP01',
//			],
//		],
//		'F-AH01' => [
//			'param' => [
//				'game_id' => 'F-AH01',
//			],
//		],
//		'F-SF01' => [
//			'param' => [
//				'game_id' => 'F-SF01',
//			],
//		],
		'F-SF02' => [
			'param' => [
				'game_id' => 'F-SF02',
			],
		],
		// 'F-ZP01' => [
		// 	'param' => [
		// 		'game_id' => 'F-ZP01',
		// 	],
		// ],
	],
	CODE_BG_FISHING => [
		CODE_BG_FISHING => [
			'param' => [],
		],
	],
]));

// bank type
define('BANK_TYPE_NORMAL', 1);
define('BANK_TYPE_EWALLET', 2);
define('BANK_TYPE_PULSA', 3);
define('BANK_TYPE', serialize([
	BANK_TYPE_NORMAL => 'Bank',
	BANK_TYPE_EWALLET => 'EWallet',
	BANK_TYPE_PULSA => 'Pulsa',
]));

// auth message
define('AUTH_INVALID_USERNAME', 'Your username is invalid.');
define('AUTH_ACC_BLOCK', 'Your account have been blocked. Please contact to operator.');
define('AUTH_RETRY', 'Please try again!');
define('S3_URL_PC', 'https://suka-staging.s3.ap-southeast-1.amazonaws.com/kasirjudi/');
define('S3_URL_M', 'https://suka-staging.s3.ap-southeast-1.amazonaws.com/kasirjudi/m/');
define('SLOT_DIR', 'slots/');
define('COCKFIGHT_DIR', 'aduayam/');
define('BANDAR_DIR', 'bandarbaccarat/');
define('CASINO_DIR', 'casino/');
define('POKER_DIR', 'poker/');
define('SPORTSBOOK_DIR', 'sportsbook/');
define('FISHING_DIR', 'tembakikan/');
define('TOGEL_DIR', 'togel/');

define('API_URL', 'https://api-staging.kasirjudi.com');
define('API_SUKA_GAME', 'https://stg-games.sukagaming.asia:8444/baccarat/api/');
define('API_SKB3_URL', 'https://api-staging.kasirjudi.com');
define('API_SKB2_URL', 'https://api-staging.kasirjudi.com');
define('API_SKB1_URL', 'https://api-staging.kasirjudi.com');


define('RESPONSE_JSON', serialize([
    'error' => false,
    'code' => 200,
    'data' => [],
    'message' => ''
]));

define('ERROR_CODE_USER_NOT_FOUND', 99); // User not found
define('ERROR_CODE_ACCOUNT_EXISTS', 100); // Account exist
define('ERROR_CODE_VALIDATE_ERROR', 101); // Error validation

define('INTERNAL_KEY', '12345678');