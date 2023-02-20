<?php
namespace Membership\Login;

use DateTime;
use DateTimeZone;
use Exception;
use IntlDateFormatter;
use stdClass;
use Wp_Memberchip_Login;
/**
 * ADMIN WP Membership Helper
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wp_Memberchip_Login
 * @subpackage Wp_Memberchip_Login/includes
 */

defined( 'ABSPATH' ) or die();

class WP_Membership_Login_Helper
{
    private static $instance;

    use WP_Membership_Login_Settings;

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $basename The ID of this plugin.
     */
    private string $basename;

    /**
     * The Version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current Version of this plugin.
     */
    private string $version;

    /**
     * Store plugin main class to allow public access.
     *
     * @since    1.0.0
     * @access   private
     * @var Wp_Memberchip_Login $main The main class.
     */
    private Wp_Memberchip_Login $main;

    /**
     * Store plugin helper class.
     *
     * @param string $basename
     * @param string $version
     *
     * @since    1.0.0
     * @access   private
     *
     * @var Wp_Memberchip_Login $main
     */

    /**
     * @return static
     */
    public static function instance(string  $version,string $basename,  Wp_Memberchip_Login $main ): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($version, $basename, $main);
        }
        return self::$instance;
    }

    public function __construct(string  $version,string $basename, Wp_Memberchip_Login $main)
    {
        $this->main = $main;
        $this->version = $version;
        $this->basename = $basename;
    }

    /**
     * @throws Exception
     */
    public function getMembershipRandomString(): string
    {
        if (function_exists('random_bytes')) {
            $bytes = random_bytes(16);
            $str = bin2hex($bytes);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes(16);
            $str = bin2hex($bytes);
        } else {
            $str = md5(uniqid('wp_membership_login_rand', true));
        }

        return $str;
    }

    public function getMSLGenerateRandomId($passwordlength = 12, $numNonAlpha = 1, $numNumberChars = 4, $useCapitalLetter = true): string
    {
        $numberChars = '123456789';
        //$specialChars = '!$&?*-:.,+@_';
        $specialChars = '!$%&=?*-;.,+~@_';
        $secureChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz';
        $stack = $secureChars;
        if ($useCapitalLetter == true) {
            $stack .= strtoupper($secureChars);
        }
        $count = $passwordlength - $numNonAlpha - $numNumberChars;
        $temp = str_shuffle($stack);
        $stack = substr($temp, 0, $count);
        if ($numNonAlpha > 0) {
            $temp = str_shuffle($specialChars);
            $stack .= substr($temp, 0, $numNonAlpha);
        }
        if ($numNumberChars > 0) {
            $temp = str_shuffle($numberChars);
            $stack .= substr($temp, 0, $numNumberChars);
        }

        return str_shuffle($stack);
    }

    public function membershipFileSizeConvert(float $bytes): string
    {
        $result = '';
        $bytes = floatval($bytes);
        $arBytes = array(
            0 => array("UNIT" => "TB", "VALUE" => pow(1024, 4)),
            1 => array("UNIT" => "GB", "VALUE" => pow(1024, 3)),
            2 => array("UNIT" => "MB", "VALUE" => pow(1024, 2)),
            3 => array("UNIT" => "KB", "VALUE" => 1024),
            4 => array("UNIT" => "B", "VALUE" => 1),
        );

        foreach ($arBytes as $arItem) {
            if ($bytes >= $arItem["VALUE"]) {
                $result = $bytes / $arItem["VALUE"];
                $result = str_replace(".", ",", strval(round($result, 2))) . " " . $arItem["UNIT"];
                break;
            }
        }
        return $result;
    }

    /**
     * @param $array
     *
     * @return object
     */
    final public function membershipArrayToObject($array): object
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::membershipArrayToObject($value);
            }
        }

        return (object)$array;
    }

    public function wp_membership_login_date_format_language(DateTime $dt, string $format, string $language = 'en'): string
    {
        $curTz = $dt->getTimezone();
        if ($curTz->getName() === 'Z') {
            //INTL don't know Z
            $curTz = new DateTimeZone('Europe/Berlin');
        }

        $formatPattern = strtr($format, array(
            'D' => '{#1}',
            'l' => '{#2}',
            'M' => '{#3}',
            'F' => '{#4}',
        ));
        $strDate = $dt->format($formatPattern);
        $regEx = '~\{#\d}~';
        while (preg_match($regEx, $strDate, $match)) {
            $IntlFormat = strtr($match[0], array(
                '{#1}' => 'E',
                '{#2}' => 'EEEE',
                '{#3}' => 'MMM',
                '{#4}' => 'MMMM',
            ));
            $fmt = datefmt_create($language, IntlDateFormatter::FULL, IntlDateFormatter::FULL,
                $curTz, IntlDateFormatter::GREGORIAN, $IntlFormat);
            $replace = $fmt ? datefmt_format($fmt, $dt) : "???";
            $strDate = str_replace($match[0], $replace, $strDate);
        }

        return $strDate;
    }

    /**
     * @param $object
     * @return array
     */
    public function object2array_recursive($object):array
    {
        if(!$object) {
            return  [];
        }
        return json_decode(json_encode($object), true);
    }

    public function wp_membership_login_current_theme_directory(): string
    {
        $current_theme_dir  = '';
        $current_theme      = wp_get_theme();
        if( $current_theme->exists() && $current_theme->parent() ){
            $parent_theme = $current_theme->parent();

            if( $parent_theme->exists() ){
                $current_theme_dir = $parent_theme->get_stylesheet();
            }
        } elseif( $current_theme->exists() ) {
            $current_theme_dir = $current_theme->get_stylesheet();
        }

        return $current_theme_dir;
    }

    /**
     * @param $id
     * @return array
     */
    public function fn_wp_membership_login_get_theme_pages($id=NULL): array
    {
        $pages = get_pages();
        $retArr = [];
        foreach ($pages as $page) {
            $ret_item = [
                'name' => $page->post_title,
                'id' => $page->ID,
                'type' => 'page'
            ];
            $retArr[] = $ret_item;
        }

        if($id) {
            foreach ($retArr as $tmp) {
                if($tmp['id'] == $id){
                    return $tmp;
                }
            }
        }
        return $retArr;
    }

    public function hupa_get_theme_posts($args): array
    {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1
        );

        $posts = get_posts($args);
        $retArr = [];
        $i = 1;
        foreach ($posts as $post) {

            $ret_item = [
                'name' => $post->post_title,
                'id' => $post->ID,
                'type' => 'post',
                'first' => $i === 1
            ];
            $retArr[] = $ret_item;
            $i++;
        }
        return $retArr;
    }

    public function fnPregWhitespace($string): string
    {
        if (!$string) {
            return '';
        }
        return trim(preg_replace('/\s+/', ' ', $string));
    }
}
