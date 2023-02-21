<?php

class WP_Membership_Gutenberg_Block_Callback
{
    public static function callback_wp_membership_block_type($attributes)
    {
        isset($attributes['className']) && $attributes['className'] ? $className = 'class="' . $attributes['className'] . '"' : $className = '';
        isset($attributes['selectedMembership']) && $attributes['selectedMembership'] ? $selectedMembership = $attributes['selectedMembership'] : $selectedMembership = '';
        isset($attributes['stayLoggedIn']) && $attributes['stayLoggedIn'] ? $stayLoggedIn = $attributes['stayLoggedIn'] : $stayLoggedIn = '';

        $memberArr = [];
        if ($selectedMembership) {
            $args = sprintf('WHERE m.id=%d', (int)$selectedMembership);
            $membership = apply_filters('wp-memberchip-login/get_membership_login', $args, false);
            if ($membership->status) {
                $memberArr = (array)$membership->record;
            }
        }
        return apply_filters('gutenberg_block_wp_membership_callback', $memberArr, $stayLoggedIn, $selectedMembership, $className);
    }

    public function gutenberg_block_wp_membership_filter($membership, $stayLoggedIn, $selectedMembership, $className)
    {
        ob_start();
        if ($membership):
            if (is_user_logged_in()): ?>
                <div class="wp-membership-login">
                    <a href="<?= site_url() ?>/<?= $membership['redirect_link'] ?>"
                       class="btn btn-outline-secondary"><?= __('Members area', 'wp-memberchip-login') ?></a>
                </div>
            <?php else:
                if (isset($_POST['wp-submit'])):
                    $user_login = filter_input(INPUT_POST, 'log', FILTER_UNSAFE_RAW);
                    $user_password = filter_input(INPUT_POST, 'pwd', FILTER_UNSAFE_RAW);
                    $rememberme = filter_input(INPUT_POST, 'rememberme', FILTER_UNSAFE_RAW);
                    $redirect_to = filter_input(INPUT_POST, 'redirect_to', FILTER_VALIDATE_URL);
                    $credentials = array(
                        'user_login' => $user_login,
                        'user_password' => $user_password,
                        'remember' => $rememberme
                    );
                    global $user;
                    $user = wp_signon($credentials, is_ssl());
                    if (is_wp_error($user)):?>
                        <div class="wp-membership-login">
                            <div <?= $className ?>>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?= $user->get_error_message() ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                            aria-label="Close"></button>
                                </div>
                                <?= $this->render_login_template($stayLoggedIn, $membership, $user_login) ?>
                            </div>
                        </div>
                    <?php else:
                        @ob_flush();
                        @ob_end_flush();
                        @ob_end_clean();
                        wp_redirect($redirect_to);
                        exit();
                    endif;
                else: ?>
                    <div class="wp-membership-login">
                        <div <?= $className ?>>
                            <?= $this->render_login_template($stayLoggedIn, $membership) ?>
                        </div>
                    </div>
                <?php
                endif; endif; endif;
        return ob_get_clean();
    }

    private function render_login_template($stayLoggedIn, $membership, $username = ''): string
    {
        $action = '';
        if(!$membership['self_active']){
           if($membership['login_link']){
               $action = ' action="'.site_url().'/'.$membership['login_link'].'"';
           }
        }
        $html = '<form name="loginform" id="loginform" '.$action.' method="post">
                     <div class="form-floating mb-2">
                            <input type="text" name="log" id="user_login" value="' . $username . '" autocomplete="username" size="20"
                                   autocapitalize="off" class="form-control" placeholder="name@example.com">
                            <label for="user_login">' . __('Username or email address', 'wp-memberchip-login') . '</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" name="pwd" id="user_pass" autocomplete="current-password" size="20"
                                   class="form-control" placeholder="name@example.com">
                            <label for="user_pass">' . __('Password', 'wp-memberchip-login') . '</label>
                        </div>';
        if ($stayLoggedIn) {
            $html .= '<p class="forgetmenot">
                <input name="rememberme" type="checkbox" id="rememberme" value="forever">
                <label for="rememberme">' . __('Stay logged in', 'wp-memberchip-login') . '</label>
            </p>';
        }
        $html .= '<span class="btn-wrapper"><button type="submit" name="wp-submit" class="btn btn-outline-secondary">' . __('Log in', 'wp-memberchip-login') . '</button></span>';

        $html .= '<input type="hidden" name="redirect_to" value="' . site_url() . '/' . $membership['redirect_link'] . '">';
        $html .= '</form>';

        return $this->html_compress_template($html);
    }
    private function html_compress_template(string $string): string
    {
        if (!$string) {
            return $string;
        }
        return preg_replace(['/<!--(.*)-->/Uis', "/[[:blank:]]+/"], ['', ' '], str_replace(["\n", "\r", "\t"], '', $string));
    }
}