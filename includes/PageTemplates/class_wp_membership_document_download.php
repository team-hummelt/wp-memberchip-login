<?php

class WP_Membership_Document_Download
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
    public static function instance(string $basename,Wp_Memberchip_Login $main): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($basename,$main);
        }
        return self::$instance;
    }

    public function __construct(string $basename, Wp_Memberchip_Login $main)
    {
        $this->main = $main;
        $this->basename = $basename;
    }

    public function security_document_download()
    {
        ob_start();
        $id = filter_input(INPUT_GET, 'id', FILTER_UNSAFE_RAW);
        $type = filter_input(INPUT_GET, 'type', FILTER_UNSAFE_RAW);
        if (!$id && !$type) {
            exit('keine Berechtigung!');
        }

        $base = json_decode(base64_decode($id), true);
        $shortcode = $base['s'];
        $docId = $base['id'];
        $args = sprintf('WHERE m.shortcode="%s" and m.document_group !=0 and m.active=1 and g.active=1',$shortcode);
        $mbl = apply_filters($this->basename.'/get_membership_login', $args, false);
        if(!$mbl->status){
            exit('keine Berechtigung!');
        }
        $mbl = $mbl->record;
        if(!apply_filters($this->basename.'/check_user_capabilities', $mbl->capabilities)){
            exit('keine Berechtigung!');
        }

        $args = sprintf('WHERE d.group=%d and d.id=%d and g.active=1', $mbl->document_group, (int) $docId);
        $document = apply_filters($this->basename.'/get_document_import', $args, false);
        if(!$document->status){
            exit('file not found');
        }

        $document = $document->record;

        $file = DOCUMENT_WP_MEMBERSHIP_UPLOAD_DIR . $document->filename;
        if (!is_file($file)) {
            exit('file not found');
        }

        if($type == 'download') {
            $record = new stdClass();
            $record->downloads = (int) $document->downloads +1;
            $record->id = (int) $docId;
            apply_filters($this->basename.'/update_document_download_count', $record);
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file);
        @header("Content-Type: $mimeType");
        @header('Content-Disposition: attachment; filename="' . $document->original . '"');
        @header('Content-Length: ' . filesize($file));
        flush();
        @readfile($file);

    }
}
