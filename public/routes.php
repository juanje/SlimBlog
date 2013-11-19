<?php
//Check if user is logged
$authenticate = function($app, $settings) {
    return function() use ($app, $settings) {
        if (!isset($_SESSION['user'])) {
            $app->flash('error', 'Login required');
            $app->redirect($settings->base_url . '/admin/login');
        }
    };
};

$isLogged = function($app, $settings) {
    return function() use ($app, $settings) {
        if (isset($_SESSION['user'])) {
            $app->redirect($settings->base_url . '/admin');
        }
    };
};

$app->hook('slim.before.dispatch', function() use ($app, $settings) {
    $user = null;
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
    }
    $app->view()->setData('user', $user);

    $app->view()->setData('settings', $settings);
});

$app->get('/(:page)', function($page = 1) use ($app, $settings, $request, $markdownParser, $capsule) {
    $posts = $capsule->table('posts')->orderBy('creation', 'desc')->skip($settings->post_per_page * ($page - 1))->take($settings->post_per_page)->get();
    $arr = array();
    foreach ($posts as $post) {
        $post['author'] = Users::get_author($post['user_id']);
        $post['date'] = date('d-m-Y H:i', $post['creation']);
        $post['url'] = $request->getUrl() . $request->getPath() . 'post/' . $post['id'];
        $post['text'] = $markdownParser->transformMarkdown($post['text']);
        $arr[] = $post;
    }

    $p = $capsule->table('posts')->count();

    $pages = ceil($p / $settings->post_per_page);

    $app->render('posts.html', array('posts' => $arr, 'pages' => $pages, 'page' => $page));
})->conditions(array('page' => '\d+'));

$app->get('/post/:id', function($id) use ($app, $request, $markdownParser) {
    if ($post = Posts::find($id)) {
        $post->author = Users::get_author($post->user_id);
        $post->date = date('d-m-Y H:i', $post->creation);
        $post->text = $markdownParser->transformMarkdown($post->text);

        $app->render('post.html', array('post' => $post));
    }
})->conditions(array('page' => '\d+'));

$app->get('/admin/login/', $isLogged($app, $settings), function() use ($app) {
    $flash = $app->view()->getData('flash');
    $error = '';
    if (isset($flash['error'])) {
        $error = $flash['error'];
    }

    $app->render('login.html', array('error' => $error));
});

$app->post('/admin/login', function() use ($app, $settings, $request) {
    $username = $request->post('form-username');
    $password = hash('sha512', $request->post('form-password'));
    $user = Users::whereRaw('username = ? AND password = ?', array($username, $password))->get();

    if ($user->count() != 0) {
        $_SESSION['user'] = $username;
        $app->redirect($settings->base_url . '/admin');
    } else {
        $app->flash('error', 'Invalid user or password');
        $app->redirect($settings->base_url . '/admin/login');
    }
});

$app->get('/admin/logout/', $authenticate($app, $settings), function() use ($app, $settings) {
    unset($_SESSION['user']);
    $app->view()->setData('user', null);
    $app->redirect($settings->base_url);
});

$app->get('/admin/', $authenticate($app, $settings), function() use ($app, $capsule, $request) {
    $posts = $capsule->table('posts')->orderBy('creation', 'desc')->get();
    $arr = array();
    foreach ($posts as $post) {
        $post['author'] = Users::get_author($post['user_id']);
        $post['date'] = date('d-m-Y H:i', $post['creation']);
        $post['url'] = $request->getUrl() . $request->getPath() . 'post/' . $post['id'];
        $arr[] = $post;
    }
    $app->render('a_posts.html', array('posts' => $arr));
});

$app->get('/admin/posts/new/', $authenticate($app, $settings), function() use ($app) {
    $app->render('a_post_new.html');
});

