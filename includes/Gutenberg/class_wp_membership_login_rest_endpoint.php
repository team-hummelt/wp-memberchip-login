<?php
namespace Membership\Login;
use stdClass;
use WP_Error;
use Wp_Memberchip_Login;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class WP_Membership_Login_Rest_Endpoint
{

    protected Wp_Memberchip_Login $main;
    protected string $basename;

    public function __construct(string $basename, Wp_Memberchip_Login $main)
    {
        $this->main = $main;
        $this->basename = $basename;
    }

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_wp_membership_login_routes()
    {
        $version = '1';
        $namespace = 'wp-membership/v' . $version;
        $base = '/';

        @register_rest_route(
            $namespace,
            $base . '(?P<method>[\S]+)',

            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'wp_membership_login_endpoint_get_response'),
                'permission_callback' => array($this, 'permissions_check')
            )
        );
    }

    /**
     * Get one item from the collection.
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function wp_membership_login_endpoint_get_response(WP_REST_Request $request)
    {

        $method = (string)$request->get_param('method');
        if (!$method) {
            return new WP_Error(404, ' Method failed');
        }

        return $this->get_method_item($method);

    }

    /**
     * GET Post Meta BY ID AND Field
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_method_item($method)
    {
        if (!$method) {
            return new WP_Error(404, ' Method failed');
        }
        $response = new stdClass();
        switch ($method) {
            case 'get-data':
                $importsArr = [];

                $imports = apply_filters($this->basename.'/get_membership_login', '');
                $def = [
                    '0' => [
                        'label' => __('select' .'...', 'wp-memberchip-login'),
                        'value' => 0
                    ]
                ];

                if(!$imports->status){
                    $response->imports = $def;
                } else {
                    foreach ($imports->record as $tmp){
                        $item = [
                            'label' => $tmp->designation,
                            'value' => $tmp->id
                        ];
                        $importsArr[] = $item;
                    }
                    if(!$importsArr){
                        $importsArr = $def;
                    }
                    $importsArr = array_merge_recursive($def, $importsArr);
                    $response->membership = $importsArr;
                }
                break;
        }
        return new WP_REST_Response($response, 200);
    }

    /**
     * Get a collection of items.
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return void
     */
    public function get_items(WP_REST_Request $request)
    {


    }

    /**
     * Check if a given request has access.
     *
     * @return bool
     */
    public function permissions_check(): bool
    {
        return current_user_can('edit_posts');
    }
}