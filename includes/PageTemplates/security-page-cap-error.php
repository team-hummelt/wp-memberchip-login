<?php
get_header();
?>
    <div class="wp-membership-login"><!--bootstrap-wrapper-->
        <div id="wp-membership-page" <?php post_class("site-content py-5 mt-5") ?>>
            <div id="content" <?php post_class("container") ?>>
                <div id="primary" class="content-area">
                    <main id="main" class="site-main">
                        <header <?php post_class("entry-header") ?>>
                            <?php the_post(); ?>
                            <h1><?= get_the_title() ?></h1>
                        </header>
                        <div <?php post_class("entry-content") ?>>
                            <h4 class="text-center text-danger mt-5">
                                Sie haben keine Berechtigung diese Seite aufzurufen.
                            </h4>
                        </div>
                        <footer <?php post_class("entry-footer") ?>>

                        </footer>
                    </main>
                </div>
            </div>
        </div>
    </div>
<?php
get_footer();