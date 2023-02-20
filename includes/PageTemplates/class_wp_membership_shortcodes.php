<?php


use Membership\Login\WP_Membership_Login_Settings;

class WP_Membership_Shortcodes
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
     * The current version of the DB-Version.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the database Version.
     */
    protected string $version;

    /**
     * Store plugin main class to allow public access.
     *
     * @since    1.0.0
     * @access   private
     * @var Wp_Memberchip_Login $main The main class.
     */
    private Wp_Memberchip_Login $main;

    /**
     * @return static
     */
    public static function instance(string $basename, string $version, Wp_Memberchip_Login $main): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($basename, $version, $main);
        }
        return self::$instance;
    }

    public function __construct(string $basename, string $version, Wp_Memberchip_Login $main)
    {
        $this->main = $main;
        $this->version = $version;
        $this->basename = $basename;
        add_shortcode('mbl-documents', array($this, 'wp_membership_documents_shortcode'));
    }

    public function wp_membership_documents_shortcode($atts, $content, $tag)
    {
        $a = shortcode_atts(array(
            "id" => "",
            "type" => 'list'
        ), $atts);
        if (!$a['id']) {
            return '';
        }
        ob_start();

        $args = sprintf('WHERE m.shortcode="%s" and m.document_group !=0 and m.active=1 and g.active=1',$a['id']);
        $mbl = apply_filters($this->basename.'/get_membership_login', $args, false);
        if(!$mbl->status){
            return '';
        }
        $mbl = $mbl->record;
        if(!apply_filters($this->basename.'/check_user_capabilities', $mbl->capabilities)){
            return '';
        }
        $args = sprintf('WHERE d.group=%d and g.active=1', $mbl->document_group);
        $documents = apply_filters($this->basename.'/get_document_import', $args);
        if(!$documents->status){
            return '';
        }
        $document_count = $documents->count;
        $documents = $documents->record;
        $html = '<div data-count="'.$document_count.'" class="wp-membership-login"></div><ul class="membership-download-list">';
        foreach ($documents as $tmp) {
            $baseArr = [
                'id' => $tmp->id,
                's' => $a['id']
            ];

            $base =  base64_encode(json_encode($baseArr));
            if($tmp->name){
                $name = $tmp->name;
            } else {
                $name = substr($tmp->original, 0, strrpos($tmp->original,'.'));
            }
            $ext = preg_replace('/^.*\./', '', $tmp->original);
            $html .= '<li class="membership-list"><a class="list-file file ext_'.$ext.'" 
                      href="'.site_url().'?'.SECURITY_QUERY_GET.'='.SECURITY_DOCUMENT_QUERY_URI.'&id='.$base.'&type=download">
                      '.$name.'</a></li>';
        }
        $html .= '</ul></div>';

        echo $this->html_compress_template($html);
        return ob_get_clean();
    }

    private function html_compress_template(string $string): string
    {
        if (!$string) {
            return $string;
        }
        return preg_replace(['/<!--(.*)-->/Uis', "/[[:blank:]]+/"], ['', ' '], str_replace(["\n", "\r", "\t"], '', $string));
    }

    private function fnPregWhitespace($string): string
    {
        if (!$string) {
            return '';
        }
        return trim(preg_replace('/\s+/', ' ', $string));
    }

}
