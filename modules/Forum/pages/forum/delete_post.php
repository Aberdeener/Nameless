<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr8
 *
 *  License: MIT
 *
 *  Delete post page
 */

if (! $user->isLoggedIn()) {
    Redirect::to(URL::build('/forum'));
    exit();
}

require_once ROOT_PATH.'/modules/Forum/classes/Forum.php';

// Always define page name
define('PAGE', 'forum');

$forum = new Forum();

// Check params are set
if (! isset($_GET['pid']) || ! is_numeric($_GET['pid'])) {
    Redirect::to(URL::build('/forum'));
    exit();
}

// Get post and forum ID
$post = $queries->getWhere('posts', ['id', '=', $_GET['pid']]);
if (! count($post)) {
    Redirect::to(URL::build('/forum'));
    exit();
}
$post = $post[0];

$forum_id = $post->forum_id;

if ($forum->canModerateForum($forum_id, $user->getAllGroupIds())) {
    if (Input::exists()) {
        if (Token::check()) {
            if (isset($_POST['tid'])) {
                // Is it the OP?
                if (isset($_POST['number']) && Input::get('number') == 10) {
                    try {
                        $queries->update('topics', Input::get('tid'), [
                            'deleted' => 1,
                        ]);
                        Log::getInstance()->log(Log::Action('forums/post/delete'), Input::get('tid'));
                        $opening_post = 1;
                    } catch (Exception $e) {
                        exit($e->getMessage());
                    }
                    $redirect = URL::build('/forum'); // Create a redirect string
                } else {
                    $redirect = URL::build('/forum/topic/'.Input::get('tid'));
                }
            } else {
                $redirect = URL::build('/forum/search/', 'p=1&s='.htmlspecialchars($_POST['search_string']));
            }

            try {
                $queries->update('posts', Input::get('pid'), [
                    'deleted' => 1,
                ]);

                if (isset($opening_post)) {
                    $posts = $queries->getWhere('posts', ['topic_id', '=', $_POST['tid']]);

                    if (count($posts)) {
                        foreach ($posts as $post) {
                            $queries->update('posts', $post->id, [
                                'deleted' => 1,
                            ]);
                            Log::getInstance()->log(Log::Action('forums/post/delete'), $post->id);
                        }
                    }
                }

                // Update latest posts in categories
                $forum->updateForumLatestPosts();
                $forum->updateTopicLatestPosts();

                Redirect::to($redirect);
                exit();
            } catch (Exception $e) {
                exit($e->getMessage());
            }
        } else {
            Redirect::to(URL::build('/forum/topic/'.Input::get('tid')));
            exit();
        }
    } else {
        echo 'No post selected';
        exit();
    }
} else {
    Redirect::to(URL::build('/forum'));
    exit();
}
