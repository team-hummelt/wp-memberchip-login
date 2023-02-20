<?php

class WP_Membership_Document_Admin_Download
{
    protected Wp_Memberchip_Login $main;
    private static $instance;

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $basename The ID of this plugin.
     */
    private string $basename;

    /**
     * @return static
     */
    public static function instance(string $basename, Wp_Memberchip_Login $main): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($basename, $main);
        }
        return self::$instance;
    }

    public function __construct(string $basename, Wp_Memberchip_Login $main)
    {
        $this->main = $main;
        $this->basename = $basename;
    }

    public function security_document_admin_download()
    {
        ob_start();

        $settings = get_option($this->basename . '_settings');
        $isUser = apply_filters($this->basename.'/check_user_capabilities', $settings['download_min_role']);
        if(!$isUser){
            exit('keine Berechtigung!');
        }

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $type = filter_input(INPUT_GET, 'type', FILTER_UNSAFE_RAW);
        if (!$id && !$type) {
            exit('keine Berechtigung!');
        }

        $args = sprintf('WHERE d.id=%d and d.active=1', (int)$id);
        $dbFile = apply_filters($this->basename.'/get_document_import', $args, false);
        if (!$dbFile->status) {
            exit('Datei nicht gefunden!');
        }
        $dbFile = $dbFile->record;

        if($type == 'download') {
            $record = new stdClass();
            $record->downloads = (int) $dbFile->downloads +1;
            $record->id = (int) $id;
            apply_filters($this->basename.'/update_document_download_count', $record);
        }

        $file = DOCUMENT_WP_MEMBERSHIP_UPLOAD_DIR . $dbFile->filename;
        if (!is_file($file)) {
            exit('Datei nicht gefunden!');
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file);
        @header("Content-Type: $mimeType");
        @header('Content-Disposition: attachment; filename="' . $dbFile->original . '"');
        @header('Content-Length: ' . filesize($file));
        flush();
        @readfile($file);
    }
}
