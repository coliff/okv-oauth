<?php
/**
 * Custom OAuth login page.
 * 
 * @package rundiz-oauth
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace RundizOauth\App\Controllers\Front\RdOauth;


if (!class_exists('\\RundizOauth\\App\\Controllers\\Front\\RdOauth\\Index')) {
    class Index
    {


        /**
         * The index action for /rd-oauth URI.
         * 
         * @global \WP_Query $wp_query
         */
        public function indexAction()
        {
            $output = [];

            $RundizOauth = new \RundizOauth\App\Libraries\RundizOauth();
            $RundizOauth->init();
            if ($RundizOauth->loginMethod === 0) {
                // if login method is using wp only.
                exit;
            }

            if (isset($_REQUEST['rdoauth']) && $_REQUEST['rdoauth'] === 'google') {
                // user choose to login with google.
                $Google = new \RundizOauth\App\Libraries\MyOauth\Google();
                $user = $Google->wpLoginWithGoogle(null);
                unset($Google);
            } elseif (isset($_REQUEST['rdoauth']) && $_REQUEST['rdoauth'] === 'facebook') {
                // user choose to login with facebook.
                $Facebook = new \RundizOauth\App\Libraries\MyOauth\Facebook();
                $user = $Facebook->wpLoginWithFacebook(null);
                unset($Facebook);
            }

            if (isset($user) && $user !== null && !is_wp_error($user)) {
                // if login success, those oauth function must return the WP_User object.
                // cannot just return $user like other login plugins because the form was not really submitted and cookie expiration can't set.
                // manually set wp login cookie.
                wp_clear_auth_cookie();
                wp_set_auth_cookie($user->ID, true);
                // do hook action as login success.
                do_action('wp_login', $user->user_email, $user);
                // redirect the logged in user.
                $RundizOauth->loggedinRedirect($user);
                exit;
            } elseif (isset($user) && is_wp_error($user)) {
                $output['form_error_msg'] = $user->get_error_message();
            }

            unset($RundizOauth, $user);

            $this->registerScripts();

            global $wp_query;
            // prevent return 404 status.
            $wp_query->is_404 = false;
            status_header(200);

            // set title of the page.
            // @link https://developer.wordpress.org/reference/hooks/document_title_parts/ Reference.
            add_filter('document_title_parts', function($title) {
                $title['title'] = __('Rundiz OAuth', 'okv-oauth');
                return $title;
            });

            $Loader = new \RundizOauth\App\Libraries\Loader();
            $Loader->loadTemplate('okv-oauth/index_v', $output);
            unset($Loader, $output);
            exit;
        }// indexAction


        /**
         * Register styles and scripts.
         */
        public function registerScripts()
        {
            wp_enqueue_style('rd-oauth-login', plugin_dir_url(RUNDIZOAUTH_FILE) . 'assets/css/rd-oauth-login.css' );
        }// registerScripts


    }
}