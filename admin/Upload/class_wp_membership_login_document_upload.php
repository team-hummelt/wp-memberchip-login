<?php

namespace Membership\Login;
/**
 * The Upload Class admin-specific functionality of the plugin.
 *
 * @link       https://wwdh.de
 */

defined('ABSPATH') or die();
use Exception;
use finfo;
use Wp_Memberchip_Login;
use stdClass;

class WP_Membership_Login_Document_Upload
{
    private static $instance;
    protected Wp_Memberchip_Login $main;
    protected array $options;
    private float $size;
    private string $type;
    private string $file_id;
    private string $originalName;

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $basename The ID of this plugin.
     */
    private string $basename;

    /**
     * The Settings of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      array $settings The Settings of this plugin.
     */
    private array $settings;

    /**
     * @return static
     */
    public static function instance(string $basename, Wp_Memberchip_Login $main ): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($basename, $main);
        }
        return self::$instance;
    }

    public function __construct(string $basename, Wp_Memberchip_Login $main)
    {

        $this->basename = $basename;
        $this->settings = get_option($this->basename . '_settings');
        $this->main = $main;
        $this->options = [
            'check_filesize' => $this->settings['check_mime_type_active'],
            'check_type' => $this->settings['check_upload_size_active'],
            'mkdir_mode' => 0755,
            'max_file_size' => (int) ($this->settings['max_file_size']) * 1024 * 1024,
            'accept_file_types' => '/\.(pdf|svg|rar|zip|jpg||jpeg|png|gif|doc|docx)$/i',
            'file_type_end' => '/\.(.+$)/i',
            'upload_dir' => DOCUMENT_WP_MEMBERSHIP_UPLOAD_DIR
        ];
    }

    /**
     * @throws Exception
     */
    public function document_upload():object
    {
        $record = new stdClass();
        $record->status = false;
        $ifUserUpload = apply_filters($this->basename.'/check_user_capabilities', $this->settings['plugin_min_role']);
        if(!$ifUserUpload){
            $this->set_header('HTTP/1.0 400 Bad Request');
            $record->msg = 'Benutzer für Upload nicht freigegeben.';
            return $record;
        }

        if (!is_dir($this->options['upload_dir'])) {
            mkdir($this->options['upload_dir'], $this->options['mkdir_mode'], true);
        }

        if (!file_exists($this->options['upload_dir'] .  '.htaccess')) {
            $htaccess = 'Require all denied';
            file_put_contents($this->options['upload_dir'] . '.htaccess', $htaccess);
        }

        if (!empty($_FILES)) {
            $tempFile = $_FILES['file']['tmp_name'];
            $fileName = $this->trim_file_name($_FILES['file']['name']);
            $content_range_header = $this->get_server_var('HTTP_CONTENT_RANGE');
            $content_range = $content_range_header ? preg_split('/[^0-9]+/', $content_range_header) : null;
            preg_match('#\.([a-z0-9]+)$#i', $fileName, $tmp);
            if ($this->options['check_type']) {
                $mimes = $this->fnPregWhitespace($this->settings['mime_types']);
                $mimes = str_replace([',','-',';'],'|',$mimes);
                $mimes = explode('|',strtolower($mimes));
                if (!in_array(strtolower($tmp[1]), $mimes)) {
                  //  preg_match($this->options['file_type_end'], $fileName, $matches, PREG_OFFSET_CAPTURE, 0);
                    $this->set_header('HTTP/1.0 400 Bad Request');
                    $record->msg = strtoupper($tmp[1]) .' nicht erlaubt!';
                    return $record;
                }
            }

            //preg_match('#\.([a-z0-9]+)$#i', $fileName, $tmp);
            $randName = $this->random_string();
            $newName = $randName . '.' . $tmp[1];
            $this->file_id = $randName;
            $name = $this->get_unique_filename($newName, $content_range);
            $this->originalName = (string)$this->get_unique_filename($fileName, $content_range);
            $targetFile = $this->options['upload_dir'] . $name;

            if (move_uploaded_file($tempFile, $targetFile)) {
                unset($tempFile);
            } else {
                $this->set_header('HTTP/1.0 400 Bad Request');
                $record->msg = 'CHECK PHP INI!';
                return $record;
            }
            $this->size = $this->get_file_size($targetFile);
            $this->type = $this->get_mime_type($targetFile);
            if ($this->type == 'application/octet-stream') {
                $this->data_file_delete($targetFile);
                $this->set_header('HTTP/1.0 400 Bad Request');
                $record->msg = 'CHECK PHP INI!';
                return $record;
            }

            if ($this->options['check_filesize']) {
                if ($this->size > (float)$this->options['max_file_size']) {
                    $this->data_file_delete($targetFile);
                    $this->set_header('HTTP/1.0 400 Bad Request');
                    $record->msg = 'Upload max:' . $this->FileSizeConvert($this->options['max_file_size']);
                    return $record;
                }
            }

            if ($this->options['check_type']) {
                if (empty($this->type)) {
                    $this->data_file_delete($targetFile);
                    $this->set_header('HTTP/1.0 400 Bad Request');
                    $record->msg = 'Mime Type error!';
                    return $record;
                }
            }
            return $this->db_handle($targetFile, $name);
        }

        return $record;
    }

    /**
     * @param $targetFile
     * @param $name
     * @return object
     */
    protected function db_handle($targetFile, $name): object
    {
        $record = new stdClass();
        $record->file_id = $this->file_id;
        $record->orginalName = $this->originalName;
        $record->filename = $name;
        $record->status = true;
        $record->size = $this->size;
        $record->type = $this->type;
        return $record;
    }

    /**
     * @param $header
     */
    protected function set_header($header): void
    {
        header("{$header}");
    }

    /**
     * @param $name
     * @param $content_range
     * @return array|mixed|string|string[]|null
     */
    protected function get_unique_filename($name, $content_range)
    {
        while (is_dir($this->get_upload_path($name))) {
            $name = $this->upcount_name($name);
        }
        while (is_file($this->get_upload_path($name))) {
            if (false == $this->get_file_size($this->get_upload_path($name))) {
                break;
            }

            $name = $this->upcount_name($name);
        }
        return $name;
    }
    /**
     * @param null $file_name
     * @param null $version
     * @return string
     */
    protected function get_upload_path($file_name = null, $version = null): string
    {
        $file_name = $file_name ? $file_name : '';
        if (empty($version)) {
            $version_path = '';
        } else {
            $version_dir = @$this->options['image_versions'][$version]['upload_dir'];
            if ($version_dir) {
                return $version_dir . $file_name;
            }
            $version_path = $version . '/';
        }
        return $this->options['upload_dir']
            . $version_path . $file_name;
    }
    /**
     * @param $matches
     * @return string
     */
    protected function upcount_name_callback($matches): string
    {
        $index = isset($matches[1]) ? ((int)$matches[1]) + 1 : 1;
        $ext = $matches[2] ?? '';
        return ' (' . $index . ')' . $ext;
    }
    /**
     * @param $name
     * @return array|string|string[]|null
     */
    protected function upcount_name($name)
    {
        return preg_replace_callback(
            '/(?:(?: \(([\d]+)\))?(\.[^.]+))?$/',
            array($this, 'upcount_name_callback'), $name, 1
        );
    }
    /**
     * @param $file_path
     * @param int $clear_stat_cache
     * @return float
     */
    protected function get_file_size($file_path, int $clear_stat_cache = 1): float
    {
        if ($clear_stat_cache) {
            if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
                clearstatcache(true, $file_path);
            } else {
                clearstatcache();
            }
        }
        return $this->fix_integer_overflow(filesize($file_path));
    }
    /**
     * @param $size
     * @return float
     */
    protected function fix_integer_overflow($size): float
    {
        if ($size < 0) {
            $size += 2.0 * (PHP_INT_MAX + 1);
        }
        return (float)$size;
    }

    /**
     * @param $name
     * @return array|string|string[]
     */
    protected function trim_file_name($name)
    {
        $name = trim($this->basename(stripslashes($name)), ".\x00..\x20");
        if (!$name) {
            $name = str_replace('.', '-', microtime(true));
        }
        return $name;
    }

    /**
     * @param string $filepath
     * @param string $suffix
     * @return string
     */
    protected function basename(string $filepath, string $suffix = ''): string
    {
        $splited = preg_split('/\//', rtrim($filepath, '/ '));
        return substr(basename('X' . $splited[count($splited) - 1], $suffix), 1);
    }

    /**
     * @param $id
     * @return mixed
     */
    protected function get_server_var($id)
    {
        return @$_SERVER[$id];
    }

    /**
     * @param $target
     */
    protected function data_file_delete($target): void
    {
        if (is_file($target)) {
            unlink($target);
        }
    }

    public function FileSizeConvert(float $bytes): string
    {
        $result = '';
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

    protected function get_mime_type(string $file): string
    {
        if (is_file($file)) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            return $finfo->file($file);
        }
        return '';

    }

    /**
     * @throws Exception
     */
    protected function random_string($length = 16): string
    {
        if (function_exists('random_bytes')) {
            $bytes = random_bytes($length);
            $str = bin2hex($bytes);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length);
            $str = bin2hex($bytes);
        } else {
            $str = md5(uniqid('random_document_app_root', true));
        }
        return $str;
    }

    private function fnPregWhitespace($string): string
    {
        if (!$string) {
            return '';
        }
        return trim(preg_replace('/\s+/', '', $string));
    }
}