$app->post('/admin/posts/new', $authenticate($app, $settings), function() use ($app, $request) {
    $title = $request->post('title');
    $text = $request->post('markdown');
    $date = time();
    $author = Users::get_id($_SESSION['user']);

    Posts::insert(array('title' => $title, 'creation' => $date, 'text' => $text, 'user_id' => $author));
    $app->render('success.html');
});

$app->post('/admin/markdown/ajax', $authenticate($app, $settings), function() use ($app, $request, $markdownParser) {
    if ($request->post('markdown') !== null) {
        echo $markdownParser->transformMarkdown($request->post('markdown'));
    }
});

$app->get('/admin/posts/edit/:id', $authenticate($app, $settings), function($id) use ($app) {
    $post = Posts::where('id', '=', $id)->first();
    $title = $post->title;
    $text = $post->text;
    $postId = $id;
    $app->render('a_post_edit.html', array('id' => $postId, 'title' => $title, 'text' => $text));
})->conditions(array('id' => '\d+'));

$app->post('/admin/posts/edit/:id', $authenticate($app, $settings), function($id) use ($app, $request) {
    Posts::where('id', '=', $id)->update(array('title' => $request->post('title'), 'text' => $request->post('markdown')));
    $app->render('success.html');
})->conditions(array('id' => '\d+'));

$app->get('/admin/posts/delete/:id', $authenticate($app, $settings), function($id) use ($app) {
    $app->render('a_post_delete.html', array('post_id' => $id));
})->conditions(array('id' => '\d+'));

$app->delete('/admin/posts/delete/:id', $authenticate($app, $settings), function($id) use ($app) {
    Posts::destroy($id);
    $app->render('success.html');
})->conditions(array('id' => '\d+'));

$app->get('/admin/settings/', $authenticate($app, $settings), function() use ($app) {
    $app->render('a_settings.html');
});

$app->post('/admin/settings/update', function() use ($app, $request) {
    $title = $request->post('title');
    $base_url = $request->post('base_url');
    $post_per_page = $request->post('post_per_page');

    Settings::where('id', '=', 1)->update(array('title' => $title, 'base_url' => $base_url, 'post_per_page' => $post_per_page));
    $app->render('success.html');
});

$app->get('/admin/users/', $authenticate($app, $settings), function() use ($app, $capsule) {
    $users = $capsule->table('users')->orderBy('created_at', 'asc')->get();
    $app->render('a_users.html', array('users' => $users));
});

$app->get('/admin/users/edit/:id', $authenticate($app, $settings), function($id) use ($app) {
    $u = Users::where('id', '=', $id)->first();
    $app->render('a_user_edit.html', array('u' => $u));
})->conditions(array('id' => '\d+'));

$app->post('/admin/users/edit/:id', $authenticate($app, $settings), function($id) use ($app, $request) {
    $username = $request->post('username');
    $password = hash('sha512', $request->post('password'));
    $email = $request->post('email');

    Users::where('id', '=', $id)->update(array('username' => $username, 'password' => $password, 'email' => $email));
    $app->render('success.html');
})->conditions(array('id' => '\d+'));

$app->get('/admin/users/delete/:id', $authenticate($app, $settings), function($id) use ($app) {
    $app->render('a_user_delete.html', array('user_id' => $id));
})->conditions(array('id' => '\d+'));

$app->delete('/admin/users/delete/:id', $authenticate($app, $settings), function($id) use ($app) {
    Users::destroy($id);
    $app->render('success.html');
})->conditions(array('id' => '\d+'));

$app->get('/admin/users/new/', $authenticate($app, $settings), function() use ($app) {
    $app->render('a_user_new.html');
});

$app->post('/admin/users/new', $authenticate($app, $settings), function() use ($app, $request) {
    $username = $request->post('username');
    $password = hash('sha512', $request->post('password'));
    $email = $request->post('email');
    $created_at = date('Y-m-d H:i:s');

    Users::insert(array('username' => $username, 'password' => $password, 'email' => $email, 'created_at' => $created_at));
    $app->render('success.html');
